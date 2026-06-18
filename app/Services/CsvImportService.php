<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\UploadStatus;
use App\Models\Upload;
use App\Support\CsvProductMapper;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

/**
 * Parses a stored CSV file into an Upload + Product rows, applying per-row
 * validation and in-file duplicate detection. Shared by the web upload
 * controller and the `import:csv` console command.
 */
class CsvImportService
{
    public function __construct(private readonly CsvProductMapper $mapper) {}

    /**
     * Build an Upload (with its Product rows) from an already-stored CSV file.
     *
     * @param  string  $fullPath  Absolute path to the readable CSV file.
     * @param  string  $originalName  Original client filename, shown in the UI.
     * @param  string  $storedPath  Relative path on the `local` disk for later cleanup.
     */
    public function createUpload(string $fullPath, string $originalName, string $storedPath): Upload
    {
        $reader = Reader::createFromPath($fullPath, 'r');
        $reader->setHeaderOffset(0);

        $maxRows = (int) config('shopify.import.max_rows', 5000);

        return DB::transaction(function () use ($originalName, $storedPath, $reader, $maxRows) {
            $upload = Upload::create([
                'original_filename' => $originalName,
                'stored_path' => $storedPath,
                'status' => UploadStatus::Pending,
            ]);

            $total = 0;
            $failed = 0;
            $skipped = 0;
            $seenHandles = [];
            $seenSkus = [];
            $rowNumber = 0;

            foreach ($reader->getRecords() as $record) {
                if ($total >= $maxRows) {
                    break;
                }

                $rowNumber++;
                $total++;

                $attributes = $this->mapper->map($record);
                $errors = $this->mapper->validate($record);

                $status = ProductStatus::Pending;
                $errorMessage = null;

                $handleKey = strtolower((string) $attributes['handle']);
                $skuKey = strtolower((string) $attributes['sku']);

                if (empty($errors) && $handleKey !== '' && isset($seenHandles[$handleKey])) {
                    $status = ProductStatus::Skipped;
                    $errorMessage = "Duplicate Handle in file (first seen on row {$seenHandles[$handleKey]}).";
                    $skipped++;
                } elseif (empty($errors) && $skuKey !== '' && isset($seenSkus[$skuKey])) {
                    $status = ProductStatus::Skipped;
                    $errorMessage = "Duplicate SKU in file (first seen on row {$seenSkus[$skuKey]}).";
                    $skipped++;
                } elseif (! empty($errors)) {
                    $status = ProductStatus::Failed;
                    $errorMessage = implode(' ', $errors);
                    $failed++;
                } else {
                    if ($handleKey !== '') {
                        $seenHandles[$handleKey] = $rowNumber;
                    }
                    if ($skuKey !== '') {
                        $seenSkus[$skuKey] = $rowNumber;
                    }
                }

                $upload->products()->create(array_merge($attributes, [
                    'row_number' => $rowNumber,
                    'status' => $status,
                    'error_message' => $errorMessage,
                ]));
            }

            $upload->update([
                'total_rows' => $total,
                'failed_rows' => $failed,
                'skipped_rows' => $skipped,
            ]);

            return $upload;
        });
    }
}
