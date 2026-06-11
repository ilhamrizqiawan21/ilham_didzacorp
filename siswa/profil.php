<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('siswa');

$user_id = $_SESSION['user_id'];

// Ambil data siswa lengkap dengan username dan kelas
$query = "SELECT s.*, k.nama_kelas, u.username 
          FROM siswa s 
          JOIN kelas k ON s.kelas_id = k.id 
          JOIN user u ON s.user_id = u.id 
          WHERE s.user_id = $user_id";
$siswa = mysqli_fetch_assoc(mysqli_query($conn, $query));

// Proses update password saja (nama tidak diubah)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profil'])) {
    $password_baru = $_POST['password_baru'];
    
    if (!empty($password_baru)) {
        $hashed = md5($password_baru); // sesuaikan dengan hash yang digunakan sistem login
        mysqli_query($conn, "UPDATE user SET password='$hashed' WHERE id=$user_id");
        echo "<script>alert('Password berhasil diubah'); window.location.href='profil';</script>";
    } else {
        echo "<script>alert('Masukkan password baru jika ingin mengubah'); window.location.href='profil';</script>";
    }
}

$title = 'Profil Saya';
include '../includes/header.php';
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-user-circle"></i> Profil Saya</h2>
    <p class="page-subtitle">Informasi data diri (hanya password yang dapat diubah)</p>
</div>

<div class="form-container">
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>NIS</label>
                <input type="text" class="form-input" value="<?= htmlspecialchars($siswa['nis']) ?>" readonly disabled>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-input" value="<?= htmlspecialchars($siswa['username']) ?>" readonly disabled>
            </div>
        </div>
        
        <div class="form-group">
            <label>Nama Lengkap</label>
            <!-- Nama hanya ditampilkan, tidak bisa diedit -->
            <input type="text" class="form-input" value="<?= htmlspecialchars($siswa['nama']) ?>" readonly>
        </div>
        
        <div class="form-group">
            <label>Kelas</label>
            <input type="text" class="form-input" value="<?= htmlspecialchars($siswa['nama_kelas']) ?>" readonly disabled>
        </div>
        
        <div class="form-group">
            <label>Jenis Kelamin</label>
            <input type="text" class="form-input" value="<?= $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>" readonly disabled>
        </div>
        
        <div class="form-group">
            <label>Ganti Password</label>
            <input type="password" name="password_baru" class="form-input" placeholder="Masukkan password baru (kosongkan jika tidak ingin mengubah)">
            <small style="color: #6b7280; font-size: 0.7rem;">*Password akan diubah setelah mengklik tombol "Update Password"</small>
        </div>
        
        <button type="submit" name="update_profil" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>