<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('siswa');
$user_id = $_SESSION['user_id'];
$siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM siswa WHERE user_id=$user_id"));
$siswa_id = $siswa['id'];

$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun_bulan = explode('-', $bulan);
$tahun = $tahun_bulan[0];
$bulan_num = $tahun_bulan[1];

$absensi = mysqli_query($conn, "SELECT p.tanggal, p.topik, a.status FROM absensi a JOIN pertemuan p ON a.pertemuan_id = p.id WHERE a.siswa_id=$siswa_id AND DATE_FORMAT(p.tanggal, '%Y-%m')='$bulan' ORDER BY p.tanggal");
$hadir = $sakit = $izin = $alpha = 0;
while($row = mysqli_fetch_assoc($absensi)) {
    if($row['status']=='hadir') $hadir++;
    elseif($row['status']=='sakit') $sakit++;
    elseif($row['status']=='izin') $izin++;
    elseif($row['status']=='alpha') $alpha++;
}
mysqli_data_seek($absensi, 0);
$title = 'Absensi Saya';
include '../includes/header.php';
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-calendar-check"></i> Absensi Saya</h2>
    <p class="page-subtitle">Rekap kehadiran per bulan</p>
</div>

<div class="form-container">
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Pilih Bulan</label>
            <select name="bulan" class="form-select" onchange="this.form.submit()">
                <?php for ($m=1;$m<=12;$m++): $val = date('Y-m', strtotime("$tahun-$m-01")); $sel = ($val==$bulan)?'selected':''; ?>
                <option value="<?= $val ?>" <?= $sel ?>><?= date('F Y', strtotime($val)) ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </form>
</div>

<div class="form-container">
    <div class="form-title">Rekap Bulan <?= date('F Y', strtotime($bulan)) ?></div>
    <div class="stats-grid" style="grid-template-columns: repeat(4,1fr);">
        <div class="stat-card"><div><h3>Hadir</h3><div class="stat-number"><?= $hadir ?></div></div></div>
        <div class="stat-card"><div><h3>Sakit</h3><div class="stat-number"><?= $sakit ?></div></div></div>
        <div class="stat-card"><div><h3>Izin</h3><div class="stat-number"><?= $izin ?></div></div></div>
        <div class="stat-card"><div><h3>Alpha</h3><div class="stat-number"><?= $alpha ?></div></div></div>
    </div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead><tr><th>Tanggal</th><th>Topik Pertemuan</th><th>Status</th></tr></thead>
            <tbody>
                <?php while($ab = mysqli_fetch_assoc($absensi)): ?>
                <tr>
                    <td><?= tgl_indonesia($ab['tanggal']) ?></td>
                    <td><?= htmlspecialchars($ab['topik']) ?></td>
                    <td><?= status_badge($ab['status']) ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($absensi)==0): ?>
                <tr><td colspan="3">Belum ada data absensi untuk bulan ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>