<?php
session_start();
require_once "../config/koneksi.php";

// =====================================================
// CEK LOGIN
// =====================================================
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php?pesan=login_dulu");
    exit;
}

$nama_admin = $_SESSION['nama_admin'];

// =====================================================
// GENERATE KODE NASABAH OTOMATIS (Format: BS001, BS002, dst.)
// =====================================================
$sqlMaxKode = "SELECT kode_nasabah FROM nasabah WHERE kode_nasabah LIKE 'BS%' ORDER BY id_nasabah DESC LIMIT 1";
$resMaxKode = mysqli_query($koneksi, $sqlMaxKode);
$nextKodeNasabah = "BS001";

if ($resMaxKode && mysqli_num_rows($resMaxKode) > 0) {
    $rowMax = mysqli_fetch_assoc($resMaxKode);
    $lastKode = $rowMax['kode_nasabah'];
    // Ambil angka dari kode terakhir (misal BS002 -> 2)
    $num = (int) filter_var($lastKode, FILTER_SANITIZE_NUMBER_INT);
    $nextKodeNasabah = 'BS' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
}

// =====================================================
// PROSES TAMBAH
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_nasabah'])) {
    $kode_nasabah = trim($_POST['kode_nasabah'] ?? '');
    if (empty($kode_nasabah)) {
        $kode_nasabah = $nextKodeNasabah;
    }

    $nama = trim($_POST['nama'] ?? '');
    $nama = preg_replace('/\s+/', ' ', $nama);

    $alamat = trim($_POST['alamat'] ?? '');
    $alamat = preg_replace('/\s+/', ' ', $alamat);

    $telepon = trim($_POST['telepon'] ?? '');
    $telepon = preg_replace('/\s+/', ' ', $telepon);

    $tanggal_bergabung = $_POST['tanggal_bergabung'] ?? '';

    if ($nama === '' || $alamat === '' || $telepon === '' || $tanggal_bergabung === '') {
        header("Location: nasabah.php?pesan=gagal");
        exit;
    }

    if (strlen($telepon) < 10) {
        header("Location: nasabah.php?pesan=gagal");
        exit;
    }

    $sqlInsert = "
        INSERT INTO nasabah
        (
            kode_nasabah,
            nama,
            alamat,
            telepon,
            tanggal_bergabung
        )
        VALUES (?,?,?,?,?)
    ";

    $stmtInsert = mysqli_prepare($koneksi, $sqlInsert);
    mysqli_stmt_bind_param(
        $stmtInsert,
        "sssss",
        $kode_nasabah,
        $nama,
        $alamat,
        $telepon,
        $tanggal_bergabung
    );

    if (!mysqli_stmt_execute($stmtInsert)) {
        mysqli_stmt_close($stmtInsert);
        header("Location: nasabah.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmtInsert);
    header("Location: nasabah.php?pesan=berhasil_tambah");
    exit;
}

// =====================================================
// PROSES EDIT
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_nasabah'])) {
    $id_nasabah = intval($_POST['id_nasabah'] ?? 0);

    $nama = trim($_POST['nama'] ?? '');
    $nama = preg_replace('/\s+/', ' ', $nama);

    $alamat = trim($_POST['alamat'] ?? '');
    $alamat = preg_replace('/\s+/', ' ', $alamat);

    $telepon = trim($_POST['telepon'] ?? '');
    $telepon = preg_replace('/\s+/', ' ', $telepon);

    $tanggal_bergabung = $_POST['tanggal_bergabung'] ?? '';

    if ($id_nasabah <= 0 || $nama === '' || $alamat === '' || $telepon === '' || $tanggal_bergabung === '') {
        header("Location: nasabah.php?pesan=gagal");
        exit;
    }

    if (strlen($telepon) < 10) {
        header("Location: nasabah.php?pesan=gagal");
        exit;
    }

    $sqlUpdate = "
        UPDATE nasabah
        SET
            nama = ?,
            alamat = ?,
            telepon = ?,
            tanggal_bergabung = ?
        WHERE id_nasabah = ?
    ";

    $stmtUpdate = mysqli_prepare($koneksi, $sqlUpdate);
    mysqli_stmt_bind_param(
        $stmtUpdate,
        "ssssi",
        $nama,
        $alamat,
        $telepon,
        $tanggal_bergabung,
        $id_nasabah
    );

    if (!mysqli_stmt_execute($stmtUpdate)) {
        mysqli_stmt_close($stmtUpdate);
        header("Location: nasabah.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmtUpdate);
    header("Location: nasabah.php?pesan=berhasil_edit");
    exit;
}

// =====================================================
// PROSES HAPUS
// =====================================================
if (isset($_GET['hapus'])) {
    $id_nasabah = intval($_GET['hapus']);

    if ($id_nasabah <= 0) {
        header("Location: nasabah.php?pesan=gagal");
        exit;
    }

    // 1. Ambil data nama nasabah terlebih dahulu
    $sqlGetNama = "SELECT nama FROM nasabah WHERE id_nasabah = ?";
    $stmtNama   = mysqli_prepare($koneksi, $sqlGetNama);
    mysqli_stmt_bind_param($stmtNama, "i", $id_nasabah);
    mysqli_stmt_execute($stmtNama);
    $resNama = mysqli_stmt_get_result($stmtNama);
    $dataNama = mysqli_fetch_assoc($resNama);
    mysqli_stmt_close($stmtNama);

    $nama_nasabah = $dataNama['nama'] ?? '';

    // 2. Cek apakah nasabah sudah memiliki transaksi di tabel 'transaksi'
    $sqlCekTf = "SELECT id_transaksi FROM transaksi WHERE id_nasabah = ? LIMIT 1";
    $stmtCek  = mysqli_prepare($koneksi, $sqlCekTf);
    mysqli_stmt_bind_param($stmtCek, "i", $id_nasabah);
    mysqli_stmt_execute($stmtCek);
    mysqli_stmt_store_result($stmtCek);

    if (mysqli_stmt_num_rows($stmtCek) > 0) {
        mysqli_stmt_close($stmtCek);
        header("Location: nasabah.php?pesan=dipakai_transaksi&nama=" . urlencode($nama_nasabah));
        exit;
    }
    mysqli_stmt_close($stmtCek);

    // 3. Jika tidak ada transaksi, jalankan query hapus
    $sqlDelete = "DELETE FROM nasabah WHERE id_nasabah = ?";
    $stmtDelete = mysqli_prepare($koneksi, $sqlDelete);
    mysqli_stmt_bind_param($stmtDelete, "i", $id_nasabah);

    try {
        if (!mysqli_stmt_execute($stmtDelete)) {
            mysqli_stmt_close($stmtDelete);
            header("Location: nasabah.php?pesan=gagal");
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        mysqli_stmt_close($stmtDelete);
        header("Location: nasabah.php?pesan=dipakai_transaksi&nama=" . urlencode($nama_nasabah));
        exit;
    }

    mysqli_stmt_close($stmtDelete);
    header("Location: nasabah.php?pesan=berhasil_hapus");
    exit;
}

// =====================================================
// AMBIL DATA NASABAH (DENGAN SEARCH & PAGINATION)
// =====================================================
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

// 1. Hitung Total Data untuk Paginasi
if ($keyword !== '') {
    $sqlCount = "SELECT COUNT(*) AS total FROM nasabah WHERE kode_nasabah LIKE ? OR nama LIKE ? OR alamat LIKE ? OR telepon LIKE ?";
    $stmtCount = mysqli_prepare($koneksi, $sqlCount);
    $searchParam = "%" . $keyword . "%";
    mysqli_stmt_bind_param($stmtCount, "ssss", $searchParam, $searchParam, $searchParam, $searchParam);
    mysqli_stmt_execute($stmtCount);
    $resCount = mysqli_stmt_get_result($stmtCount);
    $rowCount = mysqli_fetch_assoc($resCount);
    $totalData = (int)($rowCount['total'] ?? 0);
    mysqli_stmt_close($stmtCount);
} else {
    $sqlCount = "SELECT COUNT(*) AS total FROM nasabah";
    $resCount = mysqli_query($koneksi, $sqlCount);
    $rowCount = mysqli_fetch_assoc($resCount);
    $totalData = (int)($rowCount['total'] ?? 0);
}

// 2. Variabel & Konfigurasi Paginasi
$batas = 10;
$halamanAktif = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
if ($halamanAktif < 1) $halamanAktif = 1;

$totalHalaman = ceil($totalData / $batas);
if ($totalHalaman < 1) $totalHalaman = 1;

if ($halamanAktif > $totalHalaman) {
    $halamanAktif = $totalHalaman;
}

$offset = ($halamanAktif - 1) * $batas;

$mulaiData = ($totalData > 0) ? $offset + 1 : 0;
$sampaiData = min($offset + $batas, $totalData);

// 3. Query Data Utama dengan Limit dan Offset
if ($keyword !== '') {
    $sql = "
        SELECT
            id_nasabah,
            kode_nasabah,
            nama,
            alamat,
            telepon,
            tanggal_bergabung
        FROM nasabah
        WHERE kode_nasabah LIKE ? OR nama LIKE ? OR alamat LIKE ? OR telepon LIKE ?
        ORDER BY
            tanggal_bergabung DESC,
            nama ASC
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($koneksi, $sql);
    $searchParam = "%" . $keyword . "%";
    mysqli_stmt_bind_param($stmt, "ssssii", $searchParam, $searchParam, $searchParam, $searchParam, $batas, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "
        SELECT
            id_nasabah,
            kode_nasabah,
            nama,
            alamat,
            telepon,
            tanggal_bergabung
        FROM nasabah
        ORDER BY
            tanggal_bergabung DESC,
            nama ASC
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $batas, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

if (!$result) {
    header("Location: dashboard.php?pesan=gagal");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Nasabah | Bank Sampah Metro 46</title>

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

            <!-- Header -->
            <div class="page-header">
                <div>
                    <h3>Data Nasabah</h3>
                    <p>Daftar seluruh data nasabah Bank Sampah Metro 46.</p>
                </div>

                <button
                    type="button"
                    class="btn btn-add"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTambahNasabah">
                    <i class="bi bi-plus-circle"></i>
                    Tambah Nasabah
                </button>
            </div>

            <!-- Alert -->
            <?php if (isset($_GET['pesan'])) : ?>
                <?php
                $pesan = $_GET['pesan'];
                $alertClass = '';
                $alertIcon  = '';
                $alertText  = '';

                switch ($pesan) {
                    case 'berhasil_tambah':
                        $alertClass = 'success';
                        $alertIcon  = 'check-circle-fill';
                        $alertText  = 'Data nasabah berhasil ditambahkan.';
                        break;

                    case 'berhasil_edit':
                        $alertClass = 'success';
                        $alertIcon  = 'check-circle-fill';
                        $alertText  = 'Data nasabah berhasil diperbarui.';
                        break;

                    case 'berhasil_hapus':
                        $alertClass = 'success';
                        $alertIcon  = 'check-circle-fill';
                        $alertText  = 'Data nasabah berhasil dihapus.';
                        break;

                    case 'dipakai_transaksi':
                        $namaParam  = isset($_GET['nama']) ? htmlspecialchars($_GET['nama'], ENT_QUOTES, 'UTF-8') : '';
                        $textNama   = $namaParam !== '' ? ' <strong>"' . $namaParam . '"</strong>' : '';

                        $alertClass = 'warning';
                        $alertIcon  = 'exclamation-triangle-fill';
                        $alertText  = 'Nasabah' . $textNama . ' gagal dihapus karena sudah memiliki data transaksi!';
                        break;

                    default:
                        $alertClass = 'danger';
                        $alertIcon  = 'x-circle-fill';
                        $alertText  = 'Proses gagal dilakukan.';
                        break;
                }
                ?>

                <div class="alert alert-<?= $alertClass; ?> alert-dismissible fade show">
                    <i class="bi bi-<?= $alertIcon; ?> me-2"></i>
                    <?= $alertText; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Card Utama Tabel Nasabah -->
            <div class="card table-card">

                <!-- Header Card dengan Input Pencarian -->
                <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h5 class="mb-0 fw-bold text-secondary fs-6">
                        <i class="bi bi-list-ul me-2 text-success"></i>Daftar Nasabah Terdaftar
                    </h5>

                    <!-- Form Pencarian Nasabah -->
                    <form method="GET" action="nasabah.php" class="d-flex" style="max-width: 320px; width: 100%;">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm border-success rounded-start-3" placeholder="Cari kode, nama, hp, alamat..." value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                            <button type="submit" class="btn btn-success btn-sm rounded-end-3 px-3">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($keyword)): ?>
                                <a href="nasabah.php" class="btn btn-outline-secondary btn-sm ms-1 rounded-3" title="Reset Pencarian">
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
                                    <th width="60" class="text-center">No</th>
                                    <th width="130">Kode Nasabah</th>
                                    <th>Nama Nasabah</th>
                                    <th>Alamat</th>
                                    <th>No. HP</th>
                                    <th width="170">Tanggal Bergabung</th>
                                    <th width="180" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $bulanIndonesia = [
                                    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                                ];

                                if (mysqli_num_rows($result) > 0) :
                                    $no = $offset + 1;
                                    while ($row = mysqli_fetch_assoc($result)) :
                                        $tanggal = date_create($row['tanggal_bergabung']);
                                        $tanggalIndonesia =
                                            date_format($tanggal, 'd') . ' ' .
                                            $bulanIndonesia[(int) date_format($tanggal, 'n')] . ' ' .
                                            date_format($tanggal, 'Y');
                                ?>
                                        <tr>
                                            <td class="text-center fw-bold text-secondary"><?= $no++; ?></td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success border border-success fs-6 fw-bold">
                                                    <?= htmlspecialchars($row['kode_nasabah'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($row['alamat'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($row['telepon'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= $tanggalIndonesia; ?></td>
                                            <td class="text-center">
                                                <a
                                                    href="detail_monitoring.php?id=<?= $row['id_nasabah']; ?>"
                                                    class="btn btn-info btn-sm text-white"
                                                    title="Detail Monitoring">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                                <button
                                                    type="button"
                                                    class="btn btn-warning btn-sm"
                                                    title="Edit Data"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditNasabah"
                                                    data-id="<?= $row['id_nasabah']; ?>"
                                                    data-kode="<?= htmlspecialchars($row['kode_nasabah'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-alamat="<?= htmlspecialchars($row['alamat'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-telepon="<?= htmlspecialchars($row['telepon'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-tanggal="<?= $row['tanggal_bergabung']; ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a
                                                    href="nasabah.php?hapus=<?= $row['id_nasabah']; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    title="Hapus Data"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus data nasabah ini?')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <?= (!empty($keyword)) ? 'Data nasabah dengan kata kunci "' . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . '" tidak ditemukan.' : 'Belum ada data nasabah.'; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Card: Keterangan Jumlah Data & Paginasi Navigasi -->
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

            <!-- =====================================================
                 MODAL TAMBAH NASABAH
            ====================================================== -->
            <div class="modal fade" id="modalTambahNasabah" tabindex="-1" aria-labelledby="modalTambahNasabahLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTambahNasabahLabel">
                                <i class="bi bi-person-plus-fill me-2"></i>Tambah Data Nasabah
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" autocomplete="off" novalidate>
                            <input type="hidden" name="tambah_nasabah" value="1">

                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kode Nasabah</label>
                                        <input
                                            type="text"
                                            class="form-control bg-light fw-bold"
                                            name="kode_nasabah"
                                            id="kode_nasabah"
                                            value="<?= $nextKodeNasabah; ?>"
                                            readonly>
                                        <small class="text-muted">Kode digenerate otomatis oleh sistem.</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Nasabah</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="nama"
                                            id="nama"
                                            maxlength="100"
                                            placeholder="Masukkan nama nasabah"
                                            autofocus
                                            required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">No. HP</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="telepon"
                                            id="telepon"
                                            maxlength="20"
                                            placeholder="08xxxxxxxxxx"
                                            required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Bergabung</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            name="tanggal_bergabung"
                                            id="tanggal_bergabung"
                                            value="<?= date('Y-m-d'); ?>"
                                            required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea
                                        class="form-control"
                                        name="alamat"
                                        id="alamat"
                                        rows="3"
                                        maxlength="255"
                                        placeholder="Masukkan alamat lengkap"
                                        required></textarea>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-save me-1"></i>Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- =====================================================
                 MODAL EDIT NASABAH
            ====================================================== -->
            <div class="modal fade" id="modalEditNasabah" tabindex="-1" aria-labelledby="modalEditNasabahLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalEditNasabahLabel">
                                <i class="bi bi-pencil-square me-2"></i>Edit Data Nasabah
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" autocomplete="off" novalidate>
                            <input type="hidden" name="edit_nasabah" value="1">
                            <input type="hidden" name="id_nasabah" id="edit_id_nasabah">

                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kode Nasabah</label>
                                        <input
                                            type="text"
                                            class="form-control bg-light fw-bold"
                                            id="edit_kode_nasabah"
                                            readonly>
                                        <small class="text-muted">Kode nasabah tidak dapat diubah.</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Nasabah</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="nama"
                                            id="edit_nama"
                                            maxlength="100"
                                            required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">No. HP</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="telepon"
                                            id="edit_telepon"
                                            maxlength="20"
                                            required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Bergabung</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            name="tanggal_bergabung"
                                            id="edit_tanggal_bergabung"
                                            required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Alamat</label>
                                    <textarea
                                        class="form-control"
                                        name="alamat"
                                        id="edit_alamat"
                                        rows="3"
                                        maxlength="255"
                                        required></textarea>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-check-circle me-1"></i>Update
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </section>

        <?php include "components/footer.php"; ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // =====================================================
    // MODAL EDIT NASABAH
    // =====================================================
    const modalEdit = document.getElementById('modalEditNasabah');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('edit_id_nasabah').value = button.getAttribute('data-id');
            document.getElementById('edit_kode_nasabah').value = button.getAttribute('data-kode');
            document.getElementById('edit_nama').value = button.getAttribute('data-nama');
            document.getElementById('edit_alamat').value = button.getAttribute('data-alamat');
            document.getElementById('edit_telepon').value = button.getAttribute('data-telepon');
            document.getElementById('edit_tanggal_bergabung').value = button.getAttribute('data-tanggal');
        });

        modalEdit.addEventListener('hidden.bs.modal', function () {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
            }
            document.getElementById('edit_id_nasabah').value = '';
            document.getElementById('edit_kode_nasabah').value = '';
        });
    }

    // =====================================================
    // MODAL TAMBAH NASABAH
    // =====================================================
    const modalTambah = document.getElementById('modalTambahNasabah');
    if (modalTambah) {
        modalTambah.addEventListener('hidden.bs.modal', function () {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
            }
        });
    }
</script>

</body>
</html>