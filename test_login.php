<?php
include 'config.php';
$user_id = 1; // ganti dengan id admin yang ada
$username = 'admin';
$nama = 'Admin';
$role = 'admin';
$ip = '127.0.0.1';
$user_agent = 'test';
$login_time = date('Y-m-d H:i:s');
$query = "INSERT INTO log_login (user_id, username, nama_lengkap, role, ip_address, user_agent, login_time) 
          VALUES ('$user_id', '$username', '$nama', '$role', '$ip', '$user_agent', '$login_time')";
if (mysqli_query($conn, $query)) {
    echo "Berhasil insert";
} else {
    echo "Gagal: " . mysqli_error($conn);
}
?>