<?php
error_reporting(0);
include '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['messages' => []]);
    exit;
}

$kelas_id = (int)$_GET['kelas_id'];
$last_id = (int)$_GET['last_id'];

$query = "SELECT c.*, u.nama_lengkap as nama 
          FROM chat_messages c 
          JOIN user u ON c.user_id = u.id 
          WHERE c.kelas_id = $kelas_id AND c.id > $last_id 
          ORDER BY c.id ASC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['messages' => []]);
    exit;
}

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = [
    'id' => $row['id'],
    'user_id' => $row['user_id'],
    'nama' => $row['nama'],
    'message' => $row['message'],
    'time' => date('H:i', strtotime($row['created_at'])),
    'tanggal' => date('Y-m-d', strtotime($row['created_at']))  // <-- baris ini
    ];
}

echo json_encode(['messages' => $messages]);
?>