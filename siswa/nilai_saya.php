<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('siswa');
$user_id = $_SESSION['user_id'];
$siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.id, k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.user_id=$user_id"));
$siswa_id = $siswa['id'];
$kelas = $siswa['nama_kelas'];

$tahun_aktif = get_tahun_ajaran_aktif($conn);
$semester_aktif = get_semester_aktif($conn);
$nilai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM nilai_akhir WHERE siswa_id=$siswa_id AND semester='$semester_aktif' AND tahun_ajaran='$tahun_aktif'"));
$spiritual = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sikap_spiritual WHERE siswa_id=$siswa_id AND semester='$semester_aktif' AND tahun_ajaran='$tahun_aktif'"));
$sosial = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sikap_sosial WHERE siswa_id=$siswa_id AND semester='$semester_aktif' AND tahun_ajaran='$tahun_aktif'"));

$title = 'Nilai Saya';
include '../includes/header.php';
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-chart-line"></i> Nilai Saya</h2>
    <p class="page-subtitle">Kelas <?= $kelas ?> - Semester <?= $semester_aktif ?> Tahun Ajaran <?= $tahun_aktif ?></p>
</div>

<div class="form-container">
    <div class="form-title">Nilai Pengetahuan (Kognitif)</div>
    <div class="stats-grid" style="grid-template-columns: repeat(4,1fr);">
        <div class="stat-card"><div><h3>SUM 1</h3><div class="stat-number"><?= $nilai['sum1'] ?? '-' ?></div></div></div>
        <div class="stat-card"><div><h3>SUM 2</h3><div class="stat-number"><?= $nilai['sum2'] ?? '-' ?></div></div></div>
        <div class="stat-card"><div><h3>SUM 3</h3><div class="stat-number"><?= $nilai['sum3'] ?? '-' ?></div></div></div>
        <div class="stat-card"><div><h3>SUM 4</h3><div class="stat-number"><?= $nilai['sum4'] ?? '-' ?></div></div></div>
    </div>
    <div class="stats-grid" style="grid-template-columns: repeat(3,1fr); margin-top:1rem;">
        <div class="stat-card"><div><h3>STS</h3><div class="stat-number"><?= $nilai['sts'] ?? '-' ?></div></div></div>
        <div class="stat-card"><div><h3>SAS</h3><div class="stat-number"><?= $nilai['sas_jadi'] ?? '-' ?></div></div></div>
        <div class="stat-card"><div><h3>SAT</h3><div class="stat-number"><?= $nilai['sat_jadi'] ?? '-' ?></div></div></div>
    </div>
</div>

<div class="form-container">
    <div class="form-title">Nilai Sikap Spiritual (KI-1)</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead><tr><th>Indikator</th><th>Nilai</th><th>Predikat</th></tr></thead>
            <tbody>
                <?php 
                $map_sp = [
                    'taqwa'=>'Taqwa / Ketaatan Beribadah',
                    'kejujuran'=>'Kejujuran',
                    'disiplin'=>'Disiplin',
                    'sabar'=>'Kesabaran',
                    'syukur'=>'Syukur',
                    'tawadhu'=>'Tawadhu\' (Rendah Hati)'
                ];
                foreach($map_sp as $key=>$label): 
                    $val = $spiritual[$key] ?? 3;
                    $predikat = ['TB','KB','C','B','SB'][$val-1] ?? '-';
                ?>
                <tr><td><?= $label ?></td><td><?= $val ?></td><td><?= $predikat ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="form-container">
    <div class="form-title">Nilai Sikap Sosial (KI-2)</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead><tr><th>Indikator</th><th>Nilai</th><th>Predikat</th></tr></thead>
            <tbody>
                <?php 
                $map_so = [
                    'empati'=>'Empati',
                    'kerjasama'=>'Kerjasama',
                    'toleransi'=>'Toleransi',
                    'percaya_diri'=>'Percaya Diri',
                    'komunikasi'=>'Komunikasi Efektif'
                ];
                foreach($map_so as $key=>$label): 
                    $val = $sosial[$key] ?? 3;
                    $predikat = ['TB','KB','C','B','SB'][$val-1] ?? '-';
                ?>
                <tr><td><?= $label ?></td><td><?= $val ?></td><td><?= $predikat ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>