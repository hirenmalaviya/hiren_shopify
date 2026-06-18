<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\UploadController;
use App\Support\CsvProductMapper;
use App\Support\SystemNotifier;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

Route::get('/', fn () => redirect()->route('uploads.index'))->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

Route::post('/notifications/read', function () {
    SystemNotifier::markAllRead();

    return back();
})->name('notifications.read');

Route::get('/uploads', [UploadController::class, 'index'])->name('uploads.index');
Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
Route::get('/uploads/{upload}', [UploadController::class, 'show'])->name('uploads.show');
Route::post('/uploads/{upload}/retry', [UploadController::class, 'retry'])->name('uploads.retry');
Route::delete('/uploads/{upload}', [UploadController::class, 'destroy'])->name('uploads.destroy');

// Downloadable CSV template with the expected headers + one example row.
Route::get('/csv-template', function () {
    $headers = array_keys(CsvProductMapper::HEADER_MAP);
    $example = [
        'example-product', 'Example Product', '<p>A great product.</p>', 'Acme', 'Widgets',
        'tag1,tag2', 'TRUE', 'EXP-001', '19.99', '24.99', 'TRUE', 'TRUE', 'shopify', '100',
        'deny', 'manual', '0.5', 'kg', 'https://example.com/image.jpg', '1', 'Example image',
    ];

    return new StreamedResponse(function () use ($headers, $example) {
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        fputcsv($out, $example);
        fclose($out);
    }, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="product-import-template.csv"',
    ]);
})->name('csv.template');
