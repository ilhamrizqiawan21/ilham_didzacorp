<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$semester = isset($_GET['semester']) ? $_GET['semester'] : '1';
$data = mysqli_query($conn, "SELECT * FROM promes WHERE semester='$semester' ORDER BY FIELD(bulan,'Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni'), minggu_ke");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('PROMES');

$sheet->setCellValue('A1', 'PROGRAM SEMESTER (PROMES)');
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->setCellValue('A2', 'MTs. AL-IHSAN BATUJAJAR');
$sheet->mergeCells('A2:E2');
$sheet->setCellValue('A3', 'Mata Pelajaran: Al-Qur\'an Hadis - Semester ' . ($semester == 1 ? 'Ganjil' : 'Genap'));
$sheet->mergeCells('A3:E3');

$headers = ['No', 'Bulan', 'Minggu ke-', 'Topik/Materi', 'Alokasi Waktu'];
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
    $sheet->setCellValue('B' . $row, $d['bulan']);
    $sheet->setCellValue('C' . $row, $d['minggu_ke']);
    $sheet->setCellValue('D' . $row, $d['topik_materi']);
    $sheet->setCellValue('E' . $row, $d['alokasi_waktu']);
    $row++;
}

foreach (range('A', 'E') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
$lastRow = $row - 1;
$sheet->getStyle('A5:E' . $lastRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="PROMES_Semester_'.$semester.'_'.date('Ymd').'.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;