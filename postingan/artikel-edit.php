<?php
session_start();
require "../inc/koneksi.php";
require_once "auth.php"; 

// Authentication check
if(!isset($_SESSION['login']) || !isset($_SESSION['id'])){
    header("location: ../login.php");
    exit();
}

// 1. Tambahkan CSRF token protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['id'];

// 2. Validasi parameter GET 
if (!isset($_GET['p']) || !preg_match('/^[0-9]+$/', $_GET['p'])) {
    header("Location: index.php");
    exit();
}

$artikel_id = $_GET['p'];

// 3. Gunakan named parameter untuk SQL query dengan PDO
// Ubah koneksi ke PDO jika belum menggunakan PDO
try {
    // Contoh konversi mysqli ke PDO (asumsikan $conn sudah ada)
    if (!isset($pdo)) {
        // Uncomment baris berikut jika perlu mengubah ke PDO
        // $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Gunakan $conn yang sudah ada untuk contoh ini
        $stmt = $conn->prepare("SELECT * FROM artikel WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $artikel_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Jika sudah menggunakan PDO
        $stmt = $pdo->prepare("SELECT * FROM artikel WHERE id = :artikel_id AND user_id = :user_id");
        $stmt->bindParam(':artikel_id', $artikel_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (mysqli_num_rows($result) == 0) {
        header("Location: index.php");
        exit();
    }

    $artikel = mysqli_fetch_assoc($result);

    // Get categories using named parameters
    $stmt_kategori = $conn->prepare("SELECT * FROM kategori");
    $stmt_kategori->execute();
    $queryKategori = $stmt_kategori->get_result();
    
    // Handle form submission with CSRF protection
    if (isset($_POST['update'])) {
        // 1. Validasi CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed');
        }
        
        try {
            // Validasi inputan
            $errors = [];
            
            // Validasi judul
            if (empty($_POST['judul'])) {
                $errors[] = "Judul tidak boleh kosong";
            } else {
                $judul = htmlspecialchars($_POST['judul']);
            }
            
            // Validasi kategori
            if (empty($_POST['kategori']) || !preg_match('/^[0-9]+$/', $_POST['kategori'])) {
                $errors[] = "Kategori tidak valid";
            } else {
                $kategori = htmlspecialchars($_POST['kategori']);
            }
            
            // Validasi isi
            if (empty($_POST['isi'])) {
                $errors[] = "Isi artikel tidak boleh kosong";
            } else {
                $isi = htmlspecialchars($_POST['isi']);
            }
            
            // Validasi sinopsis
            if (empty($_POST['sinopsis'])) {
                $errors[] = "Sinopsis tidak boleh kosong";
            } else {
                $sinopsis = htmlspecialchars($_POST['sinopsis']);
            }
            
            // Jika ada error, tampilkan pesan
            if (!empty($errors)) {
                throw new Exception(implode("<br>", $errors));
            }
            
            $new_name = $artikel['gambar'];

            // Image handling
            if (!empty($_FILES["gambar"]["name"])) {
                $target_dir = __DIR__ . "/../css/image/";
                
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                // File validation
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($_FILES["gambar"]["type"], $allowed_types)) {
                    throw new Exception("Hanya file JPG, PNG & GIF yang diperbolehkan");
                }

                if ($_FILES["gambar"]["size"] > 4000000) {
                    throw new Exception("Ukuran file tidak boleh lebih dari 4MB");
                }

                // Generate safe filename
                $random_name = bin2hex(random_bytes(10));
                $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
                $new_name = $random_name . "." . $imageFileType;

                // Delete old image
                if (!empty($artikel['gambar'])) {
                    $old_file = $target_dir . $artikel['gambar'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }

                // Upload new image
                if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_dir . $new_name)) {
                    throw new Exception("Gagal mengupload file");
                }
            }

            // Update dengan prepared statement dan named parameters
            $stmt_update = $conn->prepare("UPDATE artikel SET kategori_id = ?, judul = ?, isi = ?, sinopsis = ?, gambar = ? WHERE id = ? AND user_id = ?");
            $stmt_update->bind_param("issssii", $kategori, $judul, $isi, $sinopsis, $new_name, $artikel_id, $user_id);
            
            if (!$stmt_update->execute()) {
                throw new Exception("Gagal mengupdate artikel: " . $conn->error);
            }

            echo '<div class="alert alert-success">Artikel berhasil diupdate</div>';
            echo '<meta http-equiv="refresh" content="2;url=index.php">';

        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }

    // Handle article archiving with CSRF protection
    if (isset($_POST['archive'])) {
        // Validasi CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed');
        }
        
        $current_time = date('Y-m-d H:i:s');
        
        // Gunakan prepared statement
        $stmt_archive = $conn->prepare("UPDATE artikel SET status = ?, archived_at = ? WHERE id = ? AND user_id = ?");
        $status = 'arsip';
        $stmt_archive->bind_param("ssii", $status, $current_time, $artikel_id, $user_id);
        
        if ($stmt_archive->execute()) {
            echo '<div class="alert alert-success">Artikel berhasil diarsipkan</div>';
            echo '<meta http-equiv="refresh" content="2;url=index.php">';
            exit;
        }
    }

    // Handle article deletion with CSRF protection
    if(isset($_POST['delete'])) {
        // Validasi CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed');
        }
        
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
        $stmt_delete = $conn->prepare("DELETE FROM artikel WHERE id = ? AND user_id = ?");
        $stmt_delete->bind_param("ii", $artikel_id, $user_id);
        
        if ($stmt_delete->execute()) {
            echo "<script>alert('Artikel berhasil dihapus!'); window.location.href = 'artikel.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal menghapus artikel: " . $conn->error . "');</script>";
        }
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    exit();
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
    <link rel="stylesheet" href="../css/artikel1.css">
    <link rel="stylesheet" href="../css/postingan.css">
    <title>Ubah Artikel - KitaSehat</title>

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

    <!-- Form Section start -->
    <section id="#" class="postingan">
        <h2>Ubah Artikel</h2>
    </section>

    <div class="container">
        <form id="form-edit-artikel" action="" method="post" enctype="multipart/form-data">
            <!-- CSRF token hidden field -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="judul">Judul <span class="text-danger">*</span></label>
                <input type="text" id="judul" name="judul" required 
                       value="<?php echo htmlspecialchars($artikel['judul']); ?>"
                       placeholder="Masukkan judul artikel">
            </div>

            <div class="form-group">
                <label for="kategori">Kategori <span class="text-danger">*</span></label>
                <select name="kategori" id="kategori" required>
                    <option value="">Pilih kategori</option>
                    <?php while ($data = mysqli_fetch_array($queryKategori)) { ?>
                        <option value="<?php echo $data['id']; ?>" 
                                <?php echo ($data['id'] == $artikel['kategori_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($data['nama']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="isi">Konten <span class="text-danger">*</span></label>
                <input id="article-content" type="hidden" name="isi" 
                        value="<?php echo htmlspecialchars($artikel['isi']); ?>">
                <trix-editor input="article-content"></trix-editor>
            </div>

            <div class="form-group">
                <label for="sinopsis">Sinopsis <span class="text-danger">*</span></label>
                <input id="article-synopsis" type="hidden" name="sinopsis" 
                        value="<?php echo htmlspecialchars($artikel['sinopsis']); ?>">
                <trix-editor input="article-synopsis"></trix-editor>
            </div>

            <div class="form-group">
                <label for="gambar">Gambar</label>
                <?php if (!empty($artikel['gambar'])): ?>
                    <div class="current-image">
                        <p>Gambar saat ini:</p>
                        <img src="../css/image/<?php echo htmlspecialchars($artikel['gambar']); ?>" alt="Gambar artikel saat ini">
                    </div>
                <?php endif; ?>
                <input type="file" id="gambar" name="gambar" 
                        accept="image/jpg,image/jpeg,image/png,image/gif"
                        onchange="previewImage(this)">
                <div id="imagePreview" class="image-preview"></div>
                <small class="text-muted">Accepted formats: JPG, PNG, GIF (max 4MB)</small>
            </div>

            <button class="btn-primary" type="submit" name="update">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>

            <?php if ($artikel['status'] == 'aktif'): ?>
            <button class="btn-warning" type="submit" name="archive" 
                    onclick="return confirm('Apakah Anda yakin ingin mengarsipkan artikel ini?')">
                <i class="fas fa-archive"></i> Arsip
            </button>
            <?php endif; ?>
            
            <!-- Print Button -->
            <button class="btn-info" type="button" onclick="printArtikel()">
                <i class="fas fa-print"></i> Cetak
            </button>

            <!-- Delete Button -->
            <button class="btn-danger" type="submit" name="delete" 
                onclick="return confirm('Apakah Anda yakin ingin menghapus artikel ini? Tindakan ini tidak dapat dibatalkan.')">
                <i class="fas fa-trash"></i> Hapus
            </button>
            
            <!-- Cancel Button -->
            <a href="artikel.php" class="btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
        </form>
    </div>
    <!-- Form Section end -->

    <!-- Footer Section start -->
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
    <!-- Footer Section End -->

    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/trix/1.3.1/trix.css">
    <script>
        // Form validation
        document.getElementById('form-tambah-artikel').onsubmit = function(e) {
            const title = document.getElementById('judul').value.trim();
            const category = document.getElementById('kategori').value;
            const content = document.getElementById('article-content').value.trim();
            const synopsis = document.getElementById('sinopsis').value.trim();
            
            if (!title || !category || !content || !synopsis) {
                alert('Please fill in all required fields');
                e.preventDefault();
                return false;
            }
            
            const image = document.getElementById('gambar').files[0];
            if (image && image.size > 4 * 1024 * 1024) {
                alert('Image size must not exceed 4MB');
                e.preventDefault();
                return false;
            }
            
            return true;
        };

    // Initialize when DOM is fully loaded
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize Trix editors
        document.querySelectorAll("trix-editor").forEach(editor => {
            editor.setAttribute("contenteditable", "true");
        });

        // Event listener for Trix initialization
        document.addEventListener("trix-initialize", function(event) {
            event.target.editor.element.setAttribute("contenteditable", "true");
        });

        // Event listener for content changes
        document.addEventListener("trix-change", function(event) {
            const element = event.target;
            const inputId = element.getAttribute("input");
            const hiddenInput = document.getElementById(inputId);
            
            if (hiddenInput) {
                hiddenInput.value = element.editor.getDocument().toString();
            }
            
            // Optional: Add validation visual feedback
            if (element.editor.getDocument().toString().trim() === "") {
                element.classList.add("is-invalid");
            } else {
                element.classList.remove("is-invalid");
            }
        });

        // Prevent file attachments (optional - remove if you want to allow file attachments)
        document.addEventListener("trix-file-accept", function(event) {
            event.preventDefault();
        });

        // Image preview functionality
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

        // Attach image preview handler
        const imageInput = document.getElementById('gambar');
        if (imageInput) {
            imageInput.addEventListener('change', function() {
                previewImage(this);
            });
        }

        // Form validation
        const form = document.getElementById('form-tambah-artikel');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const title = document.getElementById('judul').value.trim();
                    const category = document.getElementById('kategori').value;
                    const content = document.getElementById('article-content').value.trim();
                    const synopsis = document.getElementById('article-synopsis').value.trim();
                    
                    let isValid = true;
                    let errorMessage = [];

                    // Validate title
                    if (!title) {
                        errorMessage.push("Title is required");
                        isValid = false;
                    }

                    // Validate category
                    if (!category) {
                        errorMessage.push("Category is required");
                        isValid = false;
                    }

                    // Validate content
                    if (!content) {
                        errorMessage.push("Content is required");
                        isValid = false;
                    }

                    // Validate synopsis
                    if (!synopsis) {
                        errorMessage.push("Synopsis is required");
                        isValid = false;
                    }

                // Validate image if one is selected
                const image = document.getElementById('gambar').files[0];
                if (image) {
                    if (image.size > 4 * 1024 * 1024) {
                        errorMessage.push("Image size must not exceed 4MB");
                        isValid = false;
                    }

                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(image.type)) {
                        errorMessage.push("Only JPG, JPEG, PNG & GIF files are allowed");
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    alert(errorMessage.join("\n"));
                    return false;
                }

                return true;
                });
            }

                // Optional: Auto-save draft functionality
                let autoSaveTimeout;
                document.querySelectorAll("trix-editor").forEach(editor => {
                editor.addEventListener("trix-change", function() {
                        clearTimeout(autoSaveTimeout);
                        autoSaveTimeout = setTimeout(function() {
                            // Here you could implement auto-save functionality
                            console.log("Content auto-saved");
                            // Example: localStorage.setItem('draft-' + editor.getAttribute('input'), editor.editor.getDocument().toString());
                        }, 1000);
                });
                });
            });
            
            function printArtikel() {
            // Dapatkan data artikel
            const judul = document.getElementById('judul').value;
            const kategori = document.getElementById('kategori').options[document.getElementById('kategori').selectedIndex].text;
            const content = document.querySelector('trix-editor[input="article-content"]').innerHTML;
            const sinopsis = document.querySelector('trix-editor[input="article-synopsis"]').innerHTML;
            const gambar = document.querySelector('.current-image img') ? document.querySelector('.current-image img').src : '';
            
            // Buat konten untuk dicetak
            let printContent = `
                <div class="print-header">
                    <h1 style="text-align: center; color: #333; margin-bottom: 30px;">${judul}</h1>
                </div>
                <div class="artikel-detail">
                    <p class="kategori">Kategori: ${kategori}</p>
                    ${gambar ? `<div class="artikel-gambar">
                        <img src="${gambar}" style="max-width: 500px; height: auto; margin: 20px 0;">
                    </div>` : ''}
                    <div class="sinopsis-section">
                        <h3>Sinopsis:</h3>
                        <div class="sinopsis-content">
                            ${sinopsis}
                        </div>
                    </div>
                    <div class="artikel-content">
                        <h3>Isi Artikel:</h3>
                        ${content}
                    </div>
                </div>
            `;
            
            // Simpan konten halaman asli
            let originalContent = document.body.innerHTML;
            
            // Ganti konten halaman dengan yang akan dicetak
            document.body.innerHTML = `
                <style>
                    @media print {
                        body { 
                            font-family: Arial, sans-serif;
                            padding: 40px;
                            color: #333;
                        }
                        .print-header {
                            text-align: center;
                            margin-bottom: 30px;
                        }
                        .artikel-detail {
                            margin-top: 20px;
                        }
                        .kategori {
                            color: #666;
                            font-style: italic;
                            margin-bottom: 20px;
                            text-align: center;
                        }
                        .artikel-gambar {
                            text-align: center;
                            margin: 20px 0;
                        }
                        .sinopsis-section {
                            margin: 20px 0;
                            padding: 20px;
                            background-color: #f9f9f9;
                            border-radius: 5px;
                        }
                        .sinopsis-section h3 {
                            color: #333;
                            margin-bottom: 10px;
                        }
                        .sinopsis-content {
                            line-height: 1.6;
                            text-align: justify;
                        }
                        .artikel-content {
                            margin-top: 30px;
                        }
                        .artikel-content h3 {
                            color: #333;
                            margin-bottom: 15px;
                        }
                        .artikel-content {
                            line-height: 1.6;
                            text-align: justify;
                        }
                        @page {
                            margin: 2cm;
                        }
                    }
                </style>
                ${printContent}
            `;
            
            // Cetak halaman
            window.print();
            
            // Kembalikan konten asli
            document.body.innerHTML = originalContent;
            
            // Reinisialisasi Trix editor
            var event = document.createEvent('Events');
            event.initEvent('trix-initialize', true, false);
            document.dispatchEvent(event);
        }
</script>
</body>
</html>