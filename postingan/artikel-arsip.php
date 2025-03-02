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

// Handle unarchive with CSRF validation
if(isset($_POST['unarchive']) && isset($_POST['csrf_token'])) {
    // Validasi CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    // Validasi artikel_id
    if (!isset($_POST['artikel_id']) || !is_numeric($_POST['artikel_id'])) {
        echo "<script>alert('ID artikel tidak valid!'); window.location.href = 'artikel-arsip.php';</script>";
        exit();
    }
    
    $artikel_id = intval($_POST['artikel_id']);
    
    // Gunakan prepared statement untuk update
    $updateQuery = "UPDATE artikel 
                  SET status = 'aktif', 
                      archived_at = NULL 
                  WHERE id = ? 
                  AND user_id = ?";
                  
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "ii", $artikel_id, $user_id);
    $updateResult = mysqli_stmt_execute($stmt);
                                      
    if($updateResult) {
        // Regenerate CSRF token setelah operasi berhasil
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        echo "<script>alert('Artikel berhasil dibatalkan dari arsip!'); window.location.href = 'artikel-arsip.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal membatalkan arsip artikel: " . mysqli_error($conn) . "');</script>";
    }
}

// Handle delete with CSRF validation
if(isset($_POST['delete']) && isset($_POST['csrf_token'])) {
    // Validasi CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
    
    // Validasi artikel_id
    if (!isset($_POST['artikel_id']) || !is_numeric($_POST['artikel_id'])) {
        echo "<script>alert('ID artikel tidak valid!'); window.location.href = 'artikel-arsip.php';</script>";
        exit();
    }
    
    $artikel_id = intval($_POST['artikel_id']);
    
    // Get article data before deletion using prepared statement
    $queryGetArtikel = "SELECT * FROM artikel WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $queryGetArtikel);
    mysqli_stmt_bind_param($stmt, "ii", $artikel_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $artikel = mysqli_fetch_assoc($result);
    
    if(!$artikel) {
        echo "<script>alert('Artikel tidak ditemukan!'); window.location.href = 'artikel-arsip.php';</script>";
        exit();
    }
    
    // Delete image if exists
    if(!empty($artikel['gambar'])) {
        $image_path = "../css/image/" . $artikel['gambar'];
        if(file_exists($image_path) && !unlink($image_path)) {
            echo "<script>alert('Gagal menghapus gambar!');</script>";
        }
    }
    
    // Delete article from database using prepared statement
    $deleteQuery = "DELETE FROM artikel WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $deleteQuery);
    mysqli_stmt_bind_param($stmt, "ii", $artikel_id, $user_id);
    $deleteResult = mysqli_stmt_execute($stmt);
    
    if($deleteResult) {
        // Regenerate CSRF token setelah operasi berhasil
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        echo "<script>alert('Artikel berhasil dihapus!'); window.location.href = 'artikel-arsip.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus artikel: " . mysqli_error($conn) . "');</script>";
    }
}

// Fetch archived articles using prepared statement
$query = "SELECT artikel.*, kategori.nama AS nama_kategori 
          FROM artikel 
          JOIN kategori ON artikel.kategori_id = kategori.id 
          WHERE artikel.user_id = ? 
          AND artikel.status = 'arsip'
          ORDER BY artikel.archived_at DESC";
          
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$jumlahArtikel = mysqli_num_rows($result);

function bersihkanHTML($konten, $panjang) {
    // Decode HTML entities (seperti &nbsp;, &lt;, dll)
    $konten = html_entity_decode($konten);
    
    // Hapus semua tag HTML
    $teks_bersih = strip_tags($konten);
    
    // Bersihkan whitespace berlebih
    $teks_bersih = trim(preg_replace('/\s+/', ' ', $teks_bersih));
    
    // Potong teks sesuai panjang yang diinginkan
    if (strlen($teks_bersih) > $panjang) {
        $teks_bersih = substr($teks_bersih, 0, $panjang) . '...';
    }
    
    return $teks_bersih;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style2.css">
    <link rel="stylesheet" href="../css/postingan.css">
    <title>Archived Articles - KitaSehat</title>
    <style>
    .btn-primary, .btn-warning, .btn-info, .btn-danger, .btn-secondary {
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

    .current-image {
        margin: 10px 0;
        max-width: 200px;
    }

    a .btn-sm {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        margin: auto;
    }

    .content-cell, .synopsis-cell {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .table {
        table-layout: fixed;
        width: 100%;
    }
    .table th, .table td {
        word-wrap: break-word;
        padding: 8px;
    }

    .btn-action{
        margin-top: 1rem;
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
            <?php if (isset($_SESSION['login']) && $_SESSION['login'] === true): ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'penulis'): ?>
                    <a href="./index.php">Postingan</a>
                <?php endif; ?>
                <a href="../profile.php" id="login">Profile</a>
            <?php else: ?>
                <a href="login.php" id="login">Login</a>
            <?php endif; ?>
        </div>
        <div class="hamburger">
            <a href="#" id="hamburger" class="fa-solid fa-bars fa-xl"></a>
        </div>
    </div>
    <!-- Navbar end -->

    <div class="container">
        <h2 style="margin-top: 8rem;">Arsip Artikel</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Sinopsis</th>
                        <th>Gambar</th>
                        <th>Dibuat pada</th>
                        <th>Arsip pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($jumlahArtikel == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada arsip artikel ditemukan.</td>
                        </tr>
                    <?php else: 
                        $nomor = 1;
                        while ($data = mysqli_fetch_array($result)): ?>
                            <tr>
                                <td><?php echo $nomor++; ?></td>
                                <td><?php echo htmlspecialchars($data['judul']); ?></td>
                                <td><?php echo htmlspecialchars($data['nama_kategori']); ?></td>
                                <td><?php echo htmlspecialchars(bersihkanHTML($data['isi'], 90)); ?></td>
                                <td>
                                    <?php if ($data['gambar']): ?>
                                        <img src="../css/image/<?php echo htmlspecialchars($data['gambar']); ?>" class="article-image" alt="<?php echo htmlspecialchars($data['judul']); ?>">
                                    <?php else: ?>
                                        <span class="text-muted">Tidak ada gambar</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y H:i', strtotime($data['created_at'])); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($data['archived_at'])); ?></td>
                                <td>
                                    <div class="btn-action">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="artikel_id" value="<?php echo intval($data['id']); ?>">
                                            <button type="submit" name="unarchive" class="btn-warning btn-sm" onclick="return confirm('Apakah Anda yakin ingin membatalkan arsip artikel ini?')">
                                                Batal Arsip
                                            </button>
                                        </form>
                                    </div>
                                    <div class="btn-action">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="artikel_id" value="<?php echo intval($data['id']); ?>">
                                            <button type="submit" name="delete" class="btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus artikel ini?')">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer Section -->
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
                <a href="https://www.instagram.com/mmarsanj" class="link-footer">Instagram</a>
                <a href="https://web.facebook.com/mmarsa.nj" class="link-footer">Facebook</a>
                <a href="#" class="link-footer">Twitter</a>
            </div>
        </div>
        <div class="create">
            <a href="https://www.instagram.com/mmarsanj" class="wm">
                Copyright@2023 | Created and Development by mmarsanj
            </a>
        </div>
    </section>
</body>
</html>