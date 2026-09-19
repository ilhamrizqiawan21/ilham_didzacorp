<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use App\Models\TahunAjaran;
use App\Services\GuruPerformanceService;
use App\Services\Reports\Exports\ExcelReportWriter;
use Barryvdh\DomPDF\Facade\Pdf;

class GuruPerformanceExportController extends Controller
{
    public function excel(GuruPerformanceService $service, ExcelReportWriter $excel)
    {
        [$writer, $path] = $excel->open('performa_guru_', 10);
        $excel->header($writer, 'PERFORMA GURU', $this->schoolMeta(), 'Indikator evaluasi guru berbasis data LMS');
        $excel->tableHeader($writer, $this->headers());

        foreach ($this->rows($service) as $index => $row) {
            $excel->dataRow($writer, $row, $index);
        }

        [$path, $filename] = $excel->close($writer, $path, 'performa_guru_' . now()->format('Ymd_His') . '.xlsx');

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function pdf(GuruPerformanceService $service)
    {
        $pdf = Pdf::loadView('exports.pdf.table', [
            'title' => 'PERFORMA GURU',
            'context' => 'Indikator evaluasi guru berbasis data tugas, pengumpulan, nilai, feedback, dan aktivitas.',
            'headers' => $this->headers(),
            'rows' => $this->rows($service),
            'reportSchool' => $this->reportSchool(),
            'signer' => [
                'role' => 'Kepala Sekolah',
                'name' => school_setting('principal_name', '-'),
                'id_label' => school_setting('principal_nip') ? 'NIP' : 'NUPTK',
                'id_value' => school_setting('principal_nip') ?: school_setting('principal_nuptk'),
            ],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('performa_guru_' . now()->format('Ymd_His') . '.pdf');
    }

    private function rows(GuruPerformanceService $service): array
    {
        return collect($service->dashboard()['teachers'])
            ->values()
            ->map(fn (array $teacher, int $index) => [
                $index + 1,
                $teacher['nama'],
                $teacher['score'],
                $teacher['kategori'],
                $teacher['total_kelas_mapel'],
                $teacher['total_tugas'],
                $teacher['persen_pengumpulan'] . '%',
                $teacher['persen_dinilai'] . '%',
                $teacher['persen_feedback'] . '%',
                $teacher['rata_nilai_tugas'] ?? '-',
            ])
            ->all();
    }

    private function headers(): array
    {
        return ['No', 'Guru', 'Skor', 'Kategori', 'Kelas/Mapel', 'Tugas', 'Pengumpulan', 'Dinilai', 'Feedback', 'Rata Nilai'];
    }

    private function schoolMeta(): array
    {
        $tahunAjaran = TahunAjaran::getAktif();
        $semester = Pengaturan::getValue('semester_aktif', '1');

        return [
            'name' => school_setting('school_name', 'Nama Sekolah'),
            'academic_year' => $tahunAjaran?->tahun ?? '-',
            'semester_label' => $semester === '1' ? 'Ganjil' : 'Genap',
        ];
    }

    private function reportSchool(): array
    {
        $tahunAjaran = TahunAjaran::getAktif();
        $semester = Pengaturan::getValue('semester_aktif', '1');

        return [
            'name' => school_setting('school_name', 'Nama Sekolah'),
            'address' => school_setting('address', '-'),
            'phone' => school_setting('phone'),
            'email' => school_setting('email'),
            'website' => school_setting('website'),
            'logo' => school_logo_url(),
            'school_year' => $tahunAjaran?->tahun ?? '-',
            'semester' => $semester === '1' ? 'Ganjil' : 'Genap',
            'principal_name' => school_setting('principal_name', '-'),
            'principal_nip' => school_setting('principal_nip'),
            'principal_nuptk' => school_setting('principal_nuptk'),
        ];
    }
}
