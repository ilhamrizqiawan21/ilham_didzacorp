<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$data = mysqli_query($conn, "SELECT * FROM modul_ajar ORDER BY id");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Modul Ajar');

// Header utama
$sheet->setCellValue('A1', 'MODUL AJAR');
$sheet->mergeCells('A1:Z1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->setCellValue('A2', 'MTs. AL-IHSAN BATUJAJAR');
$sheet->mergeCells('A2:Z2');
$sheet->setCellValue('A3', 'Mata Pelajaran: Al-Qur\'an Hadis');
$sheet->mergeCells('A3:Z3');

// Header tabel (kolom)
$headers = [
    'ID', 'Bab', 'Identitas Madrasah', 'Nama Penyusun', 'NUPTK', 'Mapel', 'Kelas/Semester',
    'Alokasi Waktu Total', 'Tahun Pelajaran', 'Pengetahuan Awal', 'Minat', 'Latar Belakang',
    'Kebutuhan Belajar', 'Topik Panca Cinta', 'Materi Insersi', 'Jenis Pengetahuan', 'Relevansi',
    'Tingkat Kesulitan', 'Struktur Materi', 'Integrasi Nilai', 'Profil Keimanan', 'Profil Kewargaan',
    'Profil Nalar Kritis', 'Profil Kreativitas', 'Profil Kolaborasi', 'Profil Kemandirian',
    'Profil Kesehatan', 'Profil Komunikasi', 'CP Tajwid', 'Lintas Disiplin', 'Tujuan Pembelajaran 1',
    'Tujuan Pembelajaran 2', 'Tujuan Pembelajaran 3', 'Tujuan Pembelajaran 4', 'Indikator',
    'Iklim Budaya', 'Topik Kontekstual', 'Model Pembelajaran', 'Pendekatan', 'Metode',
    'Strategi Diferensiasi', 'Kemitraan Sekolah', 'Kemitraan Masyarakat', 'Mitra Digital',
    'Lingkungan Fisik', 'Lingkungan Virtual', 'Budaya Belajar', 'Pemanfaatan Digital',
    'Langkah Pertemuan 1', 'Langkah Pertemuan 2', 'Langkah Pertemuan 3', 'Langkah Pertemuan 4',
    'Asesmen Diagnostik', 'Asesmen Formatif', 'Asesmen Sumatif'
];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '5', $header);
    $sheet->getStyle($col . '5')->getFont()->setBold(true);
    $sheet->getStyle($col . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
    $col++;
}

$row = 6;
while ($d = mysqli_fetch_assoc($data)) {
    $col = 'A';
    $sheet->setCellValue($col++ . $row, $d['id']);
    $sheet->setCellValue($col++ . $row, $d['bab']);
    $sheet->setCellValue($col++ . $row, $d['identitas_madrasah']);
    $sheet->setCellValue($col++ . $row, $d['nama_penyusun']);
    $sheet->setCellValue($col++ . $row, $d['nuptk']);
    $sheet->setCellValue($col++ . $row, $d['mapel']);
    $sheet->setCellValue($col++ . $row, $d['kelas_semester']);
    $sheet->setCellValue($col++ . $row, $d['alokasi_waktu_total']);
    $sheet->setCellValue($col++ . $row, $d['tahun_pelajaran']);
    $sheet->setCellValue($col++ . $row, $d['pengetahuan_awal']);
    $sheet->setCellValue($col++ . $row, $d['minat']);
    $sheet->setCellValue($col++ . $row, $d['latar_belakang']);
    $sheet->setCellValue($col++ . $row, $d['kebutuhan_belajar']);
    $sheet->setCellValue($col++ . $row, $d['topik_panca_cinta']);
    $sheet->setCellValue($col++ . $row, $d['materi_insersi']);
    $sheet->setCellValue($col++ . $row, $d['jenis_pengetahuan']);
    $sheet->setCellValue($col++ . $row, $d['relevansi']);
    $sheet->setCellValue($col++ . $row, $d['tingkat_kesulitan']);
    $sheet->setCellValue($col++ . $row, $d['struktur_materi']);
    $sheet->setCellValue($col++ . $row, $d['integrasi_nilai']);
    $sheet->setCellValue($col++ . $row, $d['profil_keimanan']);
    $sheet->setCellValue($col++ . $row, $d['profil_kewargaan']);
    $sheet->setCellValue($col++ . $row, $d['profil_nalar_kritis']);
    $sheet->setCellValue($col++ . $row, $d['profil_kreativitas']);
    $sheet->setCellValue($col++ . $row, $d['profil_kolaborasi']);
    $sheet->setCellValue($col++ . $row, $d['profil_kemandirian']);
    $sheet->setCellValue($col++ . $row, $d['profil_kesehatan']);
    $sheet->setCellValue($col++ . $row, $d['profil_komunikasi']);
    $sheet->setCellValue($col++ . $row, $d['cp_tajwid']);
    $sheet->setCellValue($col++ . $row, $d['lintas_displin']);
    $sheet->setCellValue($col++ . $row, $d['tujuan_pembelajaran_1']);
    $sheet->setCellValue($col++ . $row, $d['tujuan_pembelajaran_2']);
    $sheet->setCellValue($col++ . $row, $d['tujuan_pembelajaran_3']);
    $sheet->setCellValue($col++ . $row, $d['tujuan_pembelajaran_4']);
    $sheet->setCellValue($col++ . $row, $d['indikator']);
    $sheet->setCellValue($col++ . $row, $d['iklim_budaya']);
    $sheet->setCellValue($col++ . $row, $d['topik_kontekstual']);
    $sheet->setCellValue($col++ . $row, $d['model_pembelajaran']);
    $sheet->setCellValue($col++ . $row, $d['pendekatan']);
    $sheet->setCellValue($col++ . $row, $d['metode']);
    $sheet->setCellValue($col++ . $row, $d['strategi_diferensiasi']);
    $sheet->setCellValue($col++ . $row, $d['kemitraan_sekolah']);
    $sheet->setCellValue($col++ . $row, $d['kemitraan_masyarakat']);
    $sheet->setCellValue($col++ . $row, $d['mitra_digital']);
    $sheet->setCellValue($col++ . $row, $d['lingkungan_fisik']);
    $sheet->setCellValue($col++ . $row, $d['lingkungan_virtual']);
    $sheet->setCellValue($col++ . $row, $d['budaya_belajar']);
    $sheet->setCellValue($col++ . $row, $d['pemanfaatan_digital']);
    $sheet->setCellValue($col++ . $row, $d['langkah_pertemuan_1']);
    $sheet->setCellValue($col++ . $row, $d['langkah_pertemuan_2']);
    $sheet->setCellValue($col++ . $row, $d['langkah_pertemuan_3']);
    $sheet->setCellValue($col++ . $row, $d['langkah_pertemuan_4']);
    $sheet->setCellValue($col++ . $row, $d['asesmen_diagnostik']);
    $sheet->setCellValue($col++ . $row, $d['asesmen_formatif']);
    $sheet->setCellValue($col++ . $row, $d['asesmen_sumatif']);
    $row++;
}

// Auto size kolom
foreach (range('A', $col) as $colLetter) {
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}

// Border untuk data
$lastRow = $row - 1;
$sheet->getStyle('A5:' . $colLetter . $lastRow)->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
]);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Modul_Ajar_'.date('Ymd').'.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>