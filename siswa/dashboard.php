<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('siswa');
$title = 'Dashboard Siswa';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$query_siswa = mysqli_query($conn, "SELECT s.*, k.nama_kelas FROM siswa s JOIN kelas k ON s.kelas_id = k.id WHERE s.user_id = $user_id");
$siswa = mysqli_fetch_assoc($query_siswa);
$siswa_id = $siswa['id'];
$kelas_id = $siswa['kelas_id'];

$tahun_aktif = get_tahun_ajaran_aktif($conn);
$semester_aktif = get_semester_aktif($conn);

// Ambil pengumuman yang relevan (semua kelas atau kelas siswa)
$pengumuman = mysqli_query($conn, "SELECT * FROM pengumuman WHERE target='semua' OR (target='kelas' AND kelas_id=$kelas_id) ORDER BY created_at DESC LIMIT 5");

// Hitung total tugas yang diberikan ke kelas siswa
$total_tugas_all = mysqli_num_rows(mysqli_query($conn, "SELECT t.id FROM tugas t 
    INNER JOIN tugas_kelas tk ON t.id = tk.tugas_id 
    WHERE tk.kelas_id = $kelas_id AND t.semester='$semester_aktif' AND t.tahun_ajaran='$tahun_aktif'"));

// Ambil 5 tugas terbaru untuk kelas siswa
$tugas_terbaru = mysqli_query($conn, "SELECT t.*, 
    (SELECT status FROM pengumpulan_tugas WHERE tugas_id=t.id AND siswa_id=$siswa_id) as status,
    (SELECT nilai FROM pengumpulan_tugas WHERE tugas_id=t.id AND siswa_id=$siswa_id) as nilai
    FROM tugas t 
    INNER JOIN tugas_kelas tk ON t.id = tk.tugas_id
    WHERE tk.kelas_id = $kelas_id AND t.semester='$semester_aktif' AND t.tahun_ajaran='$tahun_aktif'
    ORDER BY t.created_at DESC LIMIT 5");

// Hitung jumlah tugas yang belum dikumpulkan
$belum_kumpul = mysqli_num_rows(mysqli_query($conn, "SELECT t.id FROM tugas t 
    INNER JOIN tugas_kelas tk ON t.id = tk.tugas_id 
    LEFT JOIN pengumpulan_tugas pt ON pt.tugas_id=t.id AND pt.siswa_id=$siswa_id 
    WHERE tk.kelas_id = $kelas_id AND (pt.status IS NULL OR pt.status='belum') 
    AND t.semester='$semester_aktif' AND t.tahun_ajaran='$tahun_aktif'"));

// Ringkasan kehadiran bulan ini
$bulan_ini = date('Y-m');
$absensi = mysqli_query($conn, "SELECT a.status, COUNT(*) as total FROM absensi a 
    JOIN pertemuan p ON a.pertemuan_id = p.id 
    WHERE a.siswa_id = $siswa_id AND DATE_FORMAT(p.tanggal, '%Y-%m') = '$bulan_ini'
    GROUP BY a.status");
$hadir = $sakit = $izin = $alpha = 0;
while($ab = mysqli_fetch_assoc($absensi)) {
    if($ab['status'] == 'hadir') $hadir = $ab['total'];
    elseif($ab['status'] == 'sakit') $sakit = $ab['total'];
    elseif($ab['status'] == 'izin') $izin = $ab['total'];
    elseif($ab['status'] == 'alpha') $alpha = $ab['total'];
}
$total_absen = $hadir + $sakit + $izin + $alpha;
$persen_hadir = $total_absen > 0 ? round(($hadir / $total_absen) * 100) : 0;
?>

<!-- Tambahan CSS khusus dashboard siswa (tidak mengganggu navigasi) -->
<style>
/* Perbaikan kartu pengumuman */
.pengumuman-item {
    border-left: 4px solid var(--primary-500);
    transition: transform 0.2s, box-shadow 0.2s;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 12px;
    padding: 10px 12px;
}
.pengumuman-item:hover {
    transform: translateX(3px);
    box-shadow: var(--shadow-sm);
}
/* Batasi tinggi area scroll pengumuman di mobile */
@media (max-width: 768px) {
    .pengumuman-scroll {
        max-height: 400px !important;
    }
}
/* Perbaikan tabel tugas di mobile */
@media (max-width: 768px) {
    .table-wrapper {
        margin: 0 -1rem;
        padding: 0 1rem;
        width: calc(100% + 2rem);
    }
    .modern-table {
        min-width: 500px;
    }
}
/* Badge semester */
.badge-semester {
    background: var(--primary-100);
    color: var(--primary-800);
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    margin-left: 8px;
    vertical-align: middle;
}
</style>

<div class="page-header">
    <h2 class="page-title">
        <i class="fas fa-home"></i> Dashboard Siswa
        <span class="badge-semester">Semester <?= $semester_aktif ?> - <?= $tahun_aktif ?></span>
    </h2>
    <p class="page-subtitle">Selamat datang, <strong><?= htmlspecialchars($siswa['nama']) ?></strong> (Kelas <?= $siswa['nama_kelas'] ?>)</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div>
            <h3>Kehadiran Bulan Ini</h3>
            <div class="stat-number"><?= $persen_hadir ?>%</div>
            <div class="stat-trend <?= $persen_hadir >= 80 ? 'up' : 'down' ?>">Hadir <?= $hadir ?>x</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-tasks"></i></div>
        <div>
            <h3>Tugas</h3>
            <div class="stat-number"><?= $belum_kumpul ?></div>
            <div>Belum dikumpulkan dari <?= $total_tugas_all ?> tugas</div>
        </div>
    </div>
</div>

<div class="form-row">
    <!-- Pengumuman -->
    <div class="form-container">
        <div class="form-title"><i class="fas fa-bullhorn"></i> Pengumuman</div>
        <?php if(mysqli_num_rows($pengumuman) > 0): ?>
            <div class="pengumuman-scroll" style="max-height: 350px; overflow-y: auto; padding-right: 5px;">
                <?php while($p = mysqli_fetch_assoc($pengumuman)): ?>
                    <div class="pengumuman-item">
                        <h4 style="margin:0 0 5px; font-size: 1rem;"><?= htmlspecialchars($p['judul']) ?></h4>
                        <p style="font-size:0.7rem; color:#64748b; margin-bottom: 6px;">
                            <i class="far fa-calendar-alt"></i> <?= tgl_indonesia($p['created_at']) ?>
                        </p>
                        <p style="font-size:0.85rem; margin-bottom: 0; line-height: 1.4;"><?= nl2br(htmlspecialchars($p['isi'])) ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="font-style: italic; color: #666;">Belum ada pengumuman.</p>
        <?php endif; ?>
    </div>

    <!-- Tugas Terbaru -->
    <div class="form-container">
        <div class="form-title"><i class="fas fa-tasks"></i> Tugas Terbaru</div>
        <?php if(mysqli_num_rows($tugas_terbaru) > 0): ?>
            <div class="table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Tugas</th>
                            <th>Status</th>
                            <th>Nilai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($t = mysqli_fetch_assoc($tugas_terbaru)): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($t['judul']) ?></strong><br>
                                <small style="color: #64748b;"><i class="far fa-clock"></i> Deadline: <?= date('d/m/Y', strtotime($t['durasi_selesai'])) ?></small>
                            </td>
                            <td>
                                <?= ($t['status'] ?? 'belum') == 'sudah' ? '<span class="badge-hadir"><i class="fas fa-check-circle"></i> Sudah</span>' : '<span class="badge-alpha"><i class="fas fa-hourglass-half"></i> Belum</span>' ?>
                             </td>
                            <td>
                                <?= $t['nilai'] ? '<span class="badge-info" style="background:#e0e7ff; color:#4338ca;">'.$t['nilai'].'</span>' : '-' ?>
                             </td>
                            <td>
                                <a href="tugas_saya" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Lihat</a>
                             </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="font-style: italic; color: #666;">Belum ada tugas untuk semester ini.</p>
        <?php endif; ?>
        <div class="btn-group" style="margin-top: 1rem;">
            <a href="tugas_saya" class="btn btn-outline"><i class="fas fa-list"></i> Semua Tugas</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>