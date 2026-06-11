<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Absensi Format Tahunan (Excel-like)';
include '../includes/header.php';

// ========== FUNGSI BANTU ==========
function get_tahun_kalender($tahun_ajaran, $bulan) {
    list($tahun1, $tahun2) = explode('/', $tahun_ajaran);
    return ($bulan >= 7) ? (int)$tahun1 : (int)$tahun2;
}

function get_pertemuan_id($conn, $tahun_kalender, $bulan, $minggu_ke) {
    $hari = 1 + ($minggu_ke - 1) * 7;
    $tanggal = date("$tahun_kalender-$bulan-$hari");
    $query = "SELECT id FROM pertemuan WHERE tanggal='$tanggal'";
    $res = mysqli_query($conn, $query);
    if (mysqli_num_rows($res)) {
        $row = mysqli_fetch_assoc($res);
        return $row['id'];
    } else {
        $topik = "Minggu ke-$minggu_ke Bulan " . date('F', mktime(0,0,0,$bulan,1,$tahun_kalender));
        mysqli_query($conn, "INSERT INTO pertemuan (bab_id, tanggal, topik, jam_ke) VALUES (NULL, '$tanggal', '$topik', 1)");
        return mysqli_insert_id($conn);
    }
}

function get_status_absen_minggu($conn, $siswa_id, $tahun_kalender, $bulan, $minggu) {
    $hari = 1 + ($minggu - 1) * 7;
    $tanggal = date("$tahun_kalender-$bulan-$hari");
    $query = "SELECT a.status FROM absensi a JOIN pertemuan p ON a.pertemuan_id = p.id WHERE a.siswa_id=$siswa_id AND p.tanggal='$tanggal'";
    $res = mysqli_query($conn, $query);
    if ($res && mysqli_num_rows($res)) {
        $row = mysqli_fetch_assoc($res);
        return $row['status'];
    }
    return null;
}

// ========== PROSES SIMPAN ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_absensi'])) {
    $tahun_ajaran = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    $kelas_id = (int)$_POST['kelas_id'];
    $bulan = (int)$_POST['bulan'];
    $tahun_kalender = get_tahun_kalender($tahun_ajaran, $bulan);
    
    foreach ($_POST['status'] as $siswa_id => $minggu_status) {
        foreach ($minggu_status as $minggu => $status) {
            if ($status === '') continue;
            $status = mysqli_real_escape_string($conn, $status);
            $pertemuan_id = get_pertemuan_id($conn, $tahun_kalender, $bulan, $minggu);
            if ($pertemuan_id) {
                $cek = mysqli_query($conn, "SELECT id FROM absensi WHERE siswa_id=$siswa_id AND pertemuan_id=$pertemuan_id");
                if (mysqli_num_rows($cek)) {
                    mysqli_query($conn, "UPDATE absensi SET status='$status' WHERE siswa_id=$siswa_id AND pertemuan_id=$pertemuan_id");
                } else {
                    mysqli_query($conn, "INSERT INTO absensi (siswa_id, pertemuan_id, status) VALUES ($siswa_id, $pertemuan_id, '$status')");
                }
            }
        }
    }
    echo "<script>alert('Absensi berhasil disimpan'); window.location.href='absensi?kelas_id=$kelas_id&tahun_ajaran=$tahun_ajaran&bulan=$bulan';</script>";
}

// ========== DATA UNTUK DITAMPILKAN ==========
$tahun_ajaran_aktif = get_tahun_ajaran_aktif($conn);
$semester_aktif = get_semester_aktif($conn);

$tahun_ajaran = isset($_GET['tahun_ajaran']) ? mysqli_real_escape_string($conn, $_GET['tahun_ajaran']) : $tahun_ajaran_aktif;
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');

$list_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas");

$siswa = [];
$kelas_nama = '';
if ($kelas_id > 0) {
    $kelas_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id=$kelas_id"));
    $kelas_nama = $kelas_info['nama_kelas'];
    $siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE kelas_id=$kelas_id ORDER BY nama");
}

$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$daftar_tahun_ajaran = ['2024/2025', '2025/2026', '2026/2027', '2027/2028'];
?>

<style>
/* ========== PERBAIKAN TAMPILAN ABSENSI ========== */
/* Tabel lebih rapi */
.modern-table td, .modern-table th {
    padding: 0.6rem 0.4rem;
    font-size: 0.8rem;
    vertical-align: middle;
}
.status-select {
    width: 85px;
    padding: 0.3rem 0.2rem;
    font-size: 0.75rem;
    border-radius: 20px;
    border: 1px solid #cbd5e1;
    background: white;
    transition: all 0.2s;
    cursor: pointer;
}
.status-select:hover {
    border-color: var(--primary-500);
    background-color: #fefce8;
}
.bulk-action-row {
    background-color: #f1f5f9;
}
.bulk-action-select {
    width: 90px;
    padding: 0.3rem;
    font-size: 0.7rem;
    border-radius: 20px;
    border: 1px solid #cbd5e1;
}
/* Kolom total S/I/A  */
.modern-table td:last-child,
.modern-table td:nth-last-child(2),
.modern-table td:nth-last-child(3) {
    background-color: #fefce8;
    font-weight: 600;
    text-align: center;
}
/* Responsif: tabel bisa scroll horizontal */
.table-wrapper {
    overflow-x: auto;
    border-radius: 1rem;
    margin-bottom: 1rem;
}
@media (max-width: 768px) {
    .modern-table {
        min-width: 700px;
    }
    .status-select {
        width: 75px;
        font-size: 0.7rem;
    }
}
/* Tooltip / informasi kecil */
.info-tooltip {
    font-size: 0.7rem;
    color: #475569;
    cursor: help;
}
.badge-status {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
}
.bg-hadir { background: #d1fae5; color: #065f46; }
.bg-sakit { background: #fed7aa; color: #9b2c1d; }
.bg-izin { background: #dbeafe; color: #1e40af; }
.bg-alpha { background: #fee2e2; color: #991b1b; }
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-calendar-alt"></i> Absensi Format Tahunan</h2>
    <p class="page-subtitle">Input kehadiran siswa per minggu. Gunakan dropdown massal untuk mengisi cepat.</p>
</div>

<div class="form-container">
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Pilih Kelas</label>
            <select name="kelas_id" class="form-select" required>
                <option value="">-- Pilih Kelas --</option>
                <?php while($k = mysqli_fetch_assoc($list_kelas)): ?>
                    <option value="<?= $k['id'] ?>" <?= $kelas_id==$k['id']?'selected':'' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Tahun Ajaran</label>
            <select name="tahun_ajaran" class="form-select">
                <?php foreach ($daftar_tahun_ajaran as $ta): ?>
                    <option value="<?= $ta ?>" <?= $tahun_ajaran == $ta ? 'selected' : '' ?>><?= $ta ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Bulan</label>
            <select name="bulan" class="form-select">
                <?php for ($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= $bulan==$m?'selected':'' ?>><?= $nama_bulan[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary"><i class="fas fa-eye"></i> Tampilkan</button>
        </div>
    </form>
    <p class="info-tooltip" style="margin-top:0.5rem;">
        <i class="fas fa-info-circle"></i> Semester aktif: <?= $semester_aktif == '1' ? 'Semester 1' : 'Semester 2' ?> (<?= $tahun_ajaran_aktif ?>)
    </p>
</div>

<?php if ($kelas_id > 0 && $siswa && mysqli_num_rows($siswa) > 0): 
    $tahun_kalender = get_tahun_kalender($tahun_ajaran, $bulan);
?>
<div class="form-container">
    <div class="form-title">
        <i class="fas fa-users"></i> Kelas: <?= htmlspecialchars($kelas_nama) ?> - 
        <?= $nama_bulan[$bulan] ?> <?= $tahun_kalender ?> 
        (TA <?= $tahun_ajaran ?>)
    </div>
    <form method="POST">
        <input type="hidden" name="tahun_ajaran" value="<?= $tahun_ajaran ?>">
        <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
        <input type="hidden" name="bulan" value="<?= $bulan ?>">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th rowspan="2">#</th>
                        <th rowspan="2">Nama Siswa</th>
                        <th rowspan="2">L/P</th>
                        <th colspan="4" style="text-align: center;">Minggu ke-</th>
                        <th rowspan="2">S</th>
                        <th rowspan="2">I</th>
                        <th rowspan="2">A</th>
                    </tr>
                    <tr>
                        <th>1</th><th>2</th><th>3</th><th>4</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Baris aksi massal -->
                    <tr class="bulk-action-row">
                        <td colspan="3" style="text-align: right;"><small>Isi semua:</small></td>
                        <?php for ($minggu=1; $minggu<=4; $minggu++): ?>
                            <td style="text-align: center;">
                                <select class="bulk-action-select" data-minggu="<?= $minggu ?>">
                                    <option value="">--</option>
                                    <option value="hadir">Hadir</option>
                                    <option value="sakit">Sakit</option>
                                    <option value="izin">Izin</option>
                                    <option value="alpha">Alpha</option>
                                </select>
                            </td>
                        <?php endfor; ?>
                        <td colspan="3"></td>
                    </tr>
                    
                    <?php 
                    $no = 1;
                    while ($s = mysqli_fetch_assoc($siswa)):
                        $jk = isset($s['jenis_kelamin']) ? $s['jenis_kelamin'] : '-';
                        $total_s = $total_i = $total_a = 0;
                    ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($s['nama']) ?></strong></td>
                        <td style="text-align: center;"><?= $jk == 'L' ? 'L' : 'P' ?></td>
                        <?php for ($minggu=1; $minggu<=4; $minggu++):
                            $status = get_status_absen_minggu($conn, $s['id'], $tahun_kalender, $bulan, $minggu);
                            if ($status == 'sakit') $total_s++;
                            elseif ($status == 'izin') $total_i++;
                            elseif ($status == 'alpha') $total_a++;
                        ?>
                            <td style="text-align: center;">
                                <select name="status[<?= $s['id'] ?>][<?= $minggu ?>]" class="form-select status-select">
                                    <option value="">--</option>
                                    <option value="hadir" <?= $status=='hadir'?'selected':'' ?>>Hadir</option>
                                    <option value="sakit" <?= $status=='sakit'?'selected':'' ?>>Sakit</option>
                                    <option value="izin" <?= $status=='izin'?'selected':'' ?>>Izin</option>
                                    <option value="alpha" <?= $status=='alpha'?'selected':'' ?>>Alpha</option>
                                </select>
                            </td>
                        <?php endfor; ?>
                        <td style="text-align: center; font-weight: bold;"><?= $total_s ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= $total_i ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= $total_a ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="btn-group" style="margin-top: 1.5rem; justify-content: flex-end;">
            <button type="submit" name="simpan_absensi" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Absensi Bulan <?= $nama_bulan[$bulan] ?>
            </button>
        </div>
    </form>
</div>
<?php elseif($kelas_id > 0): ?>
    <div class="form-container"><p><i class="fas fa-exclamation-triangle"></i> Tidak ada siswa di kelas yang dipilih.</p></div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bulk action: isi semua dropdown siswa pada minggu yang sama
    const bulkSelects = document.querySelectorAll('.bulk-action-select');
    bulkSelects.forEach(select => {
        select.addEventListener('change', function() {
            let selectedStatus = this.value;
            if (!selectedStatus) return;
            const minggu = this.getAttribute('data-minggu');
            // Semua dropdown yang memiliki name status[?][minggu]
            const targetSelects = document.querySelectorAll(`select[name$="[${minggu}]"]`);
            targetSelects.forEach(s => {
                s.value = selectedStatus;
                // Trigger change event untuk update tampilan jika perlu
                s.dispatchEvent(new Event('change'));
            });
            // Reset bulk select ke default
            this.value = '';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>