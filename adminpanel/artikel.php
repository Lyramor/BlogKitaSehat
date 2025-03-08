<?php
  session_start();
  require_once "auth.php"; 
  if(!isset($_SESSION['login'])){
    header("location: ../login.php");
    exit();
  }

  // mengimpor file koneksi
require "../inc/koneksi.php";

// mengimpor file function
require "../inc/functions.php";

// Mengambil semua artikel
$artikel = query("SELECT * FROM artikel ORDER BY kategori_id DESC");

// Mengambil data artikel dengan kategori yang terkait
$query = mysqli_query($conn, "SELECT a.*, b.nama AS nama_kategori FROM artikel a JOIN kategori b ON a.kategori_id=b.id");

// Menghitung jumlah artikel
$jumlahArtikel = mysqli_num_rows($query);

// Mengambil semua kategori
$queryKategori = mysqli_query($conn, "SELECT * FROM kategori");

// Fungsi untuk menghasilkan string acak
function generateRandomString($length = 10)
{
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomthing = '';
  for ($i = 0; $i < $length; $i++) {
    $randomthing .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomthing;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Artikel</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

  <style>
    .no-decoration {
      text-decoration: none;
    }

    form div {
      margin-bottom: 10px;
    }

    @media print {
      .no-print {
        display: none !important;
      }
    }
  </style>

</head>

<body>
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
          <a href="../adminpanel" class="no-decoration text-muted">
            <i class="fas fa-home"></i> Home
          </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
          Artikel
        </li>
      </ol>
    </nav>

    <div class="my-5 col-12 col-md-6 no-print">
      <h3>Tambah Artikel</h3>

      <form id="form-tambah-artikel" action="" method="post" enctype="multipart/form-data">
        <div>
          <label for="judul">Judul</label>
          <input type="text" id="judul" name="judul" class="form-control" autofocus autocomplete="off">
        </div>
        <div>
          <label for="kategori">Kategori</label>
          <select name="kategori" id="kategori" class="form-control">
            <option value="">Pilih Satu</option>
            <?php
            while ($data = mysqli_fetch_array($queryKategori)) {
            ?>
              <option value="<?php echo $data['id']; ?>"><?php echo $data['nama']; ?></option>
            <?php
            }
            ?>
          </select>
        </div>
        <div>
          <label for="editor">Isi</label>
          <textarea class="form-control" id="editor" name="isi" rows="10"></textarea>
        </div>
        <div>
          <label for="sinopsis">Sinopsis</label>
          <textarea class="form-control" id="sinopsis" name="sinopsis"
            rows="3"></textarea>
        </div>
        <div>
          <label for="gambar">Gambar</label>
          <input type="file" class="form-control" id="gambar" name="gambar">
        </div>
        <div class="mt-3">
          <button class="btn btn-primary" type="submit" name="simpan">Simpan</button>
        </div>
      </form>

      <div class="container no-print mt-3">
        <button type="submit" class="btn btn-danger" name="simpan" onclick="window.print()">Print</button>
      </div>

      <?php
      if (isset($_POST['simpan'])) {
        $judul = htmlspecialchars($_POST['judul']);
        $kategori = htmlspecialchars($_POST['kategori']);
        $isi = $_POST['isi'];
        $sinopsis = htmlspecialchars($_POST['sinopsis']);

        $target_dir = "../css/image/";
        $nama_file = basename($_FILES["gambar"]["name"]);
        $target_file = $target_dir . $nama_file;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $image_size = $_FILES["gambar"]["size"];
        $random_name = generateRandomString(15);
        $new_name = $random_name . "." . $imageFileType;

        if ($judul == '' || $kategori == '' || $isi == '' || $sinopsis == '') {
      ?>
          <div class="alert alert-warning mt-3" role='alert'>
            Tidak Boleh Ada Yang Kosong
          </div>
          <?php
        } else {
          if ($nama_file != '') {
            if ($image_size > 4000000) {
          ?>
              <div class="alert alert-warning mt-3" role='alert'>
                File tidak boleh lebih dari 4mb
              </div>
            <?php
            } else {
              if ($imageFileType != 'jpg' && $imageFileType != 'png' && $imageFileType != 'gif') {
            ?>
                <div class="alert alert-warning mt-3" role='alert'>
                  File wajib bertipe jpg, png, atau gif
                </div>
              <?php
              } else {
                move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_dir . $new_name);
              }
            }
          }

          // Query insert
          $queryTambah = mysqli_query($conn, "INSERT INTO `artikel` (`kategori_id`, `judul`, `isi`, `sinopsis`, `gambar`) VALUES ('$kategori', '$judul', '$isi', '$sinopsis', '$new_name')");

          if ($queryTambah) {
              ?>
              <div class="alert alert-primary mt-3" role="alert">
                Artikel berhasil disimpan
              </div>
              <meta http-equiv="refresh" content="2; url=artikel.php">
            <?php
          } else {
            echo mysqli_error($conn);
          }
        }
      }
      ?>
    </div>

    <div class="mt-3 mb-5">
      <h2>List Artikel</h2>

      <div class="table-responsive mt-5">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Judul</th>
              <th>Kategori</th>
              <th>Isi</th>
              <th>Sinopsis</th>
              <th>Gambar</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($jumlahArtikel == 0) {
            ?>
              <tr>
                <td colspan="7" class="text-center">Data Artikel tidak tersedia</td>
              </tr>
              <?php
            } else {
              $nomor = 1;
              while ($data = mysqli_fetch_array($query)) {
              ?>
                <tr>
                  <td><?php echo $nomor; ?></td>
                  <td><?php echo $data['judul']; ?></td>
                  <td><?php echo $data['nama_kategori']; ?></td>
                  <td><?php echo $data['isi']; ?></td>
                  <td><?php echo $data['sinopsis']; ?></td>
                  <td><img src="../css/image/<?php echo $data['gambar']; ?>" alt="" width="120"></td>
                  <td><a href="artikel-detail.php?p=<?php echo $data['id']; ?>" class="btn btn-info"><i class="fas fa-search"></i></a></td>
                </tr>
              <?php
                $nomor++;
              }
            }
              ?>
          </tbody>
        </table>
      </div>
    </div>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/28.0.0/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor'))
            .catch(error => {
                console.error(error);
            });
    </script>
</body>

</html>