<?php
// Deteksi nama file untuk penentuan status menu aktif
$currentPage = basename($_SERVER['PHP_SELF']);

// Tentukan prefix jalur relatif berdasarkan lokasi file pemanggil
$basePath = file_exists("pages/components/navbar.php") ? "" : "../";

// Ambil data profil dari database jika belum didefinisikan sebelumnya
if (!isset($namaBank) && isset($koneksi)) {
    $namaBank = "Bank Sampah Metro 46";
    $logoFile = "logo.png";

    $qProfilNav = mysqli_query($koneksi, "SELECT nama_bank_sampah, logo FROM profil LIMIT 1");
    if ($qProfilNav && mysqli_num_rows($qProfilNav) > 0) {
        $dProfilNav = mysqli_fetch_assoc($qProfilNav);
        if (!empty($dProfilNav['nama_bank_sampah'])) {
            $namaBank = $dProfilNav['nama_bank_sampah'];
        }
        if (!empty($dProfilNav['logo'])) {
            $logoFile = $dProfilNav['logo'];
        }
    }
}

// Tentukan path gambar logo
$logoPath = $basePath . "assets/img/" . ($logoFile ?? 'logo.png');
if (!file_exists($logoPath)) {
    $logoPath = $basePath . "assets/img/logo.png";
}
?>

<!-- Navbar Publik -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top py-2">
    <div class="container">
        <!-- Logo & Brand (Akses ke Beranda) -->
        <a class="navbar-brand d-flex align-items-center fw-bold text-success" href="<?= $basePath; ?>index.php">
            <img src="<?= $logoPath; ?>" 
                 alt="Logo <?= htmlspecialchars($namaBank ?? 'Bank Sampah'); ?>" 
                 class="me-2 rounded" 
                 style="height: 38px; width: auto; object-fit: contain;"
                 onerror="this.src='<?= $basePath; ?>assets/img/logo.png';">
            <span><?= htmlspecialchars($namaBank ?? 'Bank Sampah Metro 46'); ?></span>
        </a>

        <!-- Toggle Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu Navigasi (Tombol Hijau Tanpa Ikon) -->
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto gap-2 mt-2 mt-lg-0">
                <li class="nav-item">
                    <a class="btn btn-sm w-100 <?= ($currentPage == 'profil.php') ? 'btn-success fw-semibold' : 'btn-outline-success'; ?>" 
                       href="<?= $basePath; ?>pages/profil.php">
                        Profil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm w-100 <?= ($currentPage == 'jenis_sampah.php' || $currentPage == 'jenis-sampah.php') ? 'btn-success fw-semibold' : 'btn-outline-success'; ?>" 
                       href="<?= $basePath; ?>pages/jenis_sampah.php">
                        Jenis Sampah
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm w-100 <?= ($currentPage == 'jadwal.php') ? 'btn-success fw-semibold' : 'btn-outline-success'; ?>" 
                       href="<?= $basePath; ?>pages/jadwal.php">
                        Jadwal
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-sm w-100 <?= ($currentPage == 'data_nasabah.php' || $currentPage == 'data-nasabah.php' || $currentPage == 'detail-monitoring.php') ? 'btn-success fw-semibold' : 'btn-outline-success'; ?>" 
                       href="<?= $basePath; ?>pages/data_nasabah.php">
                        Data Nasabah
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>