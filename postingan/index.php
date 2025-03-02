<?php
require "../inc/koneksi.php";
session_start();

// Generate atau verifikasi CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Validasi apakah user sudah login dan dapatkan ID dari session
if(isset($_SESSION['id'])) {  // Sesuaikan dengan nama session yang Anda gunakan
    $user_id = $_SESSION['id'];
    
    // Validasi user_id
    if (!is_numeric($user_id) || $user_id <= 0) {
        // Jika user_id tidak valid, atur ke 0 atau redirect ke halaman login
        $user_id = 0;
        // Atau: header("Location: login.php"); exit;
    }
    
    // Query untuk menghitung artikel berdasarkan user_id menggunakan placeholder '?'
    $query = "SELECT COUNT(*) as total_artikel FROM artikel WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id); // 'i' untuk integer
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $jumlahArtikel = $row['total_artikel'];
} else {
    $jumlahArtikel = 0; // Jika user belum login
    $user_id = 0; // Set default untuk menghindari error
}

function hitungArtikelArsip($conn, $user_id) {
    // Validasi parameter
    if (!is_numeric($user_id) || $user_id <= 0) {
        return 0;
    }
    
    // Gunakan placeholders '?' untuk MySQLi
    $query = "SELECT COUNT(*) as total_arsip FROM artikel WHERE user_id = ? AND status = ?";
    $stmt = $conn->prepare($query);
    $status = 'arsip';
    $stmt->bind_param("is", $user_id, $status); // 'i' untuk integer, 's' untuk string
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total_arsip'];
}

// Validasi parameter sebelum memanggil fungsi
if (isset($user_id) && is_numeric($user_id) && $user_id > 0) {
    $jumlahArtikelArsip = hitungArtikelArsip($conn, $user_id);
} else {
    $jumlahArtikelArsip = 0;
}

// Verifikasi parameter GET jika ada
if (isset($_GET['page'])) {
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
    if ($page === false || $page < 1) {
        $page = 1; // Default jika tidak valid
    }
} else {
    $page = 1; // Default jika tidak ada parameter
}

// Fungsi untuk escape output HTML untuk mencegah XSS
function escape_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- font awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
        integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- css -->
    <link rel="stylesheet" href="../css/style2.css">
    <link rel="stylesheet" href="../css/artikel1.css">
    <link rel="stylesheet" href="../css/postingan.css">
    <title>KitaSehat</title>
</head>

<body>
    <!-- Navbar start -->
    <div class="navbar" style="background-color: rgba(241, 241, 241);">
        <a href="#" class="navbar-logo">
            Kita<span>Sehat</span>.
        </a>
        <div class="navbar-nav">
            <a href="../index.php">Beranda</a>
            <a href="../index.php#layanan">About Us</a>
            <a href="../index.php#artikel">Artikel</a>
            <a href="../index.php#kontak">Kontak</a>
            <?php
                // Cek apakah sudah login berdasarkan session
                if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
                    // Periksa apakah ada role yang diset dalam session
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'penulis') {
                        echo '<a href="index.php">Postingan</a>';
                    }
                    // Tampilkan tombol Logout jika sudah login
                    echo '<a href="../profile.php" id="login">Profile</a>';
                } else {
                    // Jika belum login, tampilkan tombol Login
                    echo '<a href="login.php" id="login">Login</a>';
                }
            ?>
        </div>
        <div class="hamburger">
            <a href="#" id="hamburger" style="margin-left: 1rem;" class="fa-solid fa-bars fa-xl"></a>
        </div>
    </div>
    <!-- Navbar end -->

    <!-- Card Section start -->
    <section id="#" class="postingan" style="margin-top: 3rem;">
        <h2>Artikel Saya</h2>
    </section>
    <div class="card-container">
    <!-- Card Artikel -->
        <div class="card-wrapper">
            <div class="summary-card artikel">
                <div class="card-content">
                    <div class="icon-section">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="text-section">
                        <h3>Artikel</h3>
                        <p class="count"><?php echo escape_html($jumlahArtikel); ?> Artikel</p>
                        <p><a href="artikel.php">Lihat Detail</a></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-wrapper">
            <div class="summary-card artikel">
                <div class="card-content">
                    <div class="icon-section">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="text-section">
                        <h3>Arsip Artikel</h3>
                        <p class="count"><?php echo escape_html($jumlahArtikelArsip); ?> Artikel</p>
                        <p><a href="artikel-arsip.php">Lihat Detail</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Card Section end -->

    <!-- footer Section start -->
    <section class="footer">
        <div class="box-container">
            <div class="box">
                <a href="#" class="navbar-logo">
                    Kita<span>Sehat</span>.
                </a>
            </div>
            <div class="box">
                <h3>Quick Links</h3>
                <a href="#" class="link-footer">Beranda</a>
                <a href="#" class="link-footer">Layanan Kami</a>
                <a href="#" class="link-footer">Artikel</a>
                <a href="#" class="link-footer">Kontak</a>
            </div>
            <div class="box">
                <h3>Site Map</h3>
                <a href="#" class="link-footer">FAQ</a>
                <a href="#" class="link-footer">Blog</a>
                <a href="#" class="link-footer">Syarat & Ketentuan</a>
                <a href="#" class="link-footer">Kebijakan Privasi</a>
                <a href="#" class="link-footer">Karir</a>
                <a href="#" class="link-footer">Securty</a>
            </div>
            <div class="box">
                <h3>Social Media</h3>
                <a href="https://www.instagram.com/mmarsanj?igsh=MTN2MTM2YWZ3a3do" class="link-footer">Instagram</a>
                <a href="https://web.facebook.com/mmarsa.nj" class="link-footer">Facebook</a>                
                <a href="#" class="link-footer">Twitter</a>
            </div>
        </div>
        <div class="create">
            <a href="https://www.instagram.com/mmarsanj?igsh=MTN2MTM2YWZ3a3do" class="wm">
                Copyright@2023 | Created and Development by mmarsanj
            </a>
        </div>
    </section>
    <!-- Footer Section End -->
</body>
</html>