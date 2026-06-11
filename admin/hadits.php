<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
// Redirect ke halaman siswa (karena konten hadits sama untuk admin dan siswa)
header("Location: ../siswa/hadits");
exit;
?>