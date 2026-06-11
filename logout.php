<?php
// Set session name sama dengan di config.php
session_name('DIGITALISASI_SESSION');
session_start();

// Hancurkan session
session_destroy();

// Redirect ke halaman login (index.php)
header('Location: index');
exit;
?>