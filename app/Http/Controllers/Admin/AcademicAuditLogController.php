<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class AcademicAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'module' => 'nullable|in:absensi,nilai',
            'search' => 'nullable|string|max:100',
        ]);

        if (! Schema::hasTable('academic_audit_logs')) {
            return Inertia::render('Admin/AcademicAuditLog/Index', [
                'logs' => ['data' => [], 'links' => []],
                'filters' => [
                    'module' => $request->string('module')->toString(),
                    'search' => $request->string('search')->toString(),
                ],
            ]);
        }

        $query = AcademicAuditLog::with('actor')
            ->orderBy('created_at', 'desc');

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('actor', fn ($actor) => $actor
                ->where('username', 'like', "%{$search}%")
                ->orWhere('nama_lengkap', 'like', "%{$search}%"));
        }

        $logs = $query->paginate(25)
            ->withQueryString()
            ->through(fn (AcademicAuditLog $log) => [
                'id' => $log->id,
                'created_at' => optional($log->created_at)->format('d M Y H:i:s'),
                'module' => $log->module,
                'action' => $log->action,
                'actor' => $log->actor?->nama_lengkap ?? '-',
                'before_values' => $log->before_values ?? [],
                'after_values' => $log->after_values ?? [],
                'metadata' => $log->metadata ?? [],
            ]);

        return Inertia::render('Admin/AcademicAuditLog/Index', [
            'logs' => $logs,
            'filters' => [
                'module' => $request->string('module')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }
}
