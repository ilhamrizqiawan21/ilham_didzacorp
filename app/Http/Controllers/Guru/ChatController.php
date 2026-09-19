<?php

namespace App\Http\Controllers\Guru;

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
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', Auth::id())
            ->aktif()
            ->get();

        return Inertia::render('Guru/Chat/Index', [
            'rooms' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'title' => $item->mataPelajaran?->nama_mapel ?? '-',
                'subtitle' => $item->kelas?->displayName() ?? '-',
                'url' => route('guru.chat.show', $item),
            ]),
            'emptyMessage' => 'Anda belum memiliki penugasan.',
        ]);
    }

    // Pengaturan chat sesuai dengan guru mata pelajaran dan kelas yang diampu
    public function chat(KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $kelasMapel->load(['kelas', 'mataPelajaran']);

        $messages = ChatMessage::with('user')
            ->where('kelas_mapel_id', $kelasMapel->id)
            ->orderBy('created_at', 'asc')
            ->get();

        ChatMessage::where('kelas_mapel_id', $kelasMapel->id)
            ->where('user_id', '!=', Auth::id())
            ->update(['is_read' => true]);

        return Inertia::render('Guru/Chat/Show', [
            'room' => [
                'title' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'subtitle' => $kelasMapel->kelas?->displayName() ?? '-',
            ],
            'messages' => $messages->map(fn (ChatMessage $message) => [
                'id' => $message->id,
                'message' => $message->message,
                'author' => $message->user?->nama_lengkap ?? 'Unknown',
                'avatar_url' => $message->user?->foto ? Storage::disk('public')->url($message->user->foto) : null,
                'is_mine' => (int) $message->user_id === (int) Auth::id(),
                'time' => $message->created_at ? Carbon::parse($message->created_at)->format('H:i') : '',
            ]),
            'sendUrl' => route('guru.chat.send', $kelasMapel),
            'backUrl' => route('guru.chat.index'),
            'emptyMessage' => 'Belum ada pesan. Mulai percakapan!',
        ]);
    }

    // Kirim pesan
    public function send(Request $request, KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $chat = DB::transaction(function () use ($kelasMapel, $validated) {
            $chat = ChatMessage::create([
                'user_id' => Auth::id(),
                'kelas_mapel_id' => $kelasMapel->id,
                'message' => $validated['message'],
            ]);

            $siswas = Siswa::where('kelas_id', $kelasMapel->kelas_id)
                ->where('status', 'aktif')
                ->get();

            foreach ($siswas as $siswa) {
                Notifikasi::create([
                    'user_id' => $siswa->user_id,
                    'tipe' => 'chat_baru',
                    'judul' => 'Pesan baru dari guru',
                    'pesan' => 'Pesan: '.$validated['message'],
                    'link' => route('siswa.chat.show', $kelasMapel),
                ]);
            }

            return $chat;
        });

        if ($request->ajax() && ! $request->header('X-Inertia')) {
            return response()->json(['success' => true, 'message' => $chat->load('user')]);
        }

        return back()->with('success', 'Pesan berhasil dikirim.');
    }
}
