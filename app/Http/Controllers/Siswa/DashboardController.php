<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\KelasDaring;
use App\Models\KelasMapel;
use App\Models\Materi;
use App\Models\Notifikasi;
use App\Models\PengumpulanTugas;
use App\Models\Pengumuman;
use App\Models\Tugas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (! $siswa) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
        }

        $kelasMapel = KelasMapel::with(['mataPelajaran', 'guru', 'tahunAjaran'])
            ->withCount(['materi', 'tugas'])
            ->where('kelas_id', $siswa->kelas_id)
            ->aktif()
            ->get();

        $kelasMapelIds = $kelasMapel->pluck('id');

        $totalTugas = Tugas::whereIn('kelas_mapel_id', $kelasMapelIds)->count();
        $tugasSelesai = PengumpulanTugas::where('siswa_id', $siswa->id)
            ->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)
            ->whereHas('tugas', fn ($q) => $q->whereIn('kelas_mapel_id', $kelasMapelIds))
            ->count();
        $tugasBelum = max($totalTugas - $tugasSelesai, 0);
        $totalMateri = Materi::whereIn('kelas_mapel_id', $kelasMapelIds)->count();

        $tugasTerbaru = Tugas::with([
            'kelasMapel.mataPelajaran',
            'pengumpulan' => fn ($q) => $q->where('siswa_id', $siswa->id),
        ])
            ->whereIn('kelas_mapel_id', $kelasMapelIds)
            ->orderBy('batas_waktu', 'asc')
            ->take(5)
            ->get();

        $pengumuman = Pengumuman::with('creator')
            ->where(function ($q) use ($kelasMapelIds, $siswa) {
                $q->where('target', 'semua')
                    ->orWhere('target', 'siswa')
                    ->orWhere(function ($q) use ($kelasMapelIds, $siswa) {
                        $q->where('target', 'kelas_mapel')
                            ->where(function ($q) use ($kelasMapelIds, $siswa) {
                                $q->whereIn('kelas_mapel_id', $kelasMapelIds)
                                    ->orWhere('target_kelas', 'like', '%"'.$siswa->kelas_id.'"%');
                            });
                    });
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $notifikasi = Notifikasi::where('user_id', $user->id)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $kelasDaring = KelasDaring::with('kelasMapel.mataPelajaran')
            ->whereIn('kelas_mapel_id', $kelasMapelIds)
            ->where('status', 'terjadwal')
            ->where('tanggal', '>=', now()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('pelajaran_ke')
            ->take(5)
            ->get();

        return Inertia::render('Siswa/Dashboard', [
            'stats' => [
                'total_tugas' => $totalTugas,
                'tugas_selesai' => $tugasSelesai,
                'tugas_belum' => $tugasBelum,
                'total_materi' => $totalMateri,
            ],
            'tugasTerbaru' => $tugasTerbaru->map(function (Tugas $tugas) use ($siswa) {
                $pengumpulan = $tugas->pengumpulan->where('siswa_id', $siswa->id)->first();

                return [
                    'id' => $tugas->id,
                    'judul' => $tugas->judul,
                    'mata_pelajaran' => $tugas->kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
                    'batas_waktu' => $tugas->batas_waktu ? Carbon::parse($tugas->batas_waktu)->format('d/m/Y') : '-',
                    'selesai' => in_array($pengumpulan?->status, PengumpulanTugas::STATUS_SUBMITTED, true),
                    'show_url' => route('siswa.tugas.show', $tugas),
                ];
            })->values(),
            'courses' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'title' => $item->mataPelajaran?->nama_mapel ?? '-',
                'subtitle' => $item->guru?->nama_lengkap ?? 'Guru belum ditetapkan',
                'meta' => ($item->materi_count ?? 0).' materi · '.($item->tugas_count ?? 0).' tugas',
                'href' => route('siswa.kelas-mapel.show', $item),
                'badges' => [['label' => 'Kelas saya', 'color' => 'primary']],
            ])->values(),
            'notifikasi' => $notifikasi->map(fn (Notifikasi $item) => [
                'id' => $item->id,
                'tipe' => $item->tipe,
                'judul' => $item->judul,
                'pesan' => Str::limit((string) $item->pesan, 60),
                'is_read' => $item->is_read,
                'created_at' => $item->created_at ? Carbon::parse($item->created_at)->diffForHumans() : '',
            ])->values(),
            'pengumuman' => $pengumuman->map(fn (Pengumuman $item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'created_at' => $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y') : '-',
                'show_url' => route('siswa.pengumuman.show', $item),
            ])->values(),
            'kelasDaring' => $kelasDaring->map(fn (KelasDaring $item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'mata_pelajaran' => $item->kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
                'tanggal' => $item->tanggal?->format('d M Y'),
                'pelajaran_ke' => $item->pelajaran_ke,
                'meeting_url' => $item->meeting_url,
                'workspace_url' => $item->kelasMapel ? route('siswa.kelas-mapel.show', $item->kelasMapel) : null,
            ])->values(),
            'links' => [
                'notifikasi' => route('siswa.notifikasi.index'),
                'pengumuman' => route('siswa.pengumuman.index'),
                'materi' => route('siswa.materi.index'),
                'tugas' => route('siswa.tugas.index'),
                'jadwal_pelajaran' => route('siswa.jadwal-pelajaran'),
                'kelas_daring' => route('siswa.kelas-daring'),
            ],
        ]);
    }
}
