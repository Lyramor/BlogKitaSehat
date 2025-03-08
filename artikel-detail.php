<?php  
  require "inc/koneksi.php";

  if (!file_exists('../upload/profile')) {
    mkdir('../upload/profile', 0777, true);
  }

  // Mengambil nilai judul dari parameter GET
  $judul = htmlspecialchars($_GET['judul']); 

  // Mengeksekusi query SQL untuk mengambil data artikel berdasarkan judul
  $queryArtikel = mysqli_query($conn, "SELECT * FROM artikel WHERE judul='$judul'"); 
  
  // Mengambil hasil query dalam bentuk array
  $artikel = mysqli_fetch_array($queryArtikel); 

  $foto_profil = $komentar['foto_profil'];

  // Debug step: Check the actual value of $foto_profil
  error_log("Foto profil value: " . print_r($foto_profil, true));
  
  // Modify the path logic
  $foto_profil_path = (!empty($foto_profil) && $foto_profil !== NULL) 
      ? 'css/image/profile/' . $foto_profil 
      : 'https://bootdey.com/img/Content/avatar/avatar1.png';
  
  // Add additional debug logging
  error_log("Generated foto_profil_path: " . $foto_profil_path);
  
  // Add an additional check before displaying
  if (!file_exists($foto_profil_path)) {
      $foto_profil_path = 'css/image/profile/';
      error_log("Profile image not found, using default: " . $foto_profil_path);
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
  <link rel="stylesheet" href="css/style2.css">
  <link rel="stylesheet" href="css/artikel1.css">


  <title>kitasehat | Artikel</title>
</head>

<body>
  <!-- Navbar start -->
  <div class="navbar">
    <a href="index.php" class="navbar-logo">
      Kita<span>Sehat</span>.
    </a>

    <div class="search-box">
      <form action="artikel.php" method="get">
        <input type="text" name="search" id="srch" placeholder="search">
        <button type="submit"><i class="fa-solid fa-search"></i></button>
      </form>
    </div>

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
    </div>

    <div class="hamburger">
      <a href="#" id="hamburger" class="fa-solid fa-bars fa-xl"></a>
    </div>


  </div>
  <!-- Navbar end -->

  <!-- artikel section start-->
    <section id="detail" class="detail-artikel">
        <h3 style="margin-top: 2rem;"><?php echo $artikel['judul']; ?></h3>
    </section>

    <div class="container-main"></div>
    <div class="main">
      <p><?php echo $artikel['isi']; ?></p>
    </div>

    <div class="selebihnya">
        <a href="artikel.php">Artikel Lainnya</a>
    </div>
  <!-- artikel section end-->

  <!-- Komentar Section Start -->
  <section id="komentar" class="komentar-section">
      <div class="komentar-container">
          <h3>Komentar</h3>

          <?php

  function get_profile_image_path($foto_profil) {
    $possible_paths = [
        'upload/profile/' . $foto_profil,
        'assets/default-profile.png'
    ];
    
    foreach ($possible_paths as $path) {
        if (!empty($foto_profil) && file_exists($path)) {
            return $path;
        }
    }
    
    return 'assets/default-profile.png';
  }

        // Cek apakah user sudah login
        if (isset($_SESSION['login']) && $_SESSION['login'] === true):
        ?>
            <!-- Form Komentar -->
            <form action="inc/proses_komentar.php" method="POST" class="form-komentar">
                <textarea name="isi_komentar" placeholder="Tulis komentar Anda..." required></textarea>
                <input type="hidden" name="artikel_id" value="<?php echo $artikel['id']; ?>">
                <input type="hidden" name="judul" value="<?php echo htmlspecialchars($artikel['judul']); ?>">
                <button type="submit">Kirim Komentar</button>
            </form>
        <?php else: ?>
            <!-- Jika belum login -->
            <div class="login-prompt">
                <p>Silakan <a href="login.php">login</a> terlebih dahulu untuk memberikan komentar.</p>
            </div>
        <?php endif; ?>

        <!-- Daftar Komentar -->
        <div class="daftar-komentar">
            <?php
            // Hitung total komentar untuk artikel ini
            $stmt_count = $conn->prepare("SELECT COUNT(*) as total_comments FROM komentar WHERE artikel_id = ?");
            $stmt_count->bind_param("i", $artikel['id']);
            $stmt_count->execute();
            $result_count = $stmt_count->get_result();
            $total_comments = $result_count->fetch_assoc()['total_comments'];

            // Ambil 5 komentar terbaru
            $query_komentar = "SELECT k.*, u.username, u.foto_profil 
                              FROM komentar k 
                              JOIN users u ON k.user_id = u.id 
                              WHERE k.artikel_id = ? 
                              ORDER BY k.created_at DESC 
                              LIMIT 5";
            
            $stmt = $conn->prepare($query_komentar);
            $stmt->bind_param("i", $artikel['id']);
            $stmt->execute();
            $result_komentar = $stmt->get_result();
            
            if ($result_komentar->num_rows > 0):
                while ($komentar = $result_komentar->fetch_assoc()):
                    // Tentukan path foto profil
                    $foto_profil = $komentar['foto_profil'];
                    $foto_profil_path = !empty($foto_profil) ? 'css/image/profile/' . $foto_profil :  'https://bootdey.com/img/Content/avatar/avatar1.png';
            ?>
                    <div class="komentar">
                        <div class="komentar-profil">
                            <img src="<?php echo htmlspecialchars($foto_profil_path); ?>" alt="Foto Profil">
                            <span class="username"><?php echo htmlspecialchars($komentar['username']); ?></span>
                        </div>
                        <div class="komentar-isi">
                            <p><?php echo htmlspecialchars($komentar['isi']); ?></p>
                            <small><?php echo date('d M Y H:i', strtotime($komentar['created_at'])); ?></small>
                        </div>
                    </div>
            <?php 
                endwhile;
            else:
                echo "<p>Belum ada komentar.</p>";
            endif;
            ?>
        </div>

        <!-- Tombol Lihat Semua Komentar -->
        <?php if ($total_comments > 5): ?>
          <div class="lihat-semua-komentar">
            <button id="btn-semua-komentar" data-artikel-id="<?php echo $artikel['id']; ?>">
                Lihat Semua Komentar (<?php echo $total_comments; ?>)
            </button>
          </div>
        <?php endif; ?>
      </div>
  </section>
  <!-- Komentar Section End -->


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
    document.addEventListener('DOMContentLoaded', function() {
        const btnSemuaKomentar = document.getElementById('btn-semua-komentar');
        const daftarKomentar = document.querySelector('.daftar-komentar');
        
        if (btnSemuaKomentar) {
            btnSemuaKomentar.addEventListener('click', function() {
                const artikelId = this.getAttribute('data-artikel-id');
                const loadingText = 'Memuat komentar...';
                
                // Disable button and show loading text
                btnSemuaKomentar.disabled = true;
                btnSemuaKomentar.textContent = loadingText;
                
                fetch(`inc/get_all_comments.php?artikel_id=${artikelId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Gagal memuat komentar');
                        }
                        return response.text();
                    })
                    .then(html => {
                        // Hide the "Lihat Semua Komentar" button
                        btnSemuaKomentar.style.display = 'none';
                        
                        // Append comments to the existing list
                        daftarKomentar.insertAdjacentHTML('beforeend', html);
                    })
                    .catch(error => {
                        console.error('Error loading comments:', error);
                        btnSemuaKomentar.textContent = 'Gagal memuat komentar';
                        btnSemuaKomentar.disabled = false;
                    });
            });
        }
    });
  </script>
</body>

</html>