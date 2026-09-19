<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\KelasMapel;
use App\Models\NilaiAkhir;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Support\Collection;

class GuruPerformanceService
{
    public function dashboard(): array
    {
        $teachers = User::with('role')
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('nama_role', 'guru'))
            ->orderBy('nama_lengkap')
            ->get();

        $rows = $teachers->map(fn (User $teacher) => $this->teacherRow($teacher))->values();

        return [
            'summary' => [
                'total_guru' => $rows->count(),
                'rata_skor' => $this->average($rows->pluck('score')),
                'total_tugas' => (int) $rows->sum('total_tugas'),
                'perlu_dinilai' => (int) $rows->sum('perlu_dinilai'),
            ],
            'teachers' => $rows,
            'earlyWarnings' => $this->earlyWarnings(),
        ];
    }

    private function teacherRow(User $teacher): array
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', $teacher->id)
            ->aktif()
            ->get();
        $kelasMapelIds = $kelasMapel->pluck('id');
        $kelasIds = $kelasMapel->pluck('kelas_id')->unique()->values();

        $tasks = Tugas::whereIn('kelas_mapel_id', $kelasMapelIds)->get();
        $taskIds = $tasks->pluck('id');
        $studentCountByClass = Siswa::whereIn('kelas_id', $kelasIds)
            ->where('status', 'aktif')
            ->selectRaw('kelas_id, count(*) as total')
            ->groupBy('kelas_id')
            ->pluck('total', 'kelas_id');

        $expectedSubmissions = $tasks->sum(function (Tugas $task) use ($kelasMapel, $studentCountByClass) {
            $course = $kelasMapel->firstWhere('id', $task->kelas_mapel_id);

            return (int) ($studentCountByClass[$course?->kelas_id] ?? 0);
        });

        $submissions = PengumpulanTugas::with('tugas')
            ->whereIn('tugas_id', $taskIds)
            ->get();
        $submitted = $submissions
            ->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)
            ->count();
        $gradable = $submissions
            ->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)
            ->count();
        $graded = $submissions
            ->filter(fn (PengumpulanTugas $item) => $item->nilai !== null)
            ->count();
        $feedback = $submissions
            ->filter(fn (PengumpulanTugas $item) => filled($item->catatan))
            ->count();
        $averageGrade = $submissions
            ->filter(fn (PengumpulanTugas $item) => $item->nilai !== null)
            ->avg(fn (PengumpulanTugas $item) => (float) $item->nilai);

        $completionScore = $expectedSubmissions > 0 ? ($submitted / $expectedSubmissions) * 100 : 0;
        $gradingScore = $gradable > 0 ? ($graded / $gradable) * 100 : 0;
        $gradeScore = $averageGrade !== null ? (float) $averageGrade : 0;
        $feedbackScore = $gradable > 0 ? ($feedback / $gradable) * 100 : 0;
        $activityScore = $kelasMapel->count() > 0
            ? min(100, ($tasks->count() / max(1, $kelasMapel->count() * 4)) * 100)
            : 0;

        $score = round(
            ($completionScore * 0.35)
            + ($gradingScore * 0.25)
            + ($gradeScore * 0.20)
            + ($feedbackScore * 0.10)
            + ($activityScore * 0.10),
            2
        );

        $pending = $submissions
            ->whereIn('status', PengumpulanTugas::STATUS_PERLU_DINILAI)
            ->filter(fn (PengumpulanTugas $item) => $item->nilai === null)
            ->count();
        $avgGradeDays = $this->averageGradeDays($submissions);

        return [
            'id' => $teacher->id,
            'nama' => $teacher->nama_lengkap,
            'username' => $teacher->username,
            'score' => $score,
            'kategori' => $this->scoreCategory($score),
            'total_kelas_mapel' => $kelasMapel->count(),
            'total_tugas' => $tasks->count(),
            'target_pengumpulan' => (int) $expectedSubmissions,
            'pengumpulan_siswa' => $submitted,
            'persen_pengumpulan' => round($completionScore, 1),
            'sudah_dinilai' => $graded,
            'perlu_dinilai' => $pending,
            'persen_dinilai' => round($gradingScore, 1),
            'rata_nilai_tugas' => $averageGrade !== null ? round((float) $averageGrade, 2) : null,
            'persen_feedback' => round($feedbackScore, 1),
            'aktivitas_tugas' => round($activityScore, 1),
            'rata_hari_penilaian' => $avgGradeDays,
            'courses' => $kelasMapel->map(fn (KelasMapel $item) => trim(($item->kelas?->nama_kelas ?? '-') . ' - ' . ($item->mataPelajaran?->nama_mapel ?? '-')))->values(),
        ];
    }

    private function earlyWarnings(): Collection
    {
        $students = Siswa::with(['user', 'kelas'])
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();

        return $students->map(function (Siswa $student) {
            $courseIds = KelasMapel::aktif()
                ->where('kelas_id', $student->kelas_id)
                ->pluck('id');
            $totalTasks = Tugas::whereIn('kelas_mapel_id', $courseIds)->count();
            $submitted = PengumpulanTugas::where('siswa_id', $student->id)
                ->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)
                ->whereHas('tugas', fn ($query) => $query->whereIn('kelas_mapel_id', $courseIds))
                ->count();
            $missingTasks = max(0, $totalTasks - $submitted);
            $averageGrade = NilaiAkhir::where('siswa_id', $student->id)
                ->whereIn('kelas_mapel_id', $courseIds)
                ->selectRaw('AVG('.NilaiAkhir::rataAkhirExpression().') as rata')
                ->value('rata');
            $alphaCount = Absensi::where('siswa_id', $student->id)
                ->whereIn('kelas_mapel_id', $courseIds)
                ->where('status', 'alpha')
                ->where('tanggal', '>=', now()->subDays(60)->toDateString())
                ->count();

            $reasons = [];
            if ($missingTasks >= 3) $reasons[] = "{$missingTasks} tugas belum dikumpulkan";
            if ($averageGrade !== null && (float) $averageGrade < 75) $reasons[] = 'rata-rata nilai di bawah 75';
            if ($alphaCount >= 3) $reasons[] = "{$alphaCount} alpha dalam 60 hari";

            if ($reasons === []) {
                return null;
            }

            return [
                'id' => $student->id,
                'nama' => $student->user?->nama_lengkap ?? $student->nis,
                'nis' => $student->nis,
                'kelas' => trim(($student->kelas?->tingkat ? $student->kelas->tingkat . ' ' : '') . ($student->kelas?->nama_kelas ?? '-')),
                'reasons' => implode(', ', $reasons),
                'missing_tasks' => $missingTasks,
                'alpha_count' => $alphaCount,
                'average_grade' => $averageGrade !== null ? round((float) $averageGrade, 2) : null,
            ];
        })->filter()->sortByDesc(fn ($item) => $item['missing_tasks'] + $item['alpha_count'])->take(10)->values();
    }

    private function average(Collection $values): float
    {
        $filtered = $values->filter(fn ($value) => $value !== null);

        return $filtered->isNotEmpty() ? round((float) $filtered->avg(), 2) : 0.0;
    }

    private function averageGradeDays(Collection $submissions): ?float
    {
        $days = $submissions
            ->filter(fn (PengumpulanTugas $item) => $item->tanggal_kumpul && $item->graded_at)
            ->map(fn (PengumpulanTugas $item) => max(0, $item->tanggal_kumpul->diffInDays($item->graded_at, false)));

        return $days->isNotEmpty() ? round((float) $days->avg(), 1) : null;
    }

    private function scoreCategory(float $score): string
    {
        return match (true) {
            $score >= 85 => 'Sangat baik',
            $score >= 75 => 'Baik',
            $score >= 60 => 'Perlu dipantau',
            default => 'Perlu pendampingan',
        };
    }
}
