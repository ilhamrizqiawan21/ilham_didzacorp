<?php
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die('Akses ditolak');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template Nilai');

$sheet->setCellValue('A1', 'Template ini digunakan untuk impor nilai STS, SAS, SAT ke menu Olah Nilai. Isi kolom NIS atau NAMA, lalu masukkan nilai STS/SAS/SAT pada baris yang sesuai.');
$sheet->mergeCells('A1:N1');
$sheet->getStyle('A1')->getFont()->setBold(true);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

$headers = ['NO', 'NIS', 'NAMA SISWA', 'L/P', 'SUM 1', 'SUM 2', 'SUM 3', 'SUM 4', 'STS', 'SAS ASLI', 'SAS JADI', 'SAT ASLI', 'SAT JADI', 'KET.'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '2', $header);
    $sheet->getStyle($col . '2')->getFont()->setBold(true);
    $sheet->getStyle($col . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($col . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2');
    $col++;
}

$sheet->setCellValue('A3', 'Contoh:');
$sheet->setCellValue('B3', '12345');
$sheet->setCellValue('C3', 'Nama Siswa');
$sheet->setCellValue('D3', 'P');
$sheet->setCellValue('I3', '85');
$sheet->setCellValue('J3', '88');
$sheet->setCellValue('K3', '89');
$sheet->setCellValue('L3', '87');
$sheet->setCellValue('M3', '90');

foreach (range('A', 'N') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_import_nilai.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
