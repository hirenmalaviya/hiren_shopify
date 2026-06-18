<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function index(): Response
    {
        $logs = ImportLog::query()
            ->with([
                'upload:id,original_filename',
                'product:id,sku,row_number',
            ])
            ->latest()
            ->limit(300)
            ->get()
            ->map(fn (ImportLog $log) => [
                'id' => $log->id,
                'level' => $log->level->value,
                'message' => $log->message,
                'context' => $log->context,
                'created_at' => $log->created_at,
                'upload_id' => $log->upload_id,
                'upload' => $log->upload?->original_filename,
                'sku' => $log->product?->sku,
            ]);

        return Inertia::render('Logs/Index', [
            'logs' => $logs,
        ]);
    }
}
