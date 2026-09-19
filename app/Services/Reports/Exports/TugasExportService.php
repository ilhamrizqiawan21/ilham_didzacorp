<?php

namespace App\Services\Reports\Exports;

use App\Models\Kelas;
use App\Models\PengumpulanTugas;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Services\Reports\ReportMetadataService;

final class TugasExportService
{
    public function __construct(private readonly ExcelReportWriter $excel, private readonly ReportMetadataService $metadata) {}

    public function export(int $kelasId, string $semester): array
    {
        $context = $this->metadata->context($semester);
        $kelas = Kelas::findOrFail($kelasId);
        $total = Siswa::where('kelas_id', $kelasId)->where('status', 'aktif')->count();
        $tugas = Tugas::with(['kelasMapel.mataPelajaran','kelasMapel.guru'])
            ->whereHas('kelasMapel', fn ($q) => $q->where('kelas_id', $kelasId)->where('tahun_ajaran_id', $context->tahunAjaran?->id)->where('semester', $semester))
            ->withCount(['pengumpulan as sudah_kumpul' => fn ($q) => $q->whereIn('status', PengumpulanTugas::STATUS_SUBMITTED)->whereHas('siswa', fn ($s) => $s->where('kelas_id', $kelasId)->where('status', 'aktif'))])
            ->orderByDesc('created_at')->get();

        [$writer, $path] = $this->excel->open('rekap_tugas_', 9);
        $this->excel->header($writer, 'REKAP TUGAS', [...$context->school, 'academic_year' => $context->academicYearLabel(), 'semester_label' => $context->semesterLabel()], "Kelas {$kelas->tingkat} {$kelas->nama_kelas}");
        $this->excel->tableHeader($writer, ['No','Judul Tugas','Mata Pelajaran','Guru','Deadline','Kategori','Sudah Kumpul','Total Siswa','Persentase']);
        foreach ($tugas as $i => $task) {
            $percentage = $total > 0 ? round(($task->sudah_kumpul / $total) * 100, 2) : 0;
            $this->excel->dataRow($writer, [$i + 1, $task->judul, $task->kelasMapel?->mataPelajaran?->nama_mapel ?? '-', $task->kelasMapel?->guru?->nama_lengkap ?? '-', $task->batas_waktu ? date('d/m/Y', strtotime($task->batas_waktu)) : '-', $task->kategori_nilai ?? 'NH', $task->sudah_kumpul, $total, "{$percentage}%"], $i);
        }
        return $this->excel->close($writer, $path, "rekap_tugas_{$kelas->tingkat}_{$kelas->nama_kelas}_semester_{$semester}.xlsx");
    }
}
