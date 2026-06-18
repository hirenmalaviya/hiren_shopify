<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Upload;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $byStatus = Product::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $successful = (int) ($byStatus[ProductStatus::Successful->value] ?? 0);
        $failed = (int) ($byStatus[ProductStatus::Failed->value] ?? 0);
        $skipped = (int) ($byStatus[ProductStatus::Skipped->value] ?? 0);
        $pending = (int) ($byStatus[ProductStatus::Pending->value] ?? 0)
            + (int) ($byStatus[ProductStatus::Processing->value] ?? 0);

        $processed = $successful + $failed;

        $stats = [
            'uploads' => Upload::count(),
            'products' => Product::count(),
            'successful' => $successful,
            'failed' => $failed,
            'skipped' => $skipped,
            'pending' => $pending,
            'success_rate' => $processed > 0 ? (int) round($successful / $processed * 100) : 0,
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'uploads' => Upload::latest()->limit(100)->get(),
        ]);
    }
}
