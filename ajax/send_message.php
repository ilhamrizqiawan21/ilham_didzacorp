<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$kelas_id = (int)$_POST['kelas_id'];
$message = mysqli_real_escape_string($conn, $_POST['message']);

if (empty($message)) {
    http_response_code(400);
    die('Empty message');
}

$query = "INSERT INTO chat_messages (user_id, kelas_id, message) VALUES ($user_id, $kelas_id, '$message')";
mysqli_query($conn, $query);
echo 'ok';
?>