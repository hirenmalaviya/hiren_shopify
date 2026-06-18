<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Enums\UploadStatus;
use App\Http\Requests\StoreUploadRequest;
use App\Jobs\ImportProductToShopify;
use App\Jobs\ProcessCsvImport;
use App\Models\Upload;
use App\Services\CsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UploadController extends Controller
{
    public function index(): Response
    {
        $uploads = Upload::query()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Uploads/Index', [
            'uploads' => $uploads,
        ]);
    }

    public function show(Upload $upload): Response
    {
        // Store handle (e.g. "laravel-import-test") for building Shopify admin links.
        $storeHandle = str_replace('.myshopify.com', '', (string) config('shopify.store_url'));

        return Inertia::render('Uploads/Show', [
            'upload' => $upload,
            'products' => $upload->products()->orderBy('row_number')->get(),
            'storeHandle' => $storeHandle,
        ]);
    }

    public function store(StoreUploadRequest $request, CsvImportService $importer): RedirectResponse
    {
        $file = $request->file('file');
        $storedPath = $file->store('uploads', 'local');

        $upload = $importer->createUpload(
            Storage::disk('local')->path($storedPath),
            $file->getClientOriginalName(),
            $storedPath,
        );

        ProcessCsvImport::dispatch($upload);

        return redirect()
            ->route('uploads.show', $upload)
            ->with('success', "Upload received — {$upload->total_rows} rows queued for import.");
    }

    public function retry(Upload $upload): RedirectResponse
    {
        $failed = $upload->products()->where('status', ProductStatus::Failed)->get();

        if ($failed->isEmpty()) {
            return back()->with('error', 'There are no failed products to retry.');
        }

        foreach ($failed as $product) {
            $product->forceFill([
                'status' => ProductStatus::Pending,
                'error_message' => null,
            ])->save();

            ImportProductToShopify::dispatch($product);
        }

        $upload->forceFill([
            'status' => UploadStatus::Processing,
            'finished_at' => null,
        ])->save();

        return back()->with('success', "Retrying {$failed->count()} failed product(s).");
    }

    public function destroy(Upload $upload): RedirectResponse
    {
        Storage::disk('local')->delete($upload->stored_path);
        $upload->delete();

        return redirect()
            ->route('uploads.index')
            ->with('success', 'Upload deleted.');
    }
}
