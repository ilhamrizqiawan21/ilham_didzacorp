<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Rekap Absensi per Kelas';
include '../includes/header.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ========== PROSES EXPORT EXCEL ==========
if (isset($_GET['export_excel'])) {
    $kelas_id = (int)$_GET['kelas_id'];
    $bulan = $_GET['bulan'];
    $tahun_bulan = explode('-', $bulan);
    $tahun = $tahun_bulan[0];
    $bulan_angka = $tahun_bulan[1];
    
    // Ambil siswa dalam kelas tertentu
    $siswa = mysqli_query($conn, "SELECT s.id, s.nis, s.nama, k.nama_kelas 
        FROM siswa s JOIN kelas k ON s.kelas_id = k.id 
        WHERE s.kelas_id = $kelas_id ORDER BY s.nama");
    
    // Ambil pertemuan dalam bulan ini
    $pertemuan = mysqli_query($conn, "SELECT * FROM pertemuan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan' ORDER BY tanggal");
    $pertemuan_data = [];
    while($p = mysqli_fetch_assoc($pertemuan)) $pertemuan_data[] = $p;
    
    // Kumpulkan data rekap
    $rekap = [];
    while ($s = mysqli_fetch_assoc($siswa)) {
        $siswa_id = $s['id'];
        $rekap[$siswa_id] = [
            'nama' => $s['nama'],
            'nis' => $s['nis'],
            'kelas' => $s['nama_kelas'],
            'absensi' => []
        ];
        $query_absen = "SELECT a.status, p.tanggal FROM absensi a 
                        JOIN pertemuan p ON a.pertemuan_id = p.id 
                        WHERE a.siswa_id = $siswa_id AND DATE_FORMAT(p.tanggal, '%Y-%m') = '$bulan'";
        $absen = mysqli_query($conn, $query_absen);
        while ($ab = mysqli_fetch_assoc($absen)) {
            $rekap[$siswa_id]['absensi'][$ab['tanggal']] = $ab['status'];
        }
    }
    
    // Buat spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Rekap Absensi');
    
    // Header utama
    $kelas_nama = !empty($rekap) ? reset($rekap)['kelas'] : 'Kelas';
    $sheet->setCellValue('A1', 'REKAP ABSENSI SISWA');
    $sheet->setCellValue('A2', 'MTs. AL-IHSAN BATUJAJAR');
    $sheet->setCellValue('A3', 'KELAS ' . strtoupper($kelas_nama) . ' - BULAN ' . strtoupper(date('F Y', strtotime($bulan))));
    $sheet->mergeCells('A1:' . $sheet->getHighestColumn() . '1');
    $sheet->mergeCells('A2:' . $sheet->getHighestColumn() . '2');
    $sheet->mergeCells('A3:' . $sheet->getHighestColumn() . '3');
    $sheet->getStyle('A1:A3')->getFont()->setBold(true);
    $sheet->getStyle('A1')->getFont()->setSize(14);
    
    // Header tabel
    $col = 4;
    $sheet->setCellValueByColumnAndRow(1, 5, 'NO');
    $sheet->setCellValueByColumnAndRow(2, 5, 'NIS');
    $sheet->setCellValueByColumnAndRow(3, 5, 'NAMA SISWA');
    $col = 4;
    foreach ($pertemuan_data as $p) {
        $sheet->setCellValueByColumnAndRow($col, 5, tgl_indonesia($p['tanggal']) . "\n" . $p['topik']);
        $sheet->getStyleByColumnAndRow($col, 5)->getAlignment()->setWrapText(true);
        $col++;
    }
    $sheet->setCellValueByColumnAndRow($col, 5, 'HADIR');
    $sheet->setCellValueByColumnAndRow($col+1, 5, 'SAKIT');
    $sheet->setCellValueByColumnAndRow($col+2, 5, 'IZIN');
    $sheet->setCellValueByColumnAndRow($col+3, 5, 'ALPHA');
    
    // Data
    $row = 6;
    $no = 1;
    foreach ($rekap as $siswa_id => $data) {
        $hadir = $sakit = $izin = $alpha = 0;
        $col = 1;
        $sheet->setCellValueByColumnAndRow($col++, $row, $no++);
        $sheet->setCellValueByColumnAndRow($col++, $row, $data['nis']);
        $sheet->setCellValueByColumnAndRow($col++, $row, $data['nama']);
        foreach ($pertemuan_data as $p) {
            $status = isset($data['absensi'][$p['tanggal']]) ? $data['absensi'][$p['tanggal']] : '-';
            if ($status == 'hadir') $hadir++;
            elseif ($status == 'sakit') $sakit++;
            elseif ($status == 'izin') $izin++;
            elseif ($status == 'alpha') $alpha++;
            $sheet->setCellValueByColumnAndRow($col++, $row, $status);
        }
        $sheet->setCellValueByColumnAndRow($col++, $row, $hadir);
        $sheet->setCellValueByColumnAndRow($col++, $row, $sakit);
        $sheet->setCellValueByColumnAndRow($col++, $row, $izin);
        $sheet->setCellValueByColumnAndRow($col++, $row, $alpha);
        $row++;
    }
    
    // Style header tabel
    $lastColumn = $col - 1;
    $headerRange = 'A5:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumn) . '5';
    $sheet->getStyle($headerRange)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    
    // Border untuk data
    $dataRange = 'A6:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumn) . ($row-1);
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    
    // Auto size kolom
    foreach(range(1, $lastColumn) as $colIdx) {
        $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
    }
    
    // Footer
    $sheet->setCellValueByColumnAndRow($lastColumn-3, $row+2, 'Guru Mata Pelajaran');
    $sheet->setCellValueByColumnAndRow($lastColumn-3, $row+4, 'Ilham Rizqiawan, S.Pd.');
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="rekap_absensi_'.$kelas_nama.'_'.$bulan.'.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ========== TAMPILAN WEB ==========
$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");
$selected_kelas = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun_bulan = explode('-', $bulan);
$tahun = $tahun_bulan[0];
$bulan_angka = $tahun_bulan[1];

$rekap = [];
$pertemuan_data = [];
$kelas_nama = '';

if ($selected_kelas > 0) {
    // Ambil nama kelas
    $kelas_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id=$selected_kelas"));
    $kelas_nama = $kelas_info['nama_kelas'];
    
    // Ambil siswa dalam kelas tersebut
    $siswa = mysqli_query($conn, "SELECT s.id, s.nis, s.nama 
        FROM siswa s WHERE s.kelas_id = $selected_kelas ORDER BY s.nama");
    
    // Ambil pertemuan dalam bulan ini
    $pertemuan = mysqli_query($conn, "SELECT * FROM pertemuan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan' ORDER BY tanggal");
    while($p = mysqli_fetch_assoc($pertemuan)) $pertemuan_data[] = $p;
    
    // Kumpulkan data rekap
    while ($s = mysqli_fetch_assoc($siswa)) {
        $siswa_id = $s['id'];
        $rekap[$siswa_id] = [
            'nama' => $s['nama'],
            'nis' => $s['nis'],
            'absensi' => []
        ];
        $query_absen = "SELECT a.status, p.tanggal FROM absensi a 
                        JOIN pertemuan p ON a.pertemuan_id = p.id 
                        WHERE a.siswa_id = $siswa_id AND DATE_FORMAT(p.tanggal, '%Y-%m') = '$bulan'";
        $absen = mysqli_query($conn, $query_absen);
        while ($ab = mysqli_fetch_assoc($absen)) {
            $rekap[$siswa_id]['absensi'][$ab['tanggal']] = $ab['status'];
        }
    }
}
?>

<style>
/* Perbaikan tabel rekap */
.rekap-table th, .rekap-table td {
    padding: 0.5rem 0.3rem;
    vertical-align: middle;
    font-size: 0.85rem;
}
.rekap-table th {
    white-space: nowrap;
    background-color: var(--primary-50);
}
.rekap-table td {
    text-align: center;
}
.rekap-table td:first-child,
.rekap-table td:nth-child(2),
.rekap-table td:nth-child(3) {
    text-align: left;
    white-space: nowrap;
}
/* Badge tanpa ikon */
.badge-hadir, .badge-sakit, .badge-izin, .badge-alpha {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}
.badge-hadir { background: #d1fae5; color: #065f46; }
.badge-sakit { background: #fed7aa; color: #9b2c1d; }
.badge-izin { background: #dbeafe; color: #1e40af; }
.badge-alpha { background: #fee2e2; color: #991b1b; }
/* Responsif: scroll horizontal */
.table-wrapper {
    overflow-x: auto;
    border-radius: 1rem;
}
@media (max-width: 768px) {
    .rekap-table {
        min-width: 700px;
    }
    .rekap-table th, .rekap-table td {
        font-size: 0.75rem;
        padding: 0.4rem 0.2rem;
    }
}
@media print {
    .no-print { display: none !important; }
    .main-header, .navbar, .main-footer, .btn-group, .form-container .btn-group { display: none !important; }
    .form-container { box-shadow: none; border: 1px solid #ccc; margin: 0; padding: 0; }
    body { background: white; }
    .badge-hadir, .badge-sakit, .badge-izin, .badge-alpha { background: none !important; border: 1px solid #000; color: black; }
}
</style>

<div class="page-header no-print">
    <h2 class="page-title"><i class="fas fa-chart-pie"></i> Rekap Absensi per Kelas</h2>
    <p class="page-subtitle">Rekapitulasi kehadiran siswa per pertemuan berdasarkan kelas</p>
</div>

<!-- Filter Kelas dan Bulan -->
<div class="form-container no-print" style="margin-bottom: 1rem;">
    <form method="GET" class="form-row" style="align-items: flex-end;">
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
            <label>Pilih Bulan</label>
            <select name="bulan" class="form-select">
                <?= bulan_options($bulan) ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <?php if($selected_kelas > 0 && !empty($rekap)): ?>
                <a href="?export_excel=1&kelas_id=<?= $selected_kelas ?>&bulan=<?= $bulan ?>" class="btn btn-outline"><i class="fas fa-file-excel"></i> Export Excel</a>
                <button type="button" id="btnCetak" class="btn btn-outline"><i class="fas fa-print"></i> Cetak</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($selected_kelas > 0): ?>
    <?php if (!empty($rekap)): ?>
    <div class="form-container">
        <div class="form-title">Kelas <?= htmlspecialchars($kelas_nama) ?> - Bulan <?= date('F Y', strtotime($bulan)) ?></div>
        <div class="table-wrapper">
            <table class="modern-table rekap-table" id="tabelRekap">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <?php foreach ($pertemuan_data as $p): ?>
                            <th><?= tgl_indonesia($p['tanggal']) ?><br><small><?= htmlspecialchars($p['topik']) ?></small></th>
                        <?php endforeach; ?>
                        <th>Hadir</th>
                        <th>Sakit</th>
                        <th>Izin</th>
                        <th>Alpha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($rekap as $siswa_id => $data): 
                        $hadir = $sakit = $izin = $alpha = 0;
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($data['nis']) ?></td>
                        <td><strong><?= htmlspecialchars($data['nama']) ?></strong></td>
                        <?php foreach ($pertemuan_data as $p): 
                            $status = isset($data['absensi'][$p['tanggal']]) ? $data['absensi'][$p['tanggal']] : '-';
                            if ($status == 'hadir') { $hadir++; $label = 'Hadir'; $class = 'badge-hadir'; }
                            elseif ($status == 'sakit') { $sakit++; $label = 'Sakit'; $class = 'badge-sakit'; }
                            elseif ($status == 'izin') { $izin++; $label = 'Izin'; $class = 'badge-izin'; }
                            elseif ($status == 'alpha') { $alpha++; $label = 'Alpha'; $class = 'badge-alpha'; }
                            else { $label = '-'; $class = ''; }
                        ?>
                            <td><?php if ($label != '-') echo '<span class="'.$class.'">'.$label.'</span>'; else echo '-'; ?></td>
                        <?php endforeach; ?>
                        <td><span class="badge-hadir"><?= $hadir ?></span></td>
                        <td><span class="badge-sakit"><?= $sakit ?></span></td>
                        <td><span class="badge-izin"><?= $izin ?></span></td>
                        <td><span class="badge-alpha"><?= $alpha ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="form-container"><p>Tidak ada data absensi untuk kelas <?= htmlspecialchars($kelas_nama) ?> pada bulan <?= date('F Y', strtotime($bulan)) ?>.</p></div>
    <?php endif; ?>
<?php endif; ?>

<script>
document.getElementById('btnCetak')?.addEventListener('click', function() {
    window.print();
});
</script>

<?php include '../includes/footer.php'; ?>