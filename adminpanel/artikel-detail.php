<?php
  session_start();
  require_once "auth.php"; 
  if(!isset($_SESSION['login'])){
    header("location: ../login.php");
    exit();
  }

// Mengimpor file koneksi.php
require "../inc/koneksi.php";


// Mendapatkan nilai parameter GET 'p'
$id = $_GET['p'];

// Mengambil data artikel berdasarkan id dan melakukan JOIN dengan tabel kategori untuk mendapatkan nama kategori
$query = mysqli_query($conn, "SELECT a.*, b.nama AS nama_kategori FROM artikel a JOIN kategori b ON a.kategori_id=b.id WHERE a.id='$id'");
$data = mysqli_fetch_array($query);

// Mengambil data kategori selain kategori artikel yang sedang dipilih
$queryKategori = mysqli_query($conn, "SELECT * FROM kategori WHERE id!='$data[kategori_id]'");

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

// Memeriksa apakah tombol "Simpan" diklik
if (isset($_POST['simpan'])) {
  $judul = htmlspecialchars($_POST['judul']);
  $kategori = htmlspecialchars($_POST['kategori']);
  $isi = htmlspecialchars($_POST['isi']);
  $sinopsis = htmlspecialchars($_POST['sinopsis']);

  if ($judul == '' || $kategori == '' || $isi == '' || $sinopsis == '') {
    // Jika ada kolom yang kosong, tampilkan pesan kesalahan
    $pesan = 'Tidak Boleh Ada Yang Kosong';
  } else {
    // Proses penyimpanan data artikel
    $queryUpdate = mysqli_query($conn, "UPDATE artikel SET kategori_id='$kategori', judul='$judul', isi='$isi', sinopsis='$sinopsis' WHERE id='$id'");

    if ($queryUpdate) {
      // Jika berhasil diperbarui, tampilkan pesan sukses
      $pesan = 'Artikel Berhasil Diupdate';
    } else {
      // Jika terjadi kesalahan saat memperbarui data, tampilkan pesan error
      $pesan = 'Terjadi kesalahan saat mengupdate data artikel: ' . mysqli_error($conn);
    }
  }
}

// Memeriksa apakah tombol "Hapus" diklik
if (isset($_POST['hapus'])) {
  // Hapus data artikel
  $queryHapus = mysqli_query($conn, "DELETE FROM artikel WHERE id='$id'");

  if ($queryHapus) {
    // Jika berhasil dihapus, tampilkan pesan sukses
    $pesan = 'Artikel Berhasil Dihapus';
    // Redirect ke halaman artikel
    header("Location: artikel.php");
    exit();
  } else {
    // Jika terjadi kesalahan saat menghapus data, tampilkan pesan error
    $pesan = 'Terjadi kesalahan saat menghapus data artikel: ' . mysqli_error($conn);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Artikel Detail</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css"
    rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ"
    crossorigin="anonymous">

  <!-- Font Awesome CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
    integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    form div {
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-body-tertiary" data-bs-theme="dark">
    <div class="container">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
    <h2>Detail Artikel</h2>

    <div class="col-12 col-md-6 mb-5">
      <form action="" method="post" enctype="multipart/form-data">
        <div>
          <label for="judul">Judul</label>
          <input type="text" name="judul" id="judul" class="form-control" value="<?php echo $data['judul']; ?>">
        </div>
        <div>
          <label for="kategori">Kategori</label>
          <select name="kategori" id="kategori" class="form-control">
            <option value="<?php echo $data['kategori_id'] ?>"><?php echo $data['nama_kategori'] ?></option>
            <?php
            while ($dataKategori = mysqli_fetch_array($queryKategori)) {
            ?>
              <option value="<?php echo $dataKategori['id']; ?>"><?php echo $dataKategori['nama']; ?></option>
            <?php
            }
            ?>
          </select>
        </div>
        <div>
          <label for="editor">Isi</label>
          <textarea class="form-control" id="editor" name="isi" rows="10"><?php echo $data['isi']; ?></textarea>
        </div>
        <div>
          <label for="sinopsis">Sinopsis</label>
          <textarea class="form-control" id="sinopsis" name="sinopsis" rows="3"><?php echo $data['sinopsis']; ?></textarea>
        </div>
        <div>
          <label for="currentFoto">Foto Produk</label>
          <img src="../css/image/<?php echo $data['gambar'] ?>" width="160" alt="">
        </div>
        <div>
          <label for="gambar">Gambar</label>
          <input type="file" class="form-control" id="gambar" name="gambar">
        </div>
        <div>
          <button type="submit" class="btn btn-primary" name="simpan">Simpan</button>
          <button type="submit" class="btn btn-danger" name="hapus">Hapus</button>
        </div>
      </form>
      <?php 
      // Memeriksa apakah tombol "Simpan" diklik
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
          // Jika ada kolom yang kosong, tampilkan pesan kesalahan
          echo '<div class="alert alert-warning mt-3" role="alert">Tidak Boleh Ada Yang Kosong</div>';
        } else {
          // Cek apakah terdapat perubahan pada gambar
          if ($_FILES["gambar"]["error"] === 0) {
            if ($image_size > 4000000) {
              // Jika ukuran gambar melebihi batas maksimum
              echo '<div class="alert alert-warning mt-3" role="alert">File tidak boleh lebih dari 4mb</div>';
            } else {
              if ($imageFileType != 'jpg' && $imageFileType != 'png' && $imageFileType != 'gif') {
                // Jika tipe file gambar tidak sesuai dengan yang diizinkan (jpg, png, gif)
                echo '<div class="alert alert-warning mt-3" role="alert">File wajib bertipe jpg, png, atau gif</div>';
              } else {
                // Pindahkan file gambar ke direktori tujuan
                move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_dir . $new_name);
                // Perbarui data artikel termasuk gambar baru
                $queryUpdate = mysqli_query($conn, "UPDATE artikel SET kategori_id='$kategori', judul='$judul', isi='$isi', sinopsis='$sinopsis', gambar='$new_name' WHERE id='$id'");

                if ($queryUpdate) {
                  // Jika berhasil diperbarui, tampilkan pesan sukses
                  echo '<div class="alert alert-primary mt-3" role="alert">Artikel Berhasil Diupdate</div>';
                  // Redirect ke halaman artikel
                  echo '<meta http-equiv="refresh" content="1; url=artikel.php">';
                } else {
                  // Jika terjadi kesalahan saat memperbarui data, tampilkan pesan error
                  echo '<div class="alert alert-danger mt-3" role="alert">Terjadi kesalahan saat mengupdate data artikel: ' . mysqli_error($conn) . '</div>';
                }
              }
            }
          } else {
            // Jika tidak ada perubahan pada gambar, perbarui data artikel tanpa mengubah gambar
            $queryUpdate = mysqli_query($conn, "UPDATE artikel SET kategori_id='$kategori', judul='$judul', isi='$isi', sinopsis='$sinopsis' WHERE id='$id'");

            if ($queryUpdate) {
              // Jika berhasil diperbarui, tampilkan pesan sukses
              echo '<div class="alert alert-primary mt-3" role="alert">Artikel Berhasil Diupdate</div>';
              // Redirect ke halaman artikel
              echo '<meta http-equiv="refresh" content="1; url=artikel.php">';
            } else {
              // Jika terjadi kesalahan saat memperbarui data, tampilkan pesan error
              echo '<div class="alert alert-danger mt-3" role="alert">Terjadi kesalahan saat mengupdate data artikel: ' . mysqli_error($conn) . '</div>';
            }
          }
        }
      }

      // Memeriksa apakah tombol "Hapus" diklik
      if (isset($_POST['hapus'])) {
        // Hapus data artikel
        $queryHapus = mysqli_query($conn, "DELETE FROM artikel WHERE id='$id'");

        if ($queryHapus) {
          // Jika berhasil dihapus, tampilkan pesan sukses
          echo '<div class="alert alert-primary mt-3" role="alert">Artikel Berhasil Dihapus</div>';
          // Redirect ke halaman artikel
          echo '<meta http-equiv="refresh" content="1; url=artikel.php">';
        } else {
          // Jika terjadi kesalahan saat menghapus data, tampilkan pesan error
          echo '<div class="alert alert-danger mt-3" role="alert">Terjadi kesalahan saat menghapus data artikel: ' . mysqli_error($conn) . '</div>';
        }
      }
      ?>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-wjvqRQ7RzP9w/dj7NCUfpbueMHNQh+1N5LL3/SwIppbYJ4bgWb/8KLCto4eQVnI0"
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