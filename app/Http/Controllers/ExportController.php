<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\NilaiAkhir;
use App\Models\SikapSosial;
use App\Models\SikapSpiritual;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\Pengaturan;
use App\Models\PengumpulanTugas;
use App\Services\Reports\Exports\AbsensiExportService;
use App\Services\Reports\Exports\NilaiExportService;
use App\Services\Reports\Exports\TugasExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;

class ExportController extends Controller
{
    // ─────────────────────────────────────────────
    // EXPORT EXCEL - REKAP NILAI
    // ─────────────────────────────────────────────
    public function excelNilai(Request $request, NilaiExportService $exportService)
    {
        $filters = $this->validatedExportFilters($request);
        [$path, $filename] = $exportService->export($filters['kelas_id'], $filters['semester']);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────
    // EXPORT EXCEL - REKAP ABSENSI
    // ─────────────────────────────────────────────
    public function excelAbsensi(Request $request, AbsensiExportService $exportService)
    {
        $filters = $this->validatedExportFilters($request, withMonth: true);
        [$path, $filename] = $exportService->export($filters['kelas_id'], $filters['semester'], $filters['bulan']);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────
    // EXPORT EXCEL - REKAP TUGAS
    // ─────────────────────────────────────────────
    public function excelTugas(Request $request, TugasExportService $exportService)
    {
        $filters = $this->validatedExportFilters($request);
        [$path, $filename] = $exportService->export($filters['kelas_id'], $filters['semester']);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────
    // EXPORT PDF - REKAP NILAI
    // ─────────────────────────────────────────────
    public function pdfNilai(Request $request)
    {
        $filters = $this->validatedExportFilters($request);
        $kelasId = $filters['kelas_id'];
        $semester = $filters['semester'];
        $taAktif = TahunAjaran::getAktif();

        $kelas = Kelas::findOrFail($kelasId);
        $siswaList = Siswa::with('user')->where('kelas_id', $kelasId)->where('status', 'aktif')->orderBy('nis')->get();
        $mapelList = $this->mapelListUntukKelas($kelasId, $taAktif?->id, $semester);

        $nilaiData = NilaiAkhir::whereIn('siswa_id', $siswaList->pluck('id'))
            ->where('tahun_ajaran_id', $taAktif?->id)->where('semester', $semester)
            ->get()->groupBy('siswa_id');

        $rekap = [];
        foreach ($siswaList as $s) {
            $sn = $nilaiData->get($s->id, collect());
            $row = ['nis' => $s->nis, 'nama' => $s->user->nama_lengkap ?? '-', 'nilai' => []];
            foreach ($mapelList as $mp) {
                $n = $sn->firstWhere('kelas_mapel_id', $mp->kelas_mapel_id);
                $row['nilai'][$mp->id] = $n ? $n->rata_akhir : null;
            }
            $validNilai = array_filter($row['nilai'], fn($v) => !is_null($v));
            $row['rata'] = count($validNilai) > 0 ? round(array_sum($validNilai) / count($validNilai), 2) : null;
            $rekap[] = $row;
        }

        $labelSemester = $semester == '1' ? 'Ganjil' : 'Genap';
        $reportSchool = $this->reportSchool($taAktif, $semester);
        $pdf = Pdf::loadView('exports.pdf.nilai', compact('rekap', 'mapelList', 'kelas', 'labelSemester', 'taAktif', 'reportSchool'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("rekap_nilai_{$kelas->tingkat}_{$kelas->nama_kelas}_semester_{$semester}.pdf");
    }

    // ─────────────────────────────────────────────
    // EXPORT PDF - REKAP ABSENSI
    // ─────────────────────────────────────────────
    public function pdfAbsensi(Request $request)
    {
        $filters = $this->validatedExportFilters($request, withMonth: true);
        $kelasId = $filters['kelas_id'];
        $bulan = $filters['bulan'];
        $semester = $filters['semester'];
        $taAktif = TahunAjaran::getAktif();

        $kelas = Kelas::findOrFail($kelasId);
        $siswaList = Siswa::with('user')->where('kelas_id', $kelasId)->where('status', 'aktif')->orderBy('nis')->get();

        $tanggalList = Absensi::whereHas('kelasMapel', fn($q) => $q
                ->where('kelas_id', $kelasId)
                ->where('tahun_ajaran_id', $taAktif?->id)
                ->where('semester', $semester))
            ->whereBetween('tanggal', ["{$bulan}-01", date('Y-m-t', strtotime("{$bulan}-01"))])
            ->orderBy('tanggal')->pluck('tanggal')->unique()->map(fn($d) => $d->format('Y-m-d'))->values();

        $absensiData = Absensi::whereIn('siswa_id', $siswaList->pluck('id'))
            ->whereHas('kelasMapel', fn($q) => $q
                ->where('kelas_id', $kelasId)
                ->where('tahun_ajaran_id', $taAktif?->id)
                ->where('semester', $semester))
            ->whereBetween('tanggal', ["{$bulan}-01", date('Y-m-t', strtotime("{$bulan}-01"))])
            ->get()->groupBy('siswa_id');

        $rekap = [];
        foreach ($siswaList as $s) {
            $sa = $absensiData->get($s->id, collect());
            $row = ['nis' => $s->nis, 'nama' => $s->user->nama_lengkap ?? '-', 'absensi' => [], 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];
            foreach ($tanggalList as $tgl) {
                $ab = $sa->firstWhere('tanggal', $tgl);
                $st = $ab ? $ab->status : null;
                $row['absensi'][$tgl] = $st;
                if ($st) $row[$st]++;
            }
            $rekap[] = $row;
        }

        $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $namaBulan = $bulanIndo[(int) substr($bulan, 5, 2)] . ' ' . substr($bulan, 0, 4);

        $labelSemester = $semester == '1' ? 'Ganjil' : 'Genap';
        $reportSchool = $this->reportSchool($taAktif, $semester);
        $pdf = Pdf::loadView('exports.pdf.absensi', compact('rekap', 'tanggalList', 'kelas', 'namaBulan', 'labelSemester', 'taAktif', 'reportSchool'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("rekap_absensi_{$kelas->tingkat}_{$kelas->nama_kelas}_{$bulan}.pdf");
    }

    // ─────────────────────────────────────────────
    // EXPORT PDF - REKAP TUGAS
    // ─────────────────────────────────────────────
    public function pdfTugas(Request $request)
    {
        $filters = $this->validatedExportFilters($request);
        $kelasId = $filters['kelas_id'];
        $semester = $filters['semester'];
        $taAktif = TahunAjaran::getAktif();

        $kelas = Kelas::findOrFail($kelasId);
        $totalSiswa = Siswa::where('kelas_id', $kelasId)->where('status', 'aktif')->count();

        $tugasList = Tugas::with(['kelasMapel.mataPelajaran', 'kelasMapel.guru'])
            ->whereHas('kelasMapel', fn($q) => $q->where('kelas_id', $kelasId)->where('tahun_ajaran_id', $taAktif?->id)->where('semester', $semester))
            ->withCount(['pengumpulan as sudah_kumpul' => fn($q) => $q
                ->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)
                ->whereHas('siswa', fn($siswa) => $siswa->where('kelas_id', $kelasId)->where('status', 'aktif'))])
            ->orderBy('created_at', 'desc')
            ->get();

        $labelSemester = $semester == '1' ? 'Ganjil' : 'Genap';
        $reportSchool = $this->reportSchool($taAktif, $semester);
        $pdf = Pdf::loadView('exports.pdf.tugas', compact('tugasList', 'kelas', 'labelSemester', 'taAktif', 'totalSiswa', 'reportSchool'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("rekap_tugas_{$kelas->tingkat}_{$kelas->nama_kelas}_semester_{$semester}.pdf");
    }

    public function excelSikap(Request $request)
    {
        $filters = $this->validatedExportFilters($request);
        $dataset = $this->rekapSikapKelasDataset($filters['kelas_id'], $filters['semester']);

        return $this->tableExcel('rekap_sikap_' . $dataset['slug'] . '.xlsx', 'REKAP SIKAP', $dataset['context'], $dataset['headers'], $dataset['rows'], $dataset['taAktif'], $filters['semester']);
    }

    public function pdfSikap(Request $request)
    {
        $filters = $this->validatedExportFilters($request);
        $dataset = $this->rekapSikapKelasDataset($filters['kelas_id'], $filters['semester']);

        return $this->tablePdf('rekap_sikap_' . $dataset['slug'] . '.pdf', 'REKAP SIKAP', $dataset['context'], $dataset['headers'], $dataset['rows'], $dataset['taAktif'], $filters['semester']);
    }

    public function guruNilaiExcel(Request $request, KelasMapel $kelasMapel)
    {
        $dataset = $this->guruNilaiDataset($kelasMapel);
        return $this->tableExcel('nilai_' . $dataset['slug'] . '.xlsx', 'DAFTAR NILAI', $dataset['context'], $dataset['headers'], $dataset['rows'], $dataset['taAktif'], $dataset['semester']);
    }

    public function guruNilaiPdf(Request $request, KelasMapel $kelasMapel)
    {
        $dataset = $this->guruNilaiDataset($kelasMapel);
        return $this->tablePdf(
            'nilai_' . $dataset['slug'] . '.pdf',
            'DAFTAR NILAI',
            $dataset['context'],
            $dataset['headers'],
            $dataset['rows'],
            $dataset['taAktif'],
            $dataset['semester'],
            $this->teacherSigner($request)
        );
    }

    public function guruAbsensiExcel(Request $request, KelasMapel $kelasMapel)
    {
        $dataset = $this->guruAbsensiDataset($request, $kelasMapel);
        return $this->tableExcel('absensi_' . $dataset['slug'] . '.xlsx', 'DAFTAR ABSENSI', $dataset['context'], $dataset['headers'], $dataset['rows']);
    }

    public function guruAbsensiPdf(Request $request, KelasMapel $kelasMapel)
    {
        $dataset = $this->guruAbsensiDataset($request, $kelasMapel);
        return $this->tablePdf('absensi_' . $dataset['slug'] . '.pdf', 'DAFTAR ABSENSI', $dataset['context'], $dataset['headers'], $dataset['rows'], null, null, $this->teacherSigner($request));
    }

    public function guruRekapAbsensiExcel(Request $request)
    {
        $dataset = $this->guruRekapAbsensiDataset($request);
        return $this->tableExcel('rekap_absensi_' . $dataset['slug'] . '.xlsx', 'REKAP ABSENSI', $dataset['context'], $dataset['headers'], $dataset['rows']);
    }

    public function guruRekapAbsensiPdf(Request $request)
    {
        $dataset = $this->guruRekapAbsensiDataset($request);
        return $this->tablePdf('rekap_absensi_' . $dataset['slug'] . '.pdf', 'REKAP ABSENSI', $dataset['context'], $dataset['headers'], $dataset['rows'], null, null, $this->teacherSigner($request));
    }

    public function guruSikapExcel(Request $request, KelasMapel $kelasMapel)
    {
        $dataset = $this->guruSikapDataset($kelasMapel);
        return $this->tableExcel('sikap_' . $dataset['slug'] . '.xlsx', 'DAFTAR SIKAP', $dataset['context'], $dataset['headers'], $dataset['rows'], $dataset['taAktif'], $dataset['semester']);
    }

    public function guruSikapPdf(Request $request, KelasMapel $kelasMapel)
    {
        $dataset = $this->guruSikapDataset($kelasMapel);
        return $this->tablePdf('sikap_' . $dataset['slug'] . '.pdf', 'DAFTAR SIKAP', $dataset['context'], $dataset['headers'], $dataset['rows'], $dataset['taAktif'], $dataset['semester'], $this->teacherSigner($request));
    }

    public function guruTugasExcel(Request $request, KelasMapel $kelasMapel)
    {
        $dataset = $this->guruTugasDataset($kelasMapel);
        return $this->tableExcel('tugas_' . $dataset['slug'] . '.xlsx', 'DAFTAR TUGAS', $dataset['context'], $dataset['headers'], $dataset['rows']);
    }

    public function guruTugasPdf(Request $request, KelasMapel $kelasMapel)
    {
        $dataset = $this->guruTugasDataset($kelasMapel);
        return $this->tablePdf('tugas_' . $dataset['slug'] . '.pdf', 'DAFTAR TUGAS', $dataset['context'], $dataset['headers'], $dataset['rows'], null, null, $this->teacherSigner($request));
    }

    public function guruPengumpulanTugasExcel(Request $request, KelasMapel $kelasMapel, Tugas $tugas)
    {
        $dataset = $this->guruPengumpulanTugasDataset($kelasMapel, $tugas);
        return $this->tableExcel('pengumpulan_' . $dataset['slug'] . '.xlsx', 'PENGUMPULAN TUGAS', $dataset['context'], $dataset['headers'], $dataset['rows']);
    }

    public function guruPengumpulanTugasPdf(Request $request, KelasMapel $kelasMapel, Tugas $tugas)
    {
        $dataset = $this->guruPengumpulanTugasDataset($kelasMapel, $tugas);
        return $this->tablePdf('pengumpulan_' . $dataset['slug'] . '.pdf', 'PENGUMPULAN TUGAS', $dataset['context'], $dataset['headers'], $dataset['rows'], null, null, $this->teacherSigner($request));
    }

    private function mapelListUntukKelas(int|string|null $kelasId, int|string|null $tahunAjaranId, string $semester)
    {
        return KelasMapel::with('mataPelajaran')
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('semester', $semester)
            ->join('mata_pelajaran', 'mata_pelajaran.id', '=', 'kelas_mapel.mapel_id')
            ->orderBy('mata_pelajaran.urutan')
            ->orderBy('mata_pelajaran.nama_mapel')
            ->select('kelas_mapel.*')
            ->get()
            ->map(function ($kelasMapel) {
                return (object) [
                    'id' => $kelasMapel->mapel_id,
                    'kelas_mapel_id' => $kelasMapel->id,
                    'nama_mapel' => $kelasMapel->mataPelajaran?->nama_mapel ?? '-',
                ];
            });
    }

    private function validatedExportFilters(Request $request, bool $withMonth = false): array
    {
        $rules = [
            'kelas_id' => 'required|integer|exists:kelas,id',
            'semester' => 'nullable|in:1,2',
        ];

        if ($withMonth) {
            $rules['bulan'] = 'nullable|date_format:Y-m';
        }

        $validated = $request->validate($rules);

        return [
            'kelas_id' => (int) $validated['kelas_id'],
            'semester' => $validated['semester'] ?? Pengaturan::getValue('semester_aktif', '1'),
            'bulan' => $validated['bulan'] ?? date('Y-m'),
        ];
    }

    private function rekapSikapKelasDataset(int $kelasId, string $semester): array
    {
        $taAktif = TahunAjaran::getAktif();
        $kelas = Kelas::findOrFail($kelasId);
        $siswaList = Siswa::with('user')->where('kelas_id', $kelasId)->where('status', 'aktif')->orderBy('nis')->get();
        $kelasMapelIds = KelasMapel::where('kelas_id', $kelasId)
            ->where('tahun_ajaran_id', $taAktif?->id)
            ->where('semester', $semester)
            ->pluck('id');

        $labelNilai = [1 => 'TB', 2 => 'KB', 3 => 'C', 4 => 'B', 5 => 'SB'];
        $spFields = ['taqwa', 'kejujuran', 'disiplin', 'sabar', 'syukur', 'tawadhu'];
        $soFields = ['empati', 'kerjasama', 'toleransi', 'percaya_diri', 'komunikasi'];

        $spData = SikapSpiritual::whereIn('siswa_id', $siswaList->pluck('id'))
            ->where('tahun_ajaran_id', $taAktif?->id)
            ->where('semester', $semester)
            ->whereIn('kelas_mapel_id', $kelasMapelIds)
            ->get()
            ->groupBy('siswa_id');

        $soData = SikapSosial::whereIn('siswa_id', $siswaList->pluck('id'))
            ->where('tahun_ajaran_id', $taAktif?->id)
            ->where('semester', $semester)
            ->whereIn('kelas_mapel_id', $kelasMapelIds)
            ->get()
            ->groupBy('siswa_id');

        $rows = $siswaList->values()->map(function (Siswa $siswa, int $index) use ($spFields, $soFields, $spData, $soData, $labelNilai) {
            $sp = $spData->get($siswa->id, collect());
            $so = $soData->get($siswa->id, collect());

            return array_merge([
                $index + 1,
                $siswa->nis,
                $siswa->user?->nama_lengkap ?? '-',
            ], $this->labeledAverages($sp, $spFields, $labelNilai), $this->labeledAverages($so, $soFields, $labelNilai));
        })->all();

        return [
            'headers' => ['No', 'NIS', 'Nama', 'Taqwa', 'Jujur', 'Disiplin', 'Sabar', 'Syukur', 'Tawadhu', 'Empati', 'Kerja Sama', 'Toleransi', 'Percaya Diri', 'Komunikasi'],
            'rows' => $rows,
            'context' => "Kelas {$kelas->tingkat} {$kelas->nama_kelas}",
            'slug' => $this->slug("{$kelas->tingkat}_{$kelas->nama_kelas}_semester_{$semester}"),
            'taAktif' => $taAktif,
        ];
    }

    private function guruNilaiDataset(KelasMapel $kelasMapel): array
    {
        $taAktif = TahunAjaran::getAktif();
        $semester = Pengaturan::getValue('semester_aktif', '1');
        $fields = ['sum1', 'sum2', 'sum3', 'sum4', 'nilai_harian', 'sts', 'sas', 'sat'];
        $nilaiList = NilaiAkhir::where('kelas_mapel_id', $kelasMapel->id)
            ->where('tahun_ajaran_id', $taAktif?->id)
            ->where('semester', $semester)
            ->get()
            ->keyBy('siswa_id');

        $rows = Siswa::with('user')->where('kelas_id', $kelasMapel->kelas_id)->where('status', 'aktif')->orderBy('nis')->get()
            ->values()
            ->map(function (Siswa $siswa, int $index) use ($nilaiList, $fields) {
                $nilai = $nilaiList->get($siswa->id);
                return array_merge([$index + 1, $siswa->nis, $siswa->user?->nama_lengkap ?? '-'], collect($fields)->map(fn($field) => $nilai?->{$field})->all(), [$nilai?->rata_akhir]);
            })->all();

        return $this->kelasMapelDatasetBase($kelasMapel, $taAktif, $semester) + [
            'headers' => ['No', 'NIS', 'Nama', 'SUM1', 'SUM2', 'SUM3', 'SUM4', 'Nilai Harian', 'STS', 'SAS', 'SAT', 'Rata Akhir'],
            'rows' => $rows,
        ];
    }

    private function guruAbsensiDataset(Request $request, KelasMapel $kelasMapel): array
    {
        $request->validate(['bulan' => 'nullable|date_format:Y-m']);

        $bulan = $request->input('bulan', date('Y-m'));
        $meetings = $this->attendanceMeetings($bulan, (int) $kelasMapel->pertemuan_per_minggu);
        $students = Siswa::with('user')->where('kelas_id', $kelasMapel->kelas_id)->where('status', 'aktif')->orderBy('nis')->get();
        $absensiRaw = Absensi::where('kelas_mapel_id', $kelasMapel->id)
            ->whereIn('siswa_id', $students->pluck('id'))
            ->whereBetween('tanggal', ["{$bulan}-01", date('Y-m-t', strtotime("{$bulan}-01"))])
            ->get()
            ->groupBy('siswa_id')
            ->map(fn($records) => $records->keyBy(fn(Absensi $absensi) => $absensi->tanggal?->format('Y-m-d')));

        $rows = $students->values()->map(function (Siswa $siswa, int $index) use ($meetings, $absensiRaw) {
            $row = [$index + 1, $siswa->nis, $siswa->user?->nama_lengkap ?? '-'];
            $counts = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];
            $studentAbsensi = $absensiRaw->get($siswa->id, collect());

            foreach ($meetings as $meeting) {
                $status = $studentAbsensi->get($meeting['date'])?->status;
                $row[] = ['hadir' => 'H', 'sakit' => 'S', 'izin' => 'I', 'alpha' => 'A'][$status] ?? '-';
                if ($status && isset($counts[$status])) {
                    $counts[$status]++;
                }
            }

            return array_merge($row, array_values($counts));
        })->all();

        return [
            'headers' => array_merge(['No', 'NIS', 'Nama'], $meetings->map(fn($meeting) => $meeting['title'] . ' ' . $meeting['label'])->all(), ['H', 'S', 'I', 'A']),
            'rows' => $rows,
            'context' => $this->kelasMapelContext($kelasMapel) . " - {$bulan}",
            'slug' => $this->slug($this->kelasMapelContext($kelasMapel) . "_{$bulan}"),
        ];
    }

    private function guruRekapAbsensiDataset(Request $request): array
    {
        $validated = $request->validate([
            'kelas_mapel_id' => 'required|integer|exists:kelas_mapel,id',
            'mode' => 'nullable|in:bulanan,keseluruhan',
            'bulan' => 'nullable|date_format:Y-m',
        ]);
        $mode = $validated['mode'] ?? 'bulanan';
        $bulan = $validated['bulan'] ?? date('Y-m');
        $kelasMapel = KelasMapel::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', $request->user()?->id)
            ->aktif()
            ->findOrFail((int) $validated['kelas_mapel_id']);
        $students = Siswa::with('user')
            ->where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get();
        $query = Absensi::where('kelas_mapel_id', $kelasMapel->id)
            ->whereIn('siswa_id', $students->pluck('id'));

        if ($mode === 'bulanan') {
            $query->whereBetween('tanggal', ["{$bulan}-01", date('Y-m-t', strtotime("{$bulan}-01"))]);
        }

        $absensiBySiswa = $query->get()->groupBy('siswa_id');
        $rows = $students->values()->map(function (Siswa $siswa, int $index) use ($absensiBySiswa) {
            $records = $absensiBySiswa->get($siswa->id, collect());
            $hadir = $records->where('status', 'hadir')->count();
            $sakit = $records->where('status', 'sakit')->count();
            $izin = $records->where('status', 'izin')->count();
            $alpha = $records->where('status', 'alpha')->count();
            $total = $hadir + $sakit + $izin + $alpha;

            return [
                $index + 1,
                $siswa->nis,
                $siswa->user?->nama_lengkap ?? '-',
                $hadir,
                $sakit,
                $izin,
                $alpha,
                $total,
                $total > 0 ? round(($hadir / $total) * 100, 2) . '%' : '0%',
            ];
        })->all();
        $period = $mode === 'bulanan' ? "Bulan {$bulan}" : 'Keseluruhan';

        return [
            'headers' => ['No', 'NIS', 'Nama', 'Hadir', 'Sakit', 'Izin', 'Alpha', 'Total', 'Persen Hadir'],
            'rows' => $rows,
            'context' => $this->kelasMapelContext($kelasMapel) . " - {$period}",
            'slug' => $this->slug($this->kelasMapelContext($kelasMapel) . "_{$mode}_{$bulan}"),
        ];
    }

    private function guruSikapDataset(KelasMapel $kelasMapel): array
    {
        $taAktif = TahunAjaran::getAktif();
        $semester = Pengaturan::getValue('semester_aktif', '1');
        $spFields = ['taqwa', 'kejujuran', 'disiplin', 'sabar', 'syukur', 'tawadhu'];
        $soFields = ['empati', 'kerjasama', 'toleransi', 'percaya_diri', 'komunikasi'];
        $spiritual = SikapSpiritual::where('kelas_mapel_id', $kelasMapel->id)->where('tahun_ajaran_id', $taAktif?->id)->where('semester', $semester)->get()->keyBy('siswa_id');
        $sosial = SikapSosial::where('kelas_mapel_id', $kelasMapel->id)->where('tahun_ajaran_id', $taAktif?->id)->where('semester', $semester)->get()->keyBy('siswa_id');

        $rows = Siswa::with('user')->where('kelas_id', $kelasMapel->kelas_id)->where('status', 'aktif')->orderBy('nis')->get()
            ->values()
            ->map(function (Siswa $siswa, int $index) use ($spFields, $soFields, $spiritual, $sosial) {
                $sp = $spiritual->get($siswa->id);
                $so = $sosial->get($siswa->id);
                return array_merge(
                    [$index + 1, $siswa->nis, $siswa->user?->nama_lengkap ?? '-'],
                    collect($spFields)->map(fn($field) => $sp?->{$field})->all(),
                    collect($soFields)->map(fn($field) => $so?->{$field})->all()
                );
            })->all();

        return $this->kelasMapelDatasetBase($kelasMapel, $taAktif, $semester) + [
            'headers' => ['No', 'NIS', 'Nama', 'Taqwa', 'Jujur', 'Disiplin', 'Sabar', 'Syukur', 'Tawadhu', 'Empati', 'Kerja Sama', 'Toleransi', 'Percaya Diri', 'Komunikasi'],
            'rows' => $rows,
        ];
    }

    private function guruTugasDataset(KelasMapel $kelasMapel): array
    {
        $totalSiswa = Siswa::where('kelas_id', $kelasMapel->kelas_id)->where('status', 'aktif')->count();
        $rows = Tugas::where('kelas_mapel_id', $kelasMapel->id)
            ->withCount(['pengumpulan as sudah_mengumpulkan' => fn($q) => $q
                ->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)
                ->whereHas('siswa', fn($siswa) => $siswa->where('kelas_id', $kelasMapel->kelas_id)->where('status', 'aktif'))])
            ->orderBy('created_at', 'desc')
            ->get()
            ->values()
            ->map(fn(Tugas $tugas, int $index) => [
                $index + 1,
                $tugas->judul,
                $tugas->batas_waktu?->format('d/m/Y') ?? '-',
                $tugas->sudah_mengumpulkan ?? 0,
                $totalSiswa,
                $totalSiswa > 0 ? round((($tugas->sudah_mengumpulkan ?? 0) / $totalSiswa) * 100) . '%' : '-',
            ])->all();

        return [
            'headers' => ['No', 'Judul', 'Deadline', 'Sudah Mengumpulkan', 'Total Siswa', 'Persen'],
            'rows' => $rows,
            'context' => $this->kelasMapelContext($kelasMapel),
            'slug' => $this->slug($this->kelasMapelContext($kelasMapel)),
        ];
    }

    private function guruPengumpulanTugasDataset(KelasMapel $kelasMapel, Tugas $tugas): array
    {
        abort_unless((int) $tugas->kelas_mapel_id === (int) $kelasMapel->id, 403);

        $pengumpulan = PengumpulanTugas::with(['siswa.user', 'files'])
            ->where('tugas_id', $tugas->id)
            ->get()
            ->keyBy('siswa_id');

        $rows = Siswa::with('user')
            ->where('kelas_id', $kelasMapel->kelas_id)
            ->where('status', 'aktif')
            ->orderBy('nis')
            ->get()
            ->values()
            ->map(function (Siswa $siswa, int $index) use ($pengumpulan) {
                $item = $pengumpulan->get($siswa->id);

                return [
                $index + 1,
                $siswa->user?->nama_lengkap ?? $siswa->nis,
                ucfirst(str_replace('_', ' ', (string) ($item?->status ?? 'belum'))),
                $item?->tanggal_kumpul?->format('d/m/Y H:i') ?? '-',
                $item?->nilai ?? '-',
                $item?->catatan ?? '-',
                ($item?->files->count() ?? 0) + ($item?->file_upload ? 1 : 0),
                ];
            })->all();

        return [
            'headers' => ['No', 'Siswa', 'Status', 'Tanggal Kumpul', 'Nilai', 'Catatan', 'Jumlah File'],
            'rows' => $rows,
            'context' => $this->kelasMapelContext($kelasMapel) . ' - ' . $tugas->judul,
            'slug' => $this->slug($this->kelasMapelContext($kelasMapel) . '_' . $tugas->judul),
        ];
    }

    private function tableExcel(string $filename, string $title, string $context, array $headers, array $rows, ?TahunAjaran $tahunAjaran = null, ?string $semester = null)
    {
        $writer = new Writer();
        $filePath = $this->temporaryExcelPath('export_');
        $writer->openToFile($filePath);
        $this->prepareWorksheet($writer, count($headers));

        $this->writeExcelReportHeader($writer, $title, $this->reportSchool($tahunAjaran ?? TahunAjaran::getAktif(), $semester ?? Pengaturan::getValue('semester_aktif', '1')), $context);

        $this->writeExcelTableHeader($writer, $headers);
        foreach ($rows as $index => $row) {
            $this->writeExcelDataRow($writer, $row, $index);
        }
        $writer->close();

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }

    private function tablePdf(string $filename, string $title, string $context, array $headers, array $rows, ?TahunAjaran $tahunAjaran = null, ?string $semester = null, ?array $signer = null)
    {
        $reportSchool = $this->reportSchool($tahunAjaran ?? TahunAjaran::getAktif(), $semester ?? Pengaturan::getValue('semester_aktif', '1'));
        $signer ??= $this->principalSigner($reportSchool);
        $pdf = Pdf::loadView('exports.pdf.table', compact('title', 'context', 'headers', 'rows', 'reportSchool', 'signer'));
        $pdf->setPaper('a4', count($headers) > 8 ? 'landscape' : 'portrait');

        return $pdf->download($filename);
    }

    private function teacherSigner(Request $request): array
    {
        $user = $request->user();

        return [
            'role' => 'Guru Mata Pelajaran',
            'name' => $user?->nama_lengkap ?? $user?->username ?? '-',
            'id_label' => filled($user?->nip_nis) ? 'NIP' : null,
            'id_value' => $user?->nip_nis,
        ];
    }

    private function principalSigner(array $reportSchool): array
    {
        return [
            'role' => 'Kepala Sekolah',
            'name' => $reportSchool['principal_name'] ?? '-',
            'id_label' => ($reportSchool['principal_nip'] ?? null) ? 'NIP' : (($reportSchool['principal_nuptk'] ?? null) ? 'NUPTK' : null),
            'id_value' => $reportSchool['principal_nip'] ?: ($reportSchool['principal_nuptk'] ?? null),
        ];
    }

    private function kelasMapelDatasetBase(KelasMapel $kelasMapel, ?TahunAjaran $tahunAjaran, string $semester): array
    {
        return [
            'context' => $this->kelasMapelContext($kelasMapel),
            'slug' => $this->slug($this->kelasMapelContext($kelasMapel) . "_semester_{$semester}"),
            'taAktif' => $tahunAjaran,
            'semester' => $semester,
        ];
    }

    private function kelasMapelContext(KelasMapel $kelasMapel): string
    {
        $kelasMapel->loadMissing(['kelas', 'mataPelajaran']);

        return trim(($kelasMapel->kelas?->nama_kelas ?? '-') . ' - ' . ($kelasMapel->mataPelajaran?->nama_mapel ?? '-'));
    }

    private function labeledAverages($records, array $fields, array $labels): array
    {
        return collect($fields)
            ->map(fn($field) => $records->isNotEmpty() ? ($labels[(int) round($records->avg($field))] ?? '-') : '-')
            ->all();
    }

    private function monthMondays(string $bulan): array
    {
        $monthNumber = (int) substr($bulan, 5, 2);
        $firstDay = \Carbon\Carbon::create((int) substr($bulan, 0, 4), $monthNumber, 1);
        $seninPertama = $firstDay->copy();

        if ($firstDay->dayOfWeek !== 1) {
            $seninPertama->addDays((8 - $firstDay->dayOfWeek) % 7);
        }

        $dates = [];
        for ($week = 1; $week <= 5; $week++) {
            $date = $seninPertama->copy()->addDays(($week - 1) * 7);
            if ((int) $date->format('m') === $monthNumber) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }

    private function attendanceMeetings(string $bulan, int $meetingsPerWeek): \Illuminate\Support\Collection
    {
        $meetingsPerWeek = max(1, min($meetingsPerWeek, 6));
        $monthNumber = (int) substr($bulan, 5, 2);
        $firstDay = \Carbon\Carbon::create((int) substr($bulan, 0, 4), $monthNumber, 1);
        $firstMonday = $firstDay->copy();

        if ($firstDay->dayOfWeek !== 1) {
            $firstMonday->addDays((8 - $firstDay->dayOfWeek) % 7);
        }

        $meetings = [];

        for ($week = 1; $week <= 5; $week++) {
            $weekStart = $firstMonday->copy()->addDays(($week - 1) * 7);

            for ($meeting = 1; $meeting <= $meetingsPerWeek; $meeting++) {
                $offset = (int) round((($meeting - 1) * 6) / $meetingsPerWeek);
                $date = $weekStart->copy()->addDays($offset);

                if ((int) $date->format('m') !== $monthNumber) {
                    continue;
                }

                $meetings[] = [
                    'date' => $date->format('Y-m-d'),
                    'label' => $date->format('d/m'),
                    'title' => $meetingsPerWeek > 1 ? "M{$week} P{$meeting}" : "M{$week}",
                ];
            }
        }

        return collect($meetings);
    }

    private function slug(string $value): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($value))) ?: 'export';
    }

    private function reportSchool(?TahunAjaran $tahunAjaran, string $semester): array
    {
        $labelSemester = $semester === '1' ? 'Ganjil' : ($semester === '2' ? 'Genap' : $semester);
        $logoPath = school_setting('logo_path');

        return [
            'name' => school_setting('school_name', 'Nama Sekolah'),
            'short_name' => school_setting('school_short_name', 'LMS'),
            'address' => school_setting('address', 'Alamat sekolah belum diatur'),
            'phone' => school_setting('phone'),
            'email' => school_setting('email'),
            'website' => school_setting('website'),
            'principal_name' => school_setting('principal_name', 'Nama Kepala Sekolah'),
            'principal_nip' => school_setting('principal_nip'),
            'principal_nuptk' => school_setting('principal_nuptk'),
            'school_year' => $tahunAjaran?->tahun ?? school_setting('school_year', '-'),
            'semester' => $labelSemester,
            'logo' => $this->logoDataUri($logoPath),
        ];
    }

    private function logoDataUri(?string $path): string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return school_logo_url();
        }

        $fullPath = Storage::disk('public')->path($path);
        $contents = file_get_contents($fullPath);
        $mime = mime_content_type($fullPath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function excelReportHeader(string $title, array $school, string $context): array
    {
        $principalId = $school['principal_nip'] ?: $school['principal_nuptk'];

        return [
            [$school['name']],
            [$school['address']],
            [$title],
            [$context],
            ['Tahun Ajaran', $school['school_year'], 'Semester', $school['semester']],
            ['Kepala Sekolah', $school['principal_name'], 'NIP/NUPTK', $principalId ?: '-'],
            [],
        ];
    }

    private function temporaryExcelPath(string $prefix): string
    {
        return tempnam(sys_get_temp_dir(), $prefix);
    }

    private function prepareWorksheet(Writer $writer, int $columnCount): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidth(6, 1);

        if ($columnCount >= 2) {
            $sheet->setColumnWidth(15, 2);
        }

        if ($columnCount >= 3) {
            $sheet->setColumnWidth(30, 3);
        }

        if ($columnCount > 3) {
            $sheet->setColumnWidthForRange(14, 4, $columnCount);
        }

        $lastColumn = max(0, $columnCount - 1);
        foreach ([1, 2, 3, 4] as $row) {
            $writer->getOptions()->mergeCells(0, $row, $lastColumn, $row);
        }
    }

    private function writeExcelReportHeader(Writer $writer, string $title, array $school, string $context): void
    {
        $styles = $this->excelStyles();

        $rows = $this->excelReportHeader($title, $school, $context);
        $writer->addRow(Row::fromValuesWithStyle($rows[0], $styles['school'], 24));
        $writer->addRow(Row::fromValuesWithStyle($rows[1], $styles['meta'], 18));
        $writer->addRow(Row::fromValuesWithStyle($rows[2], $styles['title'], 24));
        $writer->addRow(Row::fromValuesWithStyle($rows[3], $styles['context'], 20));
        $writer->addRow(Row::fromValuesWithStyle($rows[4], $styles['meta'], 18));
        $writer->addRow(Row::fromValuesWithStyle($rows[5], $styles['meta'], 18));
        $writer->addRow(Row::fromValues([]));
    }

    private function writeExcelTableHeader(Writer $writer, array $headers): void
    {
        $writer->addRow(Row::fromValuesWithStyle($headers, $this->excelStyles()['tableHeader'], 24));
    }

    private function writeExcelDataRow(Writer $writer, array $row, int $index): void
    {
        $style = $index % 2 === 0 ? $this->excelStyles()['row'] : $this->excelStyles()['alternateRow'];
        $writer->addRow(Row::fromValuesWithStyle($row, $style, 20));
    }

    private function excelStyles(): array
    {
        $border = new Border(
            new BorderPart(BorderName::TOP, 'CBD5E1', BorderWidth::THIN),
            new BorderPart(BorderName::RIGHT, 'CBD5E1', BorderWidth::THIN),
            new BorderPart(BorderName::BOTTOM, 'CBD5E1', BorderWidth::THIN),
            new BorderPart(BorderName::LEFT, 'CBD5E1', BorderWidth::THIN),
        );

        $base = (new Style())
            ->withFontName('Arial')
            ->withFontSize(10)
            ->withShouldWrapText(true)
            ->withCellVerticalAlignment(CellVerticalAlignment::CENTER);

        return [
            'school' => $base
                ->withFontBold(true)
                ->withFontSize(14)
                ->withFontColor('0F172A')
                ->withCellAlignment(CellAlignment::CENTER),
            'title' => $base
                ->withFontBold(true)
                ->withFontSize(13)
                ->withFontColor(Color::WHITE)
                ->withBackgroundColor('1D4ED8')
                ->withCellAlignment(CellAlignment::CENTER),
            'context' => $base
                ->withFontBold(true)
                ->withFontColor('1E3A8A')
                ->withBackgroundColor('DBEAFE')
                ->withCellAlignment(CellAlignment::CENTER),
            'meta' => $base
                ->withFontColor('475569')
                ->withBackgroundColor('F8FAFC'),
            'tableHeader' => $base
                ->withFontBold(true)
                ->withFontColor(Color::WHITE)
                ->withBackgroundColor('334155')
                ->withCellAlignment(CellAlignment::CENTER)
                ->withBorder($border),
            'row' => $base
                ->withBackgroundColor(Color::WHITE)
                ->withBorder($border),
            'alternateRow' => $base
                ->withBackgroundColor('F8FAFC')
                ->withBorder($border),
        ];
    }
}
