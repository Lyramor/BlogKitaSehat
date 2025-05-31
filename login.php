<?php
session_start();
require "inc/koneksi.php";
require "inc/functions.php";

// Cek cookie
if (isset($_COOKIE['id']) && isset($_COOKIE['key'])) {
    $id = $_COOKIE['id'];
    $key = $_COOKIE['key'];

    // Ambil username berdasarkan id
    $result = mysqli_query($conn, "SELECT id, username, role FROM users WHERE id = $id");
    $row = mysqli_fetch_assoc($result);

    // Cek cookie dan username
    if ($key === hash('sha256', $row['username'])) {
        $_SESSION['login'] = true;
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['id'] = $row['id'];
    }
}

if (isset($_POST["login"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $recaptcha_response = $_POST['g-recaptcha-response'];
    
    // Verifikasi CAPTCHA
    $secret_key = "6LeEFE0rAAAAAD7Pz-A101yYC_m8cN2P8b97spAc"; // Ganti dengan Secret Key dari Google reCAPTCHA
    $verify_url = "https://www.google.com/recaptcha/api/siteverify";
    
    $response = file_get_contents($verify_url . "?secret=" . $secret_key . "&response=" . $recaptcha_response);
    $response_data = json_decode($response);
    
    if (!$response_data->success) {
        $captcha_error = true;
    } else {
        $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");

        // Cek username
        if (mysqli_num_rows($result) === 1) {
            // Cek password
            $row = mysqli_fetch_assoc($result);
            if (password_verify($password, $row["password"])) {
                // Set session
                $_SESSION["login"] = true;
                $_SESSION["username"] = $username;
                $_SESSION["role"] = $row['role'];
                $_SESSION["id"] = $row['id'];

                // Redirect ke halaman sesuai peran
                if ($row['role'] === 'admin') {
                    header("Location: adminpanel/index.php");
                    exit;
                } else if ($row['role'] === 'user') {
                    header("Location: index.php");
                    exit;
                } else if ($row['role'] === 'penulis') {
                    header("Location: index.php");
                    exit;
                } else {
                    echo "<script>alert('Anda tidak memiliki akses.');</script>";
                }
            } else {
                $error = true;
            }
        } else {
            $error = true;
        }
    }
}

// Check for account deletion cookie
if (isset($_COOKIE['account_deleted']) && $_COOKIE['account_deleted'] === 'true') {
    $_SESSION['success'] = $_COOKIE['success_message'] ?? "Akun Anda berhasil dihapus";
    $_SESSION['alert_type'] = "success";

    // Clear the cookies
    setcookie('account_deleted', '', time() - 3600, '/');
    setcookie('success_message', '', time() - 3600, '/');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <!-- css -->
    <link rel="stylesheet" href="css/login4.css">
    <!-- Google reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <div class="main-container">
        <input type="checkbox" id="slide" />
        <div class="container">
            <div class="signup-container">
                <div class="text">Login</div>

                <?php if (isset($error)) : ?>
                    <p style="color: red; font-style: italic;"> Username / Password salah</p>
                <?php endif; ?>

                <?php if (isset($captcha_error)) : ?>
                    <p style="color: red; font-style: italic;"> Mohon verifikasi CAPTCHA terlebih dahulu</p>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="data">
                        <label for="">Username</label>
                        <input type="text" name="username" id="username" autofocus autocomplete="off" required />
                    </div>

                    <div class="data">
                        <label for="">Password</label>
                        <input type="password" name="password" id="password" required />
                    </div>

                    <!-- CAPTCHA -->
                    <div class="captcha-container">
                        <div class="g-recaptcha" data-sitekey=6LeEFE0rAAAAAEXwpu1TxhVExILaKs-pp-LU8Dph></div>
                    </div>

                    <div class="btn-signup">
                        <button type="submit" name="login">login</button>
                    </div>

                    <div class="signup-link">
                        Belum punya akun ?<a href="register.php">Register now</a>
                    </div>
                    <div class="signup-link">
                        <a href="index.php" style="text-decoration: none; color: black; ">kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Validasi form sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            var recaptcha = grecaptcha.getResponse();
            if (recaptcha === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'CAPTCHA Required',
                    text: 'Mohon verifikasi CAPTCHA terlebih dahulu!'
                });
            }
        });
    </script>
</body>

</html>