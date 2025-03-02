<?php 
  $conn = mysqli_connect("localhost", "root", "", "kitasehat");

  if(mysqli_connect_errno()){
    echo "Gagal koneksi ke database". mysqli_connect_error();
    exit();
  }

  if (!file_exists('upload/profile')) {
    mkdir('upload/profile', 0777, true);
  }
?>