<?php
include '../config.php';
header('Content-Type: application/json');

$bab_id = isset($_GET['bab_id']) ? (int)$_GET['bab_id'] : 0;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bab_id == 0 && $id == 0) {
    echo json_encode([]);
    exit;
}

$query = "SELECT id, alur_tujuan, materi, alokasi_waktu FROM atp ";
if ($id > 0) {
    $query .= "WHERE id = $id";
    $res = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($res);
    echo json_encode($data ? [$data] : []);
} else {
    $query .= "WHERE bab_id = $bab_id ORDER BY id";
    $res = mysqli_query($conn, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    echo json_encode($data);
}
?>