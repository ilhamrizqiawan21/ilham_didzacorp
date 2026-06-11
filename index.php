<?php
include 'config.php';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') header('Location: admin/dashboard');
    else header('Location: siswa/dashboard');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    $query = "SELECT * FROM user WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama'] = $user['nama_lengkap'];
        
        // ========== CATAT LOG LOGIN ==========
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT'] ?? '');
        $login_time = date('Y-m-d H:i:s');
        $query_log = "INSERT INTO log_login (user_id, username, nama_lengkap, role, ip_address, user_agent, login_time) 
                      VALUES ('{$user['id']}', '{$user['username']}', '{$user['nama_lengkap']}', '{$user['role']}', '$ip', '$user_agent', '$login_time')";
        if (!mysqli_query($conn, $query_log)) {
            // Catat error ke file di root folder
            $error_detail = mysqli_error($conn);
            file_put_contents(__DIR__ . '/login_error_log.txt', date('Y-m-d H:i:s') . " - User: {$user['username']} - Error: $error_detail\n", FILE_APPEND);
        }
        // =====================================
        
        if ($user['role'] == 'admin') header('Location: admin/dashboard');
        else header('Location: siswa/dashboard');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Digitalisasi Pembelajaran</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="login-container">
    <h2>Login Digitalisasi Pembelajaran</h2>
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <p>Apabila lupa akun, silahkan hubungi Pak Ilham - WA 0895802329062</p>
</div>
</body>
</html>