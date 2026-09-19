<?php

namespace App\Services\Reports\Exports;

use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\NilaiAkhir;
use App\Models\Siswa;
use App\Services\Reports\ReportMetadataService;

final class NilaiExportService
{
    public function __construct(private readonly ExcelReportWriter $excel, private readonly ReportMetadataService $metadata) {}

    public function export(int $kelasId, string $semester): array
    {
        $context = $this->metadata->context($semester);
        $kelas = Kelas::findOrFail($kelasId);
        $siswa = Siswa::with('user')->where('kelas_id', $kelasId)->where('status', 'aktif')->orderBy('nis')->get();
        $mapel = $this->mapelList($kelasId, $context->tahunAjaran?->id, $semester);
        $nilai = NilaiAkhir::whereIn('siswa_id', $siswa->pluck('id'))->where('tahun_ajaran_id', $context->tahunAjaran?->id)->where('semester', $semester)->get()->groupBy('siswa_id');

        [$writer, $path] = $this->excel->open('rekap_nilai_', 3 + $mapel->count() + 1);
        $this->excel->header($writer, 'REKAP NILAI', [...$context->school, 'academic_year' => $context->academicYearLabel(), 'semester_label' => $context->semesterLabel()], "Kelas {$kelas->tingkat} {$kelas->nama_kelas}");
        $this->excel->tableHeader($writer, array_merge(['No','NIS','Nama'], $mapel->pluck('nama_mapel')->toArray(), ['Rata-rata']));

        foreach ($siswa as $i => $student) {
            $studentValues = $nilai->get($student->id, collect());
            $values = [];
            foreach ($mapel as $subject) {
                $grade = $studentValues->firstWhere('kelas_mapel_id', $subject->kelas_mapel_id);
                $values[] = $grade ? (float) $grade->rata_akhir : '';
            }
            $valid = array_filter($values, fn ($v) => $v !== '');
            $average = count($valid) ? round(array_sum($valid) / count($valid), 2) : '';
            $this->excel->dataRow($writer, array_merge([$i + 1, $student->nis, $student->user?->nama_lengkap ?? '-'], $values, [$average]), $i);
        }
        return $this->excel->close($writer, $path, "rekap_nilai_{$kelas->tingkat}_{$kelas->nama_kelas}_semester_{$semester}.xlsx");
    }

    private function mapelList(int $kelasId, ?int $tahunAjaranId, string $semester)
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
            ->map(fn (KelasMapel $item) => (object) [
                'kelas_mapel_id' => $item->id,
                'nama_mapel' => $item->mataPelajaran?->nama_mapel ?? '-',
            ]);
    }
}
