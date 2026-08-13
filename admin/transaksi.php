<?php

session_start();

require_once "../config/koneksi.php";

// Cek status login admin
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php?pesan=login_dulu");
    exit;
}

$nama_admin = $_SESSION['nama_admin'];

// Ajax detail setoran untuk modal detail & edit
if (isset($_GET['ajax']) && $_GET['ajax'] === 'detail') {
    header('Content-Type: application/json');

    $id_transaksi = intval($_GET['id'] ?? 0);

    if ($id_transaksi <= 0) {
        echo json_encode(['sukses' => false]);
        exit;
    }

    $sqlHeader = "
        SELECT
            s.id_transaksi,
            s.id_nasabah,
            s.tanggal_setoran,
            s.total_berat,
            s.total_saldo,
            n.nama
        FROM transaksi s
        JOIN nasabah n ON n.id_nasabah = s.id_nasabah
        WHERE s.id_transaksi = ?
    ";

    $stmtHeader = mysqli_prepare($koneksi, $sqlHeader);
    mysqli_stmt_bind_param($stmtHeader, "i", $id_transaksi);
    mysqli_stmt_execute($stmtHeader);

    $header = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtHeader));
    mysqli_stmt_close($stmtHeader);

    if (!$header) {
        echo json_encode(['sukses' => false]);
        exit;
    }

    $sqlDetail = "
        SELECT
            d.id_jenis,
            j.nama_jenis,
            d.berat,
            d.harga_per_kg,
            d.subtotal
        FROM detail_transaksi d
        JOIN jenis_sampah j ON j.id_jenis = d.id_jenis
        WHERE d.id_transaksi = ?
        ORDER BY d.id_detail ASC
    ";

    $stmtDetail = mysqli_prepare($koneksi, $sqlDetail);
    mysqli_stmt_bind_param($stmtDetail, "i", $id_transaksi);
    mysqli_stmt_execute($stmtDetail);

    $resultDetail = mysqli_stmt_get_result($stmtDetail);
    $detail = [];

    while ($row = mysqli_fetch_assoc($resultDetail)) {
        $detail[] = $row;
    }

    mysqli_stmt_close($stmtDetail);

    echo json_encode([
        'sukses' => true,
        'header' => $header,
        'detail' => $detail,
    ]);

    exit;
}

// Cek input jenis sampah ganda
function ada_duplikat_jenis($daftar_jenis) {
    $jenis_valid = array_filter($daftar_jenis, function($val) {
        return intval($val) > 0;
    });
    
    return count($jenis_valid) !== count(array_unique($jenis_valid));
}

// Simpan data rincian sampah
function simpan_detail_setoran($koneksi, $id_transaksi, $daftar_jenis, $daftar_berat) {
    $total_saldo = 0;
    $total_berat = 0;

    foreach ($daftar_jenis as $i => $id_jenis) {
        $id_jenis = intval($id_jenis);
        $berat = floatval($daftar_berat[$i] ?? 0);

        if ($id_jenis <= 0 || $berat <= 0) {
            continue;
        }

        $sqlHarga = "SELECT harga_per_kg FROM jenis_sampah WHERE id_jenis = ?";
        $stmtHarga = mysqli_prepare($koneksi, $sqlHarga);
        mysqli_stmt_bind_param($stmtHarga, "i", $id_jenis);
        mysqli_stmt_execute($stmtHarga);

        $rowHarga = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtHarga));
        mysqli_stmt_close($stmtHarga);

        if (!$rowHarga) {
            continue;
        }

        $harga_per_kg = floatval($rowHarga['harga_per_kg']);
        $subtotal = $harga_per_kg * $berat;
        
        $total_saldo += $subtotal;
        $total_berat += $berat;

        $sqlInsertDetail = "
            INSERT INTO detail_transaksi (id_transaksi, id_jenis, berat, harga_per_kg, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmtDetail = mysqli_prepare($koneksi, $sqlInsertDetail);
        mysqli_stmt_bind_param(
            $stmtDetail,
            "iiddd",
            $id_transaksi,
            $id_jenis,
            $berat,
            $harga_per_kg,
            $subtotal
        );

        mysqli_stmt_execute($stmtDetail);
        mysqli_stmt_close($stmtDetail);
    }

    return [
        'total_saldo' => $total_saldo,
        'total_berat' => $total_berat
    ];
}

// Proses tambah data setoran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_setoran'])) {
    $id_nasabah = intval($_POST['id_nasabah'] ?? 0);
    $tanggal_setoran = $_POST['tanggal_setoran'] ?? '';
    $daftar_jenis = $_POST['id_jenis'] ?? [];
    $daftar_berat = $_POST['berat'] ?? [];

    if (
        $id_nasabah <= 0 ||
        $tanggal_setoran === '' ||
        !is_array($daftar_jenis) ||
        count($daftar_jenis) === 0
    ) {
        header("Location: transaksi.php?pesan=gagal");
        exit;
    }

    if (ada_duplikat_jenis($daftar_jenis)) {
        header("Location: transaksi.php?pesan=duplikat_jenis");
        exit;
    }

    mysqli_begin_transaction($koneksi);

    try {
        $sqlInsertHeader = "
            INSERT INTO transaksi (id_nasabah, tanggal_setoran, total_berat, total_saldo)
            VALUES (?, ?, 0, 0)
        ";

        $stmtHeader = mysqli_prepare($koneksi, $sqlInsertHeader);
        mysqli_stmt_bind_param($stmtHeader, "is", $id_nasabah, $tanggal_setoran);
        mysqli_stmt_execute($stmtHeader);

        $id_transaksi = mysqli_insert_id($koneksi);
        mysqli_stmt_close($stmtHeader);

        $hasil_detail = simpan_detail_setoran($koneksi, $id_transaksi, $daftar_jenis, $daftar_berat);
        $total_saldo = $hasil_detail['total_saldo'];
        $total_berat = $hasil_detail['total_berat'];

        if ($total_saldo <= 0 || $total_berat <= 0) {
            throw new Exception('Data rincian tidak valid');
        }

        $sqlUpdateTotal = "UPDATE transaksi SET total_berat = ?, total_saldo = ? WHERE id_transaksi = ?";
        $stmtTotal = mysqli_prepare($koneksi, $sqlUpdateTotal);
        mysqli_stmt_bind_param($stmtTotal, "ddi", $total_berat, $total_saldo, $id_transaksi);
        mysqli_stmt_execute($stmtTotal);
        mysqli_stmt_close($stmtTotal);

        mysqli_commit($koneksi);
        header("Location: transaksi.php?pesan=berhasil_tambah");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        header("Location: transaksi.php?pesan=gagal");
        exit;
    }
}

// Proses edit setoran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_setoran'])) {
    $id_transaksi = intval($_POST['id_transaksi'] ?? 0);
    $id_nasabah = intval($_POST['id_nasabah'] ?? 0);
    $tanggal_setoran = $_POST['tanggal_setoran'] ?? '';
    $daftar_jenis = $_POST['id_jenis'] ?? [];
    $daftar_berat = $_POST['berat'] ?? [];

    if (
        $id_transaksi <= 0 ||
        $id_nasabah <= 0 ||
        $tanggal_setoran === '' ||
        !is_array($daftar_jenis) ||
        count($daftar_jenis) === 0
    ) {
        header("Location: transaksi.php?pesan=gagal");
        exit;
    }

    if (ada_duplikat_jenis($daftar_jenis)) {
        header("Location: transaksi.php?pesan=duplikat_jenis");
        exit;
    }

    mysqli_begin_transaction($koneksi);

    try {
        $sqlUpdateHeader = "
            UPDATE transaksi
            SET id_nasabah = ?, tanggal_setoran = ?
            WHERE id_transaksi = ?
        ";

        $stmtHeader = mysqli_prepare($koneksi, $sqlUpdateHeader);
        mysqli_stmt_bind_param($stmtHeader, "isi", $id_nasabah, $tanggal_setoran, $id_transaksi);
        mysqli_stmt_execute($stmtHeader);
        mysqli_stmt_close($stmtHeader);

        $sqlHapusDetail = "DELETE FROM detail_transaksi WHERE id_transaksi = ?";
        $stmtHapus = mysqli_prepare($koneksi, $sqlHapusDetail);
        mysqli_stmt_bind_param($stmtHapus, "i", $id_transaksi);
        mysqli_stmt_execute($stmtHapus);
        mysqli_stmt_close($stmtHapus);

        $hasil_detail = simpan_detail_setoran($koneksi, $id_transaksi, $daftar_jenis, $daftar_berat);
        $total_saldo = $hasil_detail['total_saldo'];
        $total_berat = $hasil_detail['total_berat'];

        $sqlUpdateTotal = "UPDATE transaksi SET total_berat = ?, total_saldo = ? WHERE id_transaksi = ?";
        $stmtTotal = mysqli_prepare($koneksi, $sqlUpdateTotal);
        mysqli_stmt_bind_param($stmtTotal, "ddi", $total_berat, $total_saldo, $id_transaksi);
        mysqli_stmt_execute($stmtTotal);
        mysqli_stmt_close($stmtTotal);

        mysqli_commit($koneksi);
        header("Location: transaksi.php?pesan=berhasil_edit");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        header("Location: transaksi.php?pesan=gagal");
        exit;
    }
}

// Proses hapus setoran
if (isset($_GET['hapus'])) {
    $id_transaksi = intval($_GET['hapus']);

    if ($id_transaksi <= 0) {
        header("Location: transaksi.php?pesan=gagal");
        exit;
    }

    $sqlDelete = "DELETE FROM transaksi WHERE id_transaksi = ?";
    $stmtDelete = mysqli_prepare($koneksi, $sqlDelete);
    mysqli_stmt_bind_param($stmtDelete, "i", $id_transaksi);

    if (!mysqli_stmt_execute($stmtDelete)) {
        mysqli_stmt_close($stmtDelete);
        header("Location: transaksi.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmtDelete);
    header("Location: transaksi.php?pesan=berhasil_hapus");
    exit;
}

// Data pilihan nasabah & jenis sampah
$daftarNasabah = [];
$sqlNasabah = "SELECT id_nasabah, nama FROM nasabah ORDER BY nama ASC";
$resultNasabah = mysqli_query($koneksi, $sqlNasabah);
while ($row = mysqli_fetch_assoc($resultNasabah)) {
    $daftarNasabah[] = $row;
}

$daftarJenis = [];
$sqlJenis = "SELECT id_jenis, nama_jenis, harga_per_kg FROM jenis_sampah ORDER BY nama_jenis ASC";
$resultJenis = mysqli_query($koneksi, $sqlJenis);
while ($row = mysqli_fetch_assoc($resultJenis)) {
    $daftarJenis[] = $row;
}

// Pencarian dan paginasi data
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// Hapus spasi dari keyword agar pencarian fleksibel walau typo spasi
$keywordClean = str_replace(' ', '', $keyword);

// Format tanggal Indonesia tanpa spasi (misal: "30Juli2026")
$sqlTglIndoNoSpace = "
    CONCAT(
        DATE_FORMAT(s.tanggal_setoran, '%e'),
        ELT(MONTH(s.tanggal_setoran), 
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ),
        DATE_FORMAT(s.tanggal_setoran, '%Y')
    )
";

if ($keyword !== '') {
    $sqlCount = "
        SELECT COUNT(*) AS total 
        FROM transaksi s
        JOIN nasabah n ON n.id_nasabah = s.id_nasabah
        WHERE REPLACE(n.nama, ' ', '') LIKE ? 
           OR REPLACE(s.tanggal_setoran, ' ', '') LIKE ?
           OR {$sqlTglIndoNoSpace} LIKE ?
    ";
    $stmtCount = mysqli_prepare($koneksi, $sqlCount);
    $searchParam = "%" . $keywordClean . "%";
    mysqli_stmt_bind_param($stmtCount, "sss", $searchParam, $searchParam, $searchParam);
    mysqli_stmt_execute($stmtCount);
    $rowCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount));
    $totalData = (int)($rowCount['total'] ?? 0);
    mysqli_stmt_close($stmtCount);
} else {
    $sqlCount = "SELECT COUNT(*) AS total FROM transaksi";
    $resCount = mysqli_query($koneksi, $sqlCount);
    $rowCount = mysqli_fetch_assoc($resCount);
    $totalData = (int)($rowCount['total'] ?? 0);
}

$limit = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$totalHalaman = max(1, (int) ceil($totalData / $limit));

if ($page > $totalHalaman) {
    $page = $totalHalaman;
}

$offset = ($page - 1) * $limit;
$mulaiData = ($totalData > 0) ? $offset + 1 : 0;
$sampaiData = min($offset + $limit, $totalData);

if ($keyword !== '') {
    $sql = "
        SELECT
            s.id_transaksi,
            n.nama,
            s.tanggal_setoran,
            s.total_saldo,
            s.total_berat
        FROM transaksi s
        JOIN nasabah n ON n.id_nasabah = s.id_nasabah
        WHERE REPLACE(n.nama, ' ', '') LIKE ? 
           OR REPLACE(s.tanggal_setoran, ' ', '') LIKE ?
           OR {$sqlTglIndoNoSpace} LIKE ?
        ORDER BY s.tanggal_setoran DESC, s.id_transaksi DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($koneksi, $sql);
    $searchParam = "%" . $keywordClean . "%";
    mysqli_stmt_bind_param($stmt, "sssii", $searchParam, $searchParam, $searchParam, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "
        SELECT
            s.id_transaksi,
            n.nama,
            s.tanggal_setoran,
            s.total_saldo,
            s.total_berat
        FROM transaksi s
        JOIN nasabah n ON n.id_nasabah = s.id_nasabah
        ORDER BY s.tanggal_setoran DESC, s.id_transaksi DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

$bulanIndonesia = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Setoran | Bank Sampah Metro 46</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>

<div class="wrapper">

    <?php include "components/sidebar.php"; ?>

    <main class="main-content">

        <?php include "components/navbar.php"; ?>

        <section class="content-area">

            <div class="page-header">
                <div>
                    <h3 class="mb-1">Data Setoran</h3>
                    <p class="text-muted mb-0">
                        Kelola seluruh transaksi setoran sampah dari setiap nasabah Bank Sampah Metro 46.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn btn-add"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTambahSetoran">
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Setoran
                </button>
            </div>

            <!-- Notifikasi Sistem -->
            <?php if (isset($_GET['pesan'])) : ?>
                <?php
                $pesan = $_GET['pesan'];
                $alertClass = 'danger';
                $alertIcon = 'x-circle-fill';
                $alertText = 'Proses gagal dilakukan.';

                if ($pesan === 'berhasil_tambah') {
                    $alertClass = 'success';
                    $alertIcon = 'check-circle-fill';
                    $alertText = 'Data setoran berhasil ditambahkan.';
                } elseif ($pesan === 'berhasil_edit') {
                    $alertClass = 'success';
                    $alertIcon = 'check-circle-fill';
                    $alertText = 'Data setoran berhasil diperbarui.';
                } elseif ($pesan === 'berhasil_hapus') {
                    $alertClass = 'success';
                    $alertIcon = 'check-circle-fill';
                    $alertText = 'Data setoran berhasil dihapus.';
                } elseif ($pesan === 'duplikat_jenis') {
                    $alertClass = 'warning';
                    $alertIcon = 'exclamation-triangle-fill';
                    $alertText = 'Terdapat transaksi yang menyetor jenis sampah yang sama. Silakan pilih jenis sampah yang berbeda!';
                }
                ?>

                <div class="alert alert-<?= $alertClass; ?> alert-dismissible fade show">
                    <i class="bi bi-<?= $alertIcon; ?> me-2"></i>
                    <?= $alertText; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card table-card">

                <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h5 class="mb-0 fw-bold text-secondary fs-6">
                        <i class="bi bi-clock-history me-2 text-success"></i>Riwayat Transaksi Setoran Sampah
                    </h5>

                    <!-- Form Pencarian -->
                    <form method="GET" action="transaksi.php" class="d-flex" style="max-width: 320px; width: 100%;">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm border-success rounded-start-3" placeholder="Cari nama, tanggal..." value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                            <button type="submit" class="btn btn-success btn-sm rounded-end-3 px-3">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($keyword)): ?>
                                <a href="transaksi.php" class="btn btn-outline-secondary btn-sm ms-1 rounded-3" title="Reset Pencarian">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="70" class="text-center">No</th>
                                    <th>Nama Nasabah</th>
                                    <th width="170">Tanggal Setoran</th>
                                    <th width="160">Total Berat</th>
                                    <th width="180">Grand Total</th>
                                    <th width="220" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0) : ?>
                                    <?php $no = $offset + 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <?php
                                        $tgl = date_create($row['tanggal_setoran']);
                                        $tglIndo =
                                            date_format($tgl, 'd') . ' ' .
                                            $bulanIndonesia[(int) date_format($tgl, 'n')] . ' ' .
                                            date_format($tgl, 'Y');
                                        ?>
                                        <tr>
                                            <td class="text-center fw-bold text-secondary"><?= $no++; ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= $tglIndo; ?></td>
                                            <td><?= number_format($row['total_berat'], 1, ',', '.'); ?> Kg</td>
                                            <td class="fw-bold text-success">Rp <?= number_format($row['total_saldo'], 0, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-info btn-sm btn-lihat-setoran text-white"
                                                    title="Lihat Detail"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalDetailSetoran"
                                                    data-id="<?= $row['id_transaksi']; ?>">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>

                                                <button
                                                    type="button"
                                                    class="btn btn-warning btn-sm btn-edit-setoran text-white"
                                                    title="Edit Data"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditSetoran"
                                                    data-id="<?= $row['id_transaksi']; ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                <a
                                                    href="transaksi.php?hapus=<?= $row['id_transaksi']; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    title="Hapus Data"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus data setoran ini?')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <?= (!empty($keyword)) ? 'Data setoran dengan kata kunci "' . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . '" tidak ditemukan.' : 'Belum ada data setoran.'; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Card & Paginasi -->
                <?php if ($totalData > 0): ?>
                <div class="card-footer bg-white py-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <div class="text-muted small">
                        Menampilkan <strong><?= $mulaiData; ?>–<?= $sampaiData; ?></strong> dari <strong><?= $totalData; ?></strong> data
                    </div>

                    <?php if ($totalHalaman > 1): ?>
                    <nav aria-label="Navigasi Halaman Data Setoran">
                        <ul class="pagination pagination-sm mb-0">
                            <?php $searchQuery = !empty($keyword) ? '&search=' . urlencode($keyword) : ''; ?>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-circle me-1" href="?page=<?= $page - 1 . $searchQuery; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link rounded-circle mx-1" href="?page=<?= $i . $searchQuery; ?>">
                                        <?= $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?= ($page >= $totalHalaman) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-circle ms-1" href="?page=<?= $page + 1 . $searchQuery; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>

        </section>

        <!-- Modal Tambah Setoran -->
        <div class="modal fade" id="modalTambahSetoran" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Data Setoran
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form method="POST" id="formTambahSetoran" autocomplete="off">
                        <input type="hidden" name="tambah_setoran" value="1">

                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Nama Nasabah <span class="text-danger">*</span>
                                    </label>
                                    <select name="id_nasabah" class="form-select" required>
                                        <option value="">-- Pilih Nasabah --</option>
                                        <?php foreach ($daftarNasabah as $n) : ?>
                                            <option value="<?= $n['id_nasabah']; ?>">
                                                <?= htmlspecialchars($n['nama'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Tanggal Setoran <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="tanggal_setoran" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-semibold mb-1">Detail Sampah</h6>
                                    <small class="text-muted">Tambahkan satu atau lebih jenis sampah yang disetorkan.</small>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" data-tambah-baris="tambah">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                                </button>
                            </div>

                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light sticky-top" style="z-index: 1;">
                                        <tr>
                                            <th>Jenis Sampah</th>
                                            <th width="140" class="text-center">Berat (Kg)</th>
                                            <th width="170" class="text-center">Harga / Kg</th>
                                            <th width="180" class="text-center">Subtotal</th>
                                            <th width="90" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detailSetoran-tambah"></tbody>
                                </table>
                            </div>

                            <div class="row justify-content-end mt-4">
                                <div class="col-lg-4 col-md-5">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <label class="form-label fw-semibold">Grand Total</label>
                                            <input
                                                type="text"
                                                id="total_saldo-tambah"
                                                class="form-control form-control-lg fw-bold text-success"
                                                value="Rp0"
                                                readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save me-1"></i> Simpan Setoran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Detail Setoran -->
        <div class="modal fade" id="modalDetailSetoran" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-eye me-2"></i> Detail Setoran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-4">
                            <h5 id="detailNamaNasabah" class="fw-bold mb-1">-</h5>
                            <div id="detailTanggalSetoran" class="text-muted">-</div>
                        </div>

                        <div id="detailSetoranList"></div>

                        <div class="card bg-light border-success mt-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold fs-5">Grand Total</span>
                                    <span id="detailGrandTotal" class="fw-bold text-success fs-4">Rp0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit Setoran -->
        <div class="modal fade" id="modalEditSetoran" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil-square me-2"></i> Edit Data Setoran
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form method="POST" id="formEditSetoran" autocomplete="off">
                        <input type="hidden" name="edit_setoran" value="1">
                        <input type="hidden" name="id_transaksi" id="edit_id_transaksi">

                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Nama Nasabah <span class="text-danger">*</span>
                                    </label>
                                    <select name="id_nasabah" id="edit_id_nasabah" class="form-select" required>
                                        <option value="">-- Pilih Nasabah --</option>
                                        <?php foreach ($daftarNasabah as $n) : ?>
                                            <option value="<?= $n['id_nasabah']; ?>">
                                                <?= htmlspecialchars($n['nama'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Tanggal Setoran <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="tanggal_setoran" id="edit_tanggal_setoran" class="form-control" required>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-semibold mb-1">Detail Sampah</h6>
                                    <small class="text-muted">Edit jenis sampah yang disetorkan.</small>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" data-tambah-baris="edit">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                                </button>
                            </div>

                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light sticky-top" style="z-index: 1;">
                                        <tr>
                                            <th>Jenis Sampah</th>
                                            <th width="140" class="text-center">Berat (Kg)</th>
                                            <th width="170" class="text-center">Harga / Kg</th>
                                            <th width="180" class="text-center">Subtotal</th>
                                            <th width="90" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detailSetoran-edit"></tbody>
                                </table>
                            </div>

                            <div class="row justify-content-end mt-4">
                                <div class="col-lg-4 col-md-5">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body">
                                            <label class="form-label fw-semibold">Grand Total</label>
                                            <input
                                                type="text"
                                                id="total_saldo-edit"
                                                class="form-control form-control-lg fw-bold text-success"
                                                value="Rp0"
                                                readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-check-circle me-1"></i> Update Setoran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include "components/footer.php"; ?>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>

// Data jenis sampah dari server
const jenisSampahData = <?= json_encode($daftarJenis); ?>;

function formatRupiah(angka) {
    angka = Number(angka) || 0;
    return 'Rp' + angka.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

// Buat baris input detail sampah
function buatBarisDetail(target, idJenisTerpilih = '', beratTerpilih = '') {
    const tbody = document.getElementById('detailSetoran-' + target);
    const tr = document.createElement('tr');

    let opsi = '<option value="">-- Pilih Jenis --</option>';

    jenisSampahData.forEach(function (j) {
        const selected = String(j.id_jenis) === String(idJenisTerpilih) ? 'selected' : '';
        opsi += `<option value="${j.id_jenis}" data-harga="${j.harga_per_kg}" ${selected}>${j.nama_jenis}</option>`;
    });

    tr.innerHTML = `
        <td>
            <select name="id_jenis[]" class="form-select pilih-jenis" required>
                ${opsi}
            </select>
        </td>
        <td>
            <input type="number" name="berat[]" class="form-control input-berat text-center"
                   min="0.1" step="0.1" value="${beratTerpilih}" required>
        </td>
        <td>
            <input type="text" class="form-control input-harga text-center" value="Rp0" readonly>
        </td>
        <td>
            <input type="text" class="form-control input-subtotal text-center" value="Rp0" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-hapus-baris">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    hitungBaris(tr, target);
    return tr;
}

// Hitung subtotal baris dan total keseluruhan
function hitungBaris(tr, target) {
    const select = tr.querySelector('.pilih-jenis');
    const inputBerat = tr.querySelector('.input-berat');
    const inputHarga = tr.querySelector('.input-harga');
    const inputSubtotal = tr.querySelector('.input-subtotal');

    const opsiTerpilih = select.options[select.selectedIndex];
    const harga = opsiTerpilih ? Number(opsiTerpilih.dataset.harga || 0) : 0;
    const berat = Number(inputBerat.value || 0);

    inputHarga.value = formatRupiah(harga);
    inputSubtotal.value = formatRupiah(harga * berat);

    hitungGrandTotal(target);
}

function hitungGrandTotal(target) {
    const tbody = document.getElementById('detailSetoran-' + target);
    let total = 0;

    tbody.querySelectorAll('tr').forEach(function (tr) {
        const select = tr.querySelector('.pilih-jenis');
        const inputBerat = tr.querySelector('.input-berat');

        const opsiTerpilih = select.options[select.selectedIndex];
        const harga = opsiTerpilih ? Number(opsiTerpilih.dataset.harga || 0) : 0;
        const berat = Number(inputBerat.value || 0);

        total += harga * berat;
    });

    document.getElementById('total_saldo-' + target).value = formatRupiah(total);
}

// Validasi jika ada jenis sampah yang dipilih dua kali
function validasiDuplikatJenis(formElement) {
    const selects = formElement.querySelectorAll('.pilih-jenis');
    const nilaiTerpilih = [];

    for (let i = 0; i < selects.length; i++) {
        const val = selects[i].value;

        if (val !== '') {
            if (nilaiTerpilih.includes(val)) {
                alert('Terdapat transaksi yang menyetor jenis sampah yang sama. Silakan pilih jenis sampah yang berbeda!');
                return false;
            }
            nilaiTerpilih.push(val);
        }
    }
    return true;
}

// Event handler aksi tabel & form
document.querySelectorAll('[data-tambah-baris]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        buatBarisDetail(btn.dataset.tambahBaris);
    });
});

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('input-berat')) {
        const tr = e.target.closest('tr');
        const target = tr.closest('tbody').id.includes('edit') ? 'edit' : 'tambah';
        hitungBaris(tr, target);
    }
});

document.addEventListener('change', function (e) {
    if (e.target.classList.contains('pilih-jenis')) {
        const tr = e.target.closest('tr');
        const target = tr.closest('tbody').id.includes('edit') ? 'edit' : 'tambah';
        hitungBaris(tr, target);
    }
});

document.addEventListener('click', function (e) {
    if (e.target.closest('.btn-hapus-baris')) {
        const tr = e.target.closest('tr');
        const target = tr.closest('tbody').id.includes('edit') ? 'edit' : 'tambah';
        tr.remove();
        hitungGrandTotal(target);
    }
});

// Event submit form
document.getElementById('formTambahSetoran').addEventListener('submit', function (e) {
    if (!validasiDuplikatJenis(this)) {
        e.preventDefault();
    }
});

document.getElementById('formEditSetoran').addEventListener('submit', function (e) {
    if (!validasiDuplikatJenis(this)) {
        e.preventDefault();
    }
});

// Reset modal tambah saat dibuka/ditutup
const modalTambah = document.getElementById('modalTambahSetoran');

modalTambah.addEventListener('show.bs.modal', function () {
    document.getElementById('detailSetoran-tambah').innerHTML = '';
    buatBarisDetail('tambah');
});

modalTambah.addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
    document.getElementById('detailSetoran-tambah').innerHTML = '';
});

// Fetch data detail via AJAX untuk modal detail
document.querySelectorAll('.btn-lihat-setoran').forEach(function (btn) {
    btn.addEventListener('click', function () {
        fetch('transaksi.php?ajax=detail&id=' + btn.dataset.id)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.sukses) return;

                document.getElementById('detailNamaNasabah').textContent = data.header.nama;

                const tgl = new Date(data.header.tanggal_setoran);
                const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

                document.getElementById('detailTanggalSetoran').textContent =
                    tgl.getDate() + ' ' + bulan[tgl.getMonth()] + ' ' + tgl.getFullYear();

                const list = document.getElementById('detailSetoranList');
                list.innerHTML = '';

                data.detail.forEach(function (d) {
                    list.innerHTML += `
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <div class="fw-semibold">${d.nama_jenis}</div>
                                <small class="text-muted">${d.berat} Kg &times; ${formatRupiah(d.harga_per_kg)}</small>
                            </div>
                            <div class="fw-semibold">${formatRupiah(d.subtotal)}</div>
                        </div>
                    `;
                });

                document.getElementById('detailGrandTotal').textContent = formatRupiah(data.header.total_saldo);
            });
    });
});

// Fetch data detail via AJAX untuk modal edit
document.querySelectorAll('.btn-edit-setoran').forEach(function (btn) {
    btn.addEventListener('click', function () {
        fetch('transaksi.php?ajax=detail&id=' + btn.dataset.id)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.sukses) return;

                document.getElementById('edit_id_transaksi').value = data.header.id_transaksi;
                document.getElementById('edit_id_nasabah').value = data.header.id_nasabah;
                document.getElementById('edit_tanggal_setoran').value = data.header.tanggal_setoran;

                document.getElementById('detailSetoran-edit').innerHTML = '';

                data.detail.forEach(function (d) {
                    buatBarisDetail('edit', d.id_jenis, d.berat);
                });

                hitungGrandTotal('edit');
            });
    });
});

document.getElementById('modalEditSetoran').addEventListener('hidden.bs.modal', function () {
    this.querySelector('form').reset();
    document.getElementById('detailSetoran-edit').innerHTML = '';
});

</script>

</body>

</html>