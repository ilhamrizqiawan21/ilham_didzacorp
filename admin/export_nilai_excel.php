<?php
// File ini HARUS dipanggil langsung tanpa include header/footer
// Pastikan tidak ada spasi atau baris kosong sebelum <?php
include '../config.php';

// Cek login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die('Akses ditolak');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$kelas_id = (int)$_GET['kelas_id'];
$semester = mysqli_real_escape_string($conn, $_GET['semester']);
$tahun_ajaran = mysqli_real_escape_string($conn, $_GET['tahun']);

// Ambil data siswa dan nilai
$siswa = mysqli_query($conn, "SELECT s.id, s.nis, s.nama, s.jenis_kelamin, k.nama_kelas 
    FROM siswa s 
    JOIN kelas k ON s.kelas_id = k.id 
    WHERE s.kelas_id = $kelas_id 
    ORDER BY s.nama");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Nilai Siswa');

// ========== HEADER ==========
// Title utama
$sheet->setCellValue('A1', 'DAFTAR NILAI SISWA');
$sheet->setCellValue('A2', 'MTs. AL-IHSAN BATUJAJAR');
$sheet->setCellValue('A3', 'TAHUN PELAJARAN ' . $tahun_ajaran);
$sheet->mergeCells('A1:M1');
$sheet->mergeCells('A2:M2');
$sheet->mergeCells('A3:M3');

// Styling header utama
$sheet->getStyle('A1:A3')->getFont()->setBold(true);
$sheet->getStyle('A1')->getFont()->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Kelas dan mapel
$kelas_nama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id=$kelas_id"))['nama_kelas'];
$sheet->setCellValue('A5', 'Kelas : ' . $kelas_nama);
$sheet->setCellValue('E5', 'MATA PELAJARAN: Al-Qur\'an Hadis');
$sheet->getStyle('A5:E5')->getFont()->setBold(true);

// ========== HEADER TABEL ==========
$headers = ['NO', 'NIS', 'NAMA SISWA', 'L/P', 'SUM 1', 'SUM 2', 'SUM 3', 'SUM 4', 'STS', 'SAS ASLI', 'SAS JADI', 'SAT ASLI', 'SAT JADI', 'KET.'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '7', $header);
    $sheet->getStyle($col . '7')->getFont()->setBold(true);
    $sheet->getStyle($col . '7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($col . '7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2');
    $col++;
}

// Border untuk header
$sheet->getStyle('A7:N7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// ========== DATA ==========
$row = 8;
$no = 1;
while($s = mysqli_fetch_assoc($siswa)) {
    $nilai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM nilai_akhir WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'"));
    
    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $s['nis']);
    $sheet->setCellValue('C' . $row, $s['nama']);
    $sheet->setCellValue('D' . $row, $s['jenis_kelamin'] == 'L' ? 'L' : 'P');
    $sheet->setCellValue('E' . $row, $nilai['sum1'] ?? '');
    $sheet->setCellValue('F' . $row, $nilai['sum2'] ?? '');
    $sheet->setCellValue('G' . $row, $nilai['sum3'] ?? '');
    $sheet->setCellValue('H' . $row, $nilai['sum4'] ?? '');
    $sheet->setCellValue('I' . $row, $nilai['sts'] ?? '');
    $sheet->setCellValue('J' . $row, $nilai['sas_asli'] ?? '');
    $sheet->setCellValue('K' . $row, $nilai['sas_jadi'] ?? '');
    $sheet->setCellValue('L' . $row, $nilai['sat_asli'] ?? '');
    $sheet->setCellValue('M' . $row, $nilai['sat_jadi'] ?? '');
    $sheet->setCellValue('N' . $row, '');
    
    // Alignment tengah untuk kolom tertentu
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E' . $row . ':N' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $row++;
}

// Border untuk data
$sheet->getStyle('A7:M' . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// ========== FOOTER ==========
$footerRow = $row + 2;
$sheet->setCellValue('J' . $footerRow, 'Guru Mata Pelajaran');
$sheet->setCellValue('J' . ($footerRow + 2), 'Ilham Rizqiawan, S.Pd.');
$sheet->getStyle('J' . $footerRow)->getFont()->setBold(true);
$sheet->getStyle('J' . $footerRow . ':J' . ($footerRow + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// ========== AUTO SIZE KOLOM ==========
foreach(range('A','N') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ========== OUTPUT ==========
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="nilai_kelas_' . $kelas_nama . '_semester_' . $semester . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;