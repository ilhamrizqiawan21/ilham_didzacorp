<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('siswa');
$title = 'Materi Pembelajaran';
include '../includes/header.php';

// Ambil kelas siswa yang sedang login
$user_id = $_SESSION['user_id'];
$query_siswa = mysqli_query($conn, "SELECT kelas_id FROM siswa WHERE user_id = $user_id");
$siswa = mysqli_fetch_assoc($query_siswa);
$kelas_id_siswa = $siswa['kelas_id'] ?? 0;

// Tampilkan hanya bab yang diperuntukkan untuk kelas ini (target_kelas NULL atau mengandung kelas_id siswa)
if ($kelas_id_siswa > 0) {
    $query_bab = "SELECT * FROM bab 
                  WHERE target_kelas IS NULL 
                     OR FIND_IN_SET('$kelas_id_siswa', target_kelas) 
                  ORDER BY id";
} else {
    // Jika siswa belum memiliki kelas, tampilkan bab dengan target_kelas NULL (semua kelas)
    $query_bab = "SELECT * FROM bab WHERE target_kelas IS NULL ORDER BY id";
}
$bab_list = mysqli_query($conn, $query_bab);
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-book-open"></i> Materi Pembelajaran</h2>
    <p class="page-subtitle">Klik pada materi untuk melihat atau download file.</p>
</div>

<div class="form-container">
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr><th>#</th><th>Judul Bab</th><th>Deskripsi</th><th>File Materi</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($bab_list) > 0): ?>
                    <?php while($b = mysqli_fetch_assoc($bab_list)): ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><strong><?= htmlspecialchars($b['judul_bab']) ?></strong></td>
                        <td><?= nl2br(htmlspecialchars(substr($b['materi_pokok'],0,100))) ?>...</td>
                        <td>
                            <?php if($b['file_materi']): ?>
                                <i class="fas fa-file"></i> <?= $b['file_materi'] ?>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td class="action-buttons" style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php if($b['file_materi']): ?>
                                <a href="../uploads/materi/<?= $b['file_materi'] ?>" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Lihat</a>
                                <a href="../uploads/materi/<?= $b['file_materi'] ?>" download class="btn btn-sm btn-outline"><i class="fas fa-download"></i> Download</a>
                            <?php else: ?>
                                <span class="text-muted">Tidak ada file</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Belum ada materi yang tersedia untuk kelas Anda.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>