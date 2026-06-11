<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Dashboard Guru';
include '../includes/header.php';

// ========== PENGATURAN SEMESTER AKTIF ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_pengaturan'])) {
    $tahun = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    set_pengaturan($conn, 'tahun_ajaran_aktif', $tahun);
    set_pengaturan($conn, 'semester_aktif', $semester);
    echo "<script>alert('Pengaturan disimpan'); window.location.href='dashboard';</script>";
}

$tahun_aktif = get_tahun_ajaran_aktif($conn);
$semester_aktif = get_semester_aktif($conn);

// Hitung statistik
$total_siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa"))['total'];
$total_bab = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM bab"))['total'];
$total_pertemuan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pertemuan"))['total'];

// Data untuk grafik kehadiran (30 hari terakhir)
$labels = $hadir_data = $sakit_data = $izin_data = $alpha_data = [];
for ($i = 29; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($tanggal));
    $query = "SELECT 
                SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) as alpha
              FROM absensi a 
              JOIN pertemuan p ON a.pertemuan_id = p.id 
              WHERE p.tanggal = '$tanggal'";
    $res = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($res);
    $hadir_data[] = (int)$row['hadir'];
    $sakit_data[] = (int)$row['sakit'];
    $izin_data[] = (int)$row['izin'];
    $alpha_data[] = (int)$row['alpha'];
}

// Rata-rata nilai per kelas berdasarkan pengaturan aktif
$kelas_nilai = [];
$kelas_list = mysqli_query($conn, "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
while ($k = mysqli_fetch_assoc($kelas_list)) {
    $kelas_id = $k['id'];
    $nama_kelas = $k['nama_kelas'];
    $query = "SELECT 
                AVG(sum1) as avg_sum1, AVG(sum2) as avg_sum2, AVG(sum3) as avg_sum3, AVG(sum4) as avg_sum4,
                AVG(sts) as avg_sts, AVG(sas_jadi) as avg_sas, AVG(sat_jadi) as avg_sat
              FROM nilai_akhir na
              JOIN siswa s ON na.siswa_id = s.id
              WHERE s.kelas_id = $kelas_id AND na.semester = '$semester_aktif' AND na.tahun_ajaran = '$tahun_aktif'";
    $res = mysqli_query($conn, $query);
    $avg = mysqli_fetch_assoc($res);
    $kelas_nilai[] = [
        'kelas' => $nama_kelas,
        'sum1' => round($avg['avg_sum1'] ?? 0, 1),
        'sum2' => round($avg['avg_sum2'] ?? 0, 1),
        'sum3' => round($avg['avg_sum3'] ?? 0, 1),
        'sum4' => round($avg['avg_sum4'] ?? 0, 1),
        'sts' => round($avg['avg_sts'] ?? 0, 1),
        'sas' => round($avg['avg_sas'] ?? 0, 1),
        'sat' => round($avg['avg_sat'] ?? 0, 1),
    ];
}

// ========== AMBIL RIWAYAT LOGIN TERBARU ==========
$log_login = mysqli_query($conn, "SELECT * FROM log_login ORDER BY login_time DESC LIMIT 10");

// ========== AMBIL 2 PENGUMUMAN TERBARU ==========
$pengumuman_terbaru = mysqli_query($conn, "SELECT * FROM pengumuman ORDER BY created_at DESC LIMIT 2");
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Tambahan CSS khusus dashboard (tidak mengganggu navigasi) -->
<style>
/* Perbaikan grafik di mobile */
@media (max-width: 768px) {
    #kehadiranChart {
        height: 250px !important;
    }
}
/* Indikator scroll horizontal pada tabel */
.table-wrapper {
    position: relative;
}
.table-wrapper::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 30px;
    background: linear-gradient(to right, transparent, white);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s;
}
.table-wrapper:hover::after {
    opacity: 1;
}
/* Batasi lebar kolom IP pada tabel riwayat login */
.modern-table td:nth-child(4), 
.modern-table th:nth-child(4) {
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
/* Kartu pengumuman */
.pengumuman-item {
    border-left: 4px solid var(--primary-500);
    transition: transform 0.2s, box-shadow 0.2s;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 12px;
    padding: 8px 12px;
}
.pengumuman-item:hover {
    transform: translateX(3px);
    box-shadow: var(--shadow-sm);
}
/* Tombol lihat selengkapnya di luar tabel */
.login-footer {
    text-align: right;
    margin-top: 1rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--gray-200);
}
</style>

<div class="page-header">
    <h2 class="page-title">
        <i class="fas fa-chalkboard-user"></i> Dashboard Guru
        <span class="badge-hadir" style="font-size:0.7rem; vertical-align: middle; margin-left: 10px;">Semester <?= $semester_aktif ?> - <?= $tahun_aktif ?></span>
    </h2>
    <p class="page-subtitle">Selamat datang kembali, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>. Berikut ringkasan aktivitas pembelajaran.</p>
</div>

<!-- Statistik Utama -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div>
            <h3>Total Siswa</h3>
            <div class="stat-number"><?= $total_siswa ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-book"></i></div>
        <div>
            <h3>Total Bab</h3>
            <div class="stat-number"><?= $total_bab ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
        <div>
            <h3>Total Pertemuan</h3>
            <div class="stat-number"><?= $total_pertemuan ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-chart-simple"></i></div>
        <div>
            <h3>Rata-rata Kehadiran</h3>
            <?php
            $total_hadir = array_sum($hadir_data);
            $total_siswa_hadir = $total_siswa * count(array_filter($hadir_data, fn($v) => $v > 0));
            $persen_keseluruhan = $total_siswa_hadir > 0 ? round(($total_hadir / $total_siswa_hadir) * 100) : 0;
            ?>
            <div class="stat-number"><?= $persen_keseluruhan ?>%</div>
            <div class="stat-trend <?= $persen_keseluruhan >= 80 ? 'up' : 'down' ?>">30 hari terakhir</div>
        </div>
    </div>
</div>
<!-- Navigasi ke Sistem Pencatatan Siswa -->
<div style="text-align: right; margin-top: 1rem;">
    <a href="<?= $base_url ?>mts-alihsan/" target="_blank" class="btn btn-primary" style="background: linear-gradient(135deg, #3B82F6, #2563EB); padding: 6px 14px; font-size: 0.8rem; border-radius: 20px;">
        <i class="fas fa-clipboard-list"></i> Sistem Pencatatan Siswa
    </a>
</div>

<!-- Dua Kolom: Kelola Pengumuman & Riwayat Login -->
<div class="form-row" style="margin-bottom: 2rem;">
    <!-- Kolom Kelola Pengumuman -->
    <div class="form-container">
        <div class="form-title"><i class="fas fa-bullhorn"></i> Kelola Pengumuman</div>
        <p>Pengumuman yang diinput akan muncul di dashboard siswa.</p>
        
        <?php if (mysqli_num_rows($pengumuman_terbaru) > 0): ?>
            <div style="margin: 10px 0;">
                <strong>Pengumuman terakhir :</strong>
                <ul style="margin-top: 8px; list-style: none; padding-left: 0;">
                    <?php while($p = mysqli_fetch_assoc($pengumuman_terbaru)): ?>
                        <li class="pengumuman-item">
                            <div style="font-weight: 600;"><?= htmlspecialchars($p['judul']) ?></div>
                            <div style="font-size: 0.75rem; color: #475569;"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></div>
                            <div style="font-size: 0.8rem; margin-top: 4px;"><?= nl2br(htmlspecialchars(substr($p['isi'], 0, 100))) ?><?= strlen($p['isi']) > 100 ? '...' : '' ?></div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php else: ?>
            <p style="font-style: italic; color: #666;">Belum ada pengumuman.</p>
        <?php endif; ?>
        
        <a href="pengumuman" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Kelola Pengumuman</a>
    </div>
    
    <!-- Kolom Riwayat Login Terbaru -->
    <div class="form-container">
        <div class="form-title"><i class="fas fa-history"></i> Riwayat Login Terbaru</div>
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr><th>Waktu</th><th>Nama</th><th>Role</th><th>IP Address</th></tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($log_login) > 0): ?>
                        <?php while($log = mysqli_fetch_assoc($log_login)): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($log['login_time'])) ?></td>
                            <td><?= htmlspecialchars($log['nama_lengkap']) ?></td>
                            <td><?= $log['role'] == 'admin' ? 'Guru' : 'Siswa' ?></td>
                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center;">Belum ada riwayat login</td>\n                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="login-footer">
            <a href="log_login" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> Lihat Selengkapnya</a>
        </div>
    </div>
</div>

<!-- Grafik Kehadiran -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-chart-line"></i> Grafik Kehadiran Siswa (30 Hari Terakhir)</div>
    <canvas id="kehadiranChart" style="width:100%; max-height:400px;"></canvas>
</div>

<!-- Rata-rata Nilai per Kelas -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-chart-bar"></i> Rata-rata Nilai per Kelas (Semester <?= $semester_aktif ?> - <?= $tahun_aktif ?>)</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>SUM 1</th>
                    <th>SUM 2</th>
                    <th>SUM 3</th>
                    <th>SUM 4</th>
                    <th>STS</th>
                    <th>SAS</th>
                    <th>SAT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kelas_nilai as $kn): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($kn['kelas']) ?></strong></td>
                    <td><?= $kn['sum1'] ?></td>
                    <td><?= $kn['sum2'] ?></td>
                    <td><?= $kn['sum3'] ?></td>
                    <td><?= $kn['sum4'] ?></td>
                    <td><?= $kn['sts'] ?></td>
                    <td><?= $kn['sas'] ?></td>
                    <td><?= $kn['sat'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($kelas_nilai)): ?>
                <tr><td colspan="8" style="text-align: center;">Belum ada data nilai untuk semester <?= $semester_aktif ?> <?= $tahun_aktif ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('kehadiranChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                { label: 'Hadir', data: <?= json_encode($hadir_data) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.2, fill: true },
                { label: 'Sakit', data: <?= json_encode($sakit_data) ?>, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', tension: 0.2, fill: true },
                { label: 'Izin', data: <?= json_encode($izin_data) ?>, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', tension: 0.2, fill: true },
                { label: 'Alpha', data: <?= json_encode($alpha_data) ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', tension: 0.2, fill: true }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false } },
            scales: { 
                y: { beginAtZero: true, title: { display: true, text: 'Jumlah Siswa' } }, 
                x: { title: { display: true, text: 'Tanggal' }, ticks: { maxRotation: 30, autoSkip: true, maxTicksLimit: 12 } } 
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>