<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\KelasMapel;
use App\Models\Notifikasi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (! $siswa) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
        }

        $kelasMapel = KelasMapel::with(['mataPelajaran', 'guru'])
            ->where('kelas_id', $siswa->kelas_id)
            ->aktif()
            ->get();

        return Inertia::render('Siswa/Chat/Index', [
            'rooms' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'title' => $item->mataPelajaran?->nama_mapel ?? '-',
                'subtitle' => $item->guru?->nama_lengkap ?? '-',
                'url' => route('siswa.chat.show', $item),
            ]),
            'emptyMessage' => 'Belum ada mata pelajaran.',
        ]);
    }

    // Tampilan chat yang difilter sesuai dengan guru dan mapel yang dipilih oleh siswa, dan menampilkan pesan yang sudah dibaca dan belum dibaca
    public function show(KelasMapel $kelasMapel)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        $this->ensureKelasMapelAktifUntukSiswa($kelasMapel, $siswa);

        $kelasMapel->load(['guru', 'mataPelajaran']);

        $messages = ChatMessage::with('user')
            ->where('kelas_mapel_id', $kelasMapel->id)
            ->orderBy('created_at', 'asc')
            ->get();

        ChatMessage::where('kelas_mapel_id', $kelasMapel->id)
            ->where('user_id', '!=', Auth::id())
            ->update(['is_read' => true]);

        return Inertia::render('Siswa/Chat/Show', [
            'room' => [
                'title' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'subtitle' => $kelasMapel->guru?->nama_lengkap ?? '-',
            ],
            'messages' => $messages->map(fn (ChatMessage $message) => [
                'id' => $message->id,
                'message' => $message->message,
                'author' => $message->user?->nama_lengkap ?? 'Unknown',
                'avatar_url' => $message->user?->foto ? Storage::disk('public')->url($message->user->foto) : null,
                'is_mine' => (int) $message->user_id === (int) Auth::id(),
                'time' => $message->created_at ? Carbon::parse($message->created_at)->format('H:i') : '',
            ]),
            'sendUrl' => route('siswa.chat.send', $kelasMapel),
            'backUrl' => route('siswa.chat.index'),
            'emptyMessage' => 'Belum ada pesan.',
        ]);
    }

    // Kirim Pesan
    public function send(Request $request, KelasMapel $kelasMapel)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        $this->ensureKelasMapelAktifUntukSiswa($kelasMapel, $siswa);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $chat = DB::transaction(function () use ($kelasMapel, $validated, $user) {
            $chat = ChatMessage::create([
                'user_id' => Auth::id(),
                'kelas_mapel_id' => $kelasMapel->id,
                'message' => $validated['message'],
            ]);

            // Kirim notifikasi ke guru
            if ($kelasMapel->guru_id) {
                Notifikasi::create([
                    'user_id' => $kelasMapel->guru_id,
                    'tipe' => 'chat_baru',
                    'judul' => 'Pesan baru dari siswa',
                    'pesan' => $user->nama_lengkap.': '.Str::limit($validated['message'], 100),
                    'link' => route('guru.chat.show', $kelasMapel),
                ]);
            }

            return $chat;
        });

        if ($request->ajax() && ! $request->header('X-Inertia')) {
            return response()->json(['success' => true, 'message' => $chat->load('user')]);
        }

        return back()->with('success', 'Pesan berhasil dikirim.');
    }

    private function ensureKelasMapelAktifUntukSiswa(KelasMapel $kelasMapel, ?Siswa $siswa): void
    {
        abort_unless(
            $siswa
            && (int) $siswa->kelas_id === (int) $kelasMapel->kelas_id
            && $kelasMapel->isAktif(),
            403
        );
    }
}
