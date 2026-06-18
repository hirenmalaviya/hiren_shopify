<?php

namespace App\Jobs;

use App\Enums\LogLevel;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ShopifyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Imports a single product into Shopify via the GraphQL upsert flow.
 * Retries on failure; records a precise error and logs on final failure.
 * The last product of an upload to reach a terminal state marks the upload complete.
 */
class ImportProductToShopify implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Product $product) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(ShopifyService $shopify): void
    {
        // Idempotency: don't re-import a product that already finished.
        if ($this->product->status === ProductStatus::Successful) {
            return;
        }

        $this->product->forceFill([
            'status' => ProductStatus::Processing,
            'attempts' => $this->product->attempts + 1,
        ])->save();

        $result = $shopify->upsertProduct($this->product);

        $this->product->forceFill([
            'status' => ProductStatus::Successful,
            'shopify_product_id' => $result['product_id'],
            'shopify_variant_id' => $result['variant_id'],
            'action' => $result['action'],
            'error_message' => null,
        ])->save();

        $this->log(LogLevel::Info, "Product imported ({$result['action']})", [
            'shopify_product_id' => $result['product_id'],
        ]);

        $this->maybeComplete();
    }

    public function failed(?Throwable $e): void
    {
        $this->product->forceFill([
            'status' => ProductStatus::Failed,
            'error_message' => $e?->getMessage(),
        ])->save();

        $this->log(LogLevel::Error, 'Product import failed', [
            'error' => $e?->getMessage(),
        ]);

        $this->maybeComplete();
    }

    /** Refresh upload tallies; if every row has reached a terminal state, finalize. */
    private function maybeComplete(): void
    {
        $upload = $this->product->upload;
        $upload->refreshTallies();

        if ($upload->fresh()->processed_rows >= $upload->total_rows) {
            $upload->markCompleted();
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(LogLevel $level, string $message, array $context = []): void
    {
        $context = array_merge($context, [
            'sku' => $this->product->sku,
            'handle' => $this->product->handle,
            'row' => $this->product->row_number,
        ]);

        $this->product->logs()->create([
            'upload_id' => $this->product->upload_id,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);

        Log::channel('shopify_import')->{$level->value}($message, array_merge([
            'upload_id' => $this->product->upload_id,
            'product_id' => $this->product->id,
        ], $context));
    }
}
