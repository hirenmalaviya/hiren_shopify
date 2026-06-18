<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCsvImport;
use App\Services\CsvImportService;
use App\Support\CsvProductMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ImportCsv extends Command
{
    protected $signature = 'import:csv {path : Path to the CSV file}';

    protected $description = 'Import a product CSV from the filesystem (stores it, creates rows, queues the Shopify import).';

    public function handle(CsvImportService $importer): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        // Validate header columns before doing any work.
        try {
            $reader = Reader::createFromPath($path, 'r');
            $reader->setHeaderOffset(0);
            $missing = CsvProductMapper::missingColumns($reader->getHeader());
        } catch (\Throwable $e) {
            $this->error('Could not read CSV: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! empty($missing)) {
            $this->error('CSV is missing required column(s): '.implode(', ', $missing));

            return self::FAILURE;
        }

        // Copy into managed storage so the record references a stable path.
        $storedPath = 'uploads/'.uniqid('cli_', true).'.csv';
        Storage::disk('local')->put($storedPath, file_get_contents($path));

        $upload = $importer->createUpload(
            Storage::disk('local')->path($storedPath),
            basename($path),
            $storedPath,
        );

        ProcessCsvImport::dispatch($upload);

        $this->info("Upload #{$upload->id} created — {$upload->total_rows} rows queued for import.");
        $this->line('Run the worker to process: php artisan queue:work --stop-when-empty');

        return self::SUCCESS;
    }
}
