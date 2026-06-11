<?php
include '../config.php';
header('Content-Type: application/json');
$tugas_id = (int)$_GET['tugas_id'];
$query = "SELECT s.id as siswa_id, s.nis, s.nama, k.nama_kelas as kelas, 
                 COALESCE(nt.status, 'belum') as status, nt.nilai
          FROM siswa s 
          LEFT JOIN kelas k ON s.kelas_id = k.id
          LEFT JOIN nilai_tugas nt ON nt.siswa_id = s.id AND nt.tugas_id = $tugas_id
          ORDER BY k.nama_kelas, s.nama";
$res = mysqli_query($conn, $query);
$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}
echo json_encode($data);
?>