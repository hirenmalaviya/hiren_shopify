<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\UploadStatus;
use App\Notifications\ImportFailedNotification;
use App\Support\SystemNotifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    /** @use HasFactory<\Database\Factories\UploadFactory> */
    use HasFactory;

    protected $fillable = [
        'original_filename',
        'stored_path',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'skipped_rows',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => UploadStatus::class,
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'skipped_rows' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** Computed attributes always serialized to the frontend. */
    protected $appends = [
        'progress_percent',
        'success_rate',
    ];

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return HasMany<ImportLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    /** Recompute the per-status tallies from the products table (race-safe). */
    public function refreshTallies(): void
    {
        $counts = $this->products()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $successful = (int) ($counts[ProductStatus::Successful->value] ?? 0);
        $failed = (int) ($counts[ProductStatus::Failed->value] ?? 0);
        $skipped = (int) ($counts[ProductStatus::Skipped->value] ?? 0);

        $this->forceFill([
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'skipped_rows' => $skipped,
            'processed_rows' => $successful + $failed + $skipped,
        ])->save();
    }

    /** Set the final upload status once all products have been processed. */
    public function markCompleted(): void
    {
        $this->refreshTallies();
        $this->refresh();

        $status = match (true) {
            $this->failed_rows === 0 => UploadStatus::Completed,
            $this->successful_rows === 0 => UploadStatus::Failed,
            default => UploadStatus::CompletedWithErrors,
        };

        $this->forceFill([
            'status' => $status,
            'finished_at' => now(),
        ])->save();

        // Notify the system account (in-app bell) when any products failed.
        if ($this->failed_rows > 0) {
            SystemNotifier::send(new ImportFailedNotification($this));
        }
    }

    /** Percentage of rows that have finished processing (0–100). */
    public function getProgressPercentAttribute(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }

    /** Percentage of processed rows that succeeded (0–100). */
    public function getSuccessRateAttribute(): int
    {
        if ($this->processed_rows === 0) {
            return 0;
        }

        return (int) round(($this->successful_rows / $this->processed_rows) * 100);
    }
}
