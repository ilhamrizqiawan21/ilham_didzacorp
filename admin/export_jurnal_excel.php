<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$where = "DATE_FORMAT(hari_tanggal, '%Y-%m') = '$bulan'";
$data = mysqli_query($conn, "SELECT * FROM jurnal WHERE $where ORDER BY hari_tanggal DESC");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Jurnal Mengajar');

// Header
$sheet->setCellValue('A1', 'JURNAL MENGAJAR GURU');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->setCellValue('A2', 'MTs. AL-IHSAN BATUJAJAR');
$sheet->mergeCells('A2:G2');
$sheet->setCellValue('A3', 'Mata Pelajaran: Al-Qur\'an Hadis');
$sheet->mergeCells('A3:G3');
$sheet->setCellValue('A4', 'Bulan: ' . date('F Y', strtotime($bulan)));
$sheet->mergeCells('A4:G4');

// Header tabel
$headers = ['No', 'Tanggal', 'Bab', 'Alur Tujuan', 'Materi', 'Jam ke-', 'Keterangan'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '6', $header);
    $sheet->getStyle($col . '6')->getFont()->setBold(true);
    $sheet->getStyle($col . '6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
    $sheet->getStyle($col . '6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $col++;
}

$row = 7;
$no = 1;
while ($d = mysqli_fetch_assoc($data)) {
    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, tgl_indonesia($d['hari_tanggal']));
    $sheet->setCellValue('C' . $row, $d['bab']);
    $sheet->setCellValue('D' . $row, $d['alur_tujuan']);
    $sheet->setCellValue('E' . $row, $d['materi']);
    $sheet->setCellValue('F' . $row, $d['jam_ke']);
    $sheet->setCellValue('G' . $row, $d['keterangan']);
    $row++;
}

// Auto size kolom
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Border tabel
$lastRow = $row - 1;
$sheet->getStyle('A6:G' . $lastRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

// Output file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Jurnal_'.date('Y-m', strtotime($bulan)).'.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;