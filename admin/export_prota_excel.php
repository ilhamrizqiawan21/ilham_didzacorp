<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$where = $semester ? "WHERE semester='$semester'" : "";
$data = mysqli_query($conn, "SELECT * FROM prota $where ORDER BY semester, id");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('PROTA');

// Header
$sheet->setCellValue('A1', 'PROGRAM TAHUNAN (PROTA)');
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->setCellValue('A2', 'MTs. AL-IHSAN BATUJAJAR');
$sheet->mergeCells('A2:F2');
$sheet->setCellValue('A3', 'Mata Pelajaran: Al-Qur\'an Hadis');
$sheet->mergeCells('A3:F3');

// Header tabel
$headers = ['No', 'Bab', 'Alur Tujuan Pembelajaran', 'Materi Pokok', 'Alokasi Waktu', 'Semester'];
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
    $sheet->setCellValue('C' . $row, $d['alur_tujuan']);
    $sheet->setCellValue('D' . $row, $d['materi_pokok']);
    $sheet->setCellValue('E' . $row, $d['alokasi_waktu']);
    $sheet->setCellValue('F' . $row, ($d['semester'] == 1) ? 'Ganjil' : 'Genap');
    $row++;
}

foreach (range('A', 'F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
$lastRow = $row - 1;
$sheet->getStyle('A5:F' . $lastRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="PROTA_'.date('Ymd').'.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;