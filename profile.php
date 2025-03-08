<?php  
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "inc/koneksi.php";

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Create upload directory if it doesn't exist
$uploadDir = 'css/image/profile';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Get user data
$username = $_SESSION['username'];
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if (!$user) {
        throw new Exception("User tidak ditemukan");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Function to handle image upload
function upload_image($conn, $username) {
    $targetDir = "css/image/profile/";
    $fileName = basename($_FILES["foto_profil"]["name"]);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Generate unique filename
    $fileName = uniqid() . '.' . $fileType;
    $targetFilePath = $targetDir . $fileName;
    
    // Validate file
    $maxFileSize = 800 * 1024; // 800KB in bytes
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
    
    // Perform checks
    if (!in_array($fileType, $allowTypes)) {
        throw new Exception("Hanya file JPG, PNG, JPEG & GIF yang diperbolehkan.");
    }
    
    if ($_FILES["foto_profil"]["size"] > $maxFileSize) {
        throw new Exception("File terlalu besar. Maksimal 800KB.");
    }
    
    // Upload file
    if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $targetFilePath)) {
        // Delete old profile picture if exists
        $stmt = $conn->prepare("SELECT foto_profil FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $oldImage = $result->fetch_assoc()['foto_profil'];
        
        if ($oldImage && file_exists($targetDir . $oldImage) && $oldImage !== 'default.png') {
            unlink($targetDir . $oldImage);
        }
        
        return $fileName;
    } else {
        throw new Exception("Gagal mengupload file.");
    }
}

// Handle form submission
if (isset($_POST['update_profile'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Handle image upload if new image is selected
        if (!empty($_FILES["foto_profil"]["name"])) {
            $foto_profil = upload_image($conn, $username);
            
            // Update user data with new image
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, email = ?, phone = ?, foto_profil = ? WHERE username = ?");
            $stmt->bind_param("sssss", $nama_lengkap, $email, $phone, $foto_profil, $username);
        } else {
            // Update user data without changing image
            $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, email = ?, phone = ? WHERE username = ?");
            $stmt->bind_param("ssss", $nama_lengkap, $email, $phone, $username);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Gagal memperbarui profil");
        }
        
        mysqli_commit($conn);
        $_SESSION['success'] = "Profil berhasil diperbarui!";
        header("Location: profile.php");
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
        header("Location: profile.php");
        exit();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
  $old_password = $_POST['old_password'];
  $new_password = $_POST['new_password'];
  $confirm_password = $_POST['confirm_password'];
  
  try {
      // Verify old password
      $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $result = $stmt->get_result();
      $user_data = $result->fetch_assoc();
      
      if (!password_verify($old_password, $user_data['password'])) {
          throw new Exception("Kata sandi lama tidak sesuai");
      }
      
      // Validate new password
      if (strlen($new_password) < 8) {
          throw new Exception("Kata sandi baru minimal 8 karakter");
      }
      
      // Check if new passwords match
      if ($new_password !== $confirm_password) {
          throw new Exception("Konfirmasi kata sandi baru tidak sesuai");
      }
      
      // Hash new password
      $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      
      // Update password in database
      $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
      $stmt->bind_param("ss", $hashed_password, $username);
      
      if (!$stmt->execute()) {
          throw new Exception("Gagal mengupdate kata sandi");
      }
      
      $_SESSION['success'] = "Kata sandi berhasil diubah!";
      header("Location: profile.php");
      exit();
      
  } catch (Exception $e) {
      $_SESSION['error'] = $e->getMessage();
      header("Location: profile.php#change-password");
      exit();
  }
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
    <link rel="stylesheet" href="css/style3.css">
    <link rel="stylesheet" href="css/profile.css">
    
    <title>KitaSehat</title>
</head>

<body>
    <!-- Navbar start -->
    <div class="navbar">
        <a href="#" class="navbar-logo">
            Kita<span>Sehat</span>.
        </a>

        <div class="navbar-nav">
      <a href="index.php#beranda">Beranda</a>
      <a href="index.php#layanan">About</a>
      <a href="index.php#artikel">Artikel</a>
      <a href="index.php#kontak">Kontak</a>
      <?php
      if (session_status() == PHP_SESSION_NONE) {
        session_start();    
      // Cek apakah sudah login berdasarkan session
      if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
          // Periksa apakah ada role yang diset dalam session
          if (isset($_SESSION['role']) && $_SESSION['role'] === 'penulis') {
              echo '<a href="postingan.php">Postingan</a>';
          }
          // Tampilkan tombol Logout jika sudah login
          echo '<a href="logout.php" id="login">Logout</a>';
      } else {
          // Jika belum login, tampilkan tombol Login
          echo '<a href="login.php" id="login">Login</a>';
      }
    }
      ?>


        <div class="hamburger">
            <a href="#" id="hamburger" style="margin-left: 1rem;" class="fa-solid fa-bars fa-xl"></a>
        </div>


    </div>
    <!-- Navbar end -->

    <!-- Profile start -->
    <section class="profile-section">
        <div class="profile-header">
            <h4>Pengaturan Akun</h4>
        </div>
        
        <div class="profile-container">
            <div class="profile-sidebar">
                <ul class="profile-links">
                    <li><a href="#" class="active">Umum</a></li>
                    <li><a href="#change-password">Ubah Kata Sandi</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>

            <div class="profile-content">
                <!-- Single form for all profile updates -->
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="profile-photo">
                        <div class="photo-container">
                            <img id="profile-preview" src="<?php echo !empty($user['foto_profil']) ? 'css/image/profile/' . htmlspecialchars($user['foto_profil']) : 'https://bootdey.com/img/Content/avatar/avatar1.png'; ?>" alt="Profile">
                        </div>
                        <div class="photo-actions">
                            <input type="file" name="foto_profil" id="foto_profil" accept="image/jpeg,image/png,image/gif" style="display: none;">
                            <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($user['foto_profil'] ?? ''); ?>">
                            <button type="button" class="btn-upload" onclick="document.getElementById('foto_profil').click()">Unggah Foto Baru</button>
                            <button type="button" class="btn-reset" onclick="resetImage()">Reset</button>
                            <small>Format: JPG, GIF atau PNG. Ukuran maks. 800KB</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nama Pengguna</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-save">Simpan Perubahan</button>
                        <button type="button" class="btn-cancel">Batal</button>
                    </div>

                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="error-message" style="color: red; margin-top: 10px;">
                            <?php 
                                echo htmlspecialchars($_SESSION['error']); 
                                unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Konten Ubah Kata Sandi -->
        <div id="change-password" class="content-section" style="display: none;">
    <div class="profile-content">
        <form action="" method="POST" class="password-form">
            <div class="form-group">
                <label>Kata Sandi Lama</label>
                <input type="password" name="old_password" required>
            </div>
            <div class="form-group">
                <label>Kata Sandi Baru</label>
                <input type="password" name="new_password" required 
                      pattern=".{8,}" title="Kata sandi minimal 8 karakter">
            </div>
            <div class="form-group">
                <label>Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="confirm_password" required>
            </div>

            <?php if(isset($_SESSION['error']) && strpos($_SERVER['REQUEST_URI'], '#change-password') !== false): ?>
                <div class="error-message" style="color: red; margin-bottom: 10px;">
                    <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" name="change_password" class="btn-save">Simpan Perubahan</button>
                <button type="button" class="btn-cancel" onclick="cancelPasswordChange()">Batal</button>
            </div>
        </form>
    </div>
</div>
    </section>
    <!-- Profile end -->


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

    <script>
    // Profile links handling
    const profileLinks = document.querySelectorAll('.profile-links a');
    profileLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            if (!this.href.includes('logout.php')) {
                e.preventDefault();
                profileLinks.forEach(link => link.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href').replace('#', '');
                if (targetId === 'change-password') {
                    document.querySelector('.profile-content').style.display = 'none';
                    document.getElementById('change-password').style.display = 'block';
                } else {
                    document.querySelector('.profile-content').style.display = 'block';
                    document.getElementById('change-password').style.display = 'none';
                }
            }
        });
    });

    // Image preview and validation
    document.getElementById('foto_profil').onchange = function(e) {
        const file = e.target.files[0];
        const maxSize = 800 * 1024; // 800KB
        
        if (file) {
            // Validate file size
            if (file.size > maxSize) {
                alert('Ukuran file terlalu besar. Maksimal 800KB.');
                this.value = '';
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Format file tidak valid. Gunakan JPG, PNG, atau GIF.');
                this.value = '';
                return;
            }
            
            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-preview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    }

    // Reset image function
    function resetImage() {
        document.getElementById('profile-preview').src = 'https://bootdey.com/img/Content/avatar/avatar1.png';
        document.getElementById('foto_profil').value = '';
        document.querySelector('input[name="old_image"]').value = '';
    }

    // Display messages
    <?php if(isset($_SESSION['success'])): ?>
        alert('<?php echo addslashes($_SESSION['success']); ?>');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    // Add to your existing JavaScript
    function cancelPasswordChange() {
    // Reset form fields
    document.querySelector('.password-form').reset();
    
    // Switch back to general profile view
    document.querySelectorAll('.profile-links a').forEach(link => {
        if (!link.href.includes('#change-password')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    // Hide password change section and show general section
    document.getElementById('change-password').style.display = 'none';
    document.querySelector('.profile-content').style.display = 'block';
}

    // Add password confirmation validation
    document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
        const newPassword = document.querySelector('input[name="new_password"]').value;
        if (this.value !== newPassword) {
            this.setCustomValidity('Kata sandi tidak cocok');
        } else {
            this.setCustomValidity('');
        }
    });

    document.querySelector('input[name="new_password"]').addEventListener('input', function() {
        const confirmPassword = document.querySelector('input[name="confirm_password"]');
        if (confirmPassword.value !== this.value) {
            confirmPassword.setCustomValidity('Kata sandi tidak cocok');
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
    </script>
</body>

</html>