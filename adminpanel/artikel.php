<?php
  session_start();
  require_once "auth.php"; 
  if(!isset($_SESSION['login'])){
    header("location: ../login.php");
    exit();
  }

  // Menghasilkan CSRF token jika belum ada
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }

  // mengimpor file koneksi
  require "../inc/koneksi.php";

  // mengimpor file function
  require "../inc/functions.php";

  // Validasi parameter GET (jika ada)
  if (isset($_GET) && !empty($_GET)) {
    foreach ($_GET as $key => $value) {
      $_GET[$key] = htmlspecialchars(trim($value));
    }
  }

  // Mengambil semua artikel dengan prepared statement
  $stmt = $conn->prepare("SELECT * FROM artikel ORDER BY kategori_id DESC");
  $stmt->execute();
  $artikel = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  // Mengambil data artikel dengan kategori yang terkait menggunakan prepared statement
  $stmt = $conn->prepare("SELECT a.*, b.nama AS nama_kategori FROM artikel a JOIN kategori b ON a.kategori_id=b.id");
  $stmt->execute();
  $query = $stmt->get_result();
  $stmt->close();

  // Menghitung jumlah artikel
  $jumlahArtikel = mysqli_num_rows($query);

  // Mengambil semua kategori menggunakan prepared statement
  $stmt = $conn->prepare("SELECT * FROM kategori");
  $stmt->execute();
  $queryKategori = $stmt->get_result();
  $stmt->close();

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
            <a class="nav-link" href="../adminpanel">Home</a>
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
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div>
          <label for="judul">Judul</label>
          <input type="text" id="judul" name="judul" class="form-control" autofocus autocomplete="off" required>
          <div class="invalid-feedback">Judul tidak boleh kosong</div>
        </div>
        <div>
          <label for="kategori">Kategori</label>
          <select name="kategori" id="kategori" class="form-control" required>
            <option value="">Pilih Satu</option>
            <?php
            while ($data = mysqli_fetch_array($queryKategori)) {
            ?>
              <option value="<?php echo $data['id']; ?>"><?php echo $data['nama']; ?></option>
            <?php
            }
            ?>
          </select>
          <div class="invalid-feedback">Kategori harus dipilih</div>
        </div>
        <div>
          <label for="editor">Isi</label>
          <textarea class="form-control" id="editor" name="isi" rows="10" required></textarea>
          <div class="invalid-feedback">Isi artikel tidak boleh kosong</div>
        </div>
        <div>
          <label for="sinopsis">Sinopsis</label>
          <textarea class="form-control" id="sinopsis" name="sinopsis" rows="3" required></textarea>
          <div class="invalid-feedback">Sinopsis tidak boleh kosong</div>
        </div>
        <div>
          <label for="gambar">Gambar</label>
          <input type="file" class="form-control" id="gambar" name="gambar" required>
          <div class="invalid-feedback">Gambar harus diunggah</div>
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
        // Validasi CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
          // Token CSRF tidak valid
          echo '<div class="alert alert-danger mt-3" role="alert">
                  Permintaan tidak valid. Silakan coba lagi.
                </div>';
          exit;
        }
        
        // Validasi input form
        $error = false;
        $errorMsg = [];
        
        // Sanitasi dan validasi input
        $judul = trim($_POST['judul']);
        $kategori = trim($_POST['kategori']);
        $isi = trim($_POST['isi']);
        $sinopsis = trim($_POST['sinopsis']);
        
        if (empty($judul)) {
          $error = true;
          $errorMsg[] = "Judul tidak boleh kosong";
        }
        
        if (empty($kategori)) {
          $error = true;
          $errorMsg[] = "Kategori harus dipilih";
        }
        
        if (empty($isi)) {
          $error = true;
          $errorMsg[] = "Isi artikel tidak boleh kosong";
        }
        
        if (empty($sinopsis)) {
          $error = true;
          $errorMsg[] = "Sinopsis tidak boleh kosong";
        }
        
        // Validasi file gambar
        $new_name = "";
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != 4) {
          $target_dir = "../css/image/";
          $nama_file = basename($_FILES["gambar"]["name"]);
          $target_file = $target_dir . $nama_file;
          $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
          $image_size = $_FILES["gambar"]["size"];
          $random_name = generateRandomString(15);
          $new_name = $random_name . "." . $imageFileType;
          
          // Validasi ukuran file
          if ($image_size > 4000000) {
            $error = true;
            $errorMsg[] = "File tidak boleh lebih dari 4mb";
          }
          
          // Validasi ekstensi file
          if ($imageFileType != 'jpg' && $imageFileType != 'png' && $imageFileType != 'gif') {
            $error = true;
            $errorMsg[] = "File wajib bertipe jpg, png, atau gif";
          }
        } else {
          $error = true;
          $errorMsg[] = "Gambar harus diunggah";
        }
        
        // Tampilkan error jika ada
        if ($error) {
          echo '<div class="alert alert-warning mt-3" role="alert">';
          foreach ($errorMsg as $msg) {
            echo $msg . '<br>';
          }
          echo '</div>';
        } else {
          // Upload file gambar
          move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_dir . $new_name);
          
          // Simpan data artikel dengan prepared statement
          $stmt = $conn->prepare("INSERT INTO artikel (kategori_id, judul, isi, sinopsis, gambar) VALUES (?, ?, ?, ?, ?)");
          $stmt->bind_param("issss", $kategori, $judul, $isi, $sinopsis, $new_name);
          
          if ($stmt->execute()) {
            echo '<div class="alert alert-primary mt-3" role="alert">
                    Artikel berhasil disimpan
                  </div>';
            echo '<meta http-equiv="refresh" content="2; url=artikel.php">';
          } else {
            echo '<div class="alert alert-danger mt-3" role="alert">
                    Error: ' . $stmt->error . '
                  </div>';
          }
          
          $stmt->close();
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
                  <td><?php echo htmlspecialchars($data['judul']); ?></td>
                  <td><?php echo htmlspecialchars($data['nama_kategori']); ?></td>
                  <td><?php echo htmlspecialchars(substr($data['isi'], 0, 100)) . '...'; ?></td>
                  <td><?php echo htmlspecialchars($data['sinopsis']); ?></td>
                  <td><img src="../css/image/<?php echo htmlspecialchars($data['gambar']); ?>" alt="" width="120"></td>
                  <td><a href="artikel-detail.php?p=<?php echo htmlspecialchars($data['id']); ?>" class="btn btn-info"><i class="fas fa-search"></i></a></td>
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
          
      // Validasi form client-side
      (function() {
        'use strict';
        
        // Fetch all forms we want to apply validation styles to
        var forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.from(forms).forEach(function(form) {
          form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
              event.preventDefault();
              event.stopPropagation();
            }
            
            form.classList.add('was-validated');
          }, false);
        });
      })();
    </script>
</body>

</html>