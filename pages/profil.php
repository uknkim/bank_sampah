<?php
// Hubungkan ke database (naik satu level direktori dari folder pages)
require_once "../config/koneksi.php";

// AMBIL DATA PROFIL DARI DATABASE
// Set nilai default sebagai pertolongan jika database/tabel kosong
$namaBank   = "Bank Sampah Metro 46";
$deskripsi  = "Bank Sampah Metro 46 merupakan program pengelolaan sampah berbasis masyarakat yang bertujuan meningkatkan kepedulian terhadap lingkungan melalui kegiatan pemilahan, pengumpulan, dan pencatatan setoran sampah.";
$alamat     = "Jl. Contoh No.46, Kelurahan Cibogo, Kecamatan Cisauk, Kabupaten Tangerang";
$telepon    = "(021) 12345678";
$email      = "banksampahmetro46@gmail.com";
$logoPath   = "../assets/img/logo.png"; // Fallback ke logo default

$sqlProfil = "SELECT * FROM profil LIMIT 1";
$resProfil = mysqli_query($koneksi, $sqlProfil);

if ($resProfil && mysqli_num_rows($resProfil) > 0) {
    $data = mysqli_fetch_assoc($resProfil);
    
    if (!empty($data['nama_bank_sampah'])) $namaBank = $data['nama_bank_sampah'];
    if (!empty($data['deskripsi']))        $deskripsi = $data['deskripsi'];
    if (!empty($data['alamat']))           $alamat    = $data['alamat'];
    
    // Cek kemungkinan penamaan kolom telepon/no_hp
    if (!empty($data['telepon'])) {
        $telepon = $data['telepon'];
    } elseif (!empty($data['no_hp'])) {
        $telepon = $data['no_hp'];
    }
    
    if (!empty($data['email']))            $email     = $data['email'];
    
    // Cek kolom logo jika ada
    if (!empty($data['logo']) && file_exists("../assets/img/" . $data['logo'])) {
        $logoPath = "../assets/img/" . $data['logo'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?= htmlspecialchars($namaBank); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- Include Navbar Component -->
    <?php include "components/navbar.php"; ?>

    <!-- Header Section -->
    <section class="bg-light py-5">
        <div class="container text-center">
            <h2 class="fw-bold">Profil Bank Sampah</h2>
            <p class="text-muted mb-0">Informasi mengenai <?= htmlspecialchars($namaBank); ?>.</p>
        </div>
    </section>

    <!-- Main Profil Section -->
    <section class="py-5">
        <div class="container">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-4 text-center">
                            <img src="<?= $logoPath; ?>" 
                                 alt="Logo <?= htmlspecialchars($namaBank); ?>" 
                                 class="img-fluid rounded-circle border shadow-sm p-2" 
                                 style="max-width: 200px; height: 200px; object-fit: cover;"
                                 onerror="this.src='../assets/img/logo.png';">
                        </div>

                        <div class="col-lg-8">
                            <h3 class="fw-bold mb-3 text-success">
                                <?= htmlspecialchars($namaBank); ?>
                            </h3>
                            <p class="text-muted mb-0 style-text" style="line-height: 1.8; font-size: 1.05rem;">
                                <?= nl2br(htmlspecialchars($deskripsi)); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Informasi Kontak Section -->
    <section class="pb-5">
        <div class="container">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-secondary">
                        <i class="bi bi-info-circle me-2 text-success"></i>Informasi Kontak & Operasional
                    </h5>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center p-3 h-100 border-end-md">
                                <i class="bi bi-geo-alt fs-1 text-success"></i>
                                <h6 class="mt-3 fw-bold">Alamat</h6>
                                <p class="text-muted mb-0 small">
                                    <?= nl2br(htmlspecialchars($alamat)); ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-center p-3 h-100 border-end-md">
                                <i class="bi bi-telephone fs-1 text-primary"></i>
                                <h6 class="mt-3 fw-bold">Nomor Telepon</h6>
                                <p class="text-muted mb-0 small">
                                    <?= htmlspecialchars($telepon); ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-center p-3 h-100">
                                <i class="bi bi-envelope fs-1 text-danger"></i>
                                <h6 class="mt-3 fw-bold">Email</h6>
                                <p class="text-muted mb-0 small">
                                    <?= htmlspecialchars($email); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Include Footer Component -->
    <?php include "components/footer.php"; ?>

</body>

</html>