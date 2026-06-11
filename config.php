<?php
session_name('DIGITALISASI_SESSION');
session_start();
$host = 'localhost';
$user = 'root';
$pass = 'Hash2856@';
$db   = 'pembelajaran_quran';

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set timezone ke Indonesia
date_default_timezone_set('Asia/Jakarta');

// Base URL
$base_url = '/';

// Load PhpSpreadsheet (pastikan composer install di folder project)
require_once __DIR__ . '/vendor/autoload.php';

// Folder upload
define('UPLOAD_MATERI', __DIR__ . '/uploads/materi/');
define('UPLOAD_SISWA', __DIR__ . '/uploads/siswa_excel/');
if (!file_exists(UPLOAD_MATERI)) mkdir(UPLOAD_MATERI, 0777, true);
if (!file_exists(UPLOAD_SISWA)) mkdir(UPLOAD_SISWA, 0777, true);
?>