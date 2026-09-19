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
                'excel' => route(request()->routeIs('admin.*') ? 'admin.performa-guru.export.excel' : 'kepsek.performa-guru.export.excel'),
                'pdf' => route(request()->routeIs('admin.*') ? 'admin.performa-guru.export.pdf' : 'kepsek.performa-guru.export.pdf'),
            ],
        ]);
    }
}
