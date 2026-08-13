<?php
require_once "../config/koneksi.php";

// Ambil data nama bank sampah
$namaBank = "Bank Sampah Metro 46";
$qProfil = mysqli_query($koneksi, "SELECT nama_bank_sampah FROM profil LIMIT 1");
if ($qProfil && mysqli_num_rows($qProfil) > 0) {
    $dProfil = mysqli_fetch_assoc($qProfil);
    if (!empty($dProfil['nama_bank_sampah'])) {
        $namaBank = $dProfil['nama_bank_sampah'];
    }
}

// Query daftar jenis sampah
$sqlJenis = "SELECT * FROM jenis_sampah ORDER BY nama_jenis ASC";
$resJenis = mysqli_query($koneksi, $sqlJenis);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jenis Sampah - <?= htmlspecialchars($namaBank); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="d-flex flex-column min-vh-100">

    <?php include "components/navbar.php"; ?>

    <!-- Header Section -->
    <section class="bg-light py-5">
        <div class="container text-center">
            <h2 class="fw-bold">Jenis Sampah</h2>
            <p class="text-muted mb-0">Daftar jenis sampah yang diterima beserta harga per kilogram di <?= htmlspecialchars($namaBank); ?>.</p>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-5">
        <div class="container">
            
            <!-- Alert Informasi -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-info-circle-fill text-success fs-2 me-3 flex-shrink-0"></i>
                        <div>
                            <h5 class="fw-bold mb-1">Informasi</h5>
                            <p class="mb-0 text-muted">
                                Harga setiap jenis sampah dapat berubah sewaktu-waktu sesuai dengan kondisi pasar. Informasi berikut merupakan harga yang berlaku pada <?= htmlspecialchars($namaBank); ?>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Data Jenis Sampah -->
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-secondary">
                        <i class="bi bi-list-stars me-2 text-success"></i>Daftar Jenis Sampah
                    </h5>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="80" class="text-center py-3">No</th>
                                    <th class="py-3">Jenis Sampah</th>
                                    <th width="220" class="text-end py-3 pe-4">Harga / Kg</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($resJenis && mysqli_num_rows($resJenis) > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($resJenis)): ?>
                                        <?php
                                        $namaJenis = $row['nama_jenis'] ?? $row['jenis_sampah'] ?? '-';
                                        $harga     = (float)($row['harga'] ?? $row['harga_per_kg'] ?? 0);
                                        ?>
                                        <tr>
                                            <td class="text-center fw-bold text-secondary"><?= $no++; ?></td>
                                            <td class="fw-semibold text-dark">
                                                <i class="bi bi-recycle text-success me-2"></i><?= htmlspecialchars($namaJenis, ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="text-end fw-bold text-success pe-4">
                                                Rp <?= number_format($harga, 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                            Belum ada data jenis sampah yang tersedia.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php include "components/footer.php"; ?>

</body>

</html>