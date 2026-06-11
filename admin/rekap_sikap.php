<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Rekap Nilai Sikap';
include '../includes/header.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Konversi nilai angka (1-5) ke predikat
 * @param int|null $nilai
 * @return string
 */
function konversi_nilai_sikap($nilai) {
    if (is_null($nilai) || $nilai === '') return '-';
    $nilai = (int)$nilai;
    switch ($nilai) {
        case 5: return 'SB (Sangat Baik)';
        case 4: return 'B (Baik)';
        case 3: return 'C (Cukup)';
        case 2: return 'KB (Kurang Baik)';
        case 1: return 'TB (Tidak Baik)';
        default: return '-';
    }
}

// ========== PROSES EXPORT EXCEL ==========
if (isset($_GET['export_excel'])) {
    $kelas_id = (int)$_GET['kelas_id'];
    $semester = mysqli_real_escape_string($conn, $_GET['semester']);
    $tahun_ajaran = mysqli_real_escape_string($conn, $_GET['tahun']);
    
    // Ambil data siswa
    $siswa = mysqli_query($conn, "SELECT s.id, s.nis, s.nama, s.jenis_kelamin, k.nama_kelas 
        FROM siswa s JOIN kelas k ON s.kelas_id = k.id 
        WHERE s.kelas_id = $kelas_id ORDER BY s.nama");
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Rekap Nilai Sikap');
    
    // Header utama
    $sheet->setCellValue('A1', 'REKAP NILAI SIKAP');
    $sheet->setCellValue('A2', 'MTs. AL-IHSAN BATUJAJAR');
    $sheet->setCellValue('A3', 'TAHUN PELAJARAN ' . $tahun_ajaran . ' - SEMESTER ' . $semester);
    $sheet->mergeCells('A1:P1');
    $sheet->mergeCells('A2:P2');
    $sheet->mergeCells('A3:P3');
    $sheet->getStyle('A1:A3')->getFont()->setBold(true);
    $sheet->getStyle('A1')->getFont()->setSize(14);
    
    // Kelas
    $kelas_nama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id=$kelas_id"))['nama_kelas'];
    $sheet->setCellValue('A5', 'Kelas : ' . $kelas_nama);
    $sheet->setCellValue('E5', 'MATA PELAJARAN: Al-Qur\'an Hadis');
    $sheet->getStyle('A5:E5')->getFont()->setBold(true);
    
    // Header tabel spiritual (kolom A-J) dan sosial (K-O)
    $sheet->setCellValue('A7', 'NO');
    $sheet->setCellValue('B7', 'NIS');
    $sheet->setCellValue('C7', 'NAMA SISWA');
    $sheet->setCellValue('D7', 'L/P');
    $sheet->setCellValue('E7', 'TAQWA');
    $sheet->setCellValue('F7', 'KEJUJURAN');
    $sheet->setCellValue('G7', 'DISIPLIN');
    $sheet->setCellValue('H7', 'SABAR');
    $sheet->setCellValue('I7', 'SYUKUR');
    $sheet->setCellValue('J7', 'TAWADHU');
    $sheet->setCellValue('K7', 'EMPATI');
    $sheet->setCellValue('L7', 'KERJASAMA');
    $sheet->setCellValue('M7', 'TOLERANSI');
    $sheet->setCellValue('N7', 'PERCAYA DIRI');
    $sheet->setCellValue('O7', 'KOMUNIKASI');
    $sheet->setCellValue('P7', 'KET.');
    
    $row = 8;
    $no = 1;
    while($s = mysqli_fetch_assoc($siswa)) {
        $sp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sikap_spiritual WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'"));
        $so = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sikap_sosial WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'"));
        
        $sheet->setCellValue('A' . $row, $no++);
        $sheet->setCellValue('B' . $row, $s['nis']);
        $sheet->setCellValue('C' . $row, $s['nama']);
        $sheet->setCellValue('D' . $row, $s['jenis_kelamin']);
        $sheet->setCellValue('E' . $row, konversi_nilai_sikap($sp['taqwa'] ?? null));
        $sheet->setCellValue('F' . $row, konversi_nilai_sikap($sp['kejujuran'] ?? null));
        $sheet->setCellValue('G' . $row, konversi_nilai_sikap($sp['disiplin'] ?? null));
        $sheet->setCellValue('H' . $row, konversi_nilai_sikap($sp['sabar'] ?? null));
        $sheet->setCellValue('I' . $row, konversi_nilai_sikap($sp['syukur'] ?? null));
        $sheet->setCellValue('J' . $row, konversi_nilai_sikap($sp['tawadhu'] ?? null));
        $sheet->setCellValue('K' . $row, konversi_nilai_sikap($so['empati'] ?? null));
        $sheet->setCellValue('L' . $row, konversi_nilai_sikap($so['kerjasama'] ?? null));
        $sheet->setCellValue('M' . $row, konversi_nilai_sikap($so['toleransi'] ?? null));
        $sheet->setCellValue('N' . $row, konversi_nilai_sikap($so['percaya_diri'] ?? null));
        $sheet->setCellValue('O' . $row, konversi_nilai_sikap($so['komunikasi'] ?? null));
        $sheet->setCellValue('P' . $row, '');
        $row++;
    }
    
    // Auto size kolom
    foreach(range('A','P') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
    
    // Border untuk tabel
    $lastRow = $row - 1;
    $sheet->getStyle('A7:P' . $lastRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $sheet->getStyle('A7:P7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
    $sheet->getStyle('A7:P7')->getFont()->setBold(true);
    
    // Footer
    $sheet->setCellValue('K' . ($row+2), 'Guru Mata Pelajaran');
    $sheet->setCellValue('K' . ($row+4), 'Ilham Rizqiawan, S.Pd.');
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="rekap_sikap_'.$kelas_nama.'_semester_'.$semester.'.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ========== TAMPILAN REKAP (WEB) ==========
$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");
$selected_kelas = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$tahun_aktif = get_tahun_ajaran_aktif($conn);
$semester_aktif = get_semester_aktif($conn);
$semester = isset($_GET['semester']) ? $_GET['semester'] : $semester_aktif;
$tahun_ajaran = isset($_GET['tahun']) ? $_GET['tahun'] : $tahun_aktif;

$siswa_data = [];
if ($selected_kelas) {
    $siswa_data = mysqli_query($conn, "SELECT s.id, s.nis, s.nama, s.jenis_kelamin 
        FROM siswa s WHERE s.kelas_id = $selected_kelas ORDER BY s.nama");
}
?>

<style>
@media print {
    .no-print { display: none !important; }
    .main-header, .navbar, .main-footer { display: none !important; }
    .form-container { box-shadow: none; border: 1px solid #ccc; margin: 0; padding: 0; }
    body { background: white; }
}
.rekap-table th, .rekap-table td { padding: 8px 5px; text-align: center; vertical-align: middle; }
.rekap-table .siswa-nama { text-align: left; }
@media (max-width: 768px) {
    .rekap-table th, .rekap-table td { font-size: 11px; padding: 4px; }
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-chart-pie"></i> Rekap Nilai Sikap</h2>
    <p class="page-subtitle">Rekapitulasi nilai spiritual dan sosial per siswa (dalam predikat). Dapat dicetak atau diexport ke Excel.</p>
</div>

<div class="form-container no-print">
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Pilih Kelas</label>
            <select name="kelas_id" class="form-select" required>
                <option value="0">-- Pilih Kelas --</option>
                <?php while($k = mysqli_fetch_assoc($kelas_list)): ?>
                    <option value="<?= $k['id'] ?>" <?= $selected_kelas == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Semester</label>
            <select name="semester" class="form-select">
                <option value="1" <?= $semester == '1' ? 'selected' : '' ?>>Semester 1</option>
                <option value="2" <?= $semester == '2' ? 'selected' : '' ?>>Semester 2</option>
            </select>
        </div>
        <div class="form-group">
            <label>Tahun Ajaran</label>
            <select name="tahun" class="form-select">
                <option value="2024/2025" <?= $tahun_ajaran == '2024/2025' ? 'selected' : '' ?>>2024/2025</option>
                <option value="2025/2026" <?= $tahun_ajaran == '2025/2026' ? 'selected' : '' ?>>2025/2026</option>
                <option value="2026/2027" <?= $tahun_ajaran == '2026/2027' ? 'selected' : '' ?>>2026/2027</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <?php if($selected_kelas): ?>
                <a href="?export_excel=1&kelas_id=<?= $selected_kelas ?>&semester=<?= $semester ?>&tahun=<?= $tahun_ajaran ?>" class="btn btn-outline"><i class="fas fa-file-excel"></i> Export Excel</a>
                <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> Cetak</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($selected_kelas && mysqli_num_rows($siswa_data) > 0): ?>
<div class="form-container">
    <div class="table-wrapper">
        <table class="modern-table rekap-table">
            <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">NIS</th>
                    <th rowspan="2">Nama Siswa</th>
                    <th rowspan="2">L/P</th>
                    <th colspan="6" style="text-align:center">Nilai Spiritual (KI-1)</th>
                    <th colspan="5" style="text-align:center">Nilai Sosial (KI-2)</th>
                </tr>
                <tr>
                    <th>Taqwa</th><th>Jujur</th><th>Disiplin</th><th>Sabar</th><th>Syukur</th><th>Tawadhu</th>
                    <th>Empati</th><th>Kerjasama</th><th>Toleransi</th><th>Percaya Diri</th><th>Komunikasi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($s = mysqli_fetch_assoc($siswa_data)):
                    $sp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sikap_spiritual WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'"));
                    $so = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sikap_sosial WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'"));
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($s['nis']) ?></td>
                    <td class="siswa-nama"><?= htmlspecialchars($s['nama']) ?></td>
                    <td><?= $s['jenis_kelamin'] ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($sp['taqwa'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($sp['kejujuran'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($sp['disiplin'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($sp['sabar'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($sp['syukur'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($sp['tawadhu'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($so['empati'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($so['kerjasama'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($so['toleransi'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($so['percaya_diri'] ?? null) ?></td>
                    <td class="text-center"><?= konversi_nilai_sikap($so['komunikasi'] ?? null) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif($selected_kelas): ?>
    <div class="form-container"><p>Tidak ada siswa di kelas ini.</p></div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>