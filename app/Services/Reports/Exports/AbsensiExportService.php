<?php

namespace App\Services\Reports\Exports;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\Reports\ReportMetadataService;

final class AbsensiExportService
{
    public function __construct(private readonly ExcelReportWriter $excel, private readonly ReportMetadataService $metadata) {}

    public function export(int $kelasId, string $semester, string $bulan): array
    {
        $context = $this->metadata->context($semester);
        $kelas = Kelas::findOrFail($kelasId);
        $siswa = Siswa::with('user')->where('kelas_id', $kelasId)->where('status', 'aktif')->orderBy('nis')->get();
        $from = "{$bulan}-01";
        $to = date('Y-m-t', strtotime($from));
        $scope = fn ($q) => $q->where('kelas_id', $kelasId)->where('tahun_ajaran_id', $context->tahunAjaran?->id)->where('semester', $semester);
        $dates = Absensi::whereHas('kelasMapel', $scope)->whereBetween('tanggal', [$from, $to])->orderBy('tanggal')->pluck('tanggal')->unique()->map(fn ($d) => $d->format('Y-m-d'))->values();
        $data = Absensi::whereIn('siswa_id', $siswa->pluck('id'))->whereHas('kelasMapel', $scope)->whereBetween('tanggal', [$from, $to])->get()->groupBy('siswa_id');

        [$writer, $path] = $this->excel->open('rekap_absensi_', 3 + $dates->count() + 4);
        $this->excel->header($writer, 'REKAP ABSENSI', [...$context->school, 'academic_year' => $context->academicYearLabel(), 'semester_label' => $context->semesterLabel()], "Kelas {$kelas->tingkat} {$kelas->nama_kelas} - Bulan {$bulan}");
        $this->excel->tableHeader($writer, array_merge(['No','NIS','Nama'], $dates->map(fn ($date) => date('d', strtotime($date)))->toArray(), ['H','S','I','A']));

        foreach ($siswa as $i => $student) {
            $records = $data->get($student->id, collect());
            $row = []; $counts = ['hadir'=>0,'sakit'=>0,'izin'=>0,'alpha'=>0];
            foreach ($dates as $date) {
                $status = $records->firstWhere('tanggal', $date)?->status;
                $row[] = match ($status) { 'hadir'=>'H','sakit'=>'S','izin'=>'I','alpha'=>'A', default=>'' };
                if (isset($counts[$status])) $counts[$status]++;
            }
            $this->excel->dataRow($writer, array_merge([$i + 1, $student->nis, $student->user?->nama_lengkap ?? '-'], $row, [$counts['hadir'],$counts['sakit'],$counts['izin'],$counts['alpha']]), $i);
        }
        return $this->excel->close($writer, $path, "rekap_absensi_{$kelas->tingkat}_{$kelas->nama_kelas}_{$bulan}.xlsx");
    }
}
