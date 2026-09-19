<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\ChatMessage;
use App\Models\KelasMapel;
use App\Models\Materi;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\Tugas;
use Inertia\Inertia;

class KelasMapelWorkspaceController extends Controller
{
    public function show(KelasMapel $kelasMapel)
    {
        $this->authorize('mengajar', $kelasMapel);

        $kelasMapel->load(['kelas', 'mataPelajaran', 'tahunAjaran']);

        $totalSiswa = Siswa::where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->count();
        $totalMateri = Materi::where('kelas_mapel_id', $kelasMapel->id)->count();
        $totalTugas = Tugas::where('kelas_mapel_id', $kelasMapel->id)->count();
        $perluDinilai = PengumpulanTugas::whereHas('tugas', fn ($query) => $query->where('kelas_mapel_id', $kelasMapel->id))
            ->whereIn('status', ['sudah', 'terlambat'])
            ->whereNull('nilai')
            ->count();
        $absensiHariIni = Absensi::where('kelas_mapel_id', $kelasMapel->id)
            ->whereDate('tanggal', today())
            ->count();

        $recentTasks = Tugas::where('kelas_mapel_id', $kelasMapel->id)
            ->withCount([
                'pengumpulan as submitted_count' => fn ($query) => $query->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED),
                'pengumpulan as pending_grading_count' => fn ($query) => $query
                    ->whereIn('status', ['sudah', 'terlambat'])
                    ->whereNull('nilai'),
            ])
            ->orderByDesc('batas_waktu')
            ->take(5)
            ->get();

        $latestMessage = ChatMessage::with('user')
            ->where('kelas_mapel_id', $kelasMapel->id)
            ->latest('created_at')
            ->first();

        $tabs = [
            ['label' => 'Ringkasan', 'href' => route('guru.kelas-mapel.show', $kelasMapel), 'icon' => 'bi-grid-1x2'],
            ['label' => 'Materi', 'href' => route('guru.materi.list', $kelasMapel), 'icon' => 'bi-file-earmark-text'],
            ['label' => 'Tugas', 'href' => route('guru.tugas.list', $kelasMapel), 'icon' => 'bi-journal-check'],
            ['label' => 'Nilai', 'href' => route('guru.nilai.input', $kelasMapel), 'icon' => 'bi-bar-chart'],
            ['label' => 'Absensi', 'href' => route('guru.absensi.create', $kelasMapel), 'icon' => 'bi-clipboard-check'],
            ['label' => 'Chat', 'href' => route('guru.chat.show', $kelasMapel), 'icon' => 'bi-chat-dots'],
        ];

        return Inertia::render('Guru/KelasMapel/Show', [
            'course' => [
                'title' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'kelas' => $kelasMapel->kelas?->displayName() ?? '-',
                'semester' => $kelasMapel->semester === '1' ? 'Ganjil' : 'Genap',
                'tahun_ajaran' => $kelasMapel->tahunAjaran?->tahun ?? '-',
                'back_url' => route('guru.dashboard'),
            ],
            'tabs' => $tabs,
            'metrics' => [
                ['label' => 'Siswa aktif', 'value' => $totalSiswa, 'icon' => 'bi-people-fill', 'tone' => 'info'],
                ['label' => 'Materi', 'value' => $totalMateri, 'icon' => 'bi-file-earmark-text-fill', 'tone' => 'primary', 'href' => route('guru.materi.list', $kelasMapel)],
                ['label' => 'Tugas', 'value' => $totalTugas, 'icon' => 'bi-journal-fill', 'tone' => 'warning', 'href' => route('guru.tugas.list', $kelasMapel)],
                ['label' => 'Perlu dinilai', 'value' => $perluDinilai, 'icon' => 'bi-pencil-square', 'tone' => 'danger', 'href' => route('guru.tugas.list', $kelasMapel)],
            ],
            'tasks' => $recentTasks->map(fn (Tugas $tugas) => [
                'id' => $tugas->id,
                'title' => $tugas->judul,
                'meta' => $tugas->batas_waktu ? 'Deadline ' . $tugas->batas_waktu->format('d M Y') : 'Tanpa deadline',
                'detail' => $tugas->submitted_count . ' pengumpulan, ' . $tugas->pending_grading_count . ' perlu dinilai',
                'href' => route('guru.tugas.pengumpulan', [$kelasMapel, $tugas]),
                'badge' => $tugas->pending_grading_count ?: null,
                'badgeColor' => 'warning text-dark',
                'icon' => 'bi-journal-check',
                'accent' => '#f59e0b',
            ])->values(),
            'attendance' => [
                'recorded_today' => $absensiHariIni,
                'total_students' => $totalSiswa,
                'href' => route('guru.absensi.create', $kelasMapel),
            ],
            'latestMessage' => $latestMessage ? [
                'author' => $latestMessage->user?->nama_lengkap ?? 'Pengguna',
                'message' => $latestMessage->message,
                'href' => route('guru.chat.show', $kelasMapel),
            ] : null,
        ]);
    }
}
