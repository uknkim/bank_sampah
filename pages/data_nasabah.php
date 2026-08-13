<?php
date_default_timezone_set('Asia/Jakarta');
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

// Fitur pencarian & paginasi
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";

if (!empty($keyword)) {
    $keywordClean = mysqli_real_escape_string($koneksi, $keyword);
    $whereClause = "WHERE nama LIKE '%$keywordClean%'";
}

// Hitung total data
$sqlTotal = "SELECT COUNT(*) AS total FROM nasabah $whereClause";
$resTotal = mysqli_query($koneksi, $sqlTotal);
$rowTotal = mysqli_fetch_assoc($resTotal);
$totalData = (int)($rowTotal['total'] ?? 0);

// Konfigurasi paginasi
$batas = 10;
$halamanAktif = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
if ($halamanAktif < 1) $halamanAktif = 1;

$totalHalaman = ceil($totalData / $batas);
if ($totalHalaman < 1) $totalHalaman = 1;

if ($halamanAktif > $totalHalaman) {
    $halamanAktif = $totalHalaman;
}

$offset = ($halamanAktif - 1) * $batas;

// Query data nasabah
$sqlNasabah = "SELECT * FROM nasabah $whereClause ORDER BY id_nasabah DESC LIMIT $batas OFFSET $offset";
$resNasabah = mysqli_query($koneksi, $sqlNasabah);

$mulaiData = ($totalData > 0) ? $offset + 1 : 0;
$sampaiData = min($offset + $batas, $totalData);

// Helper format tanggal Indonesia
function formatTanggalIndo($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') return '-';
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $splitWaktu = explode(' ', $tanggal);
    $split = explode('-', $splitWaktu[0]);
    if (count($split) < 3) return '-';
    return (int)$split[2] . ' ' . $bulanIndo[(int)$split[1]] . ' ' . $split[0];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Nasabah - <?= htmlspecialchars($namaBank); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="d-flex flex-column min-vh-100">

    <?php include "components/navbar.php"; ?>

    <!-- Header Section -->
    <section class="bg-light py-5">
        <div class="container text-center">
            <h2 class="fw-bold fs-2">Data Nasabah</h2>
            <p class="text-muted mb-0 fs-6">Daftar nasabah <?= htmlspecialchars($namaBank); ?> yang dapat melihat riwayat monitoring setoran sampah.</p>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-5">
        <div class="container">

            <!-- Alert Informasi -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-people-fill text-success fs-1 me-3 flex-shrink-0"></i>
                        <div>
                            <h5 class="fw-bold mb-1 fs-5">Informasi</h5>
                            <p class="mb-0 text-muted fs-6">
                                Halaman ini menampilkan daftar nasabah <?= htmlspecialchars($namaBank); ?>. Untuk melihat riwayat monitoring setoran sampah, silakan pilih tombol <strong class="text-success">Detail</strong> pada data nasabah yang tersedia.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Utama Tabel Nasabah -->
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h5 class="mb-0 fw-bold text-secondary fs-5">
                        <i class="bi bi-person-lines-fill me-2 text-success"></i>Daftar Nasabah
                    </h5>

                    <!-- Form Pencarian Nasabah -->
                    <form method="GET" action="data_nasabah.php" class="d-flex" style="max-width: 320px; width: 100%;">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm border-success rounded-start-3" placeholder="Cari nama nasabah..." value="<?= htmlspecialchars($keyword); ?>" autocomplete="off">
                            <button type="submit" class="btn btn-success btn-sm rounded-end-3 px-3">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($keyword)): ?>
                                <a href="data_nasabah.php" class="btn btn-outline-secondary btn-sm ms-1 rounded-3" title="Reset Pencarian">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="80" class="text-center py-3">No</th>
                                    <th class="py-3">Nama Nasabah</th>
                                    <th width="220" class="text-center py-3">Tanggal Bergabung</th>
                                    <th width="140" class="text-center py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($resNasabah && mysqli_num_rows($resNasabah) > 0): ?>
                                    <?php $no = $offset + 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($resNasabah)): ?>
                                        <?php
                                        $idNasabah    = $row['id_nasabah'] ?? $row['id'] ?? $row['id_user'] ?? '';
                                        $namaNasabah  = $row['nama'] ?? $row['nama_nasabah'] ?? '-';
                                        $tglBergabung = $row['tanggal_bergabung'] ?? $row['tgl_bergabung'] ?? $row['created_at'] ?? '';
                                        $tglFormat    = formatTanggalIndo($tglBergabung);
                                        ?>
                                        <tr>
                                            <td class="text-center fw-bold text-secondary"><?= $no++; ?></td>
                                            <td class="fw-semibold text-dark fs-6">
                                                <i class="bi bi-person-circle text-success me-2 fs-5"></i><?= htmlspecialchars($namaNasabah, ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="text-center text-muted fs-6">
                                                <i class="bi bi-calendar-check me-1 text-primary"></i><?= $tglFormat; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="detail_monitoring.php?id=<?= $idNasabah; ?>" class="btn btn-sm btn-success rounded-3 px-3 fw-medium">
                                                    <i class="bi bi-eye-fill me-1"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                                            <h6 class="fw-bold text-secondary mb-1">
                                                <?= (!empty($keyword)) ? 'Nasabah dengan kata kunci "' . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . '" tidak ditemukan.' : 'Belum ada data nasabah yang terdaftar.'; ?>
                                            </h6>
                                            <?php if (!empty($keyword)): ?>
                                                <a href="data_nasabah.php" class="btn btn-sm btn-outline-success mt-2 rounded-pill px-3">
                                                    Lihat Semua Nasabah
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Card: Paginasi -->
                <?php if ($totalData > 0): ?>
                    <div class="card-footer bg-white py-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                        <div class="text-muted small">
                            Menampilkan <strong><?= $mulaiData; ?>–<?= $sampaiData; ?></strong> dari <strong><?= $totalData; ?></strong> data
                        </div>

                        <?php if ($totalHalaman > 1): ?>
                            <nav aria-label="Navigasi Halaman Data Nasabah">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php $searchParam = !empty($keyword) ? '&search=' . urlencode($keyword) : ''; ?>

                                    <li class="page-item <?= ($halamanAktif <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link rounded-circle me-1" href="?halaman=<?= $halamanAktif - 1 . $searchParam; ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>

                                    <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                                        <li class="page-item <?= ($i == $halamanAktif) ? 'active' : ''; ?>">
                                            <a class="page-link rounded-circle mx-1" href="?halaman=<?= $i . $searchParam; ?>">
                                                <?= $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= ($halamanAktif >= $totalHalaman) ? 'disabled' : ''; ?>">
                                        <a class="page-link rounded-circle ms-1" href="?halaman=<?= $halamanAktif + 1 . $searchParam; ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </section>

    <?php include "components/footer.php"; ?>

</body>

</html>