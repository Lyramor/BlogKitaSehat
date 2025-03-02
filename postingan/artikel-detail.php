<?php
session_start();
require "../inc/koneksi.php";
require_once "auth.php";

// Authentication check
if(!isset($_SESSION['login'])){
    header("location: ../login.php");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['id'];

// Validasi parameter GET
$article_id = 0;
if (isset($_GET['p']) && is_numeric($_GET['p'])) {
    $article_id = intval($_GET['p']);
} else if (isset($_GET['p'])) {
    // Parameter ada tapi tidak valid
    die("Parameter tidak valid!");
}

// Fetch article details with user and category information using prepared statement
$query = "SELECT artikel.*, kategori.nama AS nama_kategori, users.nama_lengkap 
          FROM artikel 
          JOIN kategori ON artikel.kategori_id = kategori.id 
          JOIN users ON artikel.user_id = users.id
          WHERE artikel.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $article_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$article = mysqli_fetch_assoc($result);

// Jika artikel tidak ditemukan
if (!$article && $article_id != 0) {
    die("Artikel tidak ditemukan!");
}

// Handle archive action with CSRF validation
if(isset($_POST['archive']) && isset($_POST['csrf_token'])) {
    // Validasi CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    $currentTimestamp = date('Y-m-d H:i:s');
    
    if($article['status'] === 'arsip') {
        // Unarchive - Using prepared statement
        $updateQuery = "UPDATE artikel SET status = 'aktif', archived_at = NULL WHERE id = ?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($stmt, "i", $article_id);
        $updateResult = mysqli_stmt_execute($stmt);
    } else {
        // Archive - Using prepared statement
        $updateQuery = "UPDATE artikel SET status = 'arsip', archived_at = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($stmt, "si", $currentTimestamp, $article_id);
        $updateResult = mysqli_stmt_execute($stmt);
    }
    
    if($updateResult) {
        // Regenerate CSRF token after successful submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: " . $_SERVER['PHP_SELF'] . "?p=" . $article_id);
        exit();
    }
}

// Handle delete action with CSRF validation
if(isset($_POST['delete']) && isset($_POST['csrf_token'])) {
    // Validasi CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    if(!$article) {
        echo "<script>alert('Artikel tidak ditemukan!'); window.location.href = 'artikel.php';</script>";
        exit();
    }

    // Delete the article's image if it exists
    if (!empty($article['gambar'])) {
        $image_path = "../css/image/" . $article['gambar'];
        if (file_exists($image_path)) {
            if (!unlink($image_path)) {
                echo "<script>alert('Gagal menghapus gambar!');</script>";
            }
        }
    }
    
    // Delete the article from database using prepared statement
    $deleteQuery = "DELETE FROM artikel WHERE id = ?";
    $stmt = mysqli_prepare($conn, $deleteQuery);
    mysqli_stmt_bind_param($stmt, "i", $article_id);
    $deleteResult = mysqli_stmt_execute($stmt);

    if ($deleteResult) {
        // Regenerate CSRF token after successful submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        echo "<script>alert('Artikel berhasil dihapus!'); window.location.href = 'artikel.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus artikel: " . mysqli_error($conn) . "');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Artikel - <?php echo htmlspecialchars($article['judul'] ?? 'Tidak Ditemukan'); ?></title>
    <link rel="stylesheet" href="../css/style2.css">
    <link rel="stylesheet" href="../css/postingan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        .btn-primary, .btn-warning, .btn-info, .btn-danger {
            padding: 8px 16px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #000;
        }

        .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
        }

        .btn-primary:hover, .btn-warning:hover, .btn-info:hover, .btn-danger:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Navbar start -->
    <div class="navbar" style="background-color: rgba(241, 241, 241);">
        <a href="#" class="navbar-logo">
            Kita<span>Sehat</span>.
        </a>
        <div class="navbar-nav">
            <a href="../index.php#beranda">Beranda</a>
            <a href="../index.php#layanan">About Us</a>
            <a href="../index.php#artikel">Artikel</a>
            <a href="../index.php#kontak">Kontak</a>
            <?php
                // Cek apakah sudah login berdasarkan session
                if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
                    // Periksa apakah ada role yang diset dalam session
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'penulis') {
                        echo '<a href="./index.php">Postingan</a>';
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

    <div class="container">
        <?php if($article): ?>
        <div class="article-detail">
            <div class="article-header">
                <h1><?php echo htmlspecialchars($article['judul']); ?></h1>
                <div class="article-meta">
                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['nama_lengkap']); ?></span>
                    <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($article['nama_kategori']); ?></span>
                    <span><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($article['created_at'])); ?></span>
                    <span class="status <?php echo htmlspecialchars($article['status']); ?>">
                        <i class="fas fa-circle"></i> <?php echo ucfirst(htmlspecialchars($article['status'])); ?>
                    </span>
                    <?php if($article['archived_at']): ?>
                        <span><i class="fas fa-archive"></i> Archived: <?php echo date('d M Y', strtotime($article['archived_at'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($article['gambar']): ?>
            <div class="article-image">
                <img src="../css/image/<?php echo htmlspecialchars($article['gambar']); ?>" 
                      alt="<?php echo htmlspecialchars($article['judul']); ?>">
            </div>
            <?php endif; ?>

            <div class="article-synopsis">
                <h3>Sinopsis</h3>
                <div class="synopsis-content">
                    <?php echo htmlspecialchars($article['sinopsis']); ?>
                </div>
            </div>

            <div class="article-content">
                <h3>Konten</h3>
                <div class="content-body">
                    <?php echo $article['isi']; ?>
                </div>
            </div>

            <div class="article-actions">
              <form action="" style="display: inline;">
                <a href="./artikel.php" class="btn btn-secondary">
                  <i class="fas fa-arrow-left"></i> Kembali
                </a>
              </form>
                
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                  <button type="submit" name="archive" class="btn btn-warning">
                    <i class="fas <?php echo $article['status'] === 'arsip' ? 'fa-box-open' : 'fa-archive'; ?>"></i>
                    <?php echo $article['status'] === 'arsip' ? 'Batalkan Arsip' : 'Arsip'; ?>
                  </button>
                </form>

                <form action="" style="display: inline;">
                  <button onclick="window.print()" class="btn btn-info">
                    <i class="fas fa-print"></i> Cetak
                  </button>
                </form>

                <form method="POST" style="display: inline;" 
                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus artikel ini?')">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" name="delete" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="article-not-found">
            <h2>Artikel tidak ditemukan</h2>
            <p>Maaf, artikel yang Anda cari tidak tersedia.</p>
            <a href="./artikel.php" class="btn btn-primary">Kembali ke Daftar Artikel</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <section class="footer">
        <div class="box-container">
            <div class="box">
                <a href="#" class="navbar-logo">Kita<span>Sehat</span>.</a>
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
                <a href="#" class="link-footer">Instagram</a>
                <a href="#" class="link-footer">Facebook</a>
                <a href="#" class="link-footer">Twitter</a>
            </div>
        </div>
        <div class="create">
            <a href="#" class="wm">Copyright@2023 | Created and Development by mmarsanj</a>
        </div>
    </section>
</body>
</html>