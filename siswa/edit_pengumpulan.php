<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('siswa');
$title = 'Edit Pengumpulan Tugas';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM siswa WHERE user_id = $user_id"));
$siswa_id = $siswa['id'];
$tugas_id = (int)$_GET['id'];

// Ambil data pengumpulan yang sudah ada
$query = "SELECT * FROM pengumpulan_tugas WHERE tugas_id=$tugas_id AND siswa_id=$siswa_id";
$pengumpulan = mysqli_fetch_assoc(mysqli_query($conn, $query));
if (!$pengumpulan) {
    echo "<script>alert('Data tidak ditemukan atau Anda tidak memiliki akses.'); window.location.href='tugas_saya';</script>";
    exit;
}

// Ambil info tugas untuk judul
$tugas_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT judul FROM tugas WHERE id=$tugas_id"));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teks_jawaban = mysqli_real_escape_string($conn, $_POST['teks_jawaban']);
    $file_name = $pengumpulan['file_upload']; // default file lama
    $error = null;
    
    // ========== VALIDASI FILE UPLOAD (hanya JPG, JPEG, PDF) ==========
    if ($_FILES['file_tugas']['error'] == 0) {
        // Konfigurasi
        $allowed_ext = ['jpg', 'jpeg', 'pdf'];
        $max_size = 10 * 1024 * 1024; // 10 MB
        $target_dir = "../uploads/tugas_siswa/";
        
        // Buat folder jika belum ada
        if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);
        
        // Ekstrak ekstensi file
        $original_name = $_FILES['file_tugas']['name'];
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $file_size = $_FILES['file_tugas']['size'];
        $tmp_path = $_FILES['file_tugas']['tmp_name'];
        
        // Validasi ekstensi
        if (!in_array($file_ext, $allowed_ext)) {
            $error = "Format file tidak diizinkan! Hanya file JPG, JPEG, atau PDF yang diperbolehkan.";
        }
        // Validasi ukuran
        elseif ($file_size > $max_size) {
            $error = "Ukuran file maksimal 10 MB.";
        }
        // Validasi MIME type (double check)
        else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_path);
            finfo_close($finfo);
            
            $allowed_mime = [
                'image/jpeg',
                'image/jpg',   // kadang ada yang pakai ini
                'application/pdf'
            ];
            if (!in_array($mime_type, $allowed_mime)) {
                $error = "Tipe file tidak valid (hanya file gambar JPG/JPEG atau PDF). Deteksi MIME: $mime_type";
            }
        }
        
        // Jika tidak ada error, proses upload
        if (!$error) {
            // Buat nama file unik: timestamp + random + ekstensi
            $random = bin2hex(random_bytes(8));
            $new_filename = time() . '_' . $random . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($tmp_path, $target_file)) {
                // Hapus file lama jika ada
                if ($pengumpulan['file_upload'] && file_exists($target_dir . $pengumpulan['file_upload'])) {
                    unlink($target_dir . $pengumpulan['file_upload']);
                }
                $file_name = $new_filename;
            } else {
                $error = "Gagal mengupload file (kesalahan server).";
            }
        }
        
        // Jika error, tampilkan dan tetap gunakan file lama
        if ($error) {
            echo "<script>alert('$error');</script>";
            $file_name = $pengumpulan['file_upload'];
        }
    }
    
    // Update database
    $update = mysqli_query($conn, "UPDATE pengumpulan_tugas SET teks_jawaban='$teks_jawaban', file_upload='$file_name' WHERE tugas_id=$tugas_id AND siswa_id=$siswa_id");
    if ($update) {
        echo "<script>alert('Pengumpulan berhasil diupdate'); window.location.href='tugas_saya';</script>";
    } else {
        echo "<script>alert('Gagal update: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-edit"></i> Edit Pengumpulan Tugas</h2>
    <p class="page-subtitle">Anda dapat mengganti file atau memperbarui jawaban teks.</p>
</div>

<div class="form-container">
    <div class="form-title">Tugas: <?= htmlspecialchars($tugas_info['judul']) ?></div>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Jawaban Teks (opsional)</label>
            <textarea name="teks_jawaban" class="form-textarea" rows="5"><?= htmlspecialchars($pengumpulan['teks_jawaban'] ?? '') ?></textarea>
            <small>Anda bisa menulis jawaban di sini, atau upload file di bawah.</small>
        </div>
        <div class="form-group">
            <label>Upload File Baru (opsional, kosongkan jika tidak ingin mengganti)</label>
            <input type="file" name="file_tugas" class="form-input" accept=".jpg,.jpeg,.pdf">
            <?php if($pengumpulan['file_upload']): ?>
                <p class="text-muted" style="margin-top:5px;">File saat ini: <a href="../uploads/tugas_siswa/<?= $pengumpulan['file_upload'] ?>" target="_blank"><?= $pengumpulan['file_upload'] ?></a></p>
            <?php endif; ?>
            <small class="text-muted">Format yang diizinkan: JPG, JPEG, PDF. Maksimal 10 MB.</small>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="tugas_saya" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>