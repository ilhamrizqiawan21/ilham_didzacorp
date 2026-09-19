<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\KelasDaring;
use App\Models\KelasMapel;
use App\Models\Materi;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KelasMapelWorkspaceController extends Controller
{
    public function show(KelasMapel $kelasMapel)
    {
        $siswa = Auth::user()?->siswa;

        abort_unless(
            $siswa && (int) $siswa->kelas_id === (int) $kelasMapel->kelas_id && $kelasMapel->isAktif(),
            403,
            'Anda tidak memiliki akses ke kelas dan mata pelajaran ini.'
        );

        $kelasMapel->load(['kelas', 'mataPelajaran', 'tahunAjaran']);
        $totalTasks = Tugas::where('kelas_mapel_id', $kelasMapel->id)->count();
        $tasks = Tugas::with(['pengumpulan' => fn ($query) => $query->where('siswa_id', $siswa->id)])
            ->where('kelas_mapel_id', $kelasMapel->id)
            ->orderBy('batas_waktu')
            ->take(5)
            ->get();
        $submittedCount = PengumpulanTugas::where('siswa_id', $siswa->id)
            ->whereHas('tugas', fn ($query) => $query->where('kelas_mapel_id', $kelasMapel->id))
            ->count();
        $latestMessage = ChatMessage::with('user')->where('kelas_mapel_id', $kelasMapel->id)->latest('created_at')->first();
        $onlineClasses = KelasDaring::where('kelas_mapel_id', $kelasMapel->id)
            ->where('status', 'terjadwal')
            ->where('tanggal', '>=', now()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('pelajaran_ke')
            ->take(5)
            ->get();

        return Inertia::render('Siswa/KelasMapel/Show', [
            'course' => [
                'title' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                'kelas' => trim(($kelasMapel->kelas?->tingkat ? $kelasMapel->kelas->tingkat . ' ' : '') . ($kelasMapel->kelas?->nama_kelas ?? '-')),
                'semester' => $kelasMapel->semester === '1' ? 'Ganjil' : 'Genap',
                'tahun_ajaran' => $kelasMapel->tahunAjaran?->tahun ?? '-',
                'back_url' => route('siswa.materi.index'),
            ],
            'tabs' => [
                ['label' => 'Ringkasan', 'href' => route('siswa.kelas-mapel.show', $kelasMapel), 'icon' => 'bi-grid-1x2'],
                ['label' => 'Materi', 'href' => route('siswa.materi.list', $kelasMapel), 'icon' => 'bi-file-earmark-text'],
                ['label' => 'Tugas', 'href' => route('siswa.kelas-mapel.show', $kelasMapel) . '#tugas', 'icon' => 'bi-journal-check'],
                ['label' => 'Daring', 'href' => route('siswa.kelas-daring', ['kelas_mapel_id' => $kelasMapel->id]), 'icon' => 'bi-camera-video'],
                ['label' => 'Chat', 'href' => route('siswa.chat.show', $kelasMapel), 'icon' => 'bi-chat-dots'],
            ],
            'metrics' => [
                ['label' => 'Materi', 'value' => Materi::where('kelas_mapel_id', $kelasMapel->id)->count(), 'icon' => 'bi-file-earmark-text-fill', 'tone' => 'primary', 'href' => route('siswa.materi.list', $kelasMapel)],
                ['label' => 'Tugas', 'value' => $totalTasks, 'icon' => 'bi-journal-fill', 'tone' => 'warning'],
                ['label' => 'Sudah dikumpulkan', 'value' => $submittedCount, 'icon' => 'bi-check-circle-fill', 'tone' => 'success'],
                ['label' => 'Belum dikumpulkan', 'value' => max(0, $totalTasks - $submittedCount), 'icon' => 'bi-exclamation-circle-fill', 'tone' => 'danger'],
            ],
            'tasks' => $tasks->map(function (Tugas $tugas) {
                $submission = $tugas->pengumpulan->first();

                return [
                    'id' => $tugas->id,
                    'title' => $tugas->judul,
                    'meta' => $tugas->batas_waktu ? 'Deadline ' . $tugas->batas_waktu->format('d M Y') : 'Tanpa deadline',
                    'detail' => $submission?->nilai !== null ? 'Nilai: ' . $submission->nilai : ($submission ? 'Sudah dikumpulkan' : 'Belum dikumpulkan'),
                    'href' => route('siswa.tugas.show', $tugas),
                    'badge' => $submission ? 'Selesai' : 'Belum',
                    'badgeColor' => $submission ? 'success' : 'warning text-dark',
                    'icon' => $submission ? 'bi-check-circle' : 'bi-journal-text',
                    'accent' => $submission ? '#16a34a' : '#f59e0b',
                ];
            })->values(),
            'latestMessage' => $latestMessage ? [
                'author' => $latestMessage->user?->nama_lengkap ?? 'Pengguna',
                'message' => $latestMessage->message,
                'href' => route('siswa.chat.show', $kelasMapel),
            ] : null,
            'onlineClasses' => $onlineClasses->map(fn (KelasDaring $item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'tanggal' => $item->tanggal?->format('d M Y'),
                'pelajaran_ke' => $item->pelajaran_ke,
                'meeting_url' => $item->meeting_url,
            ])->values(),
        ]);
    }
}
