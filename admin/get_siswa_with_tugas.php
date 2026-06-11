<?php
include '../config.php';
$kelas_id = (int)$_GET['kelas_id'];
$tugas_id = (int)$_GET['tugas_id'];
$result = mysqli_query($conn, "SELECT s.id, s.nis, s.nama, pt.status, pt.nilai, pt.file_upload, pt.teks_jawaban, pt.catatan 
    FROM siswa s 
    LEFT JOIN pengumpulan_tugas pt ON pt.siswa_id = s.id AND pt.tugas_id = $tugas_id 
    WHERE s.kelas_id = $kelas_id ORDER BY s.nama");
$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'nis' => $row['nis'],
        'nama' => $row['nama'],
        'status' => $row['status'] ?: 'belum',
        'nilai' => $row['nilai'],
        'file_upload' => $row['file_upload'],
        'teks_jawaban' => $row['teks_jawaban'],
        'catatan' => $row['catatan']   // tambahkan ini
    ];
}
header('Content-Type: application/json');
echo json_encode($data);
?>