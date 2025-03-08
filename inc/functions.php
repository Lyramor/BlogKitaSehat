<?php
require "koneksi.php";

function query($query)
{
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function register($data)
{
    global $conn;

    $username = mysqli_real_escape_string($conn, strtolower(trim($data["username"])));
    $nama_lengkap = mysqli_real_escape_string($conn, trim($data["nama_lengkap"]));
    $email = mysqli_real_escape_string($conn, strtolower(trim($data["email"])));
    $password = mysqli_real_escape_string($conn, $data["password"]);
    $password1 = mysqli_real_escape_string($conn, $data["password1"]);

    // Validasi
    if (empty($username)) {
        $_SESSION["error"] = "Username tidak boleh kosong!";
        return false;
    }
    if (strlen($username) < 3) {
        $_SESSION["error"] = "Username minimal 3 karakter!";
        return false;
    }
    if (!preg_match("/^[a-zA-Z0-9]+$/", $username)) {
        $_SESSION["error"] = "Username hanya boleh mengandung huruf dan angka!";
        return false;
    }
    if (empty($nama_lengkap)) {
        $_SESSION["error"] = "Nama lengkap tidak boleh kosong!";
        return false;
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["error"] = "Email tidak valid!";
        return false;
    }
    if (empty($password) || strlen($password) < 6) {
        $_SESSION["error"] = "Password minimal 6 karakter!";
        return false;
    }
    if ($password !== $password1) {
        $_SESSION["error"] = "Konfirmasi password tidak sesuai!";
        return false;
    }

    // Cek apakah username sudah ada
    $stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION["error"] = "Username sudah terdaftar!";
        mysqli_stmt_close($stmt);
        return false;
    }
    mysqli_stmt_close($stmt);

    // Cek apakah email sudah ada
    $stmt = mysqli_prepare($conn, "SELECT email FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION["error"] = "Email sudah terdaftar!";
        mysqli_stmt_close($stmt);
        return false;
    }
    mysqli_stmt_close($stmt);

    // Enkripsi password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $foto_profil = 'default.jpg'; // Foto default

    // **Simpan data sementara di SESSION untuk pilih role nanti**
    $_SESSION["temp_user"] = [
        "username" => $username,
        "nama_lengkap" => $nama_lengkap,
        "email" => $email,
        "password" => $hashedPassword, // Pastikan sudah di-hash sebelum masuk session
        "foto_profil" => $foto_profil
    ];

    // **Tampilkan pilihan role melalui SweetAlert**
    $_SESSION["success"] = "Registrasi berhasil! Silakan pilih peran Anda.";
    return true;
}
