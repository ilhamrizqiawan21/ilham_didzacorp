<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notifikasi::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn (Notifikasi $item) => $this->format($item));

        return Inertia::render('Notifications/Index', [
            'notifikasi' => $notifications,
            'unreadCount' => Notifikasi::where('user_id', Auth::id())
                ->where('is_read', false)
                ->count(),
            'markAllReadUrl' => route($this->routePrefix() . '.mark-all-read'),
        ]);
    }

    public function markRead(Notifikasi $notifikasi)
    {
        abort_unless((int) $notifikasi->user_id === (int) Auth::id(), 403);

        $notifikasi->update(['is_read' => true]);

        if ($notifikasi->link) {
            if (request()->header('X-Inertia')) {
                return Inertia::location($notifikasi->link);
            }

            return redirect($notifikasi->link);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllRead()
    {
        Notifikasi::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    private function format(Notifikasi $item): array
    {
        return [
            'id' => $item->id,
            'tipe' => $item->tipe,
            'judul' => $item->judul,
            'pesan' => $item->pesan,
            'pesan_ringkas' => Str::limit((string) $item->pesan, 100),
            'link' => $item->link,
            'is_read' => (bool) $item->is_read,
            'created_at' => $item->created_at ? Carbon::parse($item->created_at)->diffForHumans() : '-',
            'mark_read_url' => route($this->routePrefix() . '.mark-read', $item),
        ];
    }

    private function routePrefix(): string
    {
        return match (Auth::user()->role?->nama_role) {
            'admin' => 'admin.notifikasi',
            'guru' => 'guru.notifikasi',
            'siswa' => 'siswa.notifikasi',
            'kepala_sekolah' => 'kepsek.notifikasi',
            default => abort(403),
        };
    }
}
