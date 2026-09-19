<?php

namespace App\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class SiswaExportService
{
    public function export($query): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'siswa_export_');
        $writer = new Writer;
        $writer->openToFile($filePath);
        $writer->getCurrentSheet()->setName('Data Siswa');

        foreach ([16, 22, 32, 20, 18, 14, 20] as $column => $width) {
            $writer->getCurrentSheet()->setColumnWidth($width, $column + 1);
        }

        $styles = $this->styles();
        $school = school_setting('school_name', 'Nama Sekolah');

        $writer->addRow(Row::fromValuesWithStyle([$school], $styles['school'], 24));
        $writer->addRow(Row::fromValuesWithStyle(['EXPORT DATA SISWA'], $styles['title'], 24));
        $writer->addRow(Row::fromValuesWithStyle(['Tanggal Export', now()->format('d/m/Y H:i')], $styles['meta'], 18));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValuesWithStyle([
            'NIS',
            'Username',
            'Nama',
            'Kelas',
            'Jenis Kelamin',
            'Status Siswa',
            'Status Password',
        ], $styles['tableHeader'], 24));

        $rowIndex = 0;
        $query->chunk(500, function ($students) use ($writer, $styles, &$rowIndex) {
            foreach ($students as $siswa) {
                $kelas = trim(($siswa->kelas?->tingkat ? $siswa->kelas->tingkat.' ' : '').($siswa->kelas?->nama_kelas ?? '')) ?: '-';
                $isDefaultPassword = (bool) $siswa->user?->is_password_default;
                $values = [
                    $siswa->nis ?? '-',
                    $siswa->user?->username ?? '-',
                    $siswa->user?->nama_lengkap ?? '-',
                    $kelas,
                    $siswa->user?->jenis_kelamin ?? '-',
                    $siswa->status ?? '-',
                    $isDefaultPassword ? 'Masih default' : 'Sudah diubah',
                ];
                $writer->addRow(Row::fromValuesWithStyle(
                    $values,
                    $rowIndex % 2 === 0 ? $styles['row'] : $styles['alternateRow'],
                    20
                ));
                $rowIndex++;
            }
        });

        $writer->close();

        return $filePath;
    }

    private function styles(): array
    {
        $border = new Border(
            new BorderPart(BorderName::TOP, 'CBD5E1', BorderWidth::THIN),
            new BorderPart(BorderName::BOTTOM, 'CBD5E1', BorderWidth::THIN),
            new BorderPart(BorderName::LEFT, 'CBD5E1', BorderWidth::THIN),
            new BorderPart(BorderName::RIGHT, 'CBD5E1', BorderWidth::THIN),
        );

        return [
            'school' => (new Style)
                ->withFontBold(true)
                ->withFontSize(16)
                ->withCellAlignment(CellAlignment::CENTER)
                ->withCellVerticalAlignment(CellVerticalAlignment::CENTER),
            'title' => (new Style)
                ->withFontBold(true)
                ->withFontSize(13)
                ->withCellAlignment(CellAlignment::CENTER)
                ->withCellVerticalAlignment(CellVerticalAlignment::CENTER),
            'meta' => (new Style)
                ->withFontSize(10)
                ->withBorder($border),
            'tableHeader' => (new Style)
                ->withFontBold(true)
                ->withFontSize(10)
                ->withBorder($border)
                ->withCellAlignment(CellAlignment::CENTER)
                ->withCellVerticalAlignment(CellVerticalAlignment::CENTER),
            'row' => (new Style)
                ->withFontSize(10)
                ->withBorder($border),
            'alternateRow' => (new Style)
                ->withFontSize(10)
                ->withBorder($border),
        ];
    }
}
