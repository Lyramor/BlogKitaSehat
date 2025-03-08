<?php 
  session_start();
  require_once "auth.php"; 
  if(!isset($_SESSION['login'])){
    header("location: ../login.php");
    exit();
  }

  // mengimpor file koneksi
  require "../inc/koneksi.php";


  //memanggil jumlah kategori dan artikel
  $queryKategori = mysqli_query($conn, "SELECT * FROM Kategori");
  $jumlahKategori =mysqli_num_rows($queryKategori);

  $queryArtikel = mysqli_query($conn, "SELECT * FROM artikel");
  $jumlahArtikel =mysqli_num_rows($queryArtikel);
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dasboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
      <!-- font awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
        integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    
        <style>
          .kotak {
            border: solid;
          }

          .summary-kategori{
            background-color: #00BFFF;
            border-radius: 15px;
          }
          
          .summary-artikel{
            background-color: #464646;
            border-radius: 15px;
          }

          .no-decoration{
            text-decoration: none;
          }

          .no-decoration:hover{
            text-decoration: underline;
          }
        </style>
  </head>

  <nav class="navbar navbar-expand-lg bg-body-tertiary" data-bs-theme="dark">
  <div class="container">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item me-4">
          <a class="nav-link" href="../adminpanel">Beranda</a>
        </li>
        <li class="nav-item me-5">
          <a class="nav-link" href="kategori.php">Kategori</a>
        </li>
        <li class="nav-item me-5">
          <a class="nav-link" href="artikel.php">Artikel</a>
        </li>
        <li class="nav-item me-5">
          <a class="nav-link" href="logout.php">Logout</a>
        </li>
      </ul>
    </div>
  </div>
  </nav>

  <div class="container mt-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item active" aria-current="page">
          <i class="fas fa-home"></i> Home
        </li>
      </ol>
    </nav>
    <?php if (isset($_SESSION['username'])) { ?>
      <h2>Halo <?php echo $_SESSION['username']; ?>, Selamat Datang Di Dasboard Admin</h2>
    <?php } else { ?>
      <h2>Selamat Datang Di Dasboard Admin</h2>
    <?php } ?>

    <div class="container mt-5">
      <div class="row">
        <div class="col-lg-4 col-md-6 col-12 mb-3">
          <div class="summary-kategori p-4">
            <div class="row">
              <div class="col-6">
                <i class="fas fa-align-justify fa-5x text-black-50"></i>
              </div>
              <div class="col-6 text-white">
                <h3 class="fs-2">Kategori</h3>
                <p class="fs-4"><?php echo $jumlahKategori ?> Kategori</p>
                <p><a href="kategori.php" class="text-white no-decoration">Lihat Detail</a></p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12 mb-3">
          <div class="summary-artikel p-4">
            <div class="row">
              <div class="col-6">
                <i class="fas fa-box fa-5x text-black-50"></i>
              </div>
              <div class="col-6 text-white">
                <h3 class="fs-2">Artikel</h3>
                <p class="fs-4"><?php echo $jumlahArtikel ?> Artikel</p>
                <p><a href="artikel.php" class="text-white no-decoration">Lihat Detail</a></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>

</html>