<?php
session_start();
require "inc/koneksi.php";
// Authentication check
if (!isset($_SESSION['login']) || !isset($_SESSION['id'])) {
    header("location: ../login.php");
    exit();
}

// CSRF Protection
// Generate a CSRF token if one doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// CSRF Token Validation Function
function validate_csrf_token()
{
    if (
        !isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        $_SESSION['error'] = "Validasi keamanan gagal. Silakan coba lagi.";
        header("Location: profile.php");
        exit();
    }
}

$user_id = $_SESSION['id'];
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
function upload_image($conn, $username)
{
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
    // Validate CSRF token
    validate_csrf_token();

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
    // Validate CSRF token
    validate_csrf_token();

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

// Handle account deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_account' && isset($_GET['token'])) {
    // Validate CSRF token from URL
    if (!isset($_GET['token']) || !isset($_SESSION['csrf_token']) || $_GET['token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Token keamanan tidak valid. Silakan coba lagi.";
        header("Location: profile.php");
        exit();
    }

    try {
        // Start transaction
        mysqli_begin_transaction($conn);

        // Delete user's profile picture if it exists and is not the default
        $stmt = $conn->prepare("SELECT foto_profil FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $foto_profil = $result->fetch_assoc()['foto_profil'];

        if ($foto_profil && $foto_profil !== 'default.png' && file_exists('css/image/profile/' . $foto_profil)) {
            unlink('css/image/profile/' . $foto_profil);
        }

        // Delete the user account
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);

        if (!$stmt->execute()) {
            throw new Exception("Gagal menghapus akun");
        }

        // Commit the transaction
        mysqli_commit($conn);

        // Destroy the session
        session_destroy();

        // Redirect to login page with success message
        session_start();
        $_SESSION['success'] = "Akun berhasil dihapus!";
        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
        header("Location: profile.php");
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

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

    <title>KitaSehat</title>
    <style>
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .danger-zone {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #ff8080;
            border-radius: 5px;
            background-color: #fff5f5;
        }

        .danger-zone h3 {
            color: #dc3545;
            margin-top: 0;
        }

        .danger-zone p {
            margin-bottom: 15px;
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
                }
            }
            ?>
        </div>
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
                    <li><a href="#delete-account">Hapus Akun</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
            <div class="profile-content">
                <!-- Single form for all profile updates -->
                <form action="" method="POST" enctype="multipart/form-data">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

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
                    <?php if (isset($_SESSION['error'])): ?>
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
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

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
                    <?php if (isset($_SESSION['error']) && strpos($_SERVER['REQUEST_URI'], '#change-password') !== false): ?>
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

        <!-- Konten Hapus Akun -->
        <div id="delete-account" class="content-section" style="display: none;">
            <div class="profile-content">
                <div class="danger-zone">
                    <h3>Penghapusan akun bersifat permanen dan tidak dapat dibatalkan. Semua data Anda akan dihapus dan tidak dapat dipulihkan.</h3>
                    <button type="button" class="btn-danger" onclick="confirmDeleteAccount()">Hapus Akun Saya</button>
                </div>
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
            link.addEventListener('click', function(e) {
                if (!this.href.includes('logout.php')) {
                    e.preventDefault();
                    profileLinks.forEach(link => link.classList.remove('active'));
                    this.classList.add('active');

                    const targetId = this.getAttribute('href').replace('#', '');

                    // Hide all content sections first
                    document.querySelector('.profile-content').style.display = 'none';
                    document.getElementById('change-password').style.display = 'none';
                    document.getElementById('delete-account').style.display = 'none';

                    // Show the appropriate section
                    if (targetId === 'change-password') {
                        document.getElementById('change-password').style.display = 'block';
                    } else if (targetId === 'delete-account') {
                        document.getElementById('delete-account').style.display = 'block';
                    } else {
                        document.querySelector('.profile-content').style.display = 'block';
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

        // Cancel password change
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

        // SweetAlert2 for account deletion confirmation
        function confirmDeleteAccount() {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Akun Anda akan dihapus secara permanen dan tidak dapat dipulihkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus akun saya!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show second confirmation
                    Swal.fire({
                        title: 'Konfirmasi Final',
                        text: "Ketik 'HAPUS' untuk mengkonfirmasi penghapusan akun",
                        input: 'text',
                        inputAttributes: {
                            autocapitalize: 'off'
                        },
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Hapus Sekarang',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        inputValidator: (value) => {
                            if (value !== 'HAPUS') {
                                return 'Anda harus mengetik HAPUS untuk melanjutkan!';
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to delete account action with CSRF token
                            window.location.href = `profile.php?action=delete_account&token=<?php echo $csrf_token; ?>`;
                        }
                    });
                }
            });
        }

        // Display success/error messages with SweetAlert
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                title: 'Berhasil!',
                text: '<?php echo addslashes($_SESSION['success']); ?>',
                icon: 'success',
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error']) && !strpos($_SERVER['REQUEST_URI'], '#change-password')): ?>
            Swal.fire({
                title: 'Error!',
                text: '<?php echo addslashes($_SESSION['error']); ?>',
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        // Check for hash in URL and show appropriate section
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const targetLink = document.querySelector(`.profile-links a[href="${hash}"]`);
                if (targetLink) {
                    targetLink.click();
                }
            }
        });
    </script>
</body>

</html>