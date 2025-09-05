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
  <link rel="stylesheet" href="css/testimonial.css">

  <title>kitasehat | Testimonial</title>
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
      <a href="index.php">Beranda</a>
      <a href="index.php">About Me</a>
      <a href="index.php">Artikel</a>
      <a href="index.php">Kontak</a>
      <?php
      session_start();
      // Cek apakah sudah login berdasarkan session
      if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
          // Jika pengguna sudah login, tampilkan tombol Logout
          echo '<a href="logout.php" id="login">Logout</a>';
      } else {
          // Jika pengguna belum login, tampilkan tombol Masuk
          echo '<a href="login.php" id="login">Login</a>';
      }
      ?>
    </div>

    <div class="hamburger">
      <a href="#" id="hamburger" class="fa-solid fa-bars fa-xl"></a>
    </div>


  </div>
  <!-- Navbar end -->

  <!-- Testimonial section start-->
    <section id="detail" class="detail-testimonial">
        <h3 style="margin-top: 2rem;">Testimonial</h3>
    </section>

    <div class="container-main"></div>
    <div class="main">
      <p>Berikan testimonial Anda tentang layanan kami.</p>
    </div>

    <div class="selebihnya">
        <a href="artikel.php">Kembali ke Artikel</a>
    </div>
  <!-- Testimonial section end-->

  <!-- Testimonial Form Section Start -->
  <section id="testimonial" class="testimonial-section">
      <div class="testimonial-container">
          <h3>Beri Testimonial</h3>

          <?php
        // Cek apakah user sudah login
        if (isset($_SESSION['login']) && $_SESSION['login'] === true):
        ?>
            <!-- Form Testimonial -->
            <form action="inc/proses_testimonial.php" method="POST" class="form-testimonial">
                <input type="text" name="nama" placeholder="Nama Anda" required>
                <input type="email" name="email" placeholder="Email Anda" required>
                <textarea name="testimonial" placeholder="Tulis testimonial Anda..." required></textarea>
                <button type="submit">Kirim Testimonial</button>
            </form>
        <?php else: ?>
            <!-- Jika belum login -->
            <div class="login-prompt">
                <p>Silakan <a href="login.php">login</a> terlebih dahulu untuk memberikan testimonial.</p>
            </div>
        <?php endif; ?>

        <!-- Daftar Testimonial -->
        <div class="daftar-testimonial">
            <?php
            // Ambil semua testimonial dari database
            $query_testimonial = "SELECT * FROM testimonial ORDER BY created_at DESC";
            $result_testimonial = mysqli_query($conn, $query_testimonial);
            
            if (mysqli_num_rows($result_testimonial) > 0):
                while ($testimonial = mysqli_fetch_assoc($result_testimonial)):
            ?>
                    <div class="testimonial">
                        <div class="testimonial-profil">
                            <img src="<?php echo htmlspecialchars($foto_profil_path); ?>" alt="Foto Profil">
                            <span class="nama"><?php echo htmlspecialchars($testimonial['nama']); ?></span>
                        </div>
                        <div class="testimonial-isi">
                            <p><?php echo htmlspecialchars($testimonial['testimonial']); ?></p>
                            <small><?php echo date('d M Y H:i', strtotime($testimonial['created_at'])); ?></small>
                        </div>
                    </div>
            <?php 
                endwhile;
            else:
                echo "<p>Belum ada testimonial.</p>";
            endif;
            ?>
        </div>
      </div>
  </section>
  <!-- Testimonial Form Section End -->

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