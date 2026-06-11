<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$data = mysqli_query($conn, "SELECT * FROM lkpd ORDER BY id DESC");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('LKPD');

$sheet->setCellValue('A1', 'LEMBAR KERJA PESERTA DIDIK (LKPD)');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->setCellValue('A2', 'MTs. AL-IHSAN BATUJAJAR');
$sheet->mergeCells('A2:H2');
$sheet->setCellValue('A3', 'Mata Pelajaran: Al-Qur\'an Hadis');
$sheet->mergeCells('A3:H3');

$headers = ['No', 'Bab', 'Judul LKPD', 'Petunjuk Belajar', 'Tujuan Pembelajaran', 'Materi Pokok', 'Tugas', 'Rubrik'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '5', $header);
    $sheet->getStyle($col . '5')->getFont()->setBold(true);
    $sheet->getStyle($col . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
    $col++;
}

$row = 6;
$no = 1;
while ($d = mysqli_fetch_assoc($data)) {
    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $d['bab']);
    $sheet->setCellValue('C' . $row, $d['judul']);
    $sheet->setCellValue('D' . $row, $d['petunjuk_belajar']);
    $sheet->setCellValue('E' . $row, $d['tujuan_pembelajaran']);
    $sheet->setCellValue('F' . $row, $d['materi_pokok']);
    $sheet->setCellValue('G' . $row, $d['tugas']);
    $sheet->setCellValue('H' . $row, $d['rubrik']);
    $row++;
}

foreach (range('A', 'H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
$lastRow = $row - 1;
$sheet->getStyle('A5:H' . $lastRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="LKPD_'.date('Ymd').'.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;