<?php
require "inc/koneksi.php";

// Initialize query with proper JOIN
$baseQuery = "SELECT a.id, a.judul, a.gambar, a.sinopsis, k.nama as kategori_nama 
              FROM artikel a 
              LEFT JOIN kategori k ON a.kategori_id = k.id 
              WHERE a.status = 'aktif'";

// Handle search functionality
if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
    $queryArtikel = $baseQuery . " AND (a.judul LIKE '%$keyword%' OR a.sinopsis LIKE '%$keyword%')";
} 
// Handle category filtering
else if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
    $kategori = mysqli_real_escape_string($conn, $_GET['kategori']);
    $queryArtikel = $baseQuery . " AND k.nama = '$kategori'";
} 
// Default query without filters
else {
    $queryArtikel = $baseQuery;
}

// Execute article query
$resultArtikel = mysqli_query($conn, $queryArtikel);
if (!$resultArtikel) {
    die("Query Error: " . mysqli_error($conn));
}

// Get categories for the filter menu
$queryKategori = "SELECT * FROM kategori ORDER BY nama ASC";
$resultKategori = mysqli_query($conn, $queryKategori);
if (!$resultKategori) {
    die("Query Error: " . mysqli_error($conn));
}

$countData = mysqli_num_rows($resultArtikel);
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
  <div class="navbar" style="background-color: rgba(241, 241, 241);">
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
              echo '<a href="postingan/">Postingan</a>';
          }
          
          echo '<a href="profile.php" id="login">Profile</a>';
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
    <?php while ($kategori = mysqli_fetch_array($resultKategori)) { ?>
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
    <?php if ($countData < 1) { ?>
        <h4 style="text-align: center; padding: 2rem;">Artikel yang anda cari tidak tersedia</h4>
    <?php } else { 
        while ($data = mysqli_fetch_array($resultArtikel)) { ?>
            <div class="lainnya-page">
                <div class="lainnya-img">
                    <?php if (!empty($data['gambar']) && file_exists("css/image/" . $data['gambar'])) { ?>
                        <img src="css/image/<?php echo $data['gambar']; ?>" alt="">
                    <?php } else { ?>
                        <img src="css/image/default.jpg" alt="">
                    <?php } ?>
                </div>
                <div class="content">
                    <h3><?php echo $data['judul']; ?></h3>
                    <p><?php echo $data['sinopsis']; ?></p>
                    <a href="artikel-detail.php?judul=<?php echo $data['judul']; ?>">Baca Selengkapnya...</a>
                </div>
            </div>
        <?php }
    } ?>
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
