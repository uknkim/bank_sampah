<?php
// Set zona waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

// Hubungkan ke database (naik satu level direktori)
require_once "../config/koneksi.php";

/* ==========================================================
   0. AJAX ENDPOINT: AMBIL DETAIL SATU TRANSAKSI UNTUK MODAL
========================================================== */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'detail_transaksi') {
    header('Content-Type: application/json');
    $id_transaksi = intval($_GET['id_transaksi'] ?? 0);

    if ($id_transaksi <= 0) {
        echo json_encode(['sukses' => false, 'pesan' => 'ID Transaksi tidak valid']);
        exit;
    }

    // Header Transaksi
    $sqlHeader = "
        SELECT s.id_transaksi, s.tanggal_setoran, s.total_berat, s.total_saldo, n.nama
        FROM transaksi s
        JOIN nasabah n ON n.id_nasabah = s.id_nasabah
        WHERE s.id_transaksi = ?
    ";
    $stmtH = mysqli_prepare($koneksi, $sqlHeader);
    mysqli_stmt_bind_param($stmtH, "i", $id_transaksi);
    mysqli_stmt_execute($stmtH);
    $header = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtH));
    mysqli_stmt_close($stmtH);

    if (!$header) {
        echo json_encode(['sukses' => false, 'pesan' => 'Data transaksi tidak ditemukan']);
        exit;
    }

    // Detail Item Sampah
    $sqlDetail = "
        SELECT d.id_jenis, j.nama_jenis, d.berat, d.harga_per_kg, d.subtotal
        FROM detail_transaksi d
        JOIN jenis_sampah j ON j.id_jenis = d.id_jenis
        WHERE d.id_transaksi = ?
        ORDER BY d.id_detail ASC
    ";
    $stmtD = mysqli_prepare($koneksi, $sqlDetail);
    mysqli_stmt_bind_param($stmtD, "i", $id_transaksi);
    mysqli_stmt_execute($stmtD);
    $resDetail = mysqli_stmt_get_result($stmtD);

    $detail = [];
    while ($row = mysqli_fetch_assoc($resDetail)) {
        $detail[] = $row;
    }
    mysqli_stmt_close($stmtD);

    echo json_encode([
        'sukses' => true,
        'header' => $header,
        'detail' => $detail
    ]);
    exit;
}

/* ==========================================================
   1. AMBIL DATA NAMA BANK (UNTUK HEADER TITLE)
========================================================== */
$namaBank = "Bank Sampah Metro 46";
$qProfil = mysqli_query($koneksi, "SELECT nama_bank_sampah FROM profil LIMIT 1");
if ($qProfil && mysqli_num_rows($qProfil) > 0) {
    $dProfil = mysqli_fetch_assoc($qProfil);
    if (!empty($dProfil['nama_bank_sampah'])) {
        $namaBank = $dProfil['nama_bank_sampah'];
    }
}

/* ==========================================================
   2. VALIDASI & AMBIL DATA NASABAH BERDASARKAN ID
========================================================== */
$idNasabah = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idNasabah <= 0) {
    header("Location: data_nasabah.php");
    exit();
}

$sqlNasabah = "SELECT * FROM nasabah WHERE id_nasabah = ? LIMIT 1";
$stmtN = mysqli_prepare($koneksi, $sqlNasabah);
mysqli_stmt_bind_param($stmtN, "i", $idNasabah);
mysqli_stmt_execute($stmtN);
$resNasabah = mysqli_stmt_get_result($stmtN);

if (!$resNasabah || mysqli_num_rows($resNasabah) == 0) {
    $nasabahDitemukan = false;
} else {
    $nasabahDitemukan = true;
    $dataNasabah = mysqli_fetch_assoc($resNasabah);
    
    $namaNasabah  = $dataNasabah['nama'] ?? '-';
    $alamat       = $dataNasabah['alamat'] ?? '-';
    $tglBergabung = $dataNasabah['tanggal_bergabung'] ?? $dataNasabah['created_at'] ?? '';
    $realIdNasabah = $dataNasabah['id_nasabah'];
}
mysqli_stmt_close($stmtN);

// Helper Fungsi Format Tanggal Indonesia
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

/* ==========================================================
   3. RINGKASAN DATA, GRAFIK & QUERY RIWAYAT
========================================================== */
$summary = [
    'total_transaksi' => 0,
    'total_berat' => 0,
    'total_saldo' => 0
];
$summaryPenarikan = [
    'total_penarikan_transaksi' => 0,
    'total_nominal_penarikan' => 0
];

$saldoBersihTerkini = 0;
$riwayatTransaksi = [];
$riwayatPenarikan = [];

$chartLabels = [];
$chartLineBerat = [];
$barLabels = [];
$barBeratData = [];

// Variable Paginasi Tabel Setoran (halaman)
$batasSetor = 10;
$halamanAktifSetor = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
if ($halamanAktifSetor < 1) $halamanAktifSetor = 1;

$totalHalamanSetor = 1;
$totalTransaksiCount = 0;
$mulaiDataSetor = 0;
$sampaiDataSetor = 0;

// Variable Paginasi Tabel Penarikan (hp)
$batasTarik = 10;
$halamanAktifTarik = isset($_GET['hp']) ? intval($_GET['hp']) : 1;
if ($halamanAktifTarik < 1) $halamanAktifTarik = 1;

$totalHalamanTarik = 1;
$totalPenarikanCount = 0;
$mulaiDataTarik = 0;
$sampaiDataTarik = 0;

if ($nasabahDitemukan) {
    // A. Summary Total Setoran
    $sqlSummary = "
        SELECT 
            COUNT(id_transaksi) AS total_transaksi,
            COALESCE(SUM(total_berat), 0) AS total_berat,
            COALESCE(SUM(total_saldo), 0) AS total_saldo
        FROM transaksi 
        WHERE id_nasabah = ?
    ";
    $stmtS = mysqli_prepare($koneksi, $sqlSummary);
    mysqli_stmt_bind_param($stmtS, "i", $realIdNasabah);
    mysqli_stmt_execute($stmtS);
    $summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtS));
    mysqli_stmt_close($stmtS);

    // B. Summary Total Penarikan
    $sqlSummaryPenarikan = "
        SELECT 
            COUNT(id_penarikan) AS total_penarikan_transaksi,
            COALESCE(SUM(nominal), 0) AS total_nominal_penarikan
        FROM penarikan
        WHERE id_nasabah = ?
    ";
    $stmtSP = mysqli_prepare($koneksi, $sqlSummaryPenarikan);
    mysqli_stmt_bind_param($stmtSP, "i", $realIdNasabah);
    mysqli_stmt_execute($stmtSP);
    $summaryPenarikan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSP));
    mysqli_stmt_close($stmtSP);

    // LOGIKA TOTAL SALDO TERUPDATE (Setoran - Penarikan)
    $totalAkumulasiSetor = (float)$summary['total_saldo'];
    $totalAkumulasiTarik = (float)$summaryPenarikan['total_nominal_penarikan'];
    $saldoBersihTerkini = $totalAkumulasiSetor - $totalAkumulasiTarik;

    // --- PAGINASI SETORAN ---
    $totalTransaksiCount = intval($summary['total_transaksi']);
    $totalHalamanSetor = ceil($totalTransaksiCount / $batasSetor);
    if ($totalHalamanSetor < 1) $totalHalamanSetor = 1;
    if ($halamanAktifSetor > $totalHalamanSetor) $halamanAktifSetor = $totalHalamanSetor;

    $offsetSetor = ($halamanAktifSetor - 1) * $batasSetor;
    $mulaiDataSetor = ($totalTransaksiCount > 0) ? $offsetSetor + 1 : 0;
    $sampaiDataSetor = min($offsetSetor + $batasSetor, $totalTransaksiCount);

    // --- PAGINASI PENARIKAN ---
    $totalPenarikanCount = intval($summaryPenarikan['total_penarikan_transaksi']);
    $totalHalamanTarik = ceil($totalPenarikanCount / $batasTarik);
    if ($totalHalamanTarik < 1) $totalHalamanTarik = 1;
    if ($halamanAktifTarik > $totalHalamanTarik) $halamanAktifTarik = $totalHalamanTarik;

    $offsetTarik = ($halamanAktifTarik - 1) * $batasTarik;
    $mulaiDataTarik = ($totalPenarikanCount > 0) ? $offsetTarik + 1 : 0;
    $sampaiDataTarik = min($offsetTarik + $batasTarik, $totalPenarikanCount);

    // C. Data Grafik Line: Perkembangan Setoran (10 Transaksi Terbaru)
    $sqlChartLine = "
        SELECT * FROM (
            SELECT id_transaksi, tanggal_setoran, total_berat, total_saldo
            FROM transaksi
            WHERE id_nasabah = ?
            ORDER BY tanggal_setoran DESC, id_transaksi DESC
            LIMIT 10
        ) AS sub
        ORDER BY tanggal_setoran ASC, id_transaksi ASC
    ";
    $stmtCL = mysqli_prepare($koneksi, $sqlChartLine);
    mysqli_stmt_bind_param($stmtCL, "i", $realIdNasabah);
    mysqli_stmt_execute($stmtCL);
    $resCL = mysqli_stmt_get_result($stmtCL);

    $bulanShort = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
    $tempRows = [];
    $tglCounts = [];

    while ($row = mysqli_fetch_assoc($resCL)) {
        $tglStr = $row['tanggal_setoran'];
        $tglCounts[$tglStr] = ($tglCounts[$tglStr] ?? 0) + 1;
        $tempRows[] = $row;
    }

    $tglTrackers = [];
    foreach ($tempRows as $row) {
        $tgl = date_create($row['tanggal_setoran']);
        $tglStr = $row['tanggal_setoran'];
        $labelTgl = date_format($tgl, 'd') . ' ' . $bulanShort[(int)date_format($tgl, 'n')] . ' ' . date_format($tgl, 'Y');
        
        if ($tglCounts[$tglStr] > 1) {
            $tglTrackers[$tglStr] = ($tglTrackers[$tglStr] ?? 0) + 1;
            $labelTgl .= " (#" . $tglTrackers[$tglStr] . ")";
        }

        $chartLabels[] = $labelTgl;
        $chartLineBerat[] = (float)$row['total_berat'];
    }
    mysqli_stmt_close($stmtCL);

    // D. Data Grafik Bar: Total Berat berdasarkan Jenis Sampah
    $sqlChartBar = "
        SELECT 
            j.nama_jenis,
            COALESCE(SUM(d.berat), 0) AS total_berat
        FROM detail_transaksi d
        JOIN transaksi t ON t.id_transaksi = d.id_transaksi
        JOIN jenis_sampah j ON j.id_jenis = d.id_jenis
        WHERE t.id_nasabah = ?
        GROUP BY d.id_jenis, j.nama_jenis
        ORDER BY total_berat DESC
    ";
    $stmtCB = mysqli_prepare($koneksi, $sqlChartBar);
    mysqli_stmt_bind_param($stmtCB, "i", $realIdNasabah);
    mysqli_stmt_execute($stmtCB);
    $resCB = mysqli_stmt_get_result($stmtCB);

    while ($row = mysqli_fetch_assoc($resCB)) {
        $barLabels[] = $row['nama_jenis'];
        $barBeratData[] = (float)$row['total_berat'];
    }
    mysqli_stmt_close($stmtCB);

    // E. Data Tabel Riwayat Setoran
    $sqlRiwayat = "
        SELECT id_transaksi, tanggal_setoran, total_berat, total_saldo
        FROM transaksi
        WHERE id_nasabah = ?
        ORDER BY tanggal_setoran DESC, id_transaksi DESC
        LIMIT ? OFFSET ?
    ";
    $stmtR = mysqli_prepare($koneksi, $sqlRiwayat);
    mysqli_stmt_bind_param($stmtR, "iii", $realIdNasabah, $batasSetor, $offsetSetor);
    mysqli_stmt_execute($stmtR);
    $resRiwayat = mysqli_stmt_get_result($stmtR);

    while ($row = mysqli_fetch_assoc($resRiwayat)) {
        $riwayatTransaksi[] = $row;
    }
    mysqli_stmt_close($stmtR);

    // F. Data Tabel Riwayat Penarikan (LOGIKA RUNNING BALANCE MUNDUR SESUAI ADMIN)
    // 1. Hitung total penarikan sesudah offset halaman ini (untuk mendukung pagination jika ada)
    $qPenarikanSesudah = mysqli_query($koneksi, "
        SELECT COALESCE(SUM(nominal), 0) AS total_sesudah 
        FROM (
            SELECT nominal 
            FROM penarikan 
            WHERE id_nasabah = '$realIdNasabah' 
            ORDER BY tanggal_penarikan DESC, id_penarikan DESC 
            LIMIT $offsetTarik
        ) AS sub
    ");
    $penarikanSesudah = (float)mysqli_fetch_assoc($qPenarikanSesudah)['total_sesudah'];

    // Saldo awal penelusuran mundur dari saldo bersih terkini + penarikan setelah halaman ini
    $runningBalance = $saldoBersihTerkini + $penarikanSesudah;

    // 2. Ambil data penarikan untuk halaman aktif
    $sqlPenarikan = "
        SELECT p.id_penarikan, p.tanggal_penarikan, p.nominal, p.keterangan, a.nama_admin
        FROM penarikan p
        LEFT JOIN admin a ON a.id_admin = p.id_admin
        WHERE p.id_nasabah = ?
        ORDER BY p.tanggal_penarikan DESC, p.id_penarikan DESC
        LIMIT ? OFFSET ?
    ";
    $stmtP = mysqli_prepare($koneksi, $sqlPenarikan);
    mysqli_stmt_bind_param($stmtP, "iii", $realIdNasabah, $batasTarik, $offsetTarik);
    mysqli_stmt_execute($stmtP);
    $resPenarikan = mysqli_stmt_get_result($stmtP);

    while ($row = mysqli_fetch_assoc($resPenarikan)) {
        // Set saldo terkini pada baris penarikan saat ini
        $row['saldo_saat_itu'] = $runningBalance;
        
        // Untuk baris penarikan sebelumnya (di bawahnya), tambahkan kembali nominal penarikan ini
        $runningBalance += (float)$row['nominal'];
        
        $riwayatPenarikan[] = $row;
    }
    mysqli_stmt_close($stmtP);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Monitoring - <?= htmlspecialchars($namaBank); ?></title>

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
            <h2 class="fw-bold fs-2">Detail Monitoring</h2>
            <p class="text-muted mb-0 fs-6">Informasi dan riwayat monitoring setoran sampah nasabah <?= htmlspecialchars($namaBank); ?>.</p>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-5">
        <div class="container">

            <?php if (!$nasabahDitemukan): ?>
                <!-- Alert Jika Nasabah Tidak Ditemukan -->
                <div class="card shadow-sm border-0 rounded-4 text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-person-x fs-1 text-danger mb-3 d-block"></i>
                        <h4 class="fw-bold text-dark mb-2">Data Nasabah Tidak Ditemukan</h4>
                        <p class="text-muted mb-4">Nasabah dengan ID tersebut tidak terdaftar dalam sistem kami.</p>
                        <a href="data_nasabah.php" class="btn btn-success px-4 rounded-pill">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Data Nasabah
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <!-- Tombol Kembali -->
                <div class="mb-4">
                    <a href="data_nasabah.php" class="btn btn-outline-success rounded-3 fw-medium">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Nasabah
                    </a>
                </div>

                <!-- Informasi Nasabah -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-secondary fs-5">
                            <i class="bi bi-person-badge text-success me-2"></i>Informasi Nasabah
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 fs-6">
                            <div class="col-md-4">
                                <strong class="text-secondary d-block mb-1">Nama Nasabah</strong>
                                <p class="fw-bold text-dark mb-0 fs-5">
                                    <i class="bi bi-person-circle text-success me-2"></i><?= htmlspecialchars($namaNasabah); ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <strong class="text-secondary d-block mb-1">Alamat</strong>
                                <p class="fw-medium text-dark mb-0">
                                    <i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($alamat); ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <strong class="text-secondary d-block mb-1">Tanggal Bergabung</strong>
                                <p class="fw-medium text-dark mb-0">
                                    <i class="bi bi-calendar-check text-primary me-1"></i><?= formatTanggalIndo($tglBergabung); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Statistik Top Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 rounded-4 text-center h-100">
                            <div class="card-body p-4">
                                <i class="bi bi-arrow-repeat fs-1 text-primary mb-2 d-block"></i>
                                <h3 class="fw-bold text-dark fs-2 mb-1"><?= number_format($summary['total_transaksi'], 0, ',', '.'); ?></h3>
                                <p class="text-muted mb-0 fs-6">Total Transaksi Setoran</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 rounded-4 text-center h-100">
                            <div class="card-body p-4">
                                <i class="bi bi-box-seam fs-1 text-success mb-2 d-block"></i>
                                <h3 class="fw-bold text-dark fs-2 mb-1"><?= number_format($summary['total_berat'], 1, ',', '.'); ?> <span class="fs-5 text-muted">Kg</span></h3>
                                <p class="text-muted mb-0 fs-6">Total Berat Sampah</p>
                            </div>
                        </div>
                    </div>

                    <!-- TOTAL SALDO TERKUMPUL (TER-UPDATE DENGAN LOGIKA PENARIKAN) -->
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 rounded-4 text-center h-100">
                            <div class="card-body p-4">
                                <i class="bi bi-wallet2 fs-1 text-warning mb-2 d-block"></i>
                                <h3 class="fw-bold text-dark fs-2 mb-1">Rp <?= number_format($saldoBersihTerkini, 0, ',', '.'); ?></h3>
                                <p class="text-muted mb-0 fs-6">Total Saldo Terkumpul (Sisa Saldo)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Grafik Chart.js -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 rounded-4 h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="mb-0 fw-bold text-secondary fs-6">
                                    <i class="bi bi-graph-up-arrow text-primary me-2"></i>Grafik Setoran Sampah (Kg)
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div style="height: 280px;">
                                    <canvas id="chartSetoranLine"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 rounded-4 h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="mb-0 fw-bold text-secondary fs-6">
                                    <i class="bi bi-bar-chart-line text-success me-2"></i>Grafik Akumulasi Berat Sampah (Per Jenis)
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div style="height: 280px;">
                                    <canvas id="chartJenisBar"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1. TABEL RIWAYAT MONITORING SETORAN -->
                <div class="card shadow-sm border-0 rounded-4 mb-5">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-secondary fs-5">
                            <i class="bi bi-clock-history text-success me-2"></i>Riwayat Monitoring Setoran
                        </h5>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                            Total <?= number_format($summary['total_transaksi'], 0, ',', '.'); ?> Transaksi
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="70" class="text-center py-3">No</th>
                                        <th class="py-3">Tanggal Setor</th>
                                        <th class="text-center py-3">Berat Total</th>
                                        <th class="text-end py-3">Subtotal / Total Saldo</th>
                                        <th width="120" class="text-center py-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($riwayatTransaksi)): ?>
                                        <?php 
                                        $noSetor = $offsetSetor + 1; 
                                        foreach ($riwayatTransaksi as $row) : 
                                        ?>
                                            <tr>
                                                <td class="text-center fw-bold text-secondary"><?= $noSetor++; ?></td>
                                                <td class="fw-medium text-dark fs-6">
                                                    <i class="bi bi-calendar-event text-primary me-2"></i><?= formatTanggalIndo($row['tanggal_setoran']); ?>
                                                </td>
                                                <td class="text-center fw-bold text-success fs-6">
                                                    <?= number_format($row['total_berat'], 1, ',', '.'); ?> Kg
                                                </td>
                                                <td class="text-end fw-bold text-dark fs-6">
                                                    Rp <?= number_format($row['total_saldo'], 0, ',', '.'); ?>
                                                </td>
                                                <td class="text-center">
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-sm btn-success rounded-pill px-3 btn-detail-transaksi"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalDetailSetoran"
                                                        data-id="<?= $row['id_transaksi']; ?>">
                                                        <i class="bi bi-eye me-1"></i> Detail
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                                <h6 class="fw-bold text-secondary mb-0">Belum ada riwayat setoran sampah untuk nasabah ini.</h6>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($riwayatTransaksi)): ?>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="2" class="text-end py-3">Total Akumulasi Hasil Setoran:</td>
                                        <td class="text-center text-success py-3"><?= number_format($summary['total_berat'], 1, ',', '.'); ?> Kg</td>
                                        <td class="text-end text-success py-3">Rp <?= number_format($summary['total_saldo'], 0, ',', '.'); ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Card: Paginasi Setoran -->
                    <?php if ($totalTransaksiCount > 0): ?>
                    <div class="card-footer bg-white py-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                        <div class="text-muted small">
                            Menampilkan <strong><?= $mulaiDataSetor; ?>–<?= $sampaiDataSetor; ?></strong> dari <strong><?= $totalTransaksiCount; ?></strong> data
                        </div>

                        <?php if ($totalHalamanSetor > 1): ?>
                        <nav aria-label="Navigasi Halaman Riwayat Setoran">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= ($halamanAktifSetor <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link rounded-circle me-1" href="?id=<?= $realIdNasabah; ?>&halaman=<?= $halamanAktifSetor - 1; ?>&hp=<?= $halamanAktifTarik; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <?php for ($i = 1; $i <= $totalHalamanSetor; $i++): ?>
                                    <li class="page-item <?= ($i == $halamanAktifSetor) ? 'active' : ''; ?>">
                                        <a class="page-link rounded-circle mx-1" href="?id=<?= $realIdNasabah; ?>&halaman=<?= $i; ?>&hp=<?= $halamanAktifTarik; ?>">
                                            <?= $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= ($halamanAktifSetor >= $totalHalamanSetor) ? 'disabled' : ''; ?>">
                                    <a class="page-link rounded-circle ms-1" href="?id=<?= $realIdNasabah; ?>&halaman=<?= $halamanAktifSetor + 1; ?>&hp=<?= $halamanAktifTarik; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- 2. TABEL DATA PENARIKAN SALDO -->
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-secondary fs-5">
                            <i class="bi bi-cash-stack text-danger me-2"></i>Data Penarikan Saldo
                        </h5>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fs-6">
                            Total <?= number_format($summaryPenarikan['total_penarikan_transaksi'], 0, ',', '.'); ?> Penarikan
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="70" class="text-center py-3">No</th>
                                        <th class="py-3">Tanggal Penarikan</th>
                                        <th class="text-end py-3">Nominal Penarikan</th>
                                        <th class="py-3">Petugas Admin</th>
                                        <th class="py-3">Keterangan</th>
                                        <th class="text-end py-3">Total Saldo Terkini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($riwayatPenarikan)): ?>
                                        <?php 
                                        $noTarik = $offsetTarik + 1; 
                                        foreach ($riwayatPenarikan as $rowTarik) : 
                                        ?>
                                            <tr>
                                                <td class="text-center fw-bold text-secondary"><?= $noTarik++; ?></td>
                                                <td class="fw-medium text-dark fs-6">
                                                    <i class="bi bi-calendar-check text-danger me-2"></i><?= formatTanggalIndo($rowTarik['tanggal_penarikan']); ?>
                                                </td>
                                                <td class="text-end fw-bold text-danger fs-6">
                                                    - Rp <?= number_format($rowTarik['nominal'], 0, ',', '.'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= htmlspecialchars($rowTarik['nama_admin'] ?? 'Admin'); ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= htmlspecialchars(!empty($rowTarik['keterangan']) ? $rowTarik['keterangan'] : '-'); ?>
                                                </td>
                                                <td class="text-end fw-bold text-success fs-6">
                                                    Rp <?= number_format($rowTarik['saldo_saat_itu'], 0, ',', '.'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-wallet-2 fs-1 d-block mb-2 text-secondary"></i>
                                                <h6 class="fw-bold text-secondary mb-0">Belum ada riwayat penarikan saldo yang dilakukan.</h6>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($riwayatPenarikan)): ?>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="2" class="text-end py-3">Total Penarikan Saldo:</td>
                                        <td class="text-end text-danger py-3">- Rp <?= number_format($summaryPenarikan['total_nominal_penarikan'], 0, ',', '.'); ?></td>
                                        <td colspan="2" class="text-end py-3">Sisa Saldo Tabungan Bersih:</td>
                                        <td class="text-end text-success py-3">Rp <?= number_format($saldoBersihTerkini, 0, ',', '.'); ?></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Card: Paginasi Penarikan -->
                    <?php if ($totalPenarikanCount > 0): ?>
                    <div class="card-footer bg-white py-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                        <div class="text-muted small">
                            Menampilkan <strong><?= $mulaiDataTarik; ?>–<?= $sampaiDataTarik; ?></strong> dari <strong><?= $totalPenarikanCount; ?></strong> data
                        </div>

                        <?php if ($totalHalamanTarik > 1): ?>
                        <nav aria-label="Navigasi Halaman Riwayat Penarikan">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= ($halamanAktifTarik <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link rounded-circle me-1" href="?id=<?= $realIdNasabah; ?>&halaman=<?= $halamanAktifSetor; ?>&hp=<?= $halamanAktifTarik - 1; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <?php for ($j = 1; $j <= $totalHalamanTarik; $j++): ?>
                                    <li class="page-item <?= ($j == $halamanAktifTarik) ? 'active' : ''; ?>">
                                        <a class="page-link rounded-circle mx-1" href="?id=<?= $realIdNasabah; ?>&halaman=<?= $halamanAktifSetor; ?>&hp=<?= $j; ?>">
                                            <?= $j; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= ($halamanAktifTarik >= $totalHalamanTarik) ? 'disabled' : ''; ?>">
                                    <a class="page-link rounded-circle ms-1" href="?id=<?= $realIdNasabah; ?>&halaman=<?= $halamanAktifSetor; ?>&hp=<?= $halamanAktifTarik + 1; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>
    </section>

    <!-- Modal Detail Transaksi Setoran -->
    <div class="modal fade" id="modalDetailSetoran" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-success">
                        <i class="bi bi-receipt me-2"></i>Rincian Transaksi Setoran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <span class="text-muted small d-block">Nama Nasabah</span>
                            <h6 id="detailNamaNasabah" class="fw-bold text-dark mb-0">-</h6>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small d-block">Tanggal Setoran</span>
                            <h6 id="detailTanggalSetoran" class="fw-bold text-dark mb-0">-</h6>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jenis Sampah</th>
                                    <th width="120" class="text-center">Berat (Kg)</th>
                                    <th width="150" class="text-end">Harga / Kg</th>
                                    <th width="160" class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="detailSetoranBody">
                                <!-- Loaded via JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="card bg-light border-0 rounded-3">
                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                            <span class="fw-bold text-secondary">Total Bayar / Saldo</span>
                            <span id="detailGrandTotal" class="fw-bold text-success fs-5">Rp 0</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer Component -->
    <?php include "components/footer.php"; ?>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <?php if ($nasabahDitemukan): ?>
    <script>
        // Format Rupiah Helper
        function formatRupiah(angka) {
            return 'Rp ' + Number(angka || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        // Data dari PHP
        const chartLabels = <?= json_encode($chartLabels); ?>;
        const chartLineBerat = <?= json_encode($chartLineBerat); ?>;
        const barLabels = <?= json_encode($barLabels); ?>;
        const barBeratData = <?= json_encode($barBeratData); ?>;

        // 1. Chart Line Setoran Sampah (Kiri)
        const ctxLine = document.getElementById('chartSetoranLine').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Total Berat (Kg)',
                    data: chartLineBerat,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.15)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Berat (Kg)' }
                    },
                    x: {
                        title: { display: true, text: 'Tanggal Transaksi' }
                    }
                }
            }
        });

        // 2. Chart Bar Akumulasi Berat Sampah (Kanan)
        const ctxBar = document.getElementById('chartJenisBar').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{
                    label: 'Total Berat (Kg)',
                    data: barBeratData,
                    backgroundColor: 'rgba(25, 135, 84, 0.85)',
                    borderColor: '#198754',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Total Berat (Kg)' }
                    },
                    x: {
                        title: { display: true, text: 'Jenis Sampah' }
                    }
                }
            }
        });

        // 3. Script Modal AJAX Detail Transaksi
        document.querySelectorAll('.btn-detail-transaksi').forEach(btn => {
            btn.addEventListener('click', function () {
                const idTransaksi = this.dataset.id;
                const tbody = document.getElementById('detailSetoranBody');
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3">Memuat data...</td></tr>';

                fetch(`detail_monitoring.php?ajax=detail_transaksi&id_transaksi=${idTransaksi}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.sukses) {
                            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">${data.pesan}</td></tr>`;
                            return;
                        }

                        // Header Info
                        document.getElementById('detailNamaNasabah').textContent = data.header.nama;
                        
                        const tgl = new Date(data.header.tanggal_setoran);
                        const bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        document.getElementById('detailTanggalSetoran').textContent = 
                            tgl.getDate() + ' ' + bulanIndo[tgl.getMonth()] + ' ' + tgl.getFullYear();

                        // Table Detail Items
                        tbody.innerHTML = '';
                        data.detail.forEach(item => {
                            tbody.innerHTML += `
                                <tr>
                                    <td class="fw-semibold">${item.nama_jenis}</td>
                                    <td class="text-center">${Number(item.berat).toLocaleString('id-ID', {maximumFractionDigits: 1})} Kg</td>
                                    <td class="text-end">${formatRupiah(item.harga_per_kg)}</td>
                                    <td class="text-end fw-semibold">${formatRupiah(item.subtotal)}</td>
                                </tr>
                            `;
                        });

                        document.getElementById('detailGrandTotal').textContent = formatRupiah(data.header.total_saldo);
                    })
                    .catch(() => {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Gagal mengambil data detail.</td></tr>';
                    });
            });
        });
    </script>
    <?php endif; ?>

</body>

</html>