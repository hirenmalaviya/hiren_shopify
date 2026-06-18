<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UploadStatus;
use App\Exceptions\ShopifyException;
use App\Jobs\ImportProductToShopify;
use App\Models\Upload;
use App\Services\ShopifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportPipelineTest extends TestCase
{
    use RefreshDatabase;

    private function sampleUpload(): UploadedFile
    {
        $source = base_path('plan/shopify-products-csv (5) (1) (1).csv');
        $tmp = tempnam(sys_get_temp_dir(), 'csv').'.csv';
        copy($source, $tmp);

        return new UploadedFile($tmp, 'products.csv', 'text/csv', null, true);
    }

    private function fakeShopifySuccess(): void
    {
        $counter = 0;
        Http::fake(function (Request $request) use (&$counter) {
            $q = $request->data()['query'] ?? '';

            return match (true) {
                str_contains($q, 'query findProduct') => Http::response(['data' => ['products' => ['edges' => []]]]),
                str_contains($q, 'productCreateMedia') => Http::response(['data' => ['productCreateMedia' => ['media' => [], 'mediaUserErrors' => []]]]),
                str_contains($q, 'mutation productCreate') => Http::response(['data' => ['productCreate' => [
                    'product' => [
                        'id' => 'gid://shopify/Product/'.(++$counter),
                        'handle' => 'h'.$counter,
                        'variants' => ['edges' => [['node' => ['id' => 'gid://shopify/ProductVariant/'.$counter, 'inventoryItem' => ['id' => 'gid://shopify/InventoryItem/'.$counter]]]]],
                    ],
                    'userErrors' => [],
                ]]]),
                str_contains($q, 'productVariantsBulkUpdate') => Http::response(['data' => ['productVariantsBulkUpdate' => ['productVariants' => [['id' => 'gid://shopify/ProductVariant/1', 'inventoryItem' => ['id' => 'gid://shopify/InventoryItem/1']]], 'userErrors' => []]]]),
                str_contains($q, 'inventorySetQuantities') => Http::response(['data' => ['inventorySetQuantities' => ['userErrors' => []]]]),
                str_contains($q, 'collectionAddProducts') => Http::response(['data' => ['collectionAddProducts' => ['collection' => ['id' => 'gid://shopify/Collection/123'], 'userErrors' => []]]]),
                str_contains($q, 'query locations') => Http::response(['data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1', 'name' => 'Main']]]]]]),
                default => Http::response(['data' => []]),
            };
        });
        config(['shopify.location_id' => null]);
    }

    public function test_full_pipeline_imports_all_products(): void
    {
        Storage::fake('local');
        $this->fakeShopifySuccess();

        $this->post('/uploads', ['file' => $this->sampleUpload()]);

        $upload = Upload::firstOrFail();

        $this->assertSame(UploadStatus::Completed, $upload->status);
        $this->assertSame(10, $upload->successful_rows);
        $this->assertSame(10, $upload->products()->where('status', ProductStatus::Successful->value)->count());
        $this->assertNotNull($upload->finished_at);

        $product = $upload->products()->where('status', ProductStatus::Successful->value)->first();
        $this->assertNotNull($product->shopify_product_id);
        $this->assertSame('create', $product->action->value);

        // Each product wrote an info ImportLog.
        $this->assertSame(10, $upload->logs()->where('level', 'info')->count());
    }

    public function test_product_job_records_failure_and_finalizes_upload(): void
    {
        Http::fake(function (Request $request) {
            $q = $request->data()['query'] ?? '';

            return match (true) {
                str_contains($q, 'query findProduct') => Http::response(['data' => ['products' => ['edges' => []]]]),
                str_contains($q, 'mutation productCreate') => Http::response(['data' => ['productCreate' => [
                    'product' => null,
                    'userErrors' => [['field' => ['handle'], 'message' => 'Handle has already been taken']],
                ]]]),
                default => Http::response(['data' => []]),
            };
        });

        $upload = Upload::create([
            'original_filename' => 'one.csv',
            'stored_path' => 'uploads/one.csv',
            'status' => UploadStatus::Processing,
            'total_rows' => 1,
        ]);
        $product = $upload->products()->create([
            'row_number' => 1,
            'handle' => 'dup-handle',
            'title' => 'Dup',
            'sku' => 'DUP-1',
            'price' => 10,
            'status' => ProductStatus::Pending,
        ]);

        // Drive the job exactly as the queue would: handle() throws → failed().
        $job = new ImportProductToShopify($product);
        try {
            $job->handle(app(ShopifyService::class));
            $this->fail('Expected ShopifyException was not thrown.');
        } catch (ShopifyException $e) {
            $job->failed($e);
        }

        $product->refresh();
        $upload->refresh();

        $this->assertSame(ProductStatus::Failed, $product->status);
        $this->assertStringContainsString('Handle has already been taken', (string) $product->error_message);

        // Last (only) row processed → upload finalized as failed (0 successful).
        $this->assertSame(UploadStatus::Failed, $upload->status);
        $this->assertSame(1, $upload->failed_rows);
        $this->assertSame(0, $upload->successful_rows);

        // A DB import log was recorded for the dashboard log viewer.
        $this->assertSame(1, $upload->logs()->where('level', 'error')->count());

        // An in-app (database) notification was raised for the failure.
        $this->assertSame(1, \App\Support\SystemNotifier::unreadCount());
        $this->assertSame('import_failed', \App\Support\SystemNotifier::recent()[0]['data']['type']);
    }

    public function test_retry_resets_failed_products_and_redispatches(): void
    {
        Queue::fake();

        $upload = Upload::create([
            'original_filename' => 'r.csv',
            'stored_path' => 'uploads/r.csv',
            'status' => UploadStatus::CompletedWithErrors,
            'total_rows' => 2,
            'successful_rows' => 1,
            'failed_rows' => 1,
        ]);
        $upload->products()->create(['row_number' => 1, 'title' => 'OK', 'sku' => 'OK1', 'status' => ProductStatus::Successful]);
        $failed = $upload->products()->create(['row_number' => 2, 'title' => 'Bad', 'sku' => 'BAD1', 'status' => ProductStatus::Failed, 'error_message' => 'boom']);

        $this->post("/uploads/{$upload->id}/retry")->assertRedirect();

        $failed->refresh();
        $this->assertSame(ProductStatus::Pending, $failed->status);
        $this->assertNull($failed->error_message);
        $this->assertSame(UploadStatus::Processing, $upload->fresh()->status);

        Queue::assertPushed(ImportProductToShopify::class, fn ($job) => $job->product->id === $failed->id);
    }
}
