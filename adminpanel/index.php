<?php 
session_start();
require_once "auth.php"; 
if(!isset($_SESSION['login'])){
  header("location: ../login.php");
  exit();
}

// mengimpor file koneksi
require "../inc/koneksi.php";
require "../inc/log_function.php";

// Log aktivitas akses admin panel
catat_log($conn, "Akses admin panel", 'info', $_SESSION['id'], $_SESSION['username']);

//memanggil jumlah kategori dan artikel
$queryKategori = mysqli_query($conn, "SELECT * FROM Kategori");
$jumlahKategori = mysqli_num_rows($queryKategori);

$queryArtikel = mysqli_query($conn, "SELECT * FROM artikel");
$jumlahArtikel = mysqli_num_rows($queryArtikel);

// Ambil jumlah total log aktivitas
$jumlahLog = count_log_aktivitas($conn);

// Pengaturan pagination untuk log
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter berdasarkan aktivitas
$filter_aktivitas = isset($_GET['filter']) ? $_GET['filter'] : '';

// Ambil log aktivitas
$logs = get_log_aktivitas($conn, $limit, $offset, $filter_aktivitas);
$total_logs = count_log_aktivitas($conn, $filter_aktivitas);
$total_pages = ceil($total_logs / $limit);
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin</title>
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

          .summary-log{
            background-color: #28a745;
            border-radius: 15px;
          }

          .no-decoration{
            text-decoration: none;
          }

          .no-decoration:hover{
            text-decoration: underline;
          }

          .log-table {
            font-size: 0.9rem;
          }

          .badge-success { background-color: #28a745; }
          .badge-failed { background-color: #dc3545; }
          .badge-info { background-color: #17a2b8; }

          .log-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
          }

          .device-icon {
            font-size: 1.1em;
            margin-right: 5px;
          }

          .device-ponsel { color: #28a745; }
          .device-komputer { color: #007bff; }
          .device-tablet { color: #fd7e14; }
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
          <i class="fas fa-home"></i> Home
        </li>
      </ol>
    </nav>
    <?php if (isset($_SESSION['username'])) { ?>
      <h2>Halo <?php echo $_SESSION['username']; ?>, Selamat Datang Di Dashboard Admin</h2>
    <?php } else { ?>
      <h2>Selamat Datang Di Dashboard Admin</h2>
    <?php } ?>

    <!-- Summary Cards -->
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

        <div class="col-lg-4 col-md-6 col-12 mb-3">
          <div class="summary-log p-4">
            <div class="row">
              <div class="col-6">
                <i class="fas fa-clipboard-list fa-5x text-black-50"></i>
              </div>
              <div class="col-6 text-white">
                <h3 class="fs-2">Log</h3>
                <p class="fs-4"><?php echo $jumlahLog ?> Aktivitas</p>
                <p><a href="#log-section" class="text-white no-decoration">Lihat Detail</a></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Log Aktivitas Section -->
    <div class="log-section" id="log-section">
      <h3><i class="fas fa-clipboard-list"></i> Log Aktivitas Terbaru</h3>
      
      <!-- Filter dan Search -->
      <div class="row mb-3">
        <div class="col-md-6">
          <form method="GET" class="d-flex">
            <select name="filter" class="form-select me-2">
              <option value="">Semua Aktivitas</option>
              <option value="login" <?php echo $filter_aktivitas == 'login' ? 'selected' : ''; ?>>Login</option>
              <option value="register" <?php echo $filter_aktivitas == 'register' ? 'selected' : ''; ?>>Registrasi</option>
              <option value="admin" <?php echo $filter_aktivitas == 'admin' ? 'selected' : ''; ?>>Admin Panel</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if($filter_aktivitas): ?>
              <a href="?" class="btn btn-secondary ms-2">Reset</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <!-- Tabel Log -->
      <div class="table-responsive">
        <table class="table table-striped table-hover log-table">
          <thead class="table-dark">
            <tr>
              <th>Waktu</th>
              <th>Username</th>
              <th>Aktivitas</th>
              <th>Status</th>
              <th>Device</th>
              <th>Detail</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($logs)): ?>
              <tr>
                <td colspan="6" class="text-center">Tidak ada log aktivitas</td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $log): 
                $device_type = detect_device($log['user_agent']);
                $device_icon = '';
                $device_class = '';
                
                switch($device_type) {
                  case 'ponsel':
                    $device_icon = 'fas fa-mobile-alt';
                    $device_class = 'device-ponsel';
                    break;
                  case 'tablet':
                    $device_icon = 'fas fa-tablet-alt';
                    $device_class = 'device-tablet';
                    break;
                  default:
                    $device_icon = 'fas fa-desktop';
                    $device_class = 'device-komputer';
                }
              ?>
                <tr>
                  <td>
                    <small><?php echo date('d/m/Y H:i:s', strtotime($log['waktu'])); ?></small>
                  </td>
                  <td>
                    <?php echo $log['username'] ? htmlspecialchars($log['username']) : '<em>Guest</em>'; ?>
                  </td>
                  <td><?php echo htmlspecialchars($log['aktivitas']); ?></td>
                  <td>
                    <?php 
                    $badge_class = 'badge-info';
                    if ($log['status'] == 'success') $badge_class = 'badge-success';
                    elseif ($log['status'] == 'failed') $badge_class = 'badge-failed';
                    ?>
                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($log['status']); ?></span>
                  </td>
                  <td>
                    <i class="<?php echo $device_icon; ?> device-icon <?php echo $device_class; ?>"></i>
                    <small class="<?php echo $device_class; ?>"><?php echo ucfirst($device_type); ?></small>
                  </td>
                  <td>
                    <?php if ($log['detail']): ?>
                      <small><?php echo htmlspecialchars(substr($log['detail'], 0, 50)); ?><?php echo strlen($log['detail']) > 50 ? '...' : ''; ?></small>
                    <?php else: ?>
                      <small class="text-muted">-</small>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <nav aria-label="Log pagination">
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $filter_aktivitas ? '&filter='.$filter_aktivitas : ''; ?>">&laquo; Previous</a>
              </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
              <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $filter_aktivitas ? '&filter='.$filter_aktivitas : ''; ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $filter_aktivitas ? '&filter='.$filter_aktivitas : ''; ?>">Next &raquo;</a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>

      <!-- Info Total -->
      <div class="text-center mt-3">
        <small class="text-muted">
          Menampilkan <?php echo count($logs); ?> dari <?php echo $total_logs; ?> log aktivitas
          <?php if ($filter_aktivitas): ?>
            (Filter: <?php echo ucfirst($filter_aktivitas); ?>)
          <?php endif; ?>
        </small>
      </div>
    </div>

  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>

</html>