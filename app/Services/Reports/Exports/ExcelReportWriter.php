<?php

namespace App\Services\Reports\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

final class ExcelReportWriter
{
    public function open(string $prefix, int $columnCount): array
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        $writer = new Writer();
        $writer->openToFile($path);
        $this->prepareWorksheet($writer, $columnCount);
        $this->mergeReportHeader($writer, $columnCount);
        return [$writer, $path];
    }

    public function header(Writer $writer, string $title, array $school, string $subtitle): void
    {
        $styles = $this->styles();
        $writer->addRow(Row::fromValuesWithStyle([$school['name'] ?? 'Nama Sekolah'], $styles['school'], 24));
        $writer->addRow(Row::fromValuesWithStyle([$title], $styles['title'], 24));
        $writer->addRow(Row::fromValuesWithStyle([
            'Tahun Ajaran', $school['academic_year'] ?? '-',
            'Semester', $school['semester_label'] ?? '-',
        ], $styles['meta'], 18));
        $writer->addRow(Row::fromValuesWithStyle([$subtitle], $styles['meta'], 18));
        $writer->addRow(Row::fromValues([]));
    }

    public function tableHeader(Writer $writer, array $headers): void
    {
        $writer->addRow(Row::fromValuesWithStyle($headers, $this->styles()['tableHeader'], 24));
    }

    public function dataRow(Writer $writer, array $values, int $index): void
    {
        $styles = $this->styles();
        $writer->addRow(Row::fromValuesWithStyle(
            $values,
            $index % 2 === 0 ? $styles['row'] : $styles['alternateRow'],
            20
        ));
    }

    public function close(Writer $writer, string $path, string $filename): array
    {
        $writer->close();
        return [$path, $filename];
    }

    private function prepareWorksheet(Writer $writer, int $columnCount): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Laporan');
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
    }

    private function mergeReportHeader(Writer $writer, int $columnCount): void
    {
        $lastColumn = max(0, $columnCount - 1);

        foreach ([1, 2, 3, 4] as $row) {
            $writer->getOptions()->mergeCells(0, $row, $lastColumn, $row);
        }
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
            'school' => (new Style())->withFontBold(true)->withFontSize(16)->withCellAlignment(CellAlignment::CENTER)->withCellVerticalAlignment(CellVerticalAlignment::CENTER),
            'title' => (new Style())->withFontBold(true)->withFontSize(13)->withCellAlignment(CellAlignment::CENTER)->withCellVerticalAlignment(CellVerticalAlignment::CENTER),
            'meta' => (new Style())->withFontSize(10)->withBorder($border),
            'tableHeader' => (new Style())->withFontBold(true)->withFontSize(10)->withBorder($border)->withCellAlignment(CellAlignment::CENTER)->withCellVerticalAlignment(CellVerticalAlignment::CENTER),
            'row' => (new Style())->withFontSize(10)->withBorder($border),
            'alternateRow' => (new Style())->withFontSize(10)->withBorder($border),
        ];
    }
}
