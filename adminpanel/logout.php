<?php
session_start();
require __DIR__ . '/../inc/koneksi.php';
require __DIR__ . '/../inc/log_functions.php';


// Log aktivitas logout sebelum menghapus session
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    catat_log($conn, "Logout berhasil", 'info', $_SESSION['id'] ?? null, $_SESSION['username'] ?? null);
}

// Hapus session
session_unset();
session_destroy();

// Hapus cookie jika ada
if (isset($_COOKIE['id'])) {
    setcookie('id', '', time() - 3600, '/');
}
if (isset($_COOKIE['key'])) {
    setcookie('key', '', time() - 3600, '/');
}

// Redirect ke halaman login
header("Location: ../login.php");
exit;
?>