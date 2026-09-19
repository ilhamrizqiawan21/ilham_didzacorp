<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\User;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class SiswaTemplateService
{
    public const FILENAME = 'template_import_siswa.xlsx';

    public const HEADERS = [
        'username',
        'nama_lengkap',
        'nis',
        'kelas_id',
        'password',
        'jenis_kelamin',
        'angkatan',
        'status',
        'is_active',
    ];

    public function createTemplateFile(): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'template_import_siswa_');
        $kelasList = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();
        $contohKelas = $kelasList->first();

        $writer = new Writer();
        $writer->openToFile($filePath);

        $templateSheet = $writer->getCurrentSheet();
        $templateSheet->setName('Template Siswa');
        $templateSheet->setColumnWidth(22, 1);
        $templateSheet->setColumnWidth(30, 2);
        $templateSheet->setColumnWidth(18, 3);
        $templateSheet->setColumnWidth(12, 4);
        $templateSheet->setColumnWidth(24, 5);
        $templateSheet->setColumnWidth(18, 6);
        $templateSheet->setColumnWidth(14, 7);
        $templateSheet->setColumnWidth(14, 8);
        $templateSheet->setColumnWidth(14, 9);

        $headerStyle = (new Style())->withFontBold(true)->withCellAlignment(CellAlignment::CENTER);
        $writer->addRow(Row::fromValuesWithStyle(self::HEADERS, $headerStyle, 20));
        $writer->addRow(Row::fromValues([
            'siswa001',
            'Nama Siswa Contoh',
            '2026001',
            $contohKelas?->id ?? '',
            User::DEFAULT_PASSWORD,
            'L',
            '2026',
            'aktif',
            '1',
        ]));

        $instructionSheet = $writer->addNewSheetAndMakeItCurrent();
        $instructionSheet->setName('Petunjuk');
        $instructionSheet->setColumnWidth(80, 1);
        $instructions = [
            'TEMPLATE IMPORT SISWA',
            '',
            'Gunakan sheet "Template Siswa" untuk data yang akan diimport.',
            'Jangan mengubah nama, urutan, atau jumlah kolom pada baris header.',
            'username: wajib unik, maksimal 50 karakter.',
            'nama_lengkap: wajib, maksimal 100 karakter.',
            'nis: wajib unik, maksimal 20 karakter.',
            'kelas_id: wajib berupa ID yang tersedia pada sheet "Daftar Kelas".',
            'password: opsional; kosong menggunakan password default sistem.',
            'jenis_kelamin: kosong atau L/P.',
            'angkatan: opsional.',
            'status: kosong atau aktif/lulus/keluar.',
            'is_active: kosong atau 1/0, true/false, ya/tidak, aktif/nonaktif.',
            'Maksimal 500 siswa per file.',
            'Jika satu baris tidak valid, seluruh file tidak diimport.',
        ];
        foreach ($instructions as $line) {
            $writer->addRow(Row::fromValues([$line]));
        }

        $kelasSheet = $writer->addNewSheetAndMakeItCurrent();
        $kelasSheet->setName('Daftar Kelas');
        $kelasSheet->setColumnWidth(12, 1);
        $kelasSheet->setColumnWidth(14, 2);
        $kelasSheet->setColumnWidth(24, 3);
        $kelasSheet->setColumnWidth(32, 4);
        $writer->addRow(Row::fromValuesWithStyle(['kelas_id', 'tingkat', 'nama_kelas', 'label'], $headerStyle, 20));

        foreach ($kelasList as $kelas) {
            $writer->addRow(Row::fromValues([
                $kelas->id,
                $kelas->tingkat,
                $kelas->nama_kelas,
                trim("{$kelas->tingkat} {$kelas->nama_kelas}"),
            ]));
        }

        $writer->close();

        return $filePath;
    }
}
