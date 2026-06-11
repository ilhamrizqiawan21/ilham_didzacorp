<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('siswa');
$title = 'Tugas Saya';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$query_siswa = mysqli_query($conn, "SELECT id, kelas_id FROM siswa WHERE user_id = $user_id");
$siswa = mysqli_fetch_assoc($query_siswa);
$siswa_id = $siswa['id'];
$kelas_id = $siswa['kelas_id'];

// Proses hapus pengumpulan tugas
if (isset($_GET['hapus_pengumpulan'])) {
    $tugas_id = (int)$_GET['hapus_pengumpulan'];
    $cek = mysqli_query($conn, "SELECT id, file_upload FROM pengumpulan_tugas WHERE tugas_id=$tugas_id AND siswa_id=$siswa_id");
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        if ($data['file_upload'] && file_exists("../uploads/tugas_siswa/".$data['file_upload'])) {
            unlink("../uploads/tugas_siswa/".$data['file_upload']);
        }
        mysqli_query($conn, "DELETE FROM pengumpulan_tugas WHERE tugas_id=$tugas_id AND siswa_id=$siswa_id");
        echo "<script>alert('Pengumpulan tugas berhasil dihapus'); window.location.href='tugas_saya';</script>";
    } else {
        echo "<script>alert('Akses ditolak'); window.location.href='tugas_saya';</script>";
    }
    exit;
}

$tahun_aktif = get_tahun_ajaran_aktif($conn);
$semester_aktif = get_semester_aktif($conn);

$tugas = mysqli_query($conn, "SELECT t.*, pt.status, pt.nilai, pt.file_upload, pt.teks_jawaban, pt.catatan 
    FROM tugas t 
    JOIN tugas_kelas tk ON t.id = tk.tugas_id 
    LEFT JOIN pengumpulan_tugas pt ON pt.tugas_id = t.id AND pt.siswa_id = $siswa_id
    WHERE t.semester = '$semester_aktif' AND t.tahun_ajaran = '$tahun_aktif' 
      AND tk.kelas_id = $kelas_id
    ORDER BY t.created_at DESC");

$belum_selesai = 0;
$tugas_data = [];
while($row = mysqli_fetch_assoc($tugas)) {
    $tugas_data[] = $row;
    if(empty($row['nilai'])) $belum_selesai++;
}
?>

<style>
/* PERBAIKAN TATA LETAK TABEL TUGAS SISWA - DESKRIPSI FULL DAN KOMENTAR GURU */
.tugas-table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.tugas-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    min-width: 1100px;
}
.tugas-table th, 
.tugas-table td {
    padding: 12px 8px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}
.tugas-table th {
    background-color: #f3f4f6;
    font-weight: 600;
    text-align: left;
    white-space: nowrap;
}
.tugas-table td {
    word-break: break-word;
}
/* Lebar kolom */
.tugas-table th:nth-child(1), .tugas-table td:nth-child(1) { width: 40px; text-align: center; }
.tugas-table th:nth-child(2), .tugas-table td:nth-child(2) { min-width: 180px; }
.tugas-table th:nth-child(3), .tugas-table td:nth-child(3) { width: 80px; }
.tugas-table th:nth-child(4), .tugas-table td:nth-child(4) { min-width: 300px; max-width: 450px; white-space: normal; }
.tugas-table th:nth-child(5), .tugas-table td:nth-child(5) { min-width: 140px; }
.tugas-table th:nth-child(6), .tugas-table td:nth-child(6) { width: 100px; text-align: center; }
.tugas-table th:nth-child(7), .tugas-table td:nth-child(7) { width: 60px; text-align: center; }
.tugas-table th:nth-child(8), .tugas-table td:nth-child(8) { width: 160px; } /* komentar guru */
.tugas-table th:nth-child(9), .tugas-table td:nth-child(9) { width: 80px; text-align: center; }
.tugas-table th:nth-child(10), .tugas-table td:nth-child(10) { width: 100px; }
.tugas-table th:nth-child(11), .tugas-table td:nth-child(11) { width: 100px; text-align: center; }

.deskripsi-cell {
    white-space: normal;
    line-height: 1.4;
}
.deskripsi-full {
    font-size: 0.8rem;
    word-break: break-word;
}
/* Komentar guru */
.komentar-guru {
    background-color: #fffbeb;
    border-left: 3px solid #f59e0b;
    padding: 6px 8px;
    border-radius: 8px;
    font-size: 0.7rem;
    color: #78350f;
    margin: 0;
    max-width: 200px;
    white-space: normal;
}
.komentar-guru i {
    margin-right: 4px;
    color: #f59e0b;
}
.komentar-guru p {
    margin: 0;
}

.btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    font-size: 0.7rem;
    font-weight: 500;
    border-radius: 20px;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}
.btn-primary {
    background-color: #2563eb;
    color: white;
}
.btn-primary:hover {
    background-color: #1d4ed8;
}
.btn-warning {
    background-color: #f59e0b;
    color: white;
}
.btn-warning:hover {
    background-color: #d97706;
}
.btn-danger {
    background-color: #ef4444;
    color: white;
}
.btn-danger:hover {
    background-color: #dc2626;
}
.badge-sudah {
    background-color: #10b981;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    display: inline-block;
}
.badge-warning {
    background-color: #f59e0b;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    display: inline-block;
}
.badge-belum {
    background-color: #f97316;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    display: inline-block;
}
.nilai-badge {
    background-color: #e0e7ff;
    color: #4338ca;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.75rem;
    display: inline-block;
}
.action-buttons {
    display: flex;
    gap: 6px;
    justify-content: center;
    flex-wrap: wrap;
}
.text-muted {
    font-size: 0.65rem;
    color: #6b7280;
    margin-top: 4px;
}
.alert-success-custom {
    background: #d1fae5;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 1rem;
    color: #166534;
}
.alert-info-custom {
    background: #e0f2fe;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 1rem;
    color: #075985;
}
.file-link {
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 0.7rem;
    text-decoration: none;
    color: #2563eb;
}
.file-link:hover {
    background: #e5e7eb;
}

@media (max-width: 768px) {
    .tugas-table th, .tugas-table td {
        padding: 8px 4px;
        font-size: 0.75rem;
    }
    .tugas-table {
        min-width: 1200px;
    }
    .btn-sm {
        padding: 4px 8px;
        font-size: 0.65rem;
    }
    /* Hanya sembunyikan kolom jawaban teks (kolom 10) di mobile, komentar guru tetap tampil */
    .tugas-table th:nth-child(10),
    .tugas-table td:nth-child(10) {
        display: none;
    }
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-tasks"></i> Tugas Saya</h2>
    <p class="page-subtitle">Daftar tugas yang diberikan guru. Kumpulkan tugas tepat waktu.</p>
</div>

<?php if($belum_selesai == 0 && count($tugas_data) > 0): ?>
    <div class="alert-success-custom">
        <i class="fas fa-check-circle"></i> Selamat! Anda telah menyelesaikan semua tugas untuk semester <?= $semester_aktif ?> tahun <?= $tahun_aktif ?>.
    </div>
<?php elseif(count($tugas_data) == 0): ?>
    <div class="alert-info-custom">
        <i class="fas fa-info-circle"></i> Belum ada tugas untuk semester <?= $semester_aktif ?> tahun <?= $tahun_aktif ?>. Silakan cek kembali nanti.
    </div>
<?php endif; ?>

<div class="form-container">
    <div class="tugas-table-wrapper">
        <table class="tugas-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Judul Tugas</th>
                    <th>Jenis</th>
                    <th>Deskripsi Tugas (Soal)</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th>Nilai</th>
                    <th>Komentar Guru</th>
                    <th>File</th>
                    <th>Jawaban</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach($tugas_data as $t): 
                    $status = $t['status'] ?? 'belum';
                    if (!empty($t['nilai'])) {
                        $badge = '<span class="badge-sudah"><i class="fas fa-check-circle"></i> Selesai</span>';
                    } elseif ($status == 'sudah') {
                        $badge = '<span class="badge-warning"><i class="fas fa-hourglass-half"></i> Menunggu Nilai</span>';
                    } else {
                        $badge = '<span class="badge-belum"><i class="fas fa-clock"></i> Belum</span>';
                    }
                    
                    $fileLink = ($t['file_upload']) ? '<a href="../uploads/tugas_siswa/'.$t['file_upload'].'" target="_blank" class="file-link"><i class="fas fa-download"></i> Lihat</a>' : '-';
                    $jawaban = $t['teks_jawaban'] ? '<span title="'.htmlspecialchars($t['teks_jawaban']).'">'.htmlspecialchars(substr($t['teks_jawaban'],0,40)).'...</span>' : '-';
                    $nilaiTampil = ($t['nilai']) ? '<span class="nilai-badge">'.$t['nilai'].'</span>' : '-';
                    
                    if (!empty($t['catatan'])) {
                        $komentar = '<div class="komentar-guru"><i class="fas fa-comment-dots"></i> '.nl2br(htmlspecialchars($t['catatan'])).'</div>';
                    } else {
                        $komentar = '<span style="color:#9ca3af; font-style:italic;">-</span>';
                    }
                ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($t['judul']) ?></strong></td>
                    <td><?= $t['jenis'] == 'mandiri' ? 'Mandiri' : 'Kelompok' ?></td>
                    <td class="deskripsi-cell">
                        <div class="deskripsi-full">
                            <?= nl2br(htmlspecialchars($t['deskripsi'])) ?>
                        </div>
                    </td>
                    <td><?= $t['durasi_mulai'] ?> s.d. <?= $t['durasi_selesai'] ?></td>
                    <td style="text-align: center;"><?= $badge ?></td>
                    <td style="text-align: center;"><?= $nilaiTampil ?></td>
                    <td><?= $komentar ?></td>
                    <td style="text-align: center;"><?= $fileLink ?></td>
                    <td><?= $jawaban ?></td>
                    <td style="text-align: center;">
                        <?php if($status == 'belum'): ?>
                            <div class="action-buttons">
                                <a href="upload_tugas?id=<?= $t['id'] ?>" class="btn-sm btn-primary"><i class="fas fa-upload"></i> Kumpul</a>
                            </div>
                            <div class="text-muted">Upload file/tulis jawaban</div>
                        <?php else: ?>
                            <div class="action-buttons">
                                <a href="edit_pengumpulan?id=<?= $t['id'] ?>" class="btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?hapus_pengumpulan=<?= $t['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Hapus pengumpulan tugas? Semua file dan jawaban akan hilang.')"><i class="fas fa-trash-alt"></i> Hapus</a>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($tugas_data) == 0): ?>
                    <tr><td colspan="11" style="text-align: center; padding: 30px;">Belum ada tugas untuk semester <?= $semester_aktif ?> tahun <?= $tahun_aktif ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>