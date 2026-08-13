<?php

session_start();

require_once "../config/koneksi.php";

// Cek otentikasi admin
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php?pesan=login_dulu");
    exit;
}

$nama_admin = $_SESSION['nama_admin'];

// Proses tambah jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_jadwal'])) {
    $judul     = preg_replace('/\s+/', ' ', trim($_POST['judul'] ?? ''));
    $tanggal   = $_POST['tanggal'] ?? '';
    $waktu     = $_POST['waktu'] ?? '';
    $lokasi    = preg_replace('/\s+/', ' ', trim($_POST['lokasi'] ?? ''));
    $deskripsi = preg_replace('/\s+/', ' ', trim($_POST['deskripsi'] ?? ''));

    if ($judul === '' || $tanggal === '' || $waktu === '' || $lokasi === '' || $deskripsi === '') {
        header("Location: jadwal.php?pesan=gagal");
        exit;
    }

    // Cek duplikasi tanggal dan waktu kegiatan
    $sqlCek = "SELECT id_jadwal FROM jadwal WHERE tanggal = ? AND waktu = ?";
    $stmtCek = mysqli_prepare($koneksi, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "ss", $tanggal, $waktu);
    mysqli_stmt_execute($stmtCek);
    mysqli_stmt_store_result($stmtCek);

    if (mysqli_stmt_num_rows($stmtCek) > 0) {
        mysqli_stmt_close($stmtCek);
        header("Location: jadwal.php?pesan=duplikat");
        exit;
    }
    mysqli_stmt_close($stmtCek);

    // Insert data jadwal baru
    $sqlInsert = "INSERT INTO jadwal (judul, tanggal, waktu, lokasi, deskripsi) VALUES (?, ?, ?, ?, ?)";
    $stmtInsert = mysqli_prepare($koneksi, $sqlInsert);
    mysqli_stmt_bind_param($stmtInsert, "sssss", $judul, $tanggal, $waktu, $lokasi, $deskripsi);

    if (!mysqli_stmt_execute($stmtInsert)) {
        mysqli_stmt_close($stmtInsert);
        header("Location: jadwal.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmtInsert);
    header("Location: jadwal.php?pesan=berhasil_tambah");
    exit;
}

// Proses edit jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_jadwal'])) {
    $id_jadwal = intval($_POST['id_jadwal'] ?? 0);
    $judul     = preg_replace('/\s+/', ' ', trim($_POST['judul'] ?? ''));
    $tanggal   = $_POST['tanggal'] ?? '';
    $waktu     = $_POST['waktu'] ?? '';
    $lokasi    = preg_replace('/\s+/', ' ', trim($_POST['lokasi'] ?? ''));
    $deskripsi = preg_replace('/\s+/', ' ', trim($_POST['deskripsi'] ?? ''));

    if ($id_jadwal <= 0 || $judul === '' || $tanggal === '' || $waktu === '' || $lokasi === '' || $deskripsi === '') {
        header("Location: jadwal.php?pesan=gagal");
        exit;
    }

    // Cek duplikasi tanggal dan waktu (mengabaikan ID jadwal yang sedang diubah)
    $sqlCek = "SELECT id_jadwal FROM jadwal WHERE tanggal = ? AND waktu = ? AND id_jadwal != ?";
    $stmtCek = mysqli_prepare($koneksi, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "ssi", $tanggal, $waktu, $id_jadwal);
    mysqli_stmt_execute($stmtCek);
    mysqli_stmt_store_result($stmtCek);

    if (mysqli_stmt_num_rows($stmtCek) > 0) {
        mysqli_stmt_close($stmtCek);
        header("Location: jadwal.php?pesan=duplikat");
        exit;
    }
    mysqli_stmt_close($stmtCek);

    // Update data jadwal
    $sqlUpdate = "UPDATE jadwal SET judul = ?, tanggal = ?, waktu = ?, lokasi = ?, deskripsi = ? WHERE id_jadwal = ?";
    $stmtUpdate = mysqli_prepare($koneksi, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "sssssi", $judul, $tanggal, $waktu, $lokasi, $deskripsi, $id_jadwal);

    if (!mysqli_stmt_execute($stmtUpdate)) {
        mysqli_stmt_close($stmtUpdate);
        header("Location: jadwal.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmtUpdate);
    header("Location: jadwal.php?pesan=berhasil_edit");
    exit;
}

// Proses hapus jadwal
if (isset($_GET['hapus'])) {
    $id_jadwal = intval($_GET['hapus']);

    if ($id_jadwal <= 0) {
        header("Location: jadwal.php?pesan=gagal");
        exit;
    }

    $sqlDelete = "DELETE FROM jadwal WHERE id_jadwal = ?";
    $stmtDelete = mysqli_prepare($koneksi, $sqlDelete);
    mysqli_stmt_bind_param($stmtDelete, "i", $id_jadwal);

    if (!mysqli_stmt_execute($stmtDelete)) {
        mysqli_stmt_close($stmtDelete);
        header("Location: jadwal.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmtDelete);
    header("Location: jadwal.php?pesan=berhasil_hapus");
    exit;
}

// Pencarian dan paginasi data
$keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$keywordClean = str_replace(' ', '', $keyword);

// Format tanggal Indonesia tanpa spasi (misal: "11Agustus2026")
$sqlTglIndoNoSpace = "
    CONCAT(
        DATE_FORMAT(tanggal, '%e'),
        ELT(MONTH(tanggal), 
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ),
        DATE_FORMAT(tanggal, '%Y')
    )
";

if ($keyword !== '') {
    $sqlCount = "
        SELECT COUNT(*) AS total 
        FROM jadwal 
        WHERE REPLACE(judul, ' ', '') LIKE ? 
           OR REPLACE(tanggal, ' ', '') LIKE ?
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
    $sqlCount = "SELECT COUNT(*) AS total FROM jadwal";
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
        SELECT id_jadwal, judul, tanggal, waktu, lokasi, deskripsi 
        FROM jadwal 
        WHERE REPLACE(judul, ' ', '') LIKE ? 
           OR REPLACE(tanggal, ' ', '') LIKE ?
           OR {$sqlTglIndoNoSpace} LIKE ?
        ORDER BY tanggal DESC, waktu DESC 
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($koneksi, $sql);
    $searchParam = "%" . $keywordClean . "%";
    mysqli_stmt_bind_param($stmt, "sssii", $searchParam, $searchParam, $searchParam, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "
        SELECT id_jadwal, judul, tanggal, waktu, lokasi, deskripsi 
        FROM jadwal 
        ORDER BY tanggal DESC, waktu DESC 
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

    <title>Jadwal Kegiatan | Bank Sampah Metro 46</title>

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

            <!-- Header Halaman -->
            <div class="page-header">
                <div>
                    <h3 class="mb-1">Jadwal Kegiatan</h3>
                    <p class="text-muted mb-0">
                        Kelola jadwal kegiatan Bank Sampah Metro 46.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn btn-add"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTambahJadwal">
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Jadwal
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
                    $alertText = 'Jadwal berhasil ditambahkan.';
                } elseif ($pesan === 'berhasil_edit') {
                    $alertClass = 'success';
                    $alertIcon = 'check-circle-fill';
                    $alertText = 'Jadwal berhasil diperbarui.';
                } elseif ($pesan === 'berhasil_hapus') {
                    $alertClass = 'success';
                    $alertIcon = 'check-circle-fill';
                    $alertText = 'Jadwal berhasil dihapus.';
                } elseif ($pesan === 'duplikat') {
                    $alertClass = 'warning';
                    $alertIcon = 'exclamation-triangle-fill';
                    $alertText = 'Tanggal dan waktu kegiatan tidak boleh sama dengan jadwal yang sudah ada!';
                }
                ?>

                <div class="alert alert-<?= $alertClass; ?> alert-dismissible fade show">
                    <i class="bi bi-<?= $alertIcon; ?> me-2"></i>
                    <?= $alertText; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Table Card -->
            <div class="card table-card">

                <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h5 class="mb-0 fw-bold text-secondary fs-6">
                        <i class="bi bi-calendar-event me-2 text-success"></i>Daftar Jadwal Kegiatan
                    </h5>

                    <!-- Form Pencarian -->
                    <form method="GET" action="jadwal.php" class="d-flex" style="max-width: 320px; width: 100%;">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm border-success rounded-start-3" placeholder="Cari judul, tanggal..." value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                            <button type="submit" class="btn btn-success btn-sm rounded-end-3 px-3">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($keyword)): ?>
                                <a href="jadwal.php" class="btn btn-outline-secondary btn-sm ms-1 rounded-3" title="Reset Pencarian">
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
                                    <th>Judul Kegiatan</th>
                                    <th width="170">Tanggal</th>
                                    <th width="130" class="text-center">Status</th>
                                    <th width="120" class="text-center">Waktu</th>
                                    <th>Lokasi</th>
                                    <th width="150" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0) : ?>
                                    <?php
                                    $hariIni = date('Y-m-d');
                                    $no = $offset + 1;
                                    ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <?php
                                        $tanggal = date_create($row['tanggal']);
                                        $tanggalIndonesia = date_format($tanggal, 'd') . ' ' .
                                            $bulanIndonesia[(int)date_format($tanggal, 'n')] . ' ' .
                                            date_format($tanggal, 'Y');

                                        if ($row['tanggal'] > $hariIni) {
                                            $status = "Akan Datang";
                                            $badge  = "success";
                                        } elseif ($row['tanggal'] == $hariIni) {
                                            $status = "Hari Ini";
                                            $badge  = "warning";
                                        } else {
                                            $status = "Selesai";
                                            $badge  = "secondary";
                                        }
                                        ?>
                                        <tr>
                                            <td class="text-center fw-bold text-secondary"><?= $no++; ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= $tanggalIndonesia; ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $badge; ?>">
                                                    <?= $status; ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?= substr($row['waktu'], 0, 5); ?> WIB</td>
                                            <td><?= htmlspecialchars($row['lokasi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-warning btn-sm text-white me-1"
                                                    title="Edit Data"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditJadwal"
                                                    data-id="<?= $row['id_jadwal']; ?>"
                                                    data-judul="<?= htmlspecialchars($row['judul'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-tanggal="<?= $row['tanggal']; ?>"
                                                    data-waktu="<?= substr($row['waktu'], 0, 5); ?>"
                                                    data-lokasi="<?= htmlspecialchars($row['lokasi'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-deskripsi="<?= htmlspecialchars($row['deskripsi'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a
                                                    href="jadwal.php?hapus=<?= $row['id_jadwal']; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    title="Hapus Data"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <?= (!empty($keyword)) ? 'Jadwal kegiatan dengan kata kunci "' . htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') . '" tidak ditemukan.' : 'Belum ada jadwal kegiatan tercatat.'; ?>
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
                    <nav aria-label="Navigasi Halaman Jadwal Kegiatan">
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

            <!-- Modal Tambah Jadwal -->
            <div class="modal fade" id="modalTambahJadwal" tabindex="-1" aria-labelledby="modalTambahJadwalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" autocomplete="off" novalidate>
                            <input type="hidden" name="tambah_jadwal" value="1">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTambahJadwalLabel">
                                    <i class="bi bi-plus-circle me-2"></i> Tambah Jadwal Kegiatan
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="judul" class="form-label">Judul Kegiatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="judul" name="judul" maxlength="100" placeholder="Contoh: Sosialisasi Pemilahan Sampah" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="waktu" class="form-label">Waktu <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="waktu" name="waktu" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="lokasi" class="form-label">Lokasi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lokasi" name="lokasi" maxlength="150" placeholder="Contoh: Balai Warga RT 01" required>
                                </div>
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" maxlength="500" placeholder="Masukkan deskripsi kegiatan..." required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i> Batal
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-save me-1"></i> Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Edit Jadwal -->
            <div class="modal fade" id="modalEditJadwal" tabindex="-1" aria-labelledby="modalEditJadwalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" autocomplete="off" novalidate>
                            <input type="hidden" name="edit_jadwal" value="1">
                            <input type="hidden" id="edit_id_jadwal" name="id_jadwal">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalEditJadwalLabel">
                                    <i class="bi bi-pencil-square me-2"></i> Edit Jadwal Kegiatan
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="edit_judul" class="form-label">Judul Kegiatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_judul" name="judul" maxlength="100" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="edit_tanggal" name="tanggal" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="edit_waktu" class="form-label">Waktu <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="edit_waktu" name="waktu" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_lokasi" class="form-label">Lokasi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_lokasi" name="lokasi" maxlength="150" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="5" maxlength="500" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-1"></i> Batal
                                </button>
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-check-circle me-1"></i> Update
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
    const modalEdit = document.getElementById('modalEditJadwal');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            document.getElementById('edit_id_jadwal').value = button.dataset.id;
            document.getElementById('edit_judul').value = button.dataset.judul;
            document.getElementById('edit_tanggal').value = button.dataset.tanggal;
            document.getElementById('edit_waktu').value = button.dataset.waktu;
            document.getElementById('edit_lokasi').value = button.dataset.lokasi;
            document.getElementById('edit_deskripsi').value = button.dataset.deskripsi;
        });

        modalEdit.addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    }

    const modalTambah = document.getElementById('modalTambahJadwal');
    if (modalTambah) {
        modalTambah.addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    }
</script>

</body>

</html>