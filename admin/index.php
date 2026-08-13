<?php
session_start();

require_once "../config/koneksi.php";

// cek session admin, kalo belum login lempar ke login.php
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php?pesan=login_dulu");
    exit;
}

// data admin dari session
$id_admin   = $_SESSION['id_admin'];
$username   = $_SESSION['username'];
$nama_admin = $_SESSION['nama_admin'];

// hitung total nasabah
$sqlNasabah = "SELECT COUNT(*) AS total FROM nasabah";
$resultNasabah = mysqli_query($koneksi, $sqlNasabah);
$totalNasabah = mysqli_fetch_assoc($resultNasabah)['total'];

// hitung total jenis sampah
$sqlJenis = "SELECT COUNT(*) AS total FROM jenis_sampah";
$resultJenis = mysqli_query($koneksi, $sqlJenis);
$totalJenis = mysqli_fetch_assoc($resultJenis)['total'];

// hitung total transaksi setoran
$sqlSetoran = "SELECT COUNT(*) AS total FROM transaksi";
$resultSetoran = mysqli_query($koneksi, $sqlSetoran);
$totalSetoran = mysqli_fetch_assoc($resultSetoran)['total'];

// hitung total berat sampah
$sqlBerat = "SELECT COALESCE(SUM(total_berat),0) AS total_berat FROM transaksi";
$resultBerat = mysqli_query($koneksi, $sqlBerat);
$totalBerat = mysqli_fetch_assoc($resultBerat)['total_berat'];

// ambil 5 transaksi terbaru
$sqlTransaksi = "
SELECT
    transaksi.tanggal_setoran,
    transaksi.total_berat,
    transaksi.total_saldo,
    nasabah.nama
FROM transaksi
INNER JOIN nasabah ON transaksi.id_nasabah = nasabah.id_nasabah
ORDER BY transaksi.tanggal_setoran DESC
LIMIT 5
";
$resultTransaksi = mysqli_query($koneksi, $sqlTransaksi);

// ambil 3 jadwal terdekat
$sqlJadwal = "SELECT * FROM jadwal ORDER BY tanggal ASC, waktu ASC LIMIT 3";
$resultJadwal = mysqli_query($koneksi, $sqlJadwal);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrator | Bank Sampah Metro 46</title>

    <!-- cdn css & asset lokal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

    <div class="wrapper">

        <!-- sidebar menu -->
        <?php include "components/sidebar.php"; ?>

        <!-- konten utama -->
        <main class="main-content">

            <!-- navbar atas -->
            <?php include "components/navbar.php"; ?>

            <!-- area isi dashboard -->
            <section class="content-area">

                <!-- header judul -->
                <div class="page-header">
                    <div>
                        <h3>Dashboard</h3>
                        <p>Selamat datang di Sistem Informasi Bank Sampah Metro 46.</p>
                    </div>
                </div>

                <!-- ringkasan statistik -->
                <div class="row g-4">

                    <!-- total nasabah -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Total Nasabah</h6>
                                    <h2><?php echo $totalNasabah; ?></h2>
                                </div>
                                <i class="bi bi-people fs-1 text-primary"></i>
                            </div>
                        </div>
                    </div>

                    <!-- jenis sampah -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Jenis Sampah</h6>
                                    <h2><?php echo $totalJenis; ?></h2>
                                </div>
                                <i class="bi bi-recycle fs-1 text-success"></i>
                            </div>
                        </div>
                    </div>

                    <!-- total setoran -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Total Setoran</h6>
                                    <h2><?php echo $totalSetoran; ?></h2>
                                </div>
                                <i class="bi bi-arrow-down-circle fs-1 text-warning"></i>
                            </div>
                        </div>
                    </div>

                    <!-- total berat -->
                    <div class="col-lg-3 col-md-6">
                        <div class="card dashboard-card h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Total Berat</h6>
                                    <h2><?php echo number_format($totalBerat, 2); ?> Kg</h2>
                                </div>
                                <i class="bi bi-box-seam fs-1 text-danger"></i>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ucapan selamat datang -->
                <div class="card dashboard-card mt-4">
                    <div class="card-body">
                        <h4>Selamat Datang, <strong><?php echo htmlspecialchars($nama_admin); ?></strong></h4>
                        <p class="mb-0">
                            Dashboard ini menampilkan ringkasan data Bank Sampah Metro 46 yang berasal langsung dari database, meliputi jumlah nasabah, jenis sampah, transaksi setoran, total berat sampah, transaksi terbaru, serta jadwal kegiatan yang akan datang.
                        </p>
                    </div>
                </div>

                <div class="row mt-4">

                    <!-- tabel transaksi terbaru -->
                    <div class="col-lg-8">
                        <div class="card dashboard-card">
                            <div class="card-header">
                                <h5 class="mb-0">Transaksi Setoran Terbaru</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nasabah</th>
                                                <th>Tanggal</th>
                                                <th>Berat</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (mysqli_num_rows($resultTransaksi) > 0) {
                                                $no = 1;
                                                while ($row = mysqli_fetch_assoc($resultTransaksi)) {
                                            ?>
                                                    <tr>
                                                        <td><?php echo $no++; ?></td>
                                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                                        <td><?php echo date("d-m-Y", strtotime($row['tanggal_setoran'])); ?></td>
                                                        <td><?php echo number_format($row['total_berat'], 2); ?> Kg</td>
                                                        <td>Rp <?php echo number_format($row['total_saldo'], 0, ',', '.'); ?></td>
                                                    </tr>
                                            <?php
                                                }
                                            } else {
                                            ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">Belum ada data transaksi.</td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- jadwal kegiatan -->
                    <div class="col-lg-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">Jadwal Kegiatan Terdekat</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                if (mysqli_num_rows($resultJadwal) > 0) {
                                    while ($jadwal = mysqli_fetch_assoc($resultJadwal)) {
                                ?>
                                        <div class="border-bottom pb-3 mb-3">
                                            <h6 class="mb-2"><?php echo htmlspecialchars($jadwal['judul']); ?></h6>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                <?php echo date("d-m-Y", strtotime($jadwal['tanggal'])); ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo substr($jadwal['waktu'], 0, 5); ?> WIB
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-geo-alt me-1"></i>
                                                <?php echo htmlspecialchars($jadwal['lokasi']); ?>
                                            </small>
                                        </div>
                                <?php
                                    }
                                } else {
                                ?>
                                    <div class="text-center text-muted py-4">Belum ada jadwal kegiatan.</div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                </div>

            </section>

            <!-- footer admin -->
            <?php include "components/footer.php"; ?>

        </main>

    </div>

    <!-- js bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>