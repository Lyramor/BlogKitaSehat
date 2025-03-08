<?php  
require "inc/koneksi.php";

// Mengambil data artikel dari tabel "artikel"
$queryArtikel = mysqli_query($conn, "SELECT id, judul, gambar, sinopsis FROM artikel");

// Mengambil data kategori dari tabel "kategori"
$queryKategori = mysqli_query($conn, "SELECT * FROM kategori");


// Cek apakah terdapat pencarian berdasarkan kata kunci (keyword)
if (isset($_GET['keyword'])) {
  $keyword = $_GET['keyword'];
  $queryArtikel = mysqli_query($conn, "SELECT * FROM artikel WHERE judul LIKE '%$keyword%'");
}
// Cek apakah terdapat pencarian berdasarkan kategori
  else if (isset($_GET['kategori'])) {
    $kategori = $_GET['kategori'];
    $queryGetKategoriId = mysqli_query($conn, "SELECT id FROM kategori WHERE nama='$kategori'");
    $kategoriId = mysqli_fetch_array($queryGetKategoriId);
    $queryArtikel = mysqli_query($conn, "SELECT * FROM artikel WHERE kategori_id='$kategoriId[id]'");
  }

$countData = mysqli_num_rows($queryArtikel);
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
  <link rel="stylesheet" href="css/artikel.css">

  <title>KitaSehat | Artikel lainnya</title>
</head>

<body>
  <!-- Navbar start -->
  <div class="navbar">
    <a href="index.php" class="navbar-logo">
      Kita<span>Sehat</span>.
    </a>

    <div class="search-box">
      <form action="artikel.php" method="get" id="searchForm">
        <input type="text" name="keyword" id="srch" placeholder="search" autofocus autocomplete="off">
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

  <!-- Kategori section start -->
  <section id="artikel" class="artikel">
    <h2 style="margin-top: 2rem;">Kategori</h2>
  </section>

  <div class="kategori">
    <?php while ($kategori = mysqli_fetch_array($queryKategori)) { ?>
      <a href="artikel.php?kategori=<?php echo $kategori['nama']; ?>" class="sub-kategori">
        <?php echo $kategori['nama']; ?>
      </a>
    <?php } ?>
  </div>
  <!-- Kategori section end -->

  <!-- Artikel section start -->
  <section class="artikel">
    <h2 style="margin-top: 2rem;">Artikel lainnya</h2>
  </section>

  <?php if ($countData < 1) { ?>
    <h4 class="text-center">Artikel yang anda cari tidak tersedia</h4>
  <?php } ?>

  <div class="lainnya" id="resultContainer">
    <?php while ($data = mysqli_fetch_array($queryArtikel)) { ?>
      <div class="lainnya-page">
        <div class="lainnya-img">
          <img src="css/image/<?php echo $data['gambar']; ?>" alt="">
        </div>
        <div class="content">
          <h3><?php echo $data['judul']; ?></h3>
          <p><?php echo $data['sinopsis']; ?></p>
          <a href="artikel-detail.php?judul=<?php echo $data['judul']; ?>">Baca Selengkapnya...</a>
        </div>
      </div>
    <?php } ?>
  </div>
  <!-- Artikel section end -->

  <!-- Footer section start -->
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
        <a href="#" class="link-footer">Twitter</a>
        <a href="#" class="link-footer">Facebook</a>
      </div>
    </div>
  </section>
  <!-- Footer section end -->

  <script src="js/script.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      var searchForm = document.getElementById("searchForm");
      var resultContainer = document.getElementById("resultContainer");

      searchForm.addEventListener("keyup", function(e) {
        e.preventDefault();

        var keyword = this.elements.keyword.value.trim();

        if (keyword.length > 0) {
          // Lakukan permintaan pencarian ke search.php menggunakan AJAX
          var xhr = new XMLHttpRequest();
          xhr.open("GET", "search.php?keyword=" + keyword, true);
          xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
              resultContainer.innerHTML = xhr.responseText;
            }
          };
          xhr.send();
        } else {
          resultContainer.innerHTML = ""; // Kosongkan kontainer hasil pencarian
        }
      });
    });
  </script>

</body>

</html>
