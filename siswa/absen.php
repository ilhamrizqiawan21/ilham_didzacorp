<?php
include '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'siswa') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$query_siswa = "SELECT * FROM siswa WHERE user_id='$user_id'";
$siswa = mysqli_fetch_assoc(mysqli_query($conn, $query_siswa));
$siswa_id = $siswa['id'];

$query_absensi = "SELECT p.tanggal, p.topik, a.status FROM absensi a JOIN pertemuan p ON a.pertemuan_id = p.id WHERE a.siswa_id='$siswa_id' ORDER BY p.tanggal DESC";
$absensi = mysqli_query($conn, $query_absensi);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Kehadiran Saya</title>
    <link rel="stylesheet" type="text/css" href="../style.css">
</head>
<body>
<div class="container">
    <h2>Riwayat Absensi <?= $siswa['nama'] ?></h2>
    <table border="1">
        <tr><th>Tanggal</th><th>Topik</th><th>Status</th></tr>
        <?php while($row = mysqli_fetch_assoc($absensi)): ?>
        <tr>
            <td><?= $row['tanggal'] ?></td>
            <td><?= $row['topik'] ?></td>
            <td><?= ucfirst($row['status']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="dashboard">Kembali ke Dashboard</a>
</div>
</body>
</html>