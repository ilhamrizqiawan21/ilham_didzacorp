<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Input Nilai Sikap (Spiritual & Sosial)';
include '../includes/header.php';

// Fungsi untuk menyimpan nilai sikap (NULL jika tidak dipilih)
function simpan_sikap($conn, $siswa_id, $semester, $tahun_ajaran, $data, $jenis) {
    $table = ($jenis == 'spiritual') ? 'sikap_spiritual' : 'sikap_sosial';
    if ($jenis == 'spiritual') {
        $columns = ['taqwa', 'kejujuran', 'disiplin', 'sabar', 'syukur', 'tawadhu'];
    } else {
        $columns = ['empati', 'kerjasama', 'toleransi', 'percaya_diri', 'komunikasi'];
    }
    
    $values = [];
    foreach ($columns as $col) {
        $val = isset($data[$col]) && $data[$col] !== '' && $data[$col] !== null ? (int)$data[$col] : 'NULL';
        $values[] = $val === 'NULL' ? 'NULL' : $val;
    }
    
    $cek = mysqli_query($conn, "SELECT id FROM $table WHERE siswa_id=$siswa_id AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'");
    if (mysqli_num_rows($cek)) {
        $set = [];
        foreach ($columns as $idx => $col) {
            $set[] = "$col = {$values[$idx]}";
        }
        $query = "UPDATE $table SET " . implode(', ', $set) . " WHERE siswa_id=$siswa_id AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'";
    } else {
        $query = "INSERT INTO $table (siswa_id, semester, tahun_ajaran, " . implode(',', $columns) . ") 
                  VALUES ($siswa_id, '$semester', '$tahun_ajaran', " . implode(',', $values) . ")";
    }
    return mysqli_query($conn, $query);
}

// Proses simpan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_sikap'])) {
    $kelas_id = (int)$_POST['kelas_id'];
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $tahun_ajaran = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    
    $query_siswa = "SELECT id FROM siswa WHERE kelas_id = $kelas_id ORDER BY nama";
    $result_siswa = mysqli_query($conn, $query_siswa);
    
    while ($siswa = mysqli_fetch_assoc($result_siswa)) {
        $siswa_id = $siswa['id'];
        
        $spiritual = [];
        $spiritual_cols = ['taqwa', 'kejujuran', 'disiplin', 'sabar', 'syukur', 'tawadhu'];
        foreach ($spiritual_cols as $col) {
            $spiritual[$col] = isset($_POST['spiritual'][$col][$siswa_id]) ? $_POST['spiritual'][$col][$siswa_id] : null;
        }
        simpan_sikap($conn, $siswa_id, $semester, $tahun_ajaran, $spiritual, 'spiritual');
        
        $sosial = [];
        $sosial_cols = ['empati', 'kerjasama', 'toleransi', 'percaya_diri', 'komunikasi'];
        foreach ($sosial_cols as $col) {
            $sosial[$col] = isset($_POST['sosial'][$col][$siswa_id]) ? $_POST['sosial'][$col][$siswa_id] : null;
        }
        simpan_sikap($conn, $siswa_id, $semester, $tahun_ajaran, $sosial, 'sosial');
    }
    
    echo "<script>alert('Nilai sikap berhasil disimpan'); window.location.href='sikap?kelas_id=$kelas_id&semester=$semester&tahun=$tahun_ajaran';</script>";
}

// Filter
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
.sikap-table th, .sikap-table td { padding: 8px 5px; text-align: center; vertical-align: middle; }
.sikap-table select { width: 100px; padding: 5px; border-radius: 6px; }
.sikap-table .siswa-nama { text-align: left; }
.bulk-row { background-color: #f1f5f9; }
.bulk-select { width: 100px; padding: 4px; font-size: 0.75rem; }
@media (max-width: 768px) {
    .sikap-table select { width: 70px; font-size: 11px; }
    .sikap-table th, .sikap-table td { font-size: 11px; padding: 4px; }
    .bulk-select { width: 70px; }
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-heart"></i> Input Nilai Sikap</h2>
    <p class="page-subtitle">Nilai Spiritual (KI-1) dan Sosial (KI-2) dengan skala: SB=5, B=4, C=3, KB=2, TB=1. Biarkan kosong jika belum diisi. Gunakan dropdown di header untuk mengisi semua siswa sekaligus per aspek.</p>
</div>

<div class="form-container">
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
        </div>
    </form>
</div>

<?php if ($selected_kelas && mysqli_num_rows($siswa_data) > 0): ?>
<form method="POST">
    <input type="hidden" name="kelas_id" value="<?= $selected_kelas ?>">
    <input type="hidden" name="semester" value="<?= $semester ?>">
    <input type="hidden" name="tahun_ajaran" value="<?= $tahun_ajaran ?>">
    
    <!-- NILAI SPIRITUAL -->
    <div class="form-container">
        <div class="form-title"><i class="fas fa-pray"></i> Nilai Spiritual (KI-1)</div>
        <div class="table-wrapper">
            <table class="modern-table sikap-table">
                <thead>
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">NIS</th>
                        <th rowspan="2">Nama Siswa</th>
                        <th rowspan="2">L/P</th>
                        <th colspan="6">Aspek Spiritual</th>
                    </tr>
                    <tr>
                        <th>Taqwa</th><th>Kejujuran</th><th>Disiplin</th><th>Sabar</th><th>Syukur</th><th>Tawadhu'</th>
                    </tr>
                    <tr class="bulk-row">
                        <td colspan="4" style="text-align: center;">Isi Semua:</td>
                        <td><select class="bulk-select" data-target="taqwa"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                        <td><select class="bulk-select" data-target="kejujuran"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                        <td><select class="bulk-select" data-target="disiplin"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                        <td><select class="bulk-select" data-target="sabar"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                        <td><select class="bulk-select" data-target="syukur"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                        <td><select class="bulk-select" data-target="tawadhu"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    mysqli_data_seek($siswa_data, 0);
                    while($s = mysqli_fetch_assoc($siswa_data)):
                        $sql_sp = "SELECT * FROM sikap_spiritual WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'";
                        $sp = mysqli_fetch_assoc(mysqli_query($conn, $sql_sp));
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($s['nis']) ?></td>
                        <td class="siswa-nama"><?= htmlspecialchars($s['nama']) ?></td>
                        <td><?= $s['jenis_kelamin'] ?></td>
                        <td><select name="spiritual[taqwa][<?= $s['id'] ?>]" class="form-select spiritual-select" data-aspect="taqwa"><?= option_sikap($sp['taqwa'] ?? null) ?></select></td>
                        <td><select name="spiritual[kejujuran][<?= $s['id'] ?>]" class="form-select spiritual-select" data-aspect="kejujuran"><?= option_sikap($sp['kejujuran'] ?? null) ?></select></td>
                        <td><select name="spiritual[disiplin][<?= $s['id'] ?>]" class="form-select spiritual-select" data-aspect="disiplin"><?= option_sikap($sp['disiplin'] ?? null) ?></select></td>
                        <td><select name="spiritual[sabar][<?= $s['id'] ?>]" class="form-select spiritual-select" data-aspect="sabar"><?= option_sikap($sp['sabar'] ?? null) ?></select></td>
                        <td><select name="spiritual[syukur][<?= $s['id'] ?>]" class="form-select spiritual-select" data-aspect="syukur"><?= option_sikap($sp['syukur'] ?? null) ?></select></td>
                        <td><select name="spiritual[tawadhu][<?= $s['id'] ?>]" class="form-select spiritual-select" data-aspect="tawadhu"><?= option_sikap($sp['tawadhu'] ?? null) ?></select></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- NILAI SOSIAL -->
    <div class="form-container">
        <div class="form-title"><i class="fas fa-users"></i> Nilai Sosial (KI-2)</div>
        <div class="table-wrapper">
            <table class="modern-table sikap-table">
                <thead>
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">NIS</th>
                        <th rowspan="2">Nama Siswa</th>
                        <th rowspan="2">L/P</th>
                        <th colspan="5">Aspek Sosial</th>
                    </tr>
                    <tr>
                        <th>Empati</th><th>Kerjasama</th><th>Toleransi</th><th>Percaya Diri</th><th>Komunikasi</th>
                    </tr>
                    <tr class="bulk-row">
                        <td colspan="4" style="text-align: center;">Isi Semua:</td>
                        <td><select class="bulk-select" data-target="empati"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                        <td><select class="bulk-select" data-target="kerjasama"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                        <td><select class="bulk-select" data-target="toleransi"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                        <td><select class="bulk-select" data-target="percaya_diri"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                        <td><select class="bulk-select" data-target="komunikasi"><option value="">-- Pilih --</option><?= option_sikap_bulk() ?></select></td>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no2 = 1;
                    mysqli_data_seek($siswa_data, 0);
                    while($s = mysqli_fetch_assoc($siswa_data)):
                        $sql_so = "SELECT * FROM sikap_sosial WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'";
                        $so = mysqli_fetch_assoc(mysqli_query($conn, $sql_so));
                    ?>
                    <tr>
                        <td><?= $no2++ ?></td>
                        <td><?= htmlspecialchars($s['nis']) ?></td>
                        <td class="siswa-nama"><?= htmlspecialchars($s['nama']) ?></td>
                        <td><?= $s['jenis_kelamin'] ?></td>
                        <td><select name="sosial[empati][<?= $s['id'] ?>]" class="form-select sosial-select" data-aspect="empati"><?= option_sikap($so['empati'] ?? null) ?></select></td>
                        <td><select name="sosial[kerjasama][<?= $s['id'] ?>]" class="form-select sosial-select" data-aspect="kerjasama"><?= option_sikap($so['kerjasama'] ?? null) ?></select></td>
                        <td><select name="sosial[toleransi][<?= $s['id'] ?>]" class="form-select sosial-select" data-aspect="toleransi"><?= option_sikap($so['toleransi'] ?? null) ?></select></td>
                        <td><select name="sosial[percaya_diri][<?= $s['id'] ?>]" class="form-select sosial-select" data-aspect="percaya_diri"><?= option_sikap($so['percaya_diri'] ?? null) ?></select></td>
                        <td><select name="sosial[komunikasi][<?= $s['id'] ?>]" class="form-select sosial-select" data-aspect="komunikasi"><?= option_sikap($so['komunikasi'] ?? null) ?></select></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="form-container">
        <button type="submit" name="simpan_sikap" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Semua Nilai Sikap</button>
    </div>
</form>
<?php elseif($selected_kelas): ?>
    <div class="form-container"><p>Tidak ada siswa di kelas ini.</p></div>
<?php endif; ?>

<?php 
// Fungsi helper untuk dropdown nilai sikap (dengan opsi kosong)
function option_sikap($selected) {
    $options = [
        '' => '-- Pilih --',
        5 => 'SB (5)',
        4 => 'B (4)',
        3 => 'C (3)',
        2 => 'KB (2)',
        1 => 'TB (1)'
    ];
    $html = '';
    foreach ($options as $val => $label) {
        $sel = ($selected !== null && $selected == $val) ? 'selected' : '';
        $html .= "<option value=\"$val\" $sel>$label</option>";
    }
    return $html;
}

// Fungsi untuk dropdown bulk (tanpa label panjang)
function option_sikap_bulk() {
    $options = [
        '' => '-- Pilih --',
        5 => 'SB (5)',
        4 => 'B (4)',
        3 => 'C (3)',
        2 => 'KB (2)',
        1 => 'TB (1)'
    ];
    $html = '';
    foreach ($options as $val => $label) {
        $html .= "<option value=\"$val\">$label</option>";
    }
    return $html;
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Untuk spiritual
    document.querySelectorAll('.bulk-select').forEach(bulk => {
        bulk.addEventListener('change', function() {
            let targetAspect = this.getAttribute('data-target');
            let selectedValue = this.value;
            if (!selectedValue) return;
            // Pilih semua dropdown spiritual atau sosial sesuai konteks
            // Karena ada dua tabel, kita perlu selektor berdasarkan class
            let selects = document.querySelectorAll(`.spiritual-select[data-aspect="${targetAspect}"], .sosial-select[data-aspect="${targetAspect}"]`);
            selects.forEach(sel => {
                sel.value = selectedValue;
            });
            // Reset bulk select ke default
            this.value = '';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>