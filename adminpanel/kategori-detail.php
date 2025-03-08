<?php  
  session_start();
  require_once "auth.php"; 
  if(!isset($_SESSION['login'])){
    header("location: ../login.php");
    exit();
  }

  // mengimpor file koneksi
require "../inc/koneksi.php";

$id = $_GET['p'];

  $query = mysqli_query($conn, "SELECT * FROM kategori WHERE id='$id'");
  $data = mysqli_fetch_array($query);


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Kategori</title>

  <!-- bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">

  <!-- font awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
      integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A=="
      crossorigin="anonymous" referrerpolicy="no-referrer" />
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
  <h2>Detail Kategori</h2>

    <div class="col-12 col-md-6">  
      <form action="" method="post">
        <div>
          <label for="kategori">Kategori</label>
          <input type="text" name="kategori" id="kategori" class="form-control" value="<?php echo $data['nama']; ?>">
        </div>
        
        <div class="mt-4">
          <button type="submit" class="btn btn-primary" name="editBtn">Edit</button>
          <button type="submit" class="btn btn-danger" name="deleteBtn">Delete</button>
        </div>
      </form>

      <?php  
      if (isset($_POST['editBtn'])){
        $kategori = $_POST['kategori'];

        if($data['nama'] == $kategori){
        ?>
          <meta http-equiv="refresh" content="0; url=kategori.php">
        <?php  
        }else{
          $query = mysqli_query($conn, "SELECT * FROM kategori WHERE nama='$kategori'");
          $jumlahData = mysqli_num_rows($query);
          
          if ($jumlahData > 0){
            ?>
            <div class="alert alert-warning mt-3" role="alert">
              Kategori sudah ada
            </div>
            <?php  
          }else{
            $querySimpan = mysqli_query($conn, "UPDATE kategori SET nama='$kategori' WHERE id='$id'");
            if($querySimpan){
          ?>
          <div class="alert alert-primary mt-3" role="alert">
            Kategori Berhasil Diupdate
          </div>
          <meta http-equiv="refresh" content="1; url=kategori.php">
          <?php
            } else {
              echo mysqli_error($conn);
            }
          }
        }
      }

      if(isset($_POST['deleteBtn'])){
        $queryCheck = mysqli_query($conn, "SELECT * FROM artikel WHERE kategori_id= '$id'");
        $dataCount = mysqli_num_rows($queryCheck);

        if($dataCount > 0){
          ?>
          <div class="alert alert-warning mt-3" role="alert">
            Kategori tidak bisa dihapus karena sudah digunakan di artikel
          </div>
          <?php 
          die();
        }

        $queryDelete = mysqli_query($conn, "DELETE FROM kategori WHERE id='$id'");

        if($queryDelete){
          ?>
          <div class="alert alert-primary mt-3" role="alert">
            Kategori Berhasil Dihapus
          </div>
          <meta http-equiv="refresh" content="1; url=kategori.php">
          <?php  
        }else{
          echo mysqli_error($conn);
        }
      }
      ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
</html>