<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatistikService;
use App\Models\LogLogin;
use App\Models\Pengumuman;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected StatistikService $statistikService;

    public function __construct(StatistikService $statistikService)
    {
        $this->statistikService = $statistikService;
    }

    /**
     * Tampilkan dashboard admin.
     */
    public function index()
    {
        $statistik = $this->statistikService->dashboardAdmin();
        $loginTerbaru = LogLogin::with('user')
            ->orderBy('login_time', 'desc')
            ->take(10)
            ->get();
        $pengumuman = Pengumuman::with('creator')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'statistik' => $statistik,
            'loginTerbaru' => $loginTerbaru->map(fn (LogLogin $log) => [
                'id' => $log->id,
                'nama_lengkap' => $log->nama_lengkap,
                'role' => $log->role,
                'ip_address' => $log->ip_address,
                'login_time' => $log->login_time?->diffForHumans(),
            ])->values(),
            'pengumuman' => $pengumuman->map(fn (Pengumuman $item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'isi' => Str::limit($item->isi, 120),
                'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('d M Y') : null,
                'creator' => $item->creator?->nama_lengkap ?? 'Admin',
            ])->values(),
        ]);
    }
}
