<?php
session_start();
require_once "../config/koneksi.php";

// Wajib login admin
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php?pesan=login_dulu");
    exit;
}

// Endpoint AJAX buat modal detail setoran
if (isset($_GET['ajax']) && $_GET['ajax'] === 'detail_transaksi') {
    header('Content-Type: application/json');
    $id_transaksi = intval($_GET['id_transaksi'] ?? 0);

    if ($id_transaksi <= 0) {
        echo json_encode(['sukses' => false, 'pesan' => 'ID Transaksi tidak valid']);
        exit;
    }

    // Ambil info header transaksi
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

    // Ambil rincian item sampah
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

// Cek param ID nasabah dari URL
$id_nasabah = intval($_GET['id'] ?? 0);
if ($id_nasabah <= 0) {
    header("Location: nasabah.php");
    exit;
}

// Ambil profil nasabah
$sqlNasabah = "SELECT * FROM nasabah WHERE id_nasabah = ?";
$stmtN = mysqli_prepare($koneksi, $sqlNasabah);
mysqli_stmt_bind_param($stmtN, "i", $id_nasabah);
mysqli_stmt_execute($stmtN);
$nasabah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtN));
mysqli_stmt_close($stmtN);

if (!$nasabah) {
    header("Location: nasabah.php");
    exit;
}

// Totalan setoran sampah
$sqlSummarySetor = "
    SELECT 
        COUNT(id_transaksi) AS total_transaksi,
        COALESCE(SUM(total_berat), 0) AS total_berat_keseluruhan,
        COALESCE(SUM(total_saldo), 0) AS total_setoran_keseluruhan
    FROM transaksi 
    WHERE id_nasabah = ?
";
$stmtS = mysqli_prepare($koneksi, $sqlSummarySetor);
mysqli_stmt_bind_param($stmtS, "i", $id_nasabah);
mysqli_stmt_execute($stmtS);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtS));
mysqli_stmt_close($stmtS);

// Totalan penarikan uang
$sqlSummaryTarik = "
    SELECT 
        COUNT(id_penarikan) AS total_penarikan,
        COALESCE(SUM(nominal), 0) AS total_penarikan_keseluruhan
    FROM penarikan 
    WHERE id_nasabah = ?
";
$stmtT = mysqli_prepare($koneksi, $sqlSummaryTarik);
mysqli_stmt_bind_param($stmtT, "i", $id_nasabah);
mysqli_stmt_execute($stmtT);
$summaryTarik = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtT));
mysqli_stmt_close($stmtT);

// Hitung sisa saldo bersih saat ini
$totalSetoran = (float)$summary['total_setoran_keseluruhan'];
$totalPenarikan = (float)$summaryTarik['total_penarikan_keseluruhan'];
$saldoBersihTerkini = $totalSetoran - $totalPenarikan;

// Paginasi riwayat setoran (p)
$limit_setor = 10;
$halaman_setor = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$total_data_setor = (int)$summary['total_transaksi'];
$total_halaman_setor = $total_data_setor > 0 ? ceil($total_data_setor / $limit_setor) : 1;

if ($halaman_setor > $total_halaman_setor) {
    $halaman_setor = $total_halaman_setor;
}

$offset_setor = ($halaman_setor - 1) * $limit_setor;
$dari_data_setor = $total_data_setor > 0 ? $offset_setor + 1 : 0;
$sampai_data_setor = min($offset_setor + $limit_setor, $total_data_setor);

// Paginasi riwayat penarikan (pp)
$limit_tarik = 10;
$halaman_tarik = isset($_GET['pp']) ? max(1, intval($_GET['pp'])) : 1;
$total_data_tarik = (int)$summaryTarik['total_penarikan'];
$total_halaman_tarik = $total_data_tarik > 0 ? ceil($total_data_tarik / $limit_tarik) : 1;

if ($halaman_tarik > $total_halaman_tarik) {
    $halaman_tarik = $total_halaman_tarik;
}

$offset_tarik = ($halaman_tarik - 1) * $limit_tarik;
$dari_data_tarik = $total_data_tarik > 0 ? $offset_tarik + 1 : 0;
$sampai_data_tarik = min($offset_tarik + $limit_tarik, $total_data_tarik);

// Hitung total nominal penarikan yang terjadi SETELAH offset halaman aktif saat ini
// Digunakan agar saldo baris pertama halaman aktif akurat jika ada paginasi
$sqlPenarikanLebihBaru = "
    SELECT COALESCE(SUM(nominal), 0) AS nominal_lebih_baru
    FROM (
        SELECT nominal
        FROM penarikan
        WHERE id_nasabah = ?
        ORDER BY tanggal_penarikan DESC, id_penarikan DESC
        LIMIT ?
    ) AS sub_baru
";
$stmtNewer = mysqli_prepare($koneksi, $sqlPenarikanLebihBaru);
mysqli_stmt_bind_param($stmtNewer, "ii", $id_nasabah, $offset_tarik);
mysqli_stmt_execute($stmtNewer);
$resNewer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtNewer));
$nominalLebihBaru = (float)($resNewer['nominal_lebih_baru'] ?? 0);
mysqli_stmt_close($stmtNewer);

// Saldo awal untuk baris pertama (terbaru) di halaman aktif
$runningBalance = $saldoBersihTerkini + $nominalLebihBaru;

// Data grafik 1: Tren 10 setoran terakhir
$sqlChartLine = "
    SELECT * FROM (
        SELECT 
            id_transaksi,
            tanggal_setoran,
            total_berat,
            total_saldo
        FROM transaksi
        WHERE id_nasabah = ?
        ORDER BY tanggal_setoran DESC, id_transaksi DESC
        LIMIT 10
    ) AS sub
    ORDER BY tanggal_setoran ASC, id_transaksi ASC
";
$stmtCL = mysqli_prepare($koneksi, $sqlChartLine);
mysqli_stmt_bind_param($stmtCL, "i", $id_nasabah);
mysqli_stmt_execute($stmtCL);
$resChartLine = mysqli_stmt_get_result($stmtCL);

$chartLabels = [];
$chartBeratData = [];

$bulanIndo = [
    1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
];

$tempRows = [];
$tglCounts = [];

while ($row = mysqli_fetch_assoc($resChartLine)) {
    $tglStr = $row['tanggal_setoran'];
    $tglCounts[$tglStr] = ($tglCounts[$tglStr] ?? 0) + 1;
    $tempRows[] = $row;
}

$tglTrackers = [];
foreach ($tempRows as $row) {
    $tgl = date_create($row['tanggal_setoran']);
    $tglStr = $row['tanggal_setoran'];
    $labelTgl = date_format($tgl, 'd') . ' ' . $bulanIndo[(int)date_format($tgl, 'n')] . ' ' . date_format($tgl, 'Y');
    
    // Kasus kalau setor beberapa kali di tanggal yang sama
    if ($tglCounts[$tglStr] > 1) {
        $tglTrackers[$tglStr] = ($tglTrackers[$tglStr] ?? 0) + 1;
        $labelTgl .= " (#" . $tglTrackers[$tglStr] . ")";
    }

    $chartLabels[] = $labelTgl;
    $chartBeratData[] = (float)$row['total_berat'];
}
mysqli_stmt_close($stmtCL);

// Data grafik 2: Akumulasi berat per jenis sampah
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
mysqli_stmt_bind_param($stmtCB, "i", $id_nasabah);
mysqli_stmt_execute($stmtCB);
$resChartBar = mysqli_stmt_get_result($stmtCB);

$barLabels = [];
$barBeratData = [];

while ($row = mysqli_fetch_assoc($resChartBar)) {
    $barLabels[] = $row['nama_jenis'];
    $barBeratData[] = (float)$row['total_berat'];
}
mysqli_stmt_close($stmtCB);

// Ambil list riwayat setoran (halaman aktif)
$sqlRiwayat = "
    SELECT 
        id_transaksi,
        tanggal_setoran,
        total_berat,
        total_saldo
    FROM transaksi
    WHERE id_nasabah = ?
    ORDER BY tanggal_setoran DESC, id_transaksi DESC
    LIMIT ? OFFSET ?
";
$stmtR = mysqli_prepare($koneksi, $sqlRiwayat);
mysqli_stmt_bind_param($stmtR, "iii", $id_nasabah, $limit_setor, $offset_setor);
mysqli_stmt_execute($stmtR);
$resRiwayat = mysqli_stmt_get_result($stmtR);

// Ambil list riwayat penarikan (halaman aktif)
$sqlPenarikan = "
    SELECT 
        p.id_penarikan,
        p.tanggal_penarikan,
        p.nominal,
        p.keterangan,
        a.nama_admin
    FROM penarikan p
    LEFT JOIN admin a ON a.id_admin = p.id_admin
    WHERE p.id_nasabah = ?
    ORDER BY p.tanggal_penarikan DESC, p.id_penarikan DESC
    LIMIT ? OFFSET ?
";
$stmtP = mysqli_prepare($koneksi, $sqlPenarikan);
mysqli_stmt_bind_param($stmtP, "iii", $id_nasabah, $limit_tarik, $offset_tarik);
mysqli_stmt_execute($stmtP);
$resPenarikan = mysqli_stmt_get_result($stmtP);

$bulanIndoLengkap = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Monitoring - <?= htmlspecialchars($nasabah['nama'], ENT_QUOTES, 'UTF-8'); ?> | Bank Sampah Metro 46</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="wrapper">
    <?php include "components/sidebar.php"; ?>

    <main class="main-content">
        <?php include "components/navbar.php"; ?>

        <section class="content-area">
            
            <!-- Judul Halaman -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="mb-1">Detail Monitoring Nasabah</h3>
                    <p class="text-muted mb-0">Analisis statistik, riwayat setoran, dan pencairan saldo nasabah.</p>
                </div>
                <a href="nasabah.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Data Nasabah
                </a>
            </div>

            <!-- Profil Singkat & Card Ringkasan -->
            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-success fw-bold mb-3">
                                <i class="bi bi-person-badge me-2"></i>Informasi Nasabah
                            </h5>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" width="110">Nama</td>
                                    <td>: <strong><?= htmlspecialchars($nasabah['nama'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kode Nasabah</td>
                                    <td>: <span class="badge bg-success-subtle text-success border border-success fs-6 fw-bold"><?= htmlspecialchars($nasabah['kode_nasabah'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">No. Telepon</td>
                                    <td>: <?= htmlspecialchars(!empty($nasabah['telepon']) ? $nasabah['telepon'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Alamat</td>
                                    <td>: <?= htmlspecialchars($nasabah['alamat'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tgl Bergabung</td>
                                    <td>: <?= date('d-m-Y', strtotime($nasabah['created_at'] ?? 'now')); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm text-white bg-success h-100">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-white-50 small fw-semibold">TOTAL TRANSAKSI</div>
                                        <h3 class="fw-bold my-1"><?= number_format($summary['total_transaksi'], 0, ',', '.'); ?></h3>
                                        <span class="small">Kali Setor</span>
                                    </div>
                                    <i class="bi bi-receipt fs-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm text-white bg-primary h-100">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-white-50 small fw-semibold">TOTAL BERAT</div>
                                        <h3 class="fw-bold my-1"><?= number_format($summary['total_berat_keseluruhan'], 1, ',', '.'); ?></h3>
                                        <span class="small">Kilogram (Kg)</span>
                                    </div>
                                    <i class="bi bi-box-seam fs-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm text-white bg-warning h-100">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-white-50 small fw-semibold">TOTAL SALDO TERKINI</div>
                                        <h3 class="fw-bold my-1">Rp <?= number_format($saldoBersihTerkini, 0, ',', '.'); ?></h3>
                                        <span class="small">Sisa Saldo Tabungan</span>
                                    </div>
                                    <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Area Grafik -->
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-center">Perkembangan Total Setoran (Maks. 10 Setoran Terbaru)</h6>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="chartLineSetoran"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold mb-0 text-center">Total Berat Sampah Berdasarkan Jenis</h6>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="chartBarJenis"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Setoran -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-success">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Setoran
                    </h5>
                    <span class="badge bg-primary fs-6"><?= $summary['total_transaksi']; ?> Transaksi</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="70" class="text-center">No</th>
                                    <th>Tanggal Setoran</th>
                                    <th class="text-center">Total Berat</th>
                                    <th class="text-end">Subtotal / Total Saldo</th>
                                    <th width="120" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($resRiwayat) > 0) : ?>
                                    <?php $noSetor = $offset_setor + 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($resRiwayat)) : ?>
                                        <?php
                                        $tgl = date_create($row['tanggal_setoran']);
                                        $tglIndo = date_format($tgl, 'd') . ' ' . $bulanIndoLengkap[(int)date_format($tgl, 'n')] . ' ' . date_format($tgl, 'Y');
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $noSetor++; ?></td>
                                            <td class="fw-semibold"><?= $tglIndo; ?></td>
                                            <td class="text-center"><?= number_format($row['total_berat'], 1, ',', '.'); ?> Kg</td>
                                            <td class="text-end fw-bold text-success">
                                                + Rp <?= number_format($row['total_saldo'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="text-center">
                                                <button 
                                                    type="button" 
                                                    class="btn btn-info btn-sm text-white btn-detail-transaksi"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalDetailSetoran"
                                                    data-id="<?= $row['id_transaksi']; ?>"
                                                    title="Lihat Detail Transaksi">
                                                    <i class="bi bi-eye-fill"></i> Detail
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Belum ada riwayat transaksi setoran untuk nasabah ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">Total Hasil Setoran:</td>
                                    <td class="text-center"><?= number_format($summary['total_berat_keseluruhan'], 1, ',', '.'); ?> Kg</td>
                                    <td class="text-end text-success">Rp <?= number_format($totalSetoran, 0, ',', '.'); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Paginasi Setoran -->
                <?php if ($total_data_setor > 0) : ?>
                <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <p class="text-muted small mb-0">
                        Menampilkan <strong><?= $dari_data_setor; ?>–<?= $sampai_data_setor; ?></strong> dari <strong><?= $total_data_setor; ?></strong> data
                    </p>
                    <?php if ($total_halaman_setor > 1) : ?>
                        <nav aria-label="Page navigation setoran">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= ($halaman_setor <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?id=<?= $id_nasabah; ?>&p=<?= $halaman_setor - 1; ?>&pp=<?= $halaman_tarik; ?>">&laquo;</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_halaman_setor; $i++) : ?>
                                    <li class="page-item <?= ($i == $halaman_setor) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?id=<?= $id_nasabah; ?>&p=<?= $i; ?>&pp=<?= $halaman_tarik; ?>"><?= $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($halaman_setor >= $total_halaman_setor) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?id=<?= $id_nasabah; ?>&p=<?= $halaman_setor + 1; ?>&pp=<?= $halaman_tarik; ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tabel Riwayat Penarikan Saldo -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-danger">
                        <i class="bi bi-cash-stack me-2"></i>Data Penarikan Saldo
                    </h5>
                    <span class="badge bg-danger fs-6"><?= $summaryTarik['total_penarikan']; ?> Penarikan</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="70" class="text-center">No</th>
                                    <th>Tanggal Penarikan</th>
                                    <th class="text-end">Nominal Penarikan</th>
                                    <th>Petugas Admin</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Total Saldo Terkini</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($resPenarikan) > 0) : ?>
                                    <?php 
                                    $noTarik = $offset_tarik + 1; 
                                    $currentSaldoPenarikan = $runningBalance;
                                    ?>
                                    <?php while ($rowT = mysqli_fetch_assoc($resPenarikan)) : ?>
                                        <?php
                                        $tglT = date_create($rowT['tanggal_penarikan']);
                                        $tglIndoT = date_format($tglT, 'd') . ' ' . $bulanIndoLengkap[(int)date_format($tglT, 'n')] . ' ' . date_format($tglT, 'Y');
                                        
                                        // Saldo yang ditampilkan untuk baris ini adalah sisa saldo SETELAH penarikan ini terjadi
                                        $saldoBarisIni = $currentSaldoPenarikan;
                                        
                                        // Saat lanjut ke baris berikutnya (penarikan lebih lama), saldo berjalan DITAMBAH nominal penarikan baris ini
                                        $currentSaldoPenarikan += (float)$rowT['nominal'];
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $noTarik++; ?></td>
                                            <td class="fw-semibold"><?= $tglIndoT; ?></td>
                                            <td class="text-end fw-bold text-danger">
                                                - Rp <?= number_format($rowT['nominal'], 0, ',', '.'); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-dark border">
                                                    <?= htmlspecialchars($rowT['nama_admin'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars(!empty($rowT['keterangan']) ? $rowT['keterangan'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-end fw-bold text-success">
                                                Rp <?= number_format($saldoBarisIni, 0, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada riwayat penarikan saldo yang dilakukan oleh nasabah ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">Total Penarikan:</td>
                                    <td class="text-end text-danger">- Rp <?= number_format($totalPenarikan, 0, ',', '.'); ?></td>
                                    <td colspan="2" class="text-end">Sisa Saldo Tabungan Bersih:</td>
                                    <td class="text-end text-success">Rp <?= number_format($saldoBersihTerkini, 0, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Paginasi Penarikan -->
                <?php if ($total_data_tarik > 0) : ?>
                <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <p class="text-muted small mb-0">
                        Menampilkan <strong><?= $dari_data_tarik; ?>–<?= $sampai_data_tarik; ?></strong> dari <strong><?= $total_data_tarik; ?></strong> data
                    </p>
                    <?php if ($total_halaman_tarik > 1) : ?>
                        <nav aria-label="Page navigation penarikan">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= ($halaman_tarik <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?id=<?= $id_nasabah; ?>&p=<?= $halaman_setor; ?>&pp=<?= $halaman_tarik - 1; ?>">&laquo;</a>
                                </li>
                                <?php for ($j = 1; $j <= $total_halaman_tarik; $j++) : ?>
                                    <li class="page-item <?= ($j == $halaman_tarik) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?id=<?= $id_nasabah; ?>&p=<?= $halaman_setor; ?>&pp=<?= $j; ?>"><?= $j; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($halaman_tarik >= $total_halaman_tarik) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?id=<?= $id_nasabah; ?>&p=<?= $halaman_setor; ?>&pp=<?= $halaman_tarik + 1; ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

        </section>

        <?php include "components/footer.php"; ?>
    </main>
</div>

<!-- Modal Rincian Transaksi -->
<div class="modal fade" id="modalDetailSetoran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-receipt me-2"></i>Rincian Transaksi Setoran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div>
                        <div class="text-muted small">Nasabah</div>
                        <h6 id="detailNamaNasabah" class="fw-bold mb-0">-</h6>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">Tanggal Setoran</div>
                        <h6 id="detailTanggalSetoran" class="fw-bold mb-0">-</h6>
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
                            <!-- Format baris via JS -->
                        </tbody>
                    </table>
                </div>

                <div class="card bg-light border-0">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-6">Grand Total</span>
                        <span id="detailGrandTotal" class="fw-bold text-success fs-5">Rp0</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Helper format rupiah
function formatRupiah(angka) {
    return 'Rp ' + Number(angka || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

// Parsing data grafik dari PHP
const chartLabels = <?= json_encode($chartLabels); ?>;
const chartBeratData = <?= json_encode($chartBeratData); ?>;

const barLabels = <?= json_encode($barLabels); ?>;
const barBeratData = <?= json_encode($barBeratData); ?>;

// Render Chart Line (Tren Setoran)
const ctxLine = document.getElementById('chartLineSetoran').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Total Berat (Kg)',
            data: chartBeratData,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
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
            legend: { display: true, position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Berat (Kg)' }
            },
            x: {
                title: { display: true, text: 'Transaksi Setoran' }
            }
        }
    }
});

// Render Chart Bar (Kategori Sampah)
const ctxBar = document.getElementById('chartBarJenis').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: barLabels,
        datasets: [{
            label: 'Total Berat (Kg)',
            data: barBeratData,
            backgroundColor: '#198754',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Berat (Kg)' }
            },
            x: {
                title: { display: true, text: 'Jenis Sampah' }
            }
        }
    }
});

// Fetch detail transaksi buat modal
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

                document.getElementById('detailNamaNasabah').textContent = data.header.nama;
                
                const tgl = new Date(data.header.tanggal_setoran);
                const bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                document.getElementById('detailTanggalSetoran').textContent = 
                    tgl.getDate() + ' ' + bulanIndo[tgl.getMonth()] + ' ' + tgl.getFullYear();

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

</body>
</html>