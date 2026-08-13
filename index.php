<?php
// Hubungkan ke database
require_once "config/koneksi.php";

//1. AMBIL DATA PROFIL UTAMA
$namaBank = "Bank Sampah Metro 46";
$deskripsiProfil = "Bank Sampah Metro 46 merupakan program pengelolaan sampah berbasis masyarakat yang bertujuan meningkatkan kepedulian terhadap lingkungan melalui kegiatan pemilahan, pengumpulan, dan pencatatan setoran sampah.";

$sqlProfil = "SELECT * FROM profil LIMIT 1";
$resProfil = mysqli_query($koneksi, $sqlProfil);
if ($resProfil && mysqli_num_rows($resProfil) > 0) {
    $dataProfil = mysqli_fetch_assoc($resProfil);
    if (!empty($dataProfil['nama_bank_sampah'])) {
        $namaBank = $dataProfil['nama_bank_sampah'];
    }
    if (!empty($dataProfil['deskripsi'])) {
        $deskripsiProfil = $dataProfil['deskripsi'];
    }
}

//2. QUERY STATISTIK REAL-TIME
// Total Nasabah
$qNasabah = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM nasabah");
$totalNasabah = ($qNasabah) ? mysqli_fetch_assoc($qNasabah)['total'] : 0;

// Total Jenis Sampah
$qJenis = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM jenis_sampah");
$totalJenis = ($qJenis) ? mysqli_fetch_assoc($qJenis)['total'] : 0;

// Total Setoran (Hitung transaksi unik)
$qSetoran = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_transaksi) as total FROM detail_transaksi");
$totalSetoran = ($qSetoran) ? mysqli_fetch_assoc($qSetoran)['total'] : 0;

// Total Berat Sampah (Kg)
$qBerat = mysqli_query($koneksi, "SELECT SUM(berat) as total FROM detail_transaksi");
$resBerat = ($qBerat) ? mysqli_fetch_assoc($qBerat)['total'] : 0;
$totalBerat = number_format((float)($resBerat ?? 0), 1, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($namaBank); ?> - Beranda</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- Navbar Include -->
    <?php include "pages/components/navbar.php"; ?>

    <!-- Hero Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <h1 class="fw-bold mb-3">
                        Sistem Informasi <?= htmlspecialchars($namaBank); ?>
                    </h1>
                    <p class="text-muted mb-4 lead" style="font-size: 1.05rem; line-height: 1.7;">
                        <?= htmlspecialchars($deskripsiProfil); ?>
                    </p>
                    <a href="pages/profil.php" class="btn btn-success px-4 py-2 rounded-3 shadow-sm">
                        Selengkapnya <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-recycle text-success" style="font-size:180px;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistik Real-time -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card h-100 text-center border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <i class="bi bi-people fs-1 text-primary"></i>
                            <h2 class="mt-3 fw-bold mb-1"><?= $totalNasabah; ?></h2>
                            <p class="text-muted mb-0">Total Nasabah</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card h-100 text-center border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <i class="bi bi-recycle fs-1 text-success"></i>
                            <h2 class="mt-3 fw-bold mb-1"><?= $totalJenis; ?></h2>
                            <p class="text-muted mb-0">Jenis Sampah</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card h-100 text-center border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <i class="bi bi-arrow-down-circle fs-1 text-warning"></i>
                            <h2 class="mt-3 fw-bold mb-1"><?= $totalSetoran; ?></h2>
                            <p class="text-muted mb-0">Total Setoran</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card h-100 text-center border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <i class="bi bi-box-seam fs-1 text-danger"></i>
                            <h2 class="mt-3 fw-bold mb-1"><?= $totalBerat; ?> Kg</h2>
                            <p class="text-muted mb-0">Total Berat Sampah</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tentang Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-3">
                        Tentang <?= htmlspecialchars($namaBank); ?>
                    </h2>
                    <p class="text-muted style-text">
                        <?= htmlspecialchars($namaBank); ?> merupakan program pengelolaan sampah berbasis masyarakat yang bertujuan meningkatkan kesadaran masyarakat terhadap pentingnya menjaga kebersihan lingkungan melalui kegiatan pemilahan, pengumpulan, dan pengelolaan sampah yang bernilai ekonomis.
                    </p>
                    <p class="text-muted mb-0">
                        Melalui sistem informasi ini, masyarakat dapat melihat informasi jenis sampah, jadwal kegiatan, data nasabah, serta riwayat monitoring setoran sampah secara lebih mudah dan transparan.
                    </p>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-globe2 text-success" style="font-size:170px;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Menu Layanan Publik -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Informasi Layanan</h2>
                <p class="text-muted">Pilih menu di bawah untuk melihat informasi yang tersedia.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 text-center border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <i class="bi bi-building fs-1 text-success"></i>
                            <h5 class="mt-3 fw-bold">Profil</h5>
                            <p class="text-muted small">Informasi mengenai <?= htmlspecialchars($namaBank); ?>.</p>
                            <a href="pages/profil.php" class="btn btn-outline-success btn-sm w-100 mt-2">Lihat Profil</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 text-center border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <i class="bi bi-recycle fs-1 text-primary"></i>
                            <h5 class="mt-3 fw-bold">Jenis Sampah</h5>
                            <p class="text-muted small">Daftar jenis sampah beserta harga per kilogram.</p>
                            <a href="pages/jenis-sampah.php" class="btn btn-outline-primary btn-sm w-100 mt-2">Lihat jenis</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 text-center border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <i class="bi bi-calendar-event fs-1 text-warning"></i>
                            <h5 class="mt-3 fw-bold">Jadwal</h5>
                            <p class="text-muted small">Informasi kegiatan dan jadwal Bank Sampah.</p>
                            <a href="pages/jadwal.php" class="btn btn-outline-warning btn-sm w-100 mt-2">Lihat Jadwal</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 text-center border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <i class="bi bi-people fs-1 text-danger"></i>
                            <h5 class="mt-3 fw-bold">Data Nasabah</h5>
                            <p class="text-muted small">Lihat data nasabah dan monitoring setoran sampah.</p>
                            <a href="pages/data-nasabah.php" class="btn btn-outline-danger btn-sm w-100 mt-2">Lihat Nasabah</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Include -->
    <?php include "pages/components/footer.php"; ?>

</body>

</html>