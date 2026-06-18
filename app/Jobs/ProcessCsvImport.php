<?php

namespace App\Jobs;

use App\Enums\ProductStatus;
use App\Enums\UploadStatus;
use App\Models\Upload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orchestrates the import of one uploaded CSV: marks the upload as processing
 * and dispatches a per-product job for every pending row. Completion is
 * detected by the last product job (see ImportProductToShopify::maybeComplete).
 */
class ProcessCsvImport implements ShouldQueue
{
    use Queueable;

    public function __construct(public Upload $upload) {}

    public function handle(): void
    {
        $this->upload->forceFill([
            'status' => UploadStatus::Processing,
            'started_at' => now(),
        ])->save();

        $pending = $this->upload->products()
            ->where('status', ProductStatus::Pending)
            ->get();

        Log::channel('shopify_import')->info('CSV import started', [
            'upload_id' => $this->upload->id,
            'total_rows' => $this->upload->total_rows,
            'pending_rows' => $pending->count(),
        ]);

        // Nothing importable (every row failed validation or was skipped).
        if ($pending->isEmpty()) {
            $this->upload->markCompleted();

            return;
        }

        foreach ($pending as $product) {
            ImportProductToShopify::dispatch($product);
        }
    }

    public function failed(?Throwable $e): void
    {
        $this->upload->forceFill([
            'status' => UploadStatus::Failed,
            'finished_at' => now(),
        ])->save();

        Log::channel('shopify_import')->error('CSV import orchestration failed', [
            'upload_id' => $this->upload->id,
            'error' => $e?->getMessage(),
        ]);
    }
}
