<?php
session_start();
require_once "../config/koneksi.php";

// Cek otentikasi admin
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php?pesan=login_dulu");
    exit;
}

$nama_admin = $_SESSION['nama_admin'];

// Proses tambah jenis sampah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_jenis'])) {
    $nama_jenis = preg_replace('/\s+/', ' ', trim($_POST['nama_jenis'] ?? ''));
    $harga_per_kg = trim($_POST['harga_per_kg'] ?? '');

    if ($nama_jenis === '' || $harga_per_kg === '') {
        header("Location: jenis_sampah.php?pesan=gagal");
        exit;
    }

    if (!is_numeric($harga_per_kg) || $harga_per_kg < 0) {
        header("Location: jenis_sampah.php?pesan=harga_tidak_valid");
        exit;
    }

    // Cek duplikasi nama jenis sampah
    $sqlCek = "SELECT id_jenis FROM jenis_sampah WHERE nama_jenis = ? LIMIT 1";
    $stmtCek = mysqli_prepare($koneksi, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "s", $nama_jenis);
    mysqli_stmt_execute($stmtCek);
    $resultCek = mysqli_stmt_get_result($stmtCek);

    if (mysqli_num_rows($resultCek) > 0) {
        mysqli_stmt_close($stmtCek);
        header("Location: jenis_sampah.php?pesan=duplikat");
        exit;
    }
    mysqli_stmt_close($stmtCek);

    // Insert jenis sampah baru
    $sqlInsert = "INSERT INTO jenis_sampah (nama_jenis, harga_per_kg) VALUES (?, ?)";
    $stmtInsert = mysqli_prepare($koneksi, $sqlInsert);
    mysqli_stmt_bind_param($stmtInsert, "sd", $nama_jenis, $harga_per_kg);

    if (!mysqli_stmt_execute($stmtInsert)) {
        mysqli_stmt_close($stmtInsert);
        header("Location: jenis_sampah.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmtInsert);
    header("Location: jenis_sampah.php?pesan=berhasil_tambah");
    exit;
}

// Proses edit jenis sampah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_jenis'])) {
    $id_jenis = intval($_POST['id_jenis'] ?? 0);
    $nama_jenis = preg_replace('/\s+/', ' ', trim($_POST['nama_jenis'] ?? ''));
    $harga_per_kg = trim($_POST['harga_per_kg'] ?? '');

    if ($id_jenis <= 0 || $nama_jenis === '' || $harga_per_kg === '') {
        header("Location: jenis_sampah.php?pesan=gagal");
        exit;
    }

    if (!is_numeric($harga_per_kg) || $harga_per_kg < 0) {
        header("Location: jenis_sampah.php?pesan=harga_tidak_valid");
        exit;
    }

    // Cek duplikasi nama (abaikan ID yang sedang diubah)
    $sqlCek = "SELECT id_jenis FROM jenis_sampah WHERE nama_jenis = ? AND id_jenis <> ? LIMIT 1";
    $stmtCek = mysqli_prepare($koneksi, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "si", $nama_jenis, $id_jenis);
    mysqli_stmt_execute($stmtCek);
    $resultCek = mysqli_stmt_get_result($stmtCek);

    if (mysqli_num_rows($resultCek) > 0) {
        mysqli_stmt_close($stmtCek);
        header("Location: jenis_sampah.php?pesan=duplikat");
        exit;
    }
    mysqli_stmt_close($stmtCek);

    // Update data jenis sampah
    $sqlUpdate = "UPDATE jenis_sampah SET nama_jenis = ?, harga_per_kg = ? WHERE id_jenis = ?";
    $stmtUpdate = mysqli_prepare($koneksi, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUpdate, "sdi", $nama_jenis, $harga_per_kg, $id_jenis);

    if (!mysqli_stmt_execute($stmtUpdate)) {
        mysqli_stmt_close($stmtUpdate);
        header("Location: jenis_sampah.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmtUpdate);
    header("Location: jenis_sampah.php?pesan=berhasil_edit");
    exit;
}

// Proses hapus jenis sampah
if (isset($_GET['hapus'])) {
    $id_jenis = intval($_GET['hapus']);

    if ($id_jenis <= 0) {
        header("Location: jenis_sampah.php?pesan=gagal");
        exit;
    }

    // Ambil nama jenis sampah untuk kebutuhan alert jika gagal hapus
    $sqlGetNama = "SELECT nama_jenis FROM jenis_sampah WHERE id_jenis = ?";
    $stmtNama   = mysqli_prepare($koneksi, $sqlGetNama);
    mysqli_stmt_bind_param($stmtNama, "i", $id_jenis);
    mysqli_stmt_execute($stmtNama);
    $resNama   = mysqli_stmt_get_result($stmtNama);
    $dataNama  = mysqli_fetch_assoc($resNama);
    mysqli_stmt_close($stmtNama);

    $nama_jenis = $dataNama['nama_jenis'] ?? '';

    // Cek relasi data di tabel detail_transaksi
    $sqlCekDetail = "SELECT id_detail FROM detail_transaksi WHERE id_jenis = ? LIMIT 1";
    $stmtCek      = mysqli_prepare($koneksi, $sqlCekDetail);
    mysqli_stmt_bind_param($stmtCek, "i", $id_jenis);
    mysqli_stmt_execute($stmtCek);
    mysqli_stmt_store_result($stmtCek);

    if (mysqli_stmt_num_rows($stmtCek) > 0) {
        mysqli_stmt_close($stmtCek);
        header("Location: jenis_sampah.php?pesan=dipakai_transaksi&nama=" . urlencode($nama_jenis));
        exit;
    }
    mysqli_stmt_close($stmtCek);

    // Eksekusi hapus jika aman
    $sqlDelete = "DELETE FROM jenis_sampah WHERE id_jenis = ?";
    $stmtDelete = mysqli_prepare($koneksi, $sqlDelete);
    mysqli_stmt_bind_param($stmtDelete, "i", $id_jenis);

    try {
        if (!mysqli_stmt_execute($stmtDelete)) {
            mysqli_stmt_close($stmtDelete);
            header("Location: jenis_sampah.php?pesan=gagal");
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        mysqli_stmt_close($stmtDelete);
        header("Location: jenis_sampah.php?pesan=dipakai_transaksi&nama=" . urlencode($nama_jenis));
        exit;
    }

    mysqli_stmt_close($stmtDelete);
    header("Location: jenis_sampah.php?pesan=berhasil_hapus");
    exit;
}

// Query ambil semua data jenis sampah
$sql = "SELECT id_jenis, nama_jenis, harga_per_kg FROM jenis_sampah ORDER BY nama_jenis ASC";
$result = mysqli_query($koneksi, $sql);

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

    <title>Jenis Sampah | Bank Sampah Metro 46</title>

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
                    <h3 class="mb-1">Data Jenis Sampah</h3>
                    <p class="text-muted mb-0">
                        Kelola daftar jenis sampah beserta harga per kilogram.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn btn-add"
                    data-bs-toggle="modal"
                    data-bs-target="#modalTambahJenis">
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Jenis Sampah
                </button>
            </div>

            <!-- Notifikasi Sistem -->
            <?php if (isset($_GET['pesan'])) : ?>
                <?php if ($_GET['pesan'] == 'berhasil_tambah') : ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill me-2"></i> Data jenis sampah berhasil ditambahkan.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'berhasil_edit') : ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill me-2"></i> Data jenis sampah berhasil diperbarui.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'berhasil_hapus') : ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill me-2"></i> Data jenis sampah berhasil dihapus.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'duplikat') : ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Nama jenis sampah sudah tersedia.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'harga_tidak_valid') : ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="bi bi-currency-dollar me-2"></i> Harga per kilogram tidak valid.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'dipakai_transaksi') : ?>
                    <?php 
                        $namaParam = isset($_GET['nama']) ? htmlspecialchars($_GET['nama'], ENT_QUOTES, 'UTF-8') : '';
                        $textNama  = $namaParam !== '' ? ' <strong>"' . $namaParam . '"</strong>' : '';
                    ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Terdapat transaksi yang menyetor sampah jenis<?= $textNama; ?> dan tidak boleh dihapus!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'gagal') : ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-x-circle-fill me-2"></i> Proses gagal dilakukan.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Table Card -->
            <div class="card table-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="70" class="text-center">No</th>
                                    <th>Jenis Sampah</th>
                                    <th width="220">Harga / Kg</th>
                                    <th width="150" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0) : ?>
                                    <?php $no = 1; ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                        <tr>
                                            <td class="text-center fw-bold text-secondary"><?= $no++; ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_jenis'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>Rp <?= number_format($row['harga_per_kg'], 0, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-warning btn-sm text-white me-1"
                                                    title="Edit Data"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditJenis"
                                                    data-id="<?= $row['id_jenis']; ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama_jenis'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-harga="<?= $row['harga_per_kg']; ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <a
                                                    href="jenis_sampah.php?hapus=<?= $row['id_jenis']; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    title="Hapus Data"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus jenis sampah ini?')">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Belum ada data jenis sampah.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Tambah Jenis Sampah -->
            <div class="modal fade" id="modalTambahJenis" tabindex="-1" aria-labelledby="modalTambahJenisLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" autocomplete="off" novalidate>
                            <input type="hidden" name="tambah_jenis" value="1">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTambahJenisLabel">
                                    <i class="bi bi-plus-circle me-2"></i> Tambah Jenis Sampah
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="nama_jenis" class="form-label">Nama Jenis Sampah <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_jenis" name="nama_jenis" maxlength="100" placeholder="Contoh: Botol Plastik" autofocus required>
                                </div>
                                <div class="mb-3">
                                    <label for="harga_per_kg" class="form-label">Harga per Kg (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="harga_per_kg" name="harga_per_kg" placeholder="Contoh: 3000" min="0" step="1" inputmode="numeric" required>
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

            <!-- Modal Edit Jenis Sampah -->
            <div class="modal fade" id="modalEditJenis" tabindex="-1" aria-labelledby="modalEditJenisLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" autocomplete="off" novalidate>
                            <input type="hidden" name="edit_jenis" value="1">
                            <input type="hidden" id="edit_id_jenis" name="id_jenis">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalEditJenisLabel">
                                    <i class="bi bi-pencil-square me-2"></i> Edit Jenis Sampah
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="edit_nama_jenis" class="form-label">Nama Jenis Sampah <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_nama_jenis" name="nama_jenis" maxlength="100" placeholder="Contoh: Botol Plastik" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_harga_per_kg" class="form-label">Harga per Kg (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="edit_harga_per_kg" name="harga_per_kg" placeholder="Contoh: 3000" min="0" step="1" inputmode="numeric" required>
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
    const modalEdit = document.getElementById('modalEditJenis');
    if (modalEdit) {
        modalEdit.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            document.getElementById('edit_id_jenis').value = button.getAttribute('data-id');
            document.getElementById('edit_nama_jenis').value = button.getAttribute('data-nama');
            document.getElementById('edit_harga_per_kg').value = button.getAttribute('data-harga');
        });

        modalEdit.addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    }

    const modalTambah = document.getElementById('modalTambahJenis');
    if (modalTambah) {
        modalTambah.addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    }
</script>

</body>

</html>