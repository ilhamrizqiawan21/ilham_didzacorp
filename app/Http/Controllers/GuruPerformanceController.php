<?php

namespace App\Http\Controllers;

use App\Services\GuruPerformanceService;
use Inertia\Inertia;

class GuruPerformanceController extends Controller
{
    public function __invoke(GuruPerformanceService $service)
    {
        $payload = $service->dashboard();

        return Inertia::render('PerformaGuru/Index', [
            ...$payload,
            'exportUrls' => [
                'excel' => route('admin.performa-guru.export.excel'),
                'pdf' => route('admin.performa-guru.export.pdf'),
            ],
        ]);
    }
}
