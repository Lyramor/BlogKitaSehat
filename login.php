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

                <form action="" method="post">
                    <div class="data">
                        <label for="">Username</label>
                        <input type="text" name="username" id="username" autofocus autocomplete="off" required />
                    </div>

                    <div class="data">
                        <label for="">Password</label>
                        <input type="password" name="password" id="password" required />
                    </div>

                    <div class="btn-signup">
                        <button type="login" name="login">login</button>
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
    </div>
</body>

</html>