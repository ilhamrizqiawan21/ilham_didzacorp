<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NotifikasiController extends Controller
{
    /**
     * Tampilkan semua notifikasi guru.
     */
    public function index()
    {
        $notifikasi = Notifikasi::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $unreadCount = Notifikasi::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return Inertia::render('Guru/Notifikasi/Index', [
            'notifikasi' => $notifikasi->through(fn (Notifikasi $item) => [
                'id' => $item->id,
                'tipe' => $item->tipe,
                'judul' => $item->judul,
                'pesan' => $item->pesan,
                'pesan_ringkas' => Str::limit((string) $item->pesan, 80),
                'link' => $item->link,
                'is_read' => $item->is_read,
                'created_at' => $item->created_at ? Carbon::parse($item->created_at)->diffForHumans() : '-',
                'mark_read_url' => route('guru.notifikasi.mark-read', $item),
            ]),
            'unreadCount' => $unreadCount,
            'markAllReadUrl' => route('guru.notifikasi.mark-all-read'),
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function markRead(Notifikasi $notifikasi)
    {
        if ((int) $notifikasi->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $notifikasi->update(['is_read' => true]);

        if ($notifikasi->link) {
            if (request()->header('X-Inertia')) {
                return Inertia::location($notifikasi->link);
            }

            return redirect($notifikasi->link);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca.
     */
    public function markAllRead()
    {
        Notifikasi::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
