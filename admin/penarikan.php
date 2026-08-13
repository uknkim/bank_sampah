<?php
session_start();
require_once "../config/koneksi.php";

// cek status login admin
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php?pesan=login_dulu");
    exit;
}

$id_admin_login = intval($_SESSION['id_admin']);

// ajax endpoint buat ambil sisa saldo nasabah realtime
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_saldo_nasabah') {
    header('Content-Type: application/json');
    $id_nasabah = intval($_GET['id_nasabah'] ?? 0);

    if ($id_nasabah <= 0) {
        echo json_encode(['sukses' => false, 'pesan' => 'Nasabah tidak valid', 'saldo' => 0]);
        exit;
    }

    // total setoran dari transaksi
    $sqlSetoran = "SELECT COALESCE(SUM(total_saldo), 0) AS total_setor FROM transaksi WHERE id_nasabah = ?";
    $stmtSetor = mysqli_prepare($koneksi, $sqlSetoran);
    mysqli_stmt_bind_param($stmtSetor, "i", $id_nasabah);
    mysqli_stmt_execute($stmtSetor);
    $resSetor = mysqli_stmt_get_result($stmtSetor);
    $rowSetor = mysqli_fetch_assoc($resSetor);
    $totalSetor = (float)($rowSetor['total_setor'] ?? 0);
    mysqli_stmt_close($stmtSetor);

    // total penarikan nasabah
    $sqlTarik = "SELECT COALESCE(SUM(nominal), 0) AS total_tarik FROM penarikan WHERE id_nasabah = ?";
    $stmtTarik = mysqli_prepare($koneksi, $sqlTarik);
    mysqli_stmt_bind_param($stmtTarik, "i", $id_nasabah);
    mysqli_stmt_execute($stmtTarik);
    $resTarik = mysqli_stmt_get_result($stmtTarik);
    $rowTarik = mysqli_fetch_assoc($resTarik);
    $totalTarik = (float)($rowTarik['total_tarik'] ?? 0);
    mysqli_stmt_close($stmtTarik);

    // hitung sisa saldo
    $sisaSaldo = $totalSetor - $totalTarik;

    echo json_encode([
        'sukses' => true,
        'saldo' => $sisaSaldo,
        'saldo_formatted' => 'Rp ' . number_format($sisaSaldo, 0, ',', '.')
    ]);
    exit;
}

// simpan data penarikan baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_penarikan'])) {
    $id_nasabah = intval($_POST['id_nasabah'] ?? 0);
    $tanggal_penarikan = $_POST['tanggal_penarikan'] ?? date('Y-m-d');
    
    // hapus format titik pada nominal
    $nominal_raw = str_replace('.', '', $_POST['nominal'] ?? '0');
    $nominal = floatval($nominal_raw);
    
    $keterangan = trim($_POST['keterangan'] ?? '');
    $keterangan = preg_replace('/\s+/', ' ', $keterangan);

    // validasi field wajib termasuk keterangan/catatan
    if ($id_nasabah <= 0 || $nominal <= 0 || empty($tanggal_penarikan) || empty($keterangan)) {
        header("Location: penarikan.php?pesan=ket_kosong");
        exit;
    }

    // cek sisa saldo nasabah di backend
    $sqlSetoran = "SELECT COALESCE(SUM(total_saldo), 0) AS total_setor FROM transaksi WHERE id_nasabah = ?";
    $stmtSetor = mysqli_prepare($koneksi, $sqlSetoran);
    mysqli_stmt_bind_param($stmtSetor, "i", $id_nasabah);
    mysqli_stmt_execute($stmtSetor);
    $totalSetor = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSetor))['total_setor'] ?? 0);
    mysqli_stmt_close($stmtSetor);

    $sqlTarik = "SELECT COALESCE(SUM(nominal), 0) AS total_tarik FROM penarikan WHERE id_nasabah = ?";
    $stmtTarik = mysqli_prepare($koneksi, $sqlTarik);
    mysqli_stmt_bind_param($stmtTarik, "i", $id_nasabah);
    mysqli_stmt_execute($stmtTarik);
    $totalTarik = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTarik))['total_tarik'] ?? 0);
    mysqli_stmt_close($stmtTarik);

    $sisaSaldo = $totalSetor - $totalTarik;

    if ($nominal > $sisaSaldo) {
        header("Location: penarikan.php?pesan=saldo_kurang");
        exit;
    }

    $sqlInsert = "INSERT INTO penarikan (id_nasabah, id_admin, tanggal_penarikan, nominal, keterangan) VALUES (?, ?, ?, ?, ?)";
    $stmtInsert = mysqli_prepare($koneksi, $sqlInsert);
    mysqli_stmt_bind_param($stmtInsert, "iisds", $id_nasabah, $id_admin_login, $tanggal_penarikan, $nominal, $keterangan);

    if (!mysqli_stmt_execute($stmtInsert)) {
        mysqli_stmt_close($stmtInsert);
        header("Location: penarikan.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmtInsert);
    header("Location: penarikan.php?pesan=berhasil_tambah");
    exit;
}

// hapus penarikan
if (isset($_GET['hapus'])) {
    $id_penarikan = intval($_GET['hapus']);

    if ($id_penarikan <= 0) {
        header("Location: penarikan.php?pesan=gagal");
        exit;
    }

    $sqlDelete = "DELETE FROM penarikan WHERE id_penarikan = ?";
    $stmtDelete = mysqli_prepare($koneksi, $sqlDelete);
    mysqli_stmt_bind_param($stmtDelete, "i", $id_penarikan);

    if (mysqli_stmt_execute($stmtDelete)) {
        mysqli_stmt_close($stmtDelete);
        header("Location: penarikan.php?pesan=berhasil_hapus");
        exit;
    } else {
        mysqli_stmt_close($stmtDelete);
        header("Location: penarikan.php?pesan=gagal");
        exit;
    }
}

// opsi list nasabah
$sqlListNasabah = "SELECT id_nasabah, kode_nasabah, nama FROM nasabah ORDER BY nama ASC";
$resListNasabah = mysqli_query($koneksi, $sqlListNasabah);

// search & paginasi
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($keyword !== '') {
    $sqlCount = "
        SELECT COUNT(*) AS total 
        FROM penarikan p
        JOIN nasabah n ON n.id_nasabah = p.id_nasabah
        WHERE n.kode_nasabah LIKE ? OR n.nama LIKE ? OR p.keterangan LIKE ?
    ";
    $stmtCount = mysqli_prepare($koneksi, $sqlCount);
    $searchParam = "%" . $keyword . "%";
    mysqli_stmt_bind_param($stmtCount, "sss", $searchParam, $searchParam, $searchParam);
    mysqli_stmt_execute($stmtCount);
    $rowCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount));
    $totalData = (int)($rowCount['total'] ?? 0);
    mysqli_stmt_close($stmtCount);
} else {
    $sqlCount = "SELECT COUNT(*) AS total FROM penarikan";
    $resCount = mysqli_query($koneksi, $sqlCount);
    $rowCount = mysqli_fetch_assoc($resCount);
    $totalData = (int)($rowCount['total'] ?? 0);
}

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

// query data penarikan
if ($keyword !== '') {
    $sql = "
        SELECT 
            p.id_penarikan,
            p.tanggal_penarikan,
            p.nominal,
            p.keterangan,
            p.created_at,
            n.id_nasabah,
            n.kode_nasabah,
            n.nama AS nama_nasabah,
            n.telepon,
            a.nama_admin
        FROM penarikan p
        JOIN nasabah n ON n.id_nasabah = p.id_nasabah
        LEFT JOIN admin a ON a.id_admin = p.id_admin
        WHERE n.kode_nasabah LIKE ? OR n.nama LIKE ? OR p.keterangan LIKE ?
        ORDER BY p.tanggal_penarikan DESC, p.id_penarikan DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($koneksi, $sql);
    $searchParam = "%" . $keyword . "%";
    mysqli_stmt_bind_param($stmt, "sssii", $searchParam, $searchParam, $searchParam, $batas, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "
        SELECT 
            p.id_penarikan,
            p.tanggal_penarikan,
            p.nominal,
            p.keterangan,
            p.created_at,
            n.id_nasabah,
            n.kode_nasabah,
            n.nama AS nama_nasabah,
            n.telepon,
            a.nama_admin
        FROM penarikan p
        JOIN nasabah n ON n.id_nasabah = p.id_nasabah
        LEFT JOIN admin a ON a.id_admin = p.id_admin
        ORDER BY p.tanggal_penarikan DESC, p.id_penarikan DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $batas, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

$bulanIndonesia = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penarikan Saldo Nasabah | Bank Sampah Metro 46</title>

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

            <!-- header -->
            <div class="page-header">
                <div>
                    <h3>Penarikan Saldo Nasabah</h3>
                    <p>Kelola pencairan tabungan saldo uang tunai nasabah Bank Sampah Metro 46.</p>
                </div>

                <button
                    type="button"
                    class="btn btn-add"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTambahPenarikan">
                    <i class="bi bi-cash-stack"></i>
                    Tarik Saldo Uang
                </button>
            </div>

            <!-- alert notifikasi -->
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
                        $alertText  = 'Transaksi penarikan saldo berhasil diproses.';
                        break;

                    case 'berhasil_hapus':
                        $alertClass = 'success';
                        $alertIcon  = 'check-circle-fill';
                        $alertText  = 'Riwayat transaksi penarikan berhasil dihapus.';
                        break;

                    case 'saldo_kurang':
                        $alertClass = 'danger';
                        $alertIcon  = 'exclamation-octagon-fill';
                        $alertText  = 'Penarikan GAGAL! Nominal penarikan melebihi sisa saldo tabungan nasabah.';
                        break;

                    case 'ket_kosong':
                        $alertClass = 'warning';
                        $alertIcon  = 'exclamation-triangle-fill';
                        $alertText  = 'Penarikan GAGAL! Harap isi keterangan/catatan penarikan terlebih dahulu.';
                        break;

                    default:
                        $alertClass = 'danger';
                        $alertIcon  = 'x-circle-fill';
                        $alertText  = 'Proses gagal dilakukan. Silakan coba kembali.';
                        break;
                }
                ?>

                <div class="alert alert-<?= $alertClass; ?> alert-dismissible fade show">
                    <i class="bi bi-<?= $alertIcon; ?> me-2"></i>
                    <?= $alertText; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- card tabel data -->
            <div class="card table-card">

                <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h5 class="mb-0 fw-bold text-secondary fs-6">
                        <i class="bi bi-clock-history me-2 text-success"></i>Riwayat Transaksi Penarikan Saldo
                    </h5>

                    <!-- pencarian -->
                    <form method="GET" action="penarikan.php" class="d-flex" style="max-width: 320px; width: 100%;">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm border-success rounded-start-3" placeholder="Cari kode, nama, ket..." value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                            <button type="submit" class="btn btn-success btn-sm rounded-end-3 px-3">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($keyword)): ?>
                                <a href="penarikan.php" class="btn btn-outline-secondary btn-sm ms-1 rounded-3" title="Reset Pencarian">
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
                                    <th width="150">Tanggal Tarik</th>
                                    <th width="130">Kode Nasabah</th>
                                    <th>Nama Nasabah</th>
                                    <th class="text-end" width="160">Nominal Tarik</th>
                                    <th>Keterangan</th>
                                    <th width="120" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0) : ?>
                                    <?php
                                    $no = $offset + 1;
                                    while ($row = mysqli_fetch_assoc($result)) :
                                        $tgl = date_create($row['tanggal_penarikan']);
                                        $tglIndonesia = date_format($tgl, 'd') . ' ' . $bulanIndonesia[(int)date_format($tgl, 'n')] . ' ' . date_format($tgl, 'Y');
                                    ?>
                                        <tr>
                                            <td class="text-center fw-bold text-secondary"><?= $no++; ?></td>
                                            <td><?= $tglIndonesia; ?></td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success border border-success fs-6 fw-bold">
                                                    <?= htmlspecialchars($row['kode_nasabah'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_nasabah'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-end fw-bold text-danger">
                                                - Rp <?= number_format($row['nominal'], 0, ',', '.'); ?>
                                            </td>
                                            <td><?= htmlspecialchars(!empty($row['keterangan']) ? $row['keterangan'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-info btn-sm text-white btn-detail-penarikan"
                                                    title="Detail Penarikan"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalDetailPenarikan"
                                                    data-kode="<?= htmlspecialchars($row['kode_nasabah'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama_nasabah'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-telepon="<?= htmlspecialchars($row['telepon'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-tanggal="<?= $tglIndonesia; ?>"
                                                    data-nominal="Rp <?= number_format($row['nominal'], 0, ',', '.'); ?>"
                                                    data-keterangan="<?= htmlspecialchars(!empty($row['keterangan']) ? $row['keterangan'] : '-', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-admin="<?= htmlspecialchars($row['nama_admin'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                                <a
                                                    href="penarikan.php?hapus=<?= $row['id_penarikan']; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    title="Hapus Data"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus data penarikan ini?')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <?= (!empty($keyword)) ? 'Data penarikan dengan kata kunci "' . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . '" tidak ditemukan.' : 'Belum ada riwayat transaksi penarikan saldo.'; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- paginasi -->
                <?php if ($totalData > 0): ?>
                <div class="card-footer bg-white py-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <div class="text-muted small">
                        Menampilkan <strong><?= $mulaiData; ?>–<?= $sampaiData; ?></strong> dari <strong><?= $totalData; ?></strong> data
                    </div>

                    <?php if ($totalHalaman > 1): ?>
                    <nav aria-label="Navigasi Halaman Data Penarikan">
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

            <!-- modal tambah penarikan saldo -->
            <div class="modal fade" id="modalTambahPenarikan" tabindex="-1" aria-labelledby="modalTambahPenarikanLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTambahPenarikanLabel">
                                <i class="bi bi-cash-stack me-2"></i>Form Penarikan Saldo Uang Nasabah
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" autocomplete="off" id="formPenarikan">
                            <input type="hidden" name="tambah_penarikan" value="1">

                            <div class="modal-body">

                                <!-- alert penanda jika keterangan belum diisi -->
                                <div id="alert_keterangan_kosong" class="alert alert-warning d-none mb-3">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Isi keterangan terlebih dahulu!
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Pilih Nasabah</label>
                                        <select class="form-select" name="id_nasabah" id="select_id_nasabah" required>
                                            <option value="">-- Pilih Nasabah --</option>
                                            <?php while ($n = mysqli_fetch_assoc($resListNasabah)) : ?>
                                                <option value="<?= $n['id_nasabah']; ?>">
                                                    [<?= htmlspecialchars($n['kode_nasabah'], ENT_QUOTES, 'UTF-8'); ?>] <?= htmlspecialchars($n['nama'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tanggal Penarikan</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            name="tanggal_penarikan"
                                            value="<?= date('Y-m-d'); ?>"
                                            required>
                                    </div>
                                </div>

                                <div class="alert alert-success d-flex justify-content-between align-items-center py-2 mb-3">
                                    <div>
                                        <i class="bi bi-wallet2 me-2 fs-5"></i>
                                        <strong>Sisa Saldo Tabungan Tersedia:</strong>
                                    </div>
                                    <span id="display_sisa_saldo" class="fw-bold fs-5 text-success">Rp 0</span>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nominal Penarikan (Rp)</label>
                                    <input
                                        type="text"
                                        class="form-control form-control-lg"
                                        name="nominal"
                                        id="input_nominal"
                                        placeholder="Masukkan nominal uang yang ditarik..."
                                        required>
                                    <small id="text_peringatan" class="text-danger d-none">Nominal penarikan melebihi saldo tabungan nasabah!</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Keterangan / Catatan</label>
                                    <textarea
                                        class="form-control"
                                        name="keterangan"
                                        id="input_keterangan"
                                        rows="3"
                                        maxlength="255"
                                        placeholder="Misal: Penarikan tunai keperluan mendesak, pencairan tabungan lebaran, dll..."
                                        required></textarea>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success" id="btnSimpanPenarikan">
                                    <i class="bi bi-check-circle me-1"></i>Proses Penarikan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- modal detail penarikan saldo -->
            <div class="modal fade" id="modalDetailPenarikan" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">
                                <i class="bi bi-receipt me-2"></i>Rincian Penarikan Saldo
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-sm table-borderless mb-0 fs-6">
                                <tr>
                                    <td class="text-muted" width="140">Kode Nasabah</td>
                                    <td>: <span id="detail_kode" class="badge bg-success-subtle text-success border border-success fs-6 fw-bold">-</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama Nasabah</td>
                                    <td>: <strong id="detail_nama" class="text-dark">-</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">No. Telepon</td>
                                    <td>: <span id="detail_telepon">-</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tgl Penarikan</td>
                                    <td>: <span id="detail_tanggal">-</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Petugas Admin</td>
                                    <td>: <span id="detail_admin">-</span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Keterangan</td>
                                    <td>: <span id="detail_keterangan">-</span></td>
                                </tr>
                            </table>

                            <hr class="my-3">

                            <div class="card bg-danger-subtle border-danger border-dashed">
                                <div class="card-body text-center py-2">
                                    <div class="text-danger small fw-semibold">TOTAL DITARIK</div>
                                    <h3 id="detail_nominal" class="fw-bold text-danger my-1">Rp 0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <?php include "components/footer.php"; ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let currentSisaSaldo = 0;

    const selectNasabah = document.getElementById('select_id_nasabah');
    const displaySaldo = document.getElementById('display_sisa_saldo');
    const inputNominal = document.getElementById('input_nominal');
    const inputKeterangan = document.getElementById('input_keterangan');
    const alertKetKosong = document.getElementById('alert_keterangan_kosong');
    const btnSimpan = document.getElementById('btnSimpanPenarikan');
    const textPeringatan = document.getElementById('text_peringatan');
    const formPenarikan = document.getElementById('formPenarikan');

    // format angka pemisah titik
    function formatRupiahInput(angka) {
        let number_string = angka.replace(/[^,\d]/g, '').toString();
        let split = number_string.split(',');
        let sisa  = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        return split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
    }

    if (selectNasabah) {
        selectNasabah.addEventListener('change', function () {
            const idNasabah = this.value;
            if (!idNasabah) {
                displaySaldo.textContent = 'Rp 0';
                currentSisaSaldo = 0;
                return;
            }

            displaySaldo.textContent = 'Memuat saldo...';

            fetch(`penarikan.php?ajax=get_saldo_nasabah&id_nasabah=${idNasabah}`)
                .then(res => res.json())
                .then(data => {
                    if (data.sukses) {
                        currentSisaSaldo = data.saldo;
                        displaySaldo.textContent = data.saldo_formatted;
                    } else {
                        currentSisaSaldo = 0;
                        displaySaldo.textContent = 'Rp 0';
                    }
                    cekValidasiSaldo();
                })
                .catch(() => {
                    displaySaldo.textContent = 'Gagal memuat';
                    currentSisaSaldo = 0;
                });
        });
    }

    if (inputNominal) {
        inputNominal.addEventListener('input', function(e) {
            this.value = formatRupiahInput(this.value);
            cekValidasiSaldo();
        });
    }

    // sembunyikan alert pesan ketika pengguna mengetik di keterangan
    if (inputKeterangan) {
        inputKeterangan.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                alertKetKosong.classList.add('d-none');
            }
        });
    }

    // validasi saat tombol submit ditekan
    if (formPenarikan) {
        formPenarikan.addEventListener('submit', function(e) {
            if (inputKeterangan && inputKeterangan.value.trim() === '') {
                e.preventDefault();
                alertKetKosong.classList.remove('d-none');
                inputKeterangan.focus();
                return false;
            }

            if (inputNominal) {
                inputNominal.value = inputNominal.value.replace(/\./g, '');
            }
        });
    }

    function cekValidasiSaldo() {
        const nominalClean = inputNominal.value.replace(/\./g, '');
        const valNominal = parseFloat(nominalClean || 0);

        if (valNominal > currentSisaSaldo && currentSisaSaldo >= 0) {
            textPeringatan.classList.remove('d-none');
            btnSimpan.disabled = true;
        } else {
            textPeringatan.classList.add('d-none');
            btnSimpan.disabled = false;
        }
    }

    const modalDetail = document.getElementById('modalDetailPenarikan');
    if (modalDetail) {
        modalDetail.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('detail_kode').textContent = button.getAttribute('data-kode');
            document.getElementById('detail_nama').textContent = button.getAttribute('data-nama');
            document.getElementById('detail_telepon').textContent = button.getAttribute('data-telepon');
            document.getElementById('detail_tanggal').textContent = button.getAttribute('data-tanggal');
            document.getElementById('detail_nominal').textContent = button.getAttribute('data-nominal');
            document.getElementById('detail_keterangan').textContent = button.getAttribute('data-keterangan');
            document.getElementById('detail_admin').textContent = button.getAttribute('data-admin');
        });
    }

    const modalTambah = document.getElementById('modalTambahPenarikan');
    if (modalTambah) {
        modalTambah.addEventListener('hidden.bs.modal', function () {
            const form = this.querySelector('form');
            if (form) form.reset();
            displaySaldo.textContent = 'Rp 0';
            currentSisaSaldo = 0;
            textPeringatan.classList.add('d-none');
            alertKetKosong.classList.add('d-none');
            btnSimpan.disabled = false;
        });
    }
</script>

</body>
</html>