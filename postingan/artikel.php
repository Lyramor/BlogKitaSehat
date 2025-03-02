<?php
session_start();
require "../inc/koneksi.php";
require_once "auth.php"; 

// Authentication check
if(!isset($_SESSION['login'])){
    header("location: ../login.php");
    exit();
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['id'];

// Validasi user_id
if (!is_numeric($user_id)) {
    die("Invalid user ID");
}

// Prepared statement untuk kategori
$stmt = $conn->prepare("SELECT * FROM kategori");
$stmt->execute();
$queryKategori = $stmt->get_result();

// Handle form submission
if (isset($_POST['simpan'])) {
    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    
    // Validasi input
    $judul = trim($_POST['judul']);
    $kategori = trim($_POST['kategori']);
    $isi = $_POST['isi'];
    $sinopsis = trim($_POST['sinopsis']);
    $new_name = '';
    $error_message = '';
    
    // Validasi panjang input
    if (empty($judul) || strlen($judul) > 255) {
        $error_message = "Judul tidak boleh kosong dan maksimal 255 karakter";
    } elseif (empty($kategori) || !is_numeric($kategori)) {
        $error_message = "Kategori tidak valid";
    } elseif (empty($isi)) {
        $error_message = "Konten tidak boleh kosong";
    } elseif (empty($sinopsis)) {
        $error_message = "Sinopsis tidak boleh kosong";
    }
    
    // Jika tidak ada error, lanjutkan proses
    if (empty($error_message)) {
        // Image Upload Handling
        if (!empty($_FILES["gambar"]["name"])) {
            $target_dir = "../css/image/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $nama_file = basename($_FILES["gambar"]["name"]);
            $imageFileType = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));
            $image_size = $_FILES["gambar"]["size"];
            $random_name = bin2hex(random_bytes(10));
            $new_name = $random_name . "." . $imageFileType;

            $uploadOk = true;

            // Check file size
            if ($image_size > 4000000) {
                $error_message = "Ukuran file tidak boleh melebihi 4MB";
                $uploadOk = false;
            }

            // Allow certain file formats
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $error_message = "Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan";
                $uploadOk = false;
            }

            // Upload file if everything is ok
            if ($uploadOk) {
                if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_dir . $new_name)) {
                    // File uploaded successfully
                } else {
                    $error_message = "Maaf, terjadi kesalahan saat mengunggah file Anda.";
                    $uploadOk = false;
                }
            }

            if (!$uploadOk) {
                echo "<div class='alert alert-warning'>$error_message</div>";
                $new_name = ''; 
            }
        }

        // Database insertion dengan prepared statement
        if (empty($error_message)) {
            $stmt = $conn->prepare("INSERT INTO artikel 
                                (kategori_id, user_id, judul, isi, sinopsis, gambar, status) 
                                VALUES (?, ?, ?, ?, ?, ?, 'aktif')");
            $stmt->bind_param("iissss", $kategori, $user_id, $judul, $isi, $sinopsis, $new_name);
            
            if ($stmt->execute()) {
                echo '<div class="alert alert-success">Artikel berhasil disimpan</div>';
                echo '<meta http-equiv="refresh" content="2">';
            } else {
                echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            echo '<div class="alert alert-warning">' . $error_message . '</div>';
        }
    } else {
        echo '<div class="alert alert-warning">' . $error_message . '</div>';
    }
}

// Fetch articles with active status only using prepared statement
$stmt = $conn->prepare("SELECT artikel.*, kategori.nama AS nama_kategori 
                      FROM artikel 
                      JOIN kategori ON artikel.kategori_id = kategori.id 
                      WHERE artikel.user_id = ? 
                      AND artikel.status = 'aktif'
                      ORDER BY artikel.id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$query = $stmt->get_result();
$jumlahArtikel = $query->num_rows;

// Handle article deletion
if(isset($_POST['delete'])) {
    // CSRF Token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    
    $artikel_id = $_POST['artikel_id']; 
    
    // Validasi artikel_id
    if (!is_numeric($artikel_id)) {
        echo "<script>alert('ID artikel tidak valid!'); window.location.href = 'artikel.php';</script>";
        exit();
    }

    // Ambil data artikel sebelum dihapus dengan prepared statement
    $stmt = $conn->prepare("SELECT * FROM artikel WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $artikel_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $artikel = $result->fetch_assoc();

    if(!$artikel) {
        echo "<script>alert('Artikel tidak ditemukan!'); window.location.href = 'artikel.php';</script>";
        exit();
    }

    // Hapus gambar jika ada
    if (!empty($artikel['gambar'])) {
        $image_path = "../css/image/" . $artikel['gambar'];
        if (file_exists($image_path) && !unlink($image_path)) {
            echo "<script>alert('Gagal menghapus gambar!');</script>";
        }
    }

    // Hapus artikel dari database dengan prepared statement
    $stmt = $conn->prepare("DELETE FROM artikel WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $artikel_id, $user_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Artikel berhasil dihapus!'); window.location.href = 'artikel.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menghapus artikel: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

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
    
    return htmlspecialchars($teks_bersih, ENT_QUOTES, 'UTF-8');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <!-- Trix Editor CSS & JS -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.css">
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.js"></script>

    <!-- css -->
    <link rel="stylesheet" href="../css/style2.css">
    <link rel="stylesheet" href="../css/postingan.css">
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
    <title>KitaSehat</title>
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

    <!-- Form Section start -->
    <section id="#" class="postingan">
        <h2>Artikel Saya</h2>
    </section>
    <!-- tabel Artikel -->
    <div class="container">
    <h3 style="margin-bottom: 1rem;">Tambah Artikel Baru</h3>
        <form id="form-tambah-artikel" action="" method="post" enctype="multipart/form-data">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="judul">Judul <span class="text-danger">*</span></label>
                <input type="text" id="judul" name="judul" required 
                        placeholder="Masukkan judul artikel" maxlength="255">
            </div>

            <div class="form-group">
                <label for="kategori">Kategori <span class="text-danger">*</span></label>
                <select name="kategori" id="kategori" required>
                    <option value="">Pilih kategori</option>
                    <?php while ($data = mysqli_fetch_array($queryKategori)) { ?>
                        <option value="<?php echo $data['id']; ?>"><?php echo htmlspecialchars($data['nama'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="isi">Kontent <span class="text-danger">*</span></label>
                <input id="article-content" type="hidden" name="isi" required>
                <trix-editor input="article-content" class="trix-content" placeholder="Tulis artikel kamu disini"></trix-editor>
            </div>

            <div class="form-group">
                <label for="sinopsis">Sinopsis <span class="text-danger">*</span></label>
                <input id="article-synopsis" type="hidden" name="sinopsis" required>
                <trix-editor input="article-synopsis" class="trix-content" placeholder="Tulis ringkasan artikel kamu"></trix-editor>
            </div>

            <div class="form-group">
                <label for="gambar">Gambar</label>
                <input type="file" id="gambar" name="gambar" 
                        accept="image/jpg,image/jpeg,image/png,image/gif"
                        onchange="previewImage(this)">
                <div id="imagePreview" class="image-preview"></div>
                <small class="text-muted">Format yang diterima: JPG, PNG, GIF (maks 4MB)</small>
            </div>

            <div class="form-group">
                <button class="btn-primary" type="submit" name="simpan">Simpan</button>
                <button class="btn-danger" type="button" onclick="window.print()">Cetak</button>
            </div>
        </form>
    </div>

    <div class="container">
        <h2 style="margin-top: 1rem;">Artikel Saya</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Konten</th>
                        <th>Sinopsis</th>
                        <th>Gambar</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($jumlahArtikel == 0) { ?>
                        <tr><td colspan="8" class="text-center">Tidak ada artikel yang tersedia</td></tr>
                    <?php } else {
                        $nomor = 1;
                        while ($data = mysqli_fetch_array($query)) { ?>
                            <tr>
                                <td><?php echo $nomor++; ?></td>
                                <td><?php echo htmlspecialchars($data['judul'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($data['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="content-cell">
                                    <?php echo bersihkanHTML($data['isi'], 100); ?>
                                </td>
                                <td class="synopsis-cell">
                                    <?php echo bersihkanHTML($data['sinopsis'], 100); ?>
                                </td>
                                <td>
                                    <?php if ($data['gambar']) { ?>
                                        <img src="../css/image/<?php echo htmlspecialchars($data['gambar'], ENT_QUOTES, 'UTF-8'); ?>" class="article-image">
                                    <?php } else { ?>
                                        <span class="text-muted">Tidak Ada Gambar</span>
                                    <?php } ?>
                                </td>
                                <td class="timestamp"><?php echo htmlspecialchars($data['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="btn-action">
                                        <form action="" style="display:inline;">
                                            <a href="artikel-detail.php?p=<?php echo htmlspecialchars($data['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-info btn-sm">Lihat</a>
                                        </form>
                                    </div>
                                    <div class="btn-action">
                                        <form action="" style="display:inline;">
                                            <a href="artikel-edit.php?p=<?php echo htmlspecialchars($data['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-warning btn-sm">Edit</a>
                                        </form>   
                                    </div>
                                    <div class="btn-action">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="artikel_id" value="<?php echo htmlspecialchars($data['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" name="delete" class="btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus artikel ini?')">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- From Section end -->

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

    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.css">
    <script>
        // Validasi parameter GET
        function validateGetParameter() {
            const urlParams = new URLSearchParams(window.location.search);
            const pParam = urlParams.get('p');
            
            if (pParam !== null && !/^\d+$/.test(pParam)) {
                alert('Parameter tidak valid!');
                window.location.href = 'artikel.php';
                return false;
            }
            return true;
        }
        
        // Panggil validasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', validateGetParameter);
        
        // Form validation
        document.getElementById('form-tambah-artikel').onsubmit = function(e) {
            const title = document.getElementById('judul').value.trim();
            const category = document.getElementById('kategori').value;
            const content = document.getElementById('article-content').value.trim();
            const synopsis = document.getElementById('article-synopsis').value.trim();
            
            if (!title || !category || !content || !synopsis) {
                alert('Silakan isi semua bidang yang wajib diisi');
                e.preventDefault();
                return false;
            }
            
            // Validasi panjang judul
            if (title.length > 255) {
                alert('Judul tidak boleh lebih dari 255 karakter');
                e.preventDefault();
                return false;
            }
            
            // Validasi gambar
            const image = document.getElementById('gambar').files[0];
            if (image) {
                if (image.size > 4 * 1024 * 1024) {
                    alert('Ukuran gambar tidak boleh melebihi 4MB');
                    e.preventDefault();
                    return false;
                }
                
                const fileType = image.type.toLowerCase();
                if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif'].includes(fileType)) {
                    alert('Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan');
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        };

        // Fungsi untuk preview gambar
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '100%';
                    img.style.height = 'auto';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Styles for Trix Editor
        document.head.insertAdjacentHTML('beforeend', `
        <style>
            trix-editor {
                min-height: 300px;
                max-height: 500px;
                overflow-y: auto;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 10px;
                background-color: white;
                cursor: text;
            }
            
            trix-toolbar {
                background-color: #f8f9fa;
                padding: 5px;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-bottom: 5px;
            }

            trix-editor:focus {
                outline: none;
                border-color: #80bdff;
                box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
            }

            .trix-button-group {
                border: 1px solid #ddd;
                border-radius: 3px;
                margin-right: 5px;
            }

            .trix-button {
                background: #fff;
                border: none;
                color: #333;
                padding: 4px 8px;
                cursor: pointer;
            }

            .trix-button:hover {
                background: #f0f0f0;
            }

            .trix-button.trix-active {
                background: #e9ecef;
            }
        </style>
        `);
    </script>
</body>
</html>