<?php
include '../config.php';
$kelas_id = (int)$_GET['kelas_id'];
$result = mysqli_query($conn, "SELECT id, nis, nama FROM siswa WHERE kelas_id=$kelas_id ORDER BY nama");
$data = [];
while($row = mysqli_fetch_assoc($result)) $data[] = $row;
header('Content-Type: application/json');
echo json_encode($data);
?>