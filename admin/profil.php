<?php

session_start();

require_once "../config/koneksi.php";

// Cek otentikasi admin
if (!isset($_SESSION['id_admin'])) {
    header("Location: login.php?pesan=login_dulu");
    exit;
}

$nama_admin = $_SESSION['nama_admin'];

// Ambil data profil bank sampah
$profil = null;
$sqlProfil = "SELECT * FROM profil LIMIT 1";
$resultProfil = mysqli_query($koneksi, $sqlProfil);

if ($resultProfil && mysqli_num_rows($resultProfil) > 0) {
    $profil = mysqli_fetch_assoc($resultProfil);
}

// Proses simpan profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_bank_sampah = trim($_POST['nama_bank_sampah'] ?? '');
    $alamat           = trim($_POST['alamat'] ?? '');
    $telepon          = trim($_POST['telepon'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $deskripsi        = trim($_POST['deskripsi'] ?? '');

    // Validasi field wajib
    if ($nama_bank_sampah === '' || $alamat === '' || $telepon === '' || $email === '' || $deskripsi === '') {
        header("Location: profil.php?pesan=gagal");
        exit;
    }

    // Validasi format email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: profil.php?pesan=email_tidak_valid");
        exit;
    }

    $logo = $profil['logo'] ?? null;

    // Upload logo (opsional)
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            header("Location: profil.php?pesan=upload_gagal");
            exit;
        }

        // Ukuran maksimal 2 MB
        $maksimalUkuran = 2 * 1024 * 1024;
        if ($_FILES['logo']['size'] > $maksimalUkuran) {
            header("Location: profil.php?pesan=ukuran_logo");
            exit;
        }

        // Validasi MIME Type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['logo']['tmp_name']);
        finfo_close($finfo);

        $allowedMime = ['image/jpeg', 'image/png'];
        if (!in_array($mimeType, $allowedMime)) {
            header("Location: profil.php?pesan=tipe_logo");
            exit;
        }

        // Validasi ekstensi
        $extensi = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png'];

        if (!in_array($extensi, $allowedExt)) {
            header("Location: profil.php?pesan=tipe_logo");
            exit;
        }

        $folderUpload = "../assets/img/";
        $namaLogoBaru = "logo_" . date("YmdHis") . "_" . bin2hex(random_bytes(4)) . "." . $extensi;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $folderUpload . $namaLogoBaru)) {
            // Hapus logo lama jika ada dan bukan default
            if (!empty($logo) && $logo !== "logo.png" && file_exists($folderUpload . $logo)) {
                unlink($folderUpload . $logo);
            }
            $logo = $namaLogoBaru;
        } else {
            header("Location: profil.php?pesan=upload_gagal");
            exit;
        }
    }

    // Simpan data (UPDATE jika sudah ada, INSERT jika belum)
    if ($profil) {
        $sql = "UPDATE profil SET nama_bank_sampah = ?, alamat = ?, telepon = ?, email = ?, deskripsi = ?, logo = ? WHERE id_profil = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssi", $nama_bank_sampah, $alamat, $telepon, $email, $deskripsi, $logo, $profil['id_profil']);
    } else {
        $sql = "INSERT INTO profil (nama_bank_sampah, alamat, telepon, email, deskripsi, logo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $nama_bank_sampah, $alamat, $telepon, $email, $deskripsi, $logo);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: profil.php?pesan=gagal");
        exit;
    }

    mysqli_stmt_close($stmt);
    header("Location: profil.php?pesan=berhasil_edit");
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profil Bank Sampah | Bank Sampah Metro 46</title>

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
                    <h3 class="mb-1">Profil Bank Sampah</h3>
                    <p class="text-muted mb-0">
                        Kelola informasi profil Bank Sampah Metro 46.
                    </p>
                </div>
            </div>

            <!-- Notifikasi Sistem -->
            <?php if (isset($_GET['pesan'])) : ?>
                <?php if ($_GET['pesan'] == 'berhasil_edit') : ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill me-2"></i> Data profil berhasil disimpan.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'gagal') : ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Semua data wajib diisi.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'email_tidak_valid') : ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="bi bi-envelope-x-fill me-2"></i> Format email tidak valid.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'ukuran_logo') : ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="bi bi-image-fill me-2"></i> Ukuran logo maksimal 2 MB.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'tipe_logo') : ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="bi bi-file-earmark-image-fill me-2"></i> Logo hanya boleh berformat JPG, JPEG, atau PNG.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'upload_gagal') : ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i> Upload logo gagal. Silakan coba kembali.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Form Profil Utama -->
            <form method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                <div class="row">

                    <!-- Kartu Logo -->
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="mb-0 fw-bold text-secondary fs-6">
                                    <i class="bi bi-image me-2 text-success"></i>Logo Bank Sampah
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <?php
                                $logoTampil = "../assets/img/logo.png";
                                if (!empty($profil['logo']) && file_exists("../assets/img/" . $profil['logo'])) {
                                    $logoTampil = "../assets/img/" . $profil['logo'];
                                }
                                ?>
                                <img
                                    src="<?= htmlspecialchars($logoTampil, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="img-fluid rounded border p-2 mb-3 bg-light"
                                    style="max-width: 200px; max-height: 200px; object-fit: contain;"
                                    alt="Logo Bank Sampah">

                                <div class="text-start">
                                    <label for="logo" class="form-label fw-semibold">Ganti Logo</label>
                                    <input type="file" class="form-control" id="logo" name="logo" accept=".jpg,.jpeg,.png">
                                    <small class="text-muted mt-2 d-block">
                                        Format: JPG, JPEG, PNG (Maksimal 2 MB).
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu Detail Informasi -->
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="mb-0 fw-bold text-secondary fs-6">
                                    <i class="bi bi-info-circle me-2 text-success"></i>Informasi Bank Sampah
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="nama_bank_sampah" class="form-label">Nama Bank Sampah <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="nama_bank_sampah"
                                        name="nama_bank_sampah"
                                        value="<?= htmlspecialchars($profil['nama_bank_sampah'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
                                    <textarea
                                        class="form-control"
                                        id="alamat"
                                        name="alamat"
                                        rows="3"
                                        required><?= htmlspecialchars($profil['alamat'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telepon" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="telepon"
                                            name="telepon"
                                            value="<?= htmlspecialchars($profil['telepon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input
                                            type="email"
                                            class="form-control"
                                            id="email"
                                            name="email"
                                            value="<?= htmlspecialchars($profil['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                    <textarea
                                        class="form-control"
                                        id="deskripsi"
                                        name="deskripsi"
                                        rows="5"
                                        required><?= htmlspecialchars($profil['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>

                                <div class="d-flex justify-content-end pt-2">
                                    <button type="submit" class="btn btn-success px-4">
                                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>

        </section>

        <?php include "components/footer.php"; ?>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>