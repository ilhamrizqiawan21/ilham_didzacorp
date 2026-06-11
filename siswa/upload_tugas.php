<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('siswa');
$tugas_id = (int)$_GET['id'];
$title = 'Kumpul Tugas';
include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$query_siswa = mysqli_query($conn, "SELECT id FROM siswa WHERE user_id = $user_id");
$siswa = mysqli_fetch_assoc($query_siswa);
$siswa_id = $siswa['id'];

// Cek apakah sudah ada pengumpulan dengan status 'sudah'
$cek = mysqli_query($conn, "SELECT id, status FROM pengumpulan_tugas WHERE tugas_id=$tugas_id AND siswa_id=$siswa_id");
$existing = mysqli_fetch_assoc($cek);
if ($existing && $existing['status'] == 'sudah') {
    echo "<script>alert('Anda sudah mengumpulkan tugas ini'); window.location.href='tugas_saya';</script>";
    exit;
}

// Ambil data tugas
$tugas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tugas WHERE id=$tugas_id"));
if (!$tugas) {
    echo "<script>alert('Tugas tidak ditemukan'); window.location.href='tugas_saya';</script>";
    exit;
}

// Proses upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kumpul'])) {
    $teks = trim($_POST['jawaban_teks']);
    $file_name = null;
    $error = null;
    
    // Validasi jika ada file yang diupload
    if ($_FILES['file_tugas']['error'] == 0) {
        // Hanya izinkan JPG, JPEG, PDF
        $allowed_ext = ['jpg', 'jpeg', 'pdf'];
        $max_size = 10 * 1024 * 1024; // 10 MB
        $file_ext = strtolower(pathinfo($_FILES['file_tugas']['name'], PATHINFO_EXTENSION));
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
        // Validasi MIME type
        else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_path);
            finfo_close($finfo);
            
            $allowed_mime = [
                'image/jpeg',
                'image/jpg',
                'application/pdf'
            ];
            if (!in_array($mime_type, $allowed_mime)) {
                $error = "Tipe file tidak valid (hanya file gambar JPG/JPEG atau PDF). Deteksi MIME: $mime_type";
            }
        }
        
        if (!$error) {
            // Nama file unik
            $random = bin2hex(random_bytes(8));
            $file_name = time() . '_' . $random . '.' . $file_ext;
            $target_dir = "../uploads/tugas_siswa/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);
            $target_file = $target_dir . $file_name;
            
            if (!move_uploaded_file($tmp_path, $target_file)) {
                $error = "Gagal mengupload file.";
                $file_name = null;
            }
        }
    }
    
    // Pastikan minimal salah satu diisi (teks atau file)
    if (empty($file_name) && empty($teks)) {
        $error = "Anda harus mengisi jawaban teks atau mengupload file tugas.";
    }
    
    if ($error) {
        echo "<script>alert('$error'); window.history.back();</script>";
        exit;
    }
    
    // Escape data
    $teks_safe = mysqli_real_escape_string($conn, $teks);
    $file_sql = $file_name ? "'$file_name'" : "NULL";
    $teks_sql = $teks ? "'$teks_safe'" : "NULL";
    
    if ($existing) {
        $query = "UPDATE pengumpulan_tugas SET status='sudah', file_upload=$file_sql, teks_jawaban=$teks_sql, tanggal_kumpul=NOW() WHERE id={$existing['id']}";
    } else {
        $query = "INSERT INTO pengumpulan_tugas (tugas_id, siswa_id, status, file_upload, teks_jawaban, tanggal_kumpul) 
                  VALUES ($tugas_id, $siswa_id, 'sudah', $file_sql, $teks_sql, NOW())";
    }
    mysqli_query($conn, $query);
    echo "<script>alert('Tugas berhasil dikumpulkan'); window.location.href='tugas_saya';</script>";
}
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-upload"></i> Kumpul Tugas</h2>
    <p class="page-subtitle"><?= htmlspecialchars($tugas['judul']) ?></p>
</div>

<div class="form-container">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Deskripsi Tugas</label>
            <div class="form-input" style="background:#f1f5f9;"><?= nl2br(htmlspecialchars($tugas['deskripsi'])) ?></div>
        </div>
        <div class="form-group">
            <label>Durasi Pengerjaan</label>
            <div class="form-input" style="background:#f1f5f9;"><?= $tugas['durasi_mulai'] ?> s.d. <?= $tugas['durasi_selesai'] ?></div>
        </div>
        <div class="form-group">
            <label>Jawaban Teks (Opsional, minimal salah satu diisi)</label>
            <textarea name="jawaban_teks" class="form-textarea" rows="5" placeholder="Tulis jawaban Anda di sini..."></textarea>
        </div>
        <div class="form-group">
            <label>Upload File (Opsional, minimal salah satu diisi)</label>
            <input type="file" name="file_tugas" class="form-input" accept=".jpg,.jpeg,.pdf">
            <small class="text-muted">Format yang diizinkan: JPG, JPEG, PDF. Maksimal 10 MB.</small>
        </div>
        <button type="submit" name="kumpul" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kumpulkan Tugas</button>
        <a href="tugas_saya" class="btn btn-outline">Batal</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>