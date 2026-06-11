<?php
require_once '../config.php';
require_once '../includes/fungsi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_siswa.xlsx"');

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'NIS');
$sheet->setCellValue('B1', 'Nama Lengkap');
$sheet->setCellValue('C1', 'Kelas ID');
$sheet->setCellValue('D1', 'Jenis Kelamin (L/P)');
$sheet->setCellValue('A2', '001');
$sheet->setCellValue('B2', 'Ahmad Fauzi');
$sheet->setCellValue('C2', '1');
$sheet->setCellValue('D2', 'L');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;