<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\KelasMapel;
use App\Models\Materi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MateriController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
        }

        $kelasMapel = KelasMapel::with(['mataPelajaran', 'guru'])
            ->where('kelas_id', $siswa->kelas_id)
            ->aktif()
            ->get();

        return Inertia::render('Siswa/Materi/Index', [
            'kelasMapel' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'mata_pelajaran' => $item->mataPelajaran?->nama_mapel ?? '-',
                'guru' => $item->guru?->nama_lengkap ?? '-',
                'initials' => strtoupper(substr($item->mataPelajaran?->nama_mapel ?? 'MP', 0, 2)),
                'href' => route('siswa.kelas-mapel.show', $item),
            ])->values(),
        ]);
    }
    //Daftar materi yang sudah di input guru
    public function list(KelasMapel $kelasMapel)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        $this->ensureKelasMapelAktifUntukSiswa($kelasMapel, $siswa);

        $materi = Materi::where('kelas_mapel_id', $kelasMapel->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Siswa/Materi/List', [
            'kelasMapel' => [
                'id' => $kelasMapel->id,
                'mata_pelajaran' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'guru' => $kelasMapel->guru?->nama_lengkap ?? '-',
                'back_url' => route('siswa.materi.index'),
                'workspace_url' => route('siswa.kelas-mapel.show', $kelasMapel),
                'tugas_url' => route('siswa.kelas-mapel.show', $kelasMapel) . '#tugas',
                'chat_url' => route('siswa.chat.show', $kelasMapel),
            ],
            'materi' => $materi->map(fn (Materi $item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi ?: 'Tidak ada deskripsi',
                'tanggal' => $item->created_at ? Carbon::parse($item->created_at)->format('d M Y') : '',
                'download_url' => $item->file_path ? route('siswa.materi.download', [$kelasMapel, $item]) : null,
            ])->values(),
        ]);
    }
    //Unduh materi
    public function download(KelasMapel $kelasMapel, Materi $materi)
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        $this->ensureKelasMapelAktifUntukSiswa($kelasMapel, $siswa);

        $this->ensureMateriBelongsToKelasMapel($materi, $kelasMapel);

        $disk = $this->materiDisk($materi->file_path);
        if (!$disk || !$materi->file_path) {
            return back()->with('error', 'File materi tidak ditemukan.');
        }

        return response()->download($disk->path($materi->file_path), $materi->judul . '_' . basename($materi->file_path));
    }

    private function ensureMateriBelongsToKelasMapel(Materi $materi, KelasMapel $kelasMapel): void
    {
        abort_unless((int) $materi->kelas_mapel_id === (int) $kelasMapel->id, 403);
    }

    private function ensureKelasMapelAktifUntukSiswa(KelasMapel $kelasMapel, ?Siswa $siswa): void
    {
        abort_unless(
            $siswa
            && (int) $siswa->kelas_id === (int) $kelasMapel->kelas_id
            && $kelasMapel->isAktif(),
            403,
            'Anda tidak memiliki akses ke materi ini.'
        );
    }

    private function materiDisk(?string $path): ?\Illuminate\Filesystem\FilesystemAdapter
    {
        if (!$path) {
            return null;
        }

        $local = Storage::disk('local');

        return $local->exists($path) ? $local : (Storage::disk('public')->exists($path) ? Storage::disk('public') : null);
    }
}
