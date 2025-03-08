<?php
session_start();
require 'inc/koneksi.php';
require 'inc/functions.php';

if (isset($_POST["register"])) {
    // Validate phone number (you can add more complex validation)
    if (!preg_match('/^[0-9]{10,12}$/', $_POST["no_hp"])) {
        $_SESSION["error"] = "Nomor HP tidak valid.  Harus terdiri dari 10-12 digit angka.";
    } else {
        if (register($_POST) > 0) {
            // Simpan data user sementara sebelum memilih role
            $_SESSION["temp_user"] = [
                "username" => $_POST["username"],
                "nama_lengkap" => $_POST["nama_lengkap"],
                "email" => $_POST["email"],
                "no_hp" => $_POST["no_hp"], 
                "password" => password_hash($_POST["password"], PASSWORD_DEFAULT), // Hash password
                "foto_profil" => "default.png" // Bisa diganti sesuai kebutuhan
            ];
            $_SESSION["success"] = "Registrasi berhasil! Silakan pilih peran Anda.";
        } else {
            $_SESSION["error"] = "Registrasi gagal! Silakan coba lagi.";
        }
    }
}

// Jika role dipilih, simpan user ke database
if (isset($_POST["role"])) {
    if (!isset($_SESSION['temp_user'])) {
        $_SESSION["error"] = "Data registrasi tidak ditemukan!";
    } else {
        $data = $_SESSION['temp_user'];
        $role = $_POST["role"];

        // Insert ke database
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, nama_lengkap, email, no_hp, foto_profil, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param(
            $stmt,
            "sssssss",
            $data['username'],
            $data['nama_lengkap'],
            $data['email'],
            $data['no_hp'], // Include phone number
            $data['foto_profil'],
            $data['password'],
            $role
        );

        if (mysqli_stmt_execute($stmt)) {
            unset($_SESSION['temp_user']); // Hapus data sementara
            $_SESSION["success_final"] = "Akun berhasil dibuat sebagai $role! Silakan login.";
        } else {
            $_SESSION["error"] = "Gagal menyimpan peran!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - KitaSehat</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/login4.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="main-container">
        <input type="checkbox" id="slide" />
        <div class="container">
            <div class="signup-container">
                <div class="text">Register</div>

                <form action="" method="post">
                    <div class="data">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" autofocus autocomplete="off" required />
                    </div>

                    <div class="data">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" autocomplete="off" required />
                    </div>

                    <div class="data">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" autocomplete="off" required />
                    </div>

                    <div class="data">
                        <label for="no_hp">Nomor HP</label>
                        <input type="text" name="no_hp" id="no_hp" autocomplete="off" required />
                    </div>


                    <div class="data">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required />
                    </div>

                    <div class="data">
                        <label for="password1">Konfirmasi Password</label>
                        <input type="password" name="password1" id="password1" required />
                    </div>

                    <div class="btn-signup">
                        <button type="submit" name="register" id="register">Register</button>
                    </div>

                    <div class="signup-link">
                        Sudah punya akun?
                        <a href="login.php">Login</a>
                    </div>
                    <div class="signup-link">
                        <a href="index.php" style="text-decoration: none; color: black;">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SweetAlert Notifikasi -->
    <?php
    if (isset($_SESSION["success"])) {
        echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Registrasi Berhasil!',
            text: '" . $_SESSION["success"] . "',
            showDenyButton: true,
            confirmButtonText: 'User',
            denyButtonText: 'Penulis'
        }).then((result) => {
            let role = '';
            if (result.isConfirmed) {
                role = 'user';
            } else if (result.isDenied) {
                role = 'penulis';
            }
    
            if (role !== '') {
                document.getElementById('roleInput').value = role;
                document.getElementById('roleForm').submit();
            }
        });
        </script>";
        unset($_SESSION["success"]);
    }

    if (isset($_SESSION["success_final"])) {
        echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Registrasi Selesai!',
            text: '" . $_SESSION["success_final"] . "',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = 'login.php';
        });
        </script>";
        unset($_SESSION["success_final"]);
    }

    if (isset($_SESSION["error"])) {
        echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Registrasi Gagal!',
            text: '" . $_SESSION["error"] . "'
        });
        </script>";
        unset($_SESSION["error"]);
    }
    ?>

    <!-- Form hidden untuk mengirim role -->
    <form id="roleForm" method="post" action="" style="display: none;">
        <input type="hidden" name="role" id="roleInput">
    </form>

</body>

</html>