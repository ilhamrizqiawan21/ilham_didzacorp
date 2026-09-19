<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\KelasMapel;
use App\Models\NilaiAkhir;
use App\Models\Pengaturan;
use App\Models\PengumpulanTugas;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProgressController extends Controller
{
    private const SCORE_COMPONENTS = [
        'sum1' => 'Sumatif 1',
        'sum2' => 'Sumatif 2',
        'sum3' => 'Sumatif 3',
        'sum4' => 'Sumatif 4',
        'nilai_harian' => 'Nilai Harian',
        'sts' => 'STS',
        'sas' => 'SAS',
        'sat' => 'SAT',
    ];

    public function index()
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        if (!$siswa) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan.');
        }

        $siswa->loadMissing('kelas');

        $taAktif = TahunAjaran::getAktif();
        $semester = (string) Pengaturan::getValue('semester_aktif', '1');
        $batasKetuntasan = $this->masteryThreshold();

        $kelasMapel = KelasMapel::query()
            ->with('mataPelajaran:id,nama_mapel,urutan')
            ->where('kelas_id', $siswa->kelas_id)
            ->where('semester', $semester)
            ->when(
                $taAktif,
                fn ($query) => $query->where('tahun_ajaran_id', $taAktif->id),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->get()
            ->sortBy(fn (KelasMapel $item) => $item->mataPelajaran?->urutan ?? PHP_INT_MAX)
            ->values();

        $kelasMapelIds = $kelasMapel->pluck('id');

        $nilai = $kelasMapelIds->isEmpty()
            ? collect()
            : NilaiAkhir::query()
                ->where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $taAktif?->id)
                ->where('semester', $semester)
                ->whereIn('kelas_mapel_id', $kelasMapelIds)
                ->get();

        $nilaiByKelasMapel = $nilai->keyBy('kelas_mapel_id');

        $subjectScores = $kelasMapel->map(function (KelasMapel $item) use ($nilaiByKelasMapel, $batasKetuntasan) {
            $rata = $nilaiByKelasMapel->get($item->id)?->rata_akhir;
            $rata = $rata !== null ? round((float) $rata, 2) : null;

            return [
                'kelas_mapel_id' => $item->id,
                'nama_mapel' => $item->mataPelajaran?->nama_mapel ?? 'Mata Pelajaran',
                'rata' => $rata,
                'rata_label' => $rata !== null ? number_format($rata, 2) : '-',
                'status_label' => $rata === null
                    ? 'Belum ada nilai'
                    : ($rata >= $batasKetuntasan ? 'Tuntas' : 'Perlu ditingkatkan'),
                'status_tone' => $rata === null
                    ? 'secondary'
                    : ($rata >= $batasKetuntasan ? 'success' : 'warning'),
                'href' => route('siswa.kelas-mapel.show', $item),
            ];
        });

        $nilaiTersedia = $subjectScores
            ->pluck('rata')
            ->filter(fn ($value) => $value !== null)
            ->values();
        $rataNilai = $nilaiTersedia->isNotEmpty()
            ? (float) round((float) $nilaiTersedia->avg(), 2)
            : null;

        $awalBulan = now()->copy()->startOfMonth()->toDateString();
        $akhirBulan = now()->copy()->endOfMonth()->toDateString();
        $absenData = $kelasMapelIds->isEmpty()
            ? collect()
            : Absensi::query()
                ->where('siswa_id', $siswa->id)
                ->whereIn('kelas_mapel_id', $kelasMapelIds)
                ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
                ->get(['status']);

        $hadir = $absenData->where('status', 'hadir')->count();
        $sakit = $absenData->where('status', 'sakit')->count();
        $izin = $absenData->where('status', 'izin')->count();
        $alpha = $absenData->where('status', 'alpha')->count();
        $totalAbsen = $absenData->count();
        $persenHadir = $totalAbsen > 0 ? round(($hadir / $totalAbsen) * 100) : 0;

        $tugas = $kelasMapelIds->isEmpty()
            ? collect()
            : Tugas::query()
                ->with('kelasMapel.mataPelajaran:id,nama_mapel')
                ->whereIn('kelas_mapel_id', $kelasMapelIds)
                ->get(['id', 'kelas_mapel_id', 'judul', 'batas_waktu']);

        $pengumpulan = $tugas->isEmpty()
            ? collect()
            : PengumpulanTugas::query()
                ->where('siswa_id', $siswa->id)
                ->whereIn('tugas_id', $tugas->pluck('id'))
                ->get(['id', 'tugas_id', 'status', 'tanggal_kumpul']);

        $pengumpulanByTugas = $pengumpulan->keyBy('tugas_id');
        $sudahDikumpulkan = $pengumpulan
            ->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)
            ->count();
        $perluPerbaikan = $pengumpulan
            ->where('status', PengumpulanTugas::STATUS_PERLU_PERBAIKAN)
            ->count();
        $terlambat = $pengumpulan
            ->where('status', PengumpulanTugas::STATUS_TERLAMBAT)
            ->count();
        $totalTugas = $tugas->count();
        $belumDikumpulkan = max(0, $totalTugas - $sudahDikumpulkan);
        $persenPengumpulan = $totalTugas > 0
            ? round(($sudahDikumpulkan / $totalTugas) * 100)
            : 0;

        $scoreTrend = $this->buildScoreTrend($nilai);
        $trendDelta = count($scoreTrend) >= 2
            ? round($scoreTrend[array_key_last($scoreTrend)]['value'] - $scoreTrend[count($scoreTrend) - 2]['value'], 2)
            : null;

        return Inertia::render('Siswa/Progress', [
            'header' => [
                'nama' => $user->nama_lengkap,
                'kelas' => $siswa->kelas?->displayName() ?? '-',
                'tahun_ajaran' => $taAktif?->tahun,
                'semester' => $semester,
                'semester_label' => $semester === '1' ? 'Ganjil' : 'Genap',
            ],
            'stats' => [
                'rata_nilai' => $rataNilai,
                'rata_nilai_label' => $rataNilai !== null ? number_format($rataNilai, 2) : '-',
                'mapel_dinilai' => $nilaiTersedia->count(),
                'total_mapel' => $subjectScores->count(),
                'persen_hadir' => $persenHadir,
                'persen_pengumpulan' => $persenPengumpulan,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpha' => $alpha,
                'total_absen' => $totalAbsen,
                'total_tugas' => $totalTugas,
                'tugas_dikumpulkan' => $sudahDikumpulkan,
                'tugas_belum' => $belumDikumpulkan,
                'tugas_perlu_perbaikan' => $perluPerbaikan,
                'tugas_terlambat' => $terlambat,
                'bulan_label' => now()->locale('id')->translatedFormat('F'),
                'batas_ketuntasan' => $batasKetuntasan,
                'trend_delta' => $trendDelta,
                'trend_delta_label' => $trendDelta !== null
                    ? sprintf('%+.2f', $trendDelta)
                    : null,
            ],
            'subjectScores' => $subjectScores->values(),
            'focusItems' => $this->buildFocusItems(
                $tugas,
                $pengumpulanByTugas,
                $subjectScores,
                $alpha,
                $batasKetuntasan
            ),
            'scoreTrend' => $scoreTrend,
        ]);
    }

    private function masteryThreshold(): float
    {
        $configured = (float) Pengaturan::getValue('kkm_default', '75');

        return round(min(100, max(0, $configured)), 2);
    }

    private function buildScoreTrend(Collection $nilai): array
    {
        return collect(self::SCORE_COMPONENTS)
            ->map(function (string $label, string $column) use ($nilai) {
                $values = $nilai
                    ->pluck($column)
                    ->filter(fn ($value) => $value !== null)
                    ->map(fn ($value) => (float) $value)
                    ->values();

                if ($values->isEmpty()) {
                    return null;
                }

                $average = round((float) $values->avg(), 2);

                return [
                    'key' => $column,
                    'label' => $label,
                    'value' => $average,
                    'value_label' => number_format($average, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildFocusItems(
        Collection $tugas,
        Collection $pengumpulanByTugas,
        Collection $subjectScores,
        int $alpha,
        float $batasKetuntasan
    ): array {
        $items = collect();

        $belumDikumpulkan = $tugas
            ->filter(function (Tugas $tugas) use ($pengumpulanByTugas) {
                $status = $pengumpulanByTugas->get($tugas->id)?->status;

                return !in_array($status, PengumpulanTugas::STATUS_SUBMITTED, true);
            })
            ->sortBy(fn (Tugas $tugas) => $tugas->batas_waktu?->timestamp ?? PHP_INT_MAX)
            ->take(3);

        foreach ($belumDikumpulkan as $tugasBelum) {
            $lewatBatas = $tugasBelum->batas_waktu?->isPast() ?? false;
            $mapel = $tugasBelum->kelasMapel?->mataPelajaran?->nama_mapel;
            $deadline = $tugasBelum->batas_waktu
                ? $tugasBelum->batas_waktu->copy()->locale('id')->translatedFormat('d M Y H:i')
                : 'tanpa batas waktu';

            $items->push([
                'type' => 'task',
                'tone' => $lewatBatas ? 'danger' : 'warning',
                'icon' => $lewatBatas ? 'bi-exclamation-octagon-fill' : 'bi-hourglass-split',
                'title' => $lewatBatas ? 'Tugas melewati batas waktu' : 'Tugas belum dikumpulkan',
                'description' => trim(($mapel ? $mapel.' · ' : '').$tugasBelum->judul.' · '.$deadline),
                'href' => route('siswa.tugas.show', $tugasBelum),
                'action_label' => 'Buka tugas',
            ]);
        }

        $perluPerbaikan = $pengumpulanByTugas
            ->filter(fn (PengumpulanTugas $item) => $item->status === PengumpulanTugas::STATUS_PERLU_PERBAIKAN)
            ->keys();

        foreach ($tugas->whereIn('id', $perluPerbaikan)->take(2) as $tugasPerbaikan) {
            $mapel = $tugasPerbaikan->kelasMapel?->mataPelajaran?->nama_mapel;

            $items->push([
                'type' => 'revision',
                'tone' => 'warning',
                'icon' => 'bi-arrow-repeat',
                'title' => 'Tugas perlu diperbaiki',
                'description' => trim(($mapel ? $mapel.' · ' : '').$tugasPerbaikan->judul),
                'href' => route('siswa.tugas.show', $tugasPerbaikan),
                'action_label' => 'Perbaiki tugas',
            ]);
        }

        $nilaiRendah = $subjectScores
            ->filter(fn (array $item) => $item['rata'] !== null && $item['rata'] < $batasKetuntasan)
            ->sortBy('rata')
            ->take(2);

        foreach ($nilaiRendah as $subject) {
            $items->push([
                'type' => 'score',
                'tone' => 'warning',
                'icon' => 'bi-graph-up-arrow',
                'title' => $subject['nama_mapel'].' perlu perhatian',
                'description' => 'Rata-rata '.$subject['rata_label'].' · batas ketuntasan '.number_format($batasKetuntasan, 2),
                'href' => $subject['href'],
                'action_label' => 'Buka kelas',
            ]);
        }

        if ($alpha > 0) {
            $items->push([
                'type' => 'attendance',
                'tone' => 'danger',
                'icon' => 'bi-calendar-x-fill',
                'title' => 'Kehadiran perlu diperhatikan',
                'description' => $alpha.' kali alpha pada bulan ini.',
                'href' => null,
                'action_label' => null,
            ]);
        }

        return $items->take(5)->values()->all();
    }
}
