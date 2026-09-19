<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\KelasMapel;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\Tugas;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherDashboardService
{
    public function __construct(private readonly StatistikService $statistikService)
    {
    }

    public function forGuru(int $guruId): array
    {
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran', 'tahunAjaran'])
            ->withCount(['materi', 'tugas'])
            ->where('guru_id', $guruId)
            ->aktif()
            ->get();

        $kelasMapelIds = $kelasMapel->pluck('id');
        $kelasIds = $kelasMapel->pluck('kelas_id')->unique()->values();
        $kelasIdByKelasMapel = $kelasMapel->pluck('kelas_id', 'id');
        $chartMonths = $this->chartMonths();

        return [
            'statistik' => $this->statistikService->dashboardGuru($guruId),
            'kelasMapel' => $kelasMapel->map(fn (KelasMapel $item) => [
                'id' => $item->id,
                'kelas' => $item->kelas?->nama_kelas ?? '-',
                'mata_pelajaran' => $item->mataPelajaran?->nama_mapel ?? '-',
                'semester' => $item->semester === '1' ? 'Ganjil' : 'Genap',
                'workspace_url' => route('guru.kelas-mapel.show', $item),
                'materi_count' => (int) $item->materi_count,
                'tugas_count' => (int) $item->tugas_count,
            ])->values(),
            'tugasBelumDikumpulkan' => $this->tugasBelumDikumpulkan($kelasMapel, $kelasMapelIds, $kelasIds),
            'siswaJarangMasuk' => $this->siswaJarangMasuk($kelasMapelIds),
            'tugasPerluDinilai' => $this->tugasPerluDinilai($kelasMapelIds),
            'kehadiranChart' => $this->kehadiranChart($kelasMapelIds, $chartMonths),
            'pengumpulanTugasChart' => $this->pengumpulanTugasChart(
                $kelasMapelIds,
                $kelasIds,
                $kelasIdByKelasMapel,
                $chartMonths
            ),
        ];
    }

    private function tugasBelumDikumpulkan(Collection $kelasMapel, Collection $kelasMapelIds, Collection $kelasIds): Collection
    {
        if ($kelasMapelIds->isEmpty()) {
            return collect();
        }

        $totalSiswaByKelas = Siswa::whereIn('kelas_id', $kelasIds)
            ->where('status', 'aktif')
            ->selectRaw('kelas_id, COUNT(*) as total')
            ->groupBy('kelas_id')
            ->pluck('total', 'kelas_id');

        $kelasMapelById = $kelasMapel->keyBy('id');
        $tugas = Tugas::with(['kelasMapel.kelas', 'kelasMapel.mataPelajaran'])
            ->whereIn('kelas_mapel_id', $kelasMapelIds)
            ->whereNotNull('batas_waktu')
            ->where('batas_waktu', '<', now())
            ->orderByDesc('batas_waktu')
            ->get();

        if ($tugas->isEmpty()) {
            return collect();
        }

        $sudahMengumpulkanByTugas = PengumpulanTugas::query()
            ->join('tugas', 'tugas.id', '=', 'pengumpulan_tugas.tugas_id')
            ->join('kelas_mapel', 'kelas_mapel.id', '=', 'tugas.kelas_mapel_id')
            ->join('siswa', 'siswa.id', '=', 'pengumpulan_tugas.siswa_id')
            ->whereIn('pengumpulan_tugas.tugas_id', $tugas->pluck('id'))
            ->whereIn('pengumpulan_tugas.status', PengumpulanTugas::STATUS_SUBMITTED)
            ->where('siswa.status', 'aktif')
            ->whereColumn('siswa.kelas_id', 'kelas_mapel.kelas_id')
            ->selectRaw('pengumpulan_tugas.tugas_id, COUNT(*) as total')
            ->groupBy('pengumpulan_tugas.tugas_id')
            ->pluck('total', 'pengumpulan_tugas.tugas_id');

        return $tugas
            ->map(function (Tugas $tugas) use ($totalSiswaByKelas, $kelasMapelById, $sudahMengumpulkanByTugas) {
                $kelasMapel = $kelasMapelById->get($tugas->kelas_mapel_id);
                $totalSiswa = (int) ($totalSiswaByKelas[$kelasMapel?->kelas_id] ?? 0);
                $sudahMengumpulkan = (int) ($sudahMengumpulkanByTugas[$tugas->id] ?? 0);
                $belum = max(0, $totalSiswa - $sudahMengumpulkan);

                if ($belum <= 0) {
                    return null;
                }

                return [
                    'id' => $tugas->id,
                    'judul' => $tugas->judul,
                    'kelas' => $tugas->kelasMapel?->kelas?->nama_kelas ?? '-',
                    'mata_pelajaran' => $tugas->kelasMapel?->mataPelajaran?->nama_mapel ?? '-',
                    'belum' => $belum,
                    'total_siswa' => $totalSiswa,
                    'batas_waktu' => $tugas->batas_waktu?->format('d M Y'),
                    'url' => $kelasMapel
                        ? route('guru.tugas.pengumpulan', [$kelasMapel, $tugas])
                        : route('guru.tugas.index'),
                ];
            })
            ->filter()
            ->sortByDesc('belum')
            ->take(5)
            ->values();
    }

    private function siswaJarangMasuk(Collection $kelasMapelIds): Collection
    {
        if ($kelasMapelIds->isEmpty()) {
            return collect();
        }

        $since = now()->subDays(60)->toDateString();

        return Absensi::query()
            ->join('siswa', 'siswa.id', '=', 'absensi.siswa_id')
            ->join('users', 'users.id', '=', 'siswa.user_id')
            ->join('kelas', 'kelas.id', '=', 'siswa.kelas_id')
            ->whereIn('absensi.kelas_mapel_id', $kelasMapelIds)
            ->where('siswa.status', 'aktif')
            ->where('absensi.tanggal', '>=', $since)
            ->select([
                'siswa.id',
                'siswa.nis',
                'users.nama_lengkap',
                'kelas.nama_kelas',
            ])
            ->selectRaw('COUNT(*) as total_absensi')
            ->selectRaw("SUM(CASE WHEN absensi.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir")
            ->selectRaw("SUM(CASE WHEN absensi.status = 'alpha' THEN 1 ELSE 0 END) as total_alpha")
            ->groupBy('siswa.id', 'siswa.nis', 'users.nama_lengkap', 'kelas.nama_kelas')
            ->havingRaw('COUNT(*) >= 3')
            ->havingRaw("(SUM(CASE WHEN absensi.status = 'hadir' THEN 1 ELSE 0 END) / COUNT(*)) < 0.75")
            ->orderByRaw("(SUM(CASE WHEN absensi.status = 'hadir' THEN 1 ELSE 0 END) / COUNT(*)) ASC")
            ->orderByDesc(DB::raw("SUM(CASE WHEN absensi.status = 'alpha' THEN 1 ELSE 0 END)"))
            ->take(5)
            ->get()
            ->map(function ($item) {
                $totalAbsensi = (int) $item->total_absensi;
                $totalHadir = (int) $item->total_hadir;

                return [
                    'id' => $item->id,
                    'nama' => $item->nama_lengkap,
                    'nis' => $item->nis,
                    'kelas' => $item->nama_kelas,
                    'persen_hadir' => $totalAbsensi > 0 ? round(($totalHadir / $totalAbsensi) * 100) : 0,
                    'total_absensi' => $totalAbsensi,
                    'total_alpha' => (int) $item->total_alpha,
                    'url' => route('guru.absensi.index', ['siswa_id' => $item->id]),
                ];
            })
            ->values();
    }

    private function tugasPerluDinilai(Collection $kelasMapelIds): Collection
    {
        if ($kelasMapelIds->isEmpty()) {
            return collect();
        }

        return PengumpulanTugas::query()
            ->join('tugas', 'tugas.id', '=', 'pengumpulan_tugas.tugas_id')
            ->join('kelas_mapel', 'kelas_mapel.id', '=', 'tugas.kelas_mapel_id')
            ->join('kelas', 'kelas.id', '=', 'kelas_mapel.kelas_id')
            ->join('mata_pelajaran', 'mata_pelajaran.id', '=', 'kelas_mapel.mapel_id')
            ->whereIn('tugas.kelas_mapel_id', $kelasMapelIds)
            ->whereIn('pengumpulan_tugas.status', ['sudah', 'terlambat'])
            ->whereNull('pengumpulan_tugas.nilai')
            ->select([
                'tugas.id',
                'tugas.kelas_mapel_id',
                'tugas.judul',
                'kelas.nama_kelas',
                'mata_pelajaran.nama_mapel',
            ])
            ->selectRaw('COUNT(*) as total')
            ->groupBy('tugas.id', 'tugas.kelas_mapel_id', 'tugas.judul', 'kelas.nama_kelas', 'mata_pelajaran.nama_mapel')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'judul' => $item->judul,
                'kelas' => $item->nama_kelas,
                'mata_pelajaran' => $item->nama_mapel,
                'total' => (int) $item->total,
                'url' => route('guru.tugas.pengumpulan', [$item->kelas_mapel_id, $item->id]),
            ])
            ->values();
    }

    private function chartMonths(): Collection
    {
        $today = now()->startOfMonth();
        $startYear = $today->month >= 7 ? $today->year : $today->year - 1;
        $start = Carbon::create($startYear, 7, 1)->startOfMonth();

        // Keep a stable academic-year axis. Future months remain zero-filled,
        // which prevents the dashboard chart shape from changing throughout
        // the year and keeps the payload predictable for the Vue page.
        return collect(range(0, 11))
            ->map(fn (int $offset) => $start->copy()->addMonths($offset));
    }

    private function kehadiranChart(Collection $kelasMapelIds, Collection $chartMonths): Collection
    {
        if ($kelasMapelIds->isEmpty() || $chartMonths->isEmpty()) {
            return collect();
        }

        $start = $chartMonths->first();
        $end = now()->endOfDay();
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', tanggal)"
            : "DATE_FORMAT(tanggal, '%Y-%m')";

        $records = Absensi::whereIn('kelas_mapel_id', $kelasMapelIds)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->select(
                DB::raw("$monthExpression as bulan"),
                DB::raw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir"),
                DB::raw("SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit"),
                DB::raw("SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin"),
                DB::raw("SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        return $chartMonths->map(function (Carbon $month) use ($records) {
            $record = $records->get($month->format('Y-m'));
            $hadir = (int) ($record->hadir ?? 0);
            $sakit = (int) ($record->sakit ?? 0);
            $izin = (int) ($record->izin ?? 0);
            $alpha = (int) ($record->alpha ?? 0);
            $total = (int) ($record->total ?? 0);

            return [
                'bulan' => $month->format('Y-m'),
                'bulan_label' => $month->translatedFormat('M Y'),
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpha' => $alpha,
                'total' => $total,
                'persen_hadir' => $total > 0 ? round(($hadir / $total) * 100, 1) : 0,
            ];
        })->values();
    }

    private function pengumpulanTugasChart(
        Collection $kelasMapelIds,
        Collection $kelasIds,
        Collection $kelasIdByKelasMapel,
        Collection $chartMonths
    ): Collection {
        if ($kelasMapelIds->isEmpty() || $chartMonths->isEmpty()) {
            return collect();
        }

        $start = $chartMonths->first();
        $end = now()->endOfDay();
        $tugas = Tugas::query()
            ->whereIn('kelas_mapel_id', $kelasMapelIds)
            ->whereNotNull('batas_waktu')
            ->whereBetween('batas_waktu', [$start, $end])
            ->get(['id', 'kelas_mapel_id', 'batas_waktu']);

        $totalSiswaByKelas = Siswa::whereIn('kelas_id', $kelasIds)
            ->where('status', 'aktif')
            ->selectRaw('kelas_id, COUNT(*) as total')
            ->groupBy('kelas_id')
            ->pluck('total', 'kelas_id');

        $collectedByTask = collect();
        if ($tugas->isNotEmpty()) {
            $collectedByTask = PengumpulanTugas::query()
                ->join('tugas', 'tugas.id', '=', 'pengumpulan_tugas.tugas_id')
                ->join('kelas_mapel', 'kelas_mapel.id', '=', 'tugas.kelas_mapel_id')
                ->join('siswa', 'siswa.id', '=', 'pengumpulan_tugas.siswa_id')
                ->whereIn('pengumpulan_tugas.tugas_id', $tugas->pluck('id'))
                ->whereIn('pengumpulan_tugas.status', PengumpulanTugas::STATUS_SUBMITTED)
                ->where('siswa.status', 'aktif')
                ->whereColumn('siswa.kelas_id', 'kelas_mapel.kelas_id')
                ->selectRaw('pengumpulan_tugas.tugas_id, COUNT(*) as total')
                ->groupBy('pengumpulan_tugas.tugas_id')
                ->pluck('total', 'pengumpulan_tugas.tugas_id');
        }

        $monthly = $tugas
            ->groupBy(fn (Tugas $item) => Carbon::parse($item->batas_waktu)->format('Y-m'))
            ->map(function ($items) use ($kelasIdByKelasMapel, $totalSiswaByKelas, $collectedByTask) {
                $total = $items->sum(function (Tugas $item) use ($kelasIdByKelasMapel, $totalSiswaByKelas) {
                    $kelasId = $kelasIdByKelasMapel[$item->kelas_mapel_id] ?? null;

                    return (int) ($totalSiswaByKelas[$kelasId] ?? 0);
                });
                $collected = $items->sum(fn (Tugas $item) => (int) ($collectedByTask[$item->id] ?? 0));

                return [
                    'collected' => $collected,
                    'total' => $total,
                ];
            });

        return $chartMonths->map(function (Carbon $month) use ($monthly) {
            $stats = $monthly->get($month->format('Y-m'), ['collected' => 0, 'total' => 0]);
            $collected = (int) $stats['collected'];
            $total = (int) $stats['total'];

            return [
                'bulan' => $month->format('Y-m'),
                'bulan_label' => $month->translatedFormat('M Y'),
                'collected' => $collected,
                'total' => $total,
                'belum' => max(0, $total - $collected),
                'persen_dikumpulkan' => $total > 0 ? round(($collected / $total) * 100, 1) : 0,
            ];
        })->values();
    }
}
