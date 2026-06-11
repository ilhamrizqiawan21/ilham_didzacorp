<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Kelola Pengumuman';
include '../includes/header.php';

// Proses tambah/edit/hapus pengumuman
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['simpan'])) {
        $id = (int)$_POST['id'];
        $judul = mysqli_real_escape_string($conn, $_POST['judul']);
        $isi = mysqli_real_escape_string($conn, $_POST['isi']);
        $target = mysqli_real_escape_string($conn, $_POST['target']);
        $kelas_id = ($target == 'kelas') ? (int)$_POST['kelas_id'] : 'NULL';
        $created_by = $_SESSION['user_id'];
        
        if ($id == 0) {
            mysqli_query($conn, "INSERT INTO pengumuman (judul, isi, target, kelas_id, created_by) 
                                 VALUES ('$judul', '$isi', '$target', $kelas_id, $created_by)");
        } else {
            mysqli_query($conn, "UPDATE pengumuman SET judul='$judul', isi='$isi', target='$target', kelas_id=$kelas_id 
                                 WHERE id=$id");
        }
        echo "<script>alert('Pengumuman disimpan'); window.location.href='pengumuman';</script>";
    } elseif (isset($_GET['hapus'])) {
        $id = (int)$_GET['hapus'];
        mysqli_query($conn, "DELETE FROM pengumuman WHERE id=$id");
        echo "<script>alert('Pengumuman dihapus'); window.location.href='pengumuman';</script>";
    }
}

$pengumuman = mysqli_query($conn, "SELECT p.*, u.nama_lengkap as pembuat, k.nama_kelas 
    FROM pengumuman p 
    LEFT JOIN user u ON p.created_by = u.id 
    LEFT JOIN kelas k ON p.kelas_id = k.id 
    ORDER BY p.created_at DESC");
$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-bullhorn"></i> Kelola Pengumuman</h2>
    <p class="page-subtitle">Buat pengumuman untuk siswa (semua kelas atau per kelas)</p>
</div>

<div class="form-container">
    <div class="form-title"><i class="fas fa-plus-circle"></i> Tambah Pengumuman</div>
    <form method="POST">
        <input type="hidden" name="id" value="0">
        <div class="form-group">
            <label>Judul</label>
            <input type="text" name="judul" class="form-input" required>
        </div>
        <div class="form-group">
            <label>Isi Pengumuman</label>
            <textarea name="isi" class="form-textarea" rows="4" required></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Target</label>
                <select name="target" id="target" class="form-select">
                    <option value="semua">Semua Kelas</option>
                    <option value="kelas">Kelas Tertentu</option>
                </select>
            </div>
            <div class="form-group" id="kelasGroup" style="display: none;">
                <label>Pilih Kelas</label>
                <select name="kelas_id" class="form-select">
                    <option value="0">-- Pilih Kelas --</option>
                    <?php while($k = mysqli_fetch_assoc($kelas_list)): ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Pengumuman</button>
    </form>
</div>

<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Daftar Pengumuman</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr><th>Judul</th><th>Isi</th><th>Target</th><th>Pembuat</th><th>Tanggal</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php while($p = mysqli_fetch_assoc($pengumuman)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['judul']) ?></strong></td>
                    <td><?= nl2br(htmlspecialchars(substr($p['isi'],0,100))) ?>...</td>
                    <td><?= $p['target'] == 'semua' ? 'Semua Kelas' : 'Kelas ' . $p['nama_kelas'] ?></td>
                    <td><?= $p['pembuat'] ?></td>
                    <td><?= tgl_indonesia($p['created_at']) ?></td>
                    <td>
                        <a href="?hapus=<?= $p['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Hapus?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($pengumuman)==0): ?>
                <tr><td colspan="6" class="text-center">Belum ada pengumuman</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('target').addEventListener('change', function() {
    document.getElementById('kelasGroup').style.display = this.value == 'kelas' ? 'block' : 'none';
});
</script>

<?php include '../includes/footer.php'; ?>