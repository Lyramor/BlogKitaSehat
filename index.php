<?php  
    session_start();
    require "inc/koneksi.php";

    $queryArtikel = mysqli_query($conn, "SELECT id, judul, gambar, sinopsis FROM artikel LIMIT 4");

    // Query untuk mengambil testimoni beserta data user
    $queryTestimoni = mysqli_query($conn, "
    SELECT t.id, t.isi, t.created_at, 
        u.username, u.email, u.foto_profil,
        CONCAT('css/image/', u.foto_profil) as foto_path
    FROM testimoni t
    INNER JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    ");

    $jumlahTestimoni = mysqli_num_rows($queryTestimoni);
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
    <link rel="stylesheet" href="css/artikel1.css">
    <title>KitaSehat</title>
</head>

<body>
    <!-- Navbar start -->
    <div class="navbar">
        <a href="#" class="navbar-logo">
            Kita<span>Sehat</span>.
        </a>

    <div class="navbar-nav">
    <a href="#beranda">Beranda</a>
    <a href="#layanan">About</a>
    <a href="#artikel">Artikel</a>
    <a href="#kontak">Kontak</a>
    <?php
    // Cek apakah session sudah dimulai
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

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
    ?>
</div>



        <div class="hamburger">
            <a href="#" id="hamburger" style="margin-left: 1rem;" class="fa-solid fa-bars fa-xl"></a>
        </div>


    </div>
    <!-- Navbar end -->

    <!-- Hero Section Start -->
    <section class="hero" id="beranda">
        <main class="content">
            <h1>Artikel <span>Kesehatan</span></h1>
            <?php if (isset($_SESSION['username'])) { ?>
                <p>Halo <?php echo $_SESSION['username']; ?>, Ayo chat dokter dan update informasi seputar kesehatan.</p>
            <?php } else { ?>
            <p>Chat dokter dan update informasi seputar kesehatan.</p>
            <?php } ?>
            <a href="register.php" class="cta">Registrasi</a>
        </main>
    </section>
    <!-- Hero Section End -->

    <!-- Abaout Section Start -->
    <section id="layanan" class="layanan">
        <h2><span>About</span> Us</h2>
    </section>

    <div class="tentang">
        <div class="tentang-kami">
            <p>Selamat datang di Website Kesehatan!</p>
            <p> Ini didedikasikan untuk menyediakan artikel-artikel kesehatan yang dapat membantu Anda
                menjaga
                kesehatan dan kebugaran tubuh. Dalam artikel-artikel ini, Anda akan menemukan berbagai informasi terkait
                kesehatan, gaya hidup sehat, tips dan trik, serta berita terkini dalam dunia kesehatan.</p>
            <p>Kami berkomitmen untuk menyajikan konten yang akurat dan terpercaya. Artikel-artikel
                kami disusun
                berdasarkan penelitian yang terkini dan dengan dukungan tenaga ahli di bidang kesehatan. Kami juga
                mengundang Anda untuk berpartisipasi dalam komunitas kami dengan memberikan komentar, berbagi
                pengalaman,
                dan bertanya tentang topik-topik kesehatan yang Anda minati.</p>
            <p>Terima kasih telah mengunjungi Website Kesehatan. Semoga artikel-artikel yang kami
                sajikan dapat
                memberikan
                manfaat dan inspirasi untuk hidup sehat. Jaga kesehatan Anda dan selamat membaca!
            </p>
        </div>
    </div>
    <!-- About Section End -->

    <!-- Artikel Section Start -->
    <section id="artikel" class="artikel">
        <h2>Artikel</h2>
    </section>

    <div class="lainnya">
        <?php while($data = mysqli_fetch_array($queryArtikel)){ ?>
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
    <div class="selebihnya">
        <a href="artikel.php">Artikel lainnya</a>
    </div>
    <!-- Artikel Section End -->

    <!-- Kontak Section Start -->
    <section id="kontak" class="kontak">
        <h2>Kontak</h2>
    </section>

    <div class="row">
        <div class="doctor">
            <div class="doctor-img">
                <img src="css/image/doctor1.jpg" alt="">
            </div>
            <h3>Dr. Muhammad Ali</h3>
            <p>Dokter Umum</p>
            <a href="http://wa.me/6285871416346">Hubungi Sekarang</a>
        </div>
        <div class="doctor">
            <div class="doctor-img">
                <img src="css/image/doctor2.jpg" alt="">
            </div>
            <h3>Dr. Masduki</h3>
            <p>Dokter Ahli Psikologi</p>
            <a href="http://wa.me/6285871416346">Hubungi Sekarang</a>
        </div>
        <div class="doctor">
            <div class="doctor-img">
                <img src="css/image/doctor3.jpg" alt="">
            </div>
            <h3>Dr. Rahman</h3>
            <p>Dokter Ahli Gizi</p>
            <a href="http://wa.me/6285871416346">Hubungi Sekarang</a>
        </div>
        <div class="doctor">
            <div class="doctor-img">
                <img src="css/image/doctor4.jpg" alt="">
            </div>
            <h3>Dr. Siti</h3>
            <p>Dokter Ahli Gigi</p>
            <a href="http://wa.me/6285871416346">Hubungi Sekarang</a>
        </div>
        <div class="doctor">
            <div class="doctor-img">
                <img src="css/image/doctor5.jpg" alt="">
            </div>
            <h3>Dr. Nur</h3>
            <p>Dokter Ahli Bedah</p>
            <a href="http://wa.me/6285871416346">Hubungi Sekarang</a>
        </div>
    </div>
    <!-- Kontak Section End -->

    <!-- Testimoni Section Start -->
    <section id="testimoni" class="testimoni">
        <h2><span>Testimoni</span> Pengguna</h2>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success" id="alertSuccess">
            <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error" id="alertError">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
        <?php endif; ?>

        <div class="testimoni-container">
        <?php if ($jumlahTestimoni > 0): ?>
            <div class="testimoni-carousel">
                <?php while ($testimoni = mysqli_fetch_assoc($queryTestimoni)): ?>
                    <div class="testimoni-slide">
                        <div class="profile-image">
                            <?php if (!empty($testimoni['foto_profil'])): ?>
                                <img src="<?php echo !empty($testimoni['foto_profil']) ? 'css/image/profile/' . htmlspecialchars($testimoni['foto_profil']) : 'https://bootdey.com/img/Content/avatar/avatar1.png'; ?>"" 
                                    alt="Foto <?php echo htmlspecialchars($testimoni['username']); ?>"
                                    onerror="this.src='css/image/default-user.png'"/>
                            <?php else: ?>
                                <img src="css/image/default-user.png" alt="Default Profile"/>
                            <?php endif; ?>
                        </div>
                        <div class="testimoni-content">
                            <p class="testimoni-text">"<?php echo htmlspecialchars($testimoni['isi']); ?>"</p>
                            <div class="user-info">
                                <p class="username"><?php echo htmlspecialchars($testimoni['username']); ?></p>
                                <p class="email"><?php echo htmlspecialchars($testimoni['email']); ?></p>
                                <p class="date"><?php echo date('d F Y', strtotime($testimoni['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <!-- Navigasi Carousel -->
            <div class="carousel-nav">
                <button class="prev-btn">&lt;</button>
                <button class="next-btn">&gt;</button>
            </div>
        <?php else: ?>
            <div class="no-testimoni">
                <p>Belum ada testimoni. Jadilah yang pertama memberikan testimoni!</p>
            </div>
        <?php endif; ?>
        </div>

        <!-- Form Testimoni -->
        <div class="form-testimoni">
            <h3>Berikan Testimoni Anda</h3>
            <?php if (isset($_SESSION['username'])): ?>
                <form action="inc/proses_testimoni.php" method="POST">
                    <textarea name="testimoni" rows="4" placeholder="Bagikan pengalaman Anda dengan KitaSehat..." required></textarea>
                    <button type="submit">Kirim Testimoni</button>
                </form>
            <?php else: ?>
                <div class="login-prompt">
                    <p>Silakan <a href="login.php">login</a> terlebih dahulu untuk memberikan testimoni.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <!-- Testimoni Section End -->

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

    <script src="js/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.querySelector('.testimoni-carousel');
        const slides = document.querySelectorAll('.testimoni-slide');
        const prevBtn = document.querySelector('.prev-btn');
        const nextBtn = document.querySelector('.next-btn');
        let currentSlide = 0;

        // Sembunyikan semua slide kecuali yang pertama
        slides.forEach((slide, index) => {
            if (index !== 0) slide.style.display = 'none';
        });

        // Fungsi untuk menampilkan slide berikutnya
        function showNextSlide() {
            slides[currentSlide].style.display = 'none';
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].style.display = 'flex';
        }

        // Fungsi untuk menampilkan slide sebelumnya
        function showPrevSlide() {
            slides[currentSlide].style.display = 'none';
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            slides[currentSlide].style.display = 'flex';
        }

        // Event listeners untuk tombol navigasi
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', showPrevSlide);
            nextBtn.addEventListener('click', showNextSlide);
        }

        // Auto-slide setiap 5 detik
        setInterval(showNextSlide, 5000);
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Handler untuk loading gambar
        const profileImages = document.querySelectorAll('.profile-image img');
        profileImages.forEach(img => {
            img.addEventListener('load', function() {
                this.classList.add('loaded');
            });
            img.addEventListener('error', function() {
                this.src = 'css/image/default-user.png';
            });
        });
    });

    // Menghilangkan alert setelah 3 detik
    setTimeout(function() {
        let successAlert = document.getElementById("alertSuccess");
        let errorAlert = document.getElementById("alertError");

        if (successAlert) {
            successAlert.style.transition = "opacity 0.5s";
            successAlert.style.opacity = "0";
            setTimeout(() => successAlert.style.display = "none", 500);
        }

        if (errorAlert) {
            errorAlert.style.transition = "opacity 0.5s";
            errorAlert.style.opacity = "0";
            setTimeout(() => errorAlert.style.display = "none", 500);
        }
    }, 3000); // 3000ms = 3 detik
</script>
</body>

</html>