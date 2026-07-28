<?php
session_start();

// Jika admin sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['id_admin'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login Administrator | Bank Sampah Metro 46</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css"
          rel="stylesheet">

    <style>

        body{

            background:#f5f7fa;

            min-height:100vh;

            display:flex;

            justify-content:center;

            align-items:center;

            font-family:Arial, Helvetica, sans-serif;

        }

        .login-card{

            width:420px;

            border:none;

            border-radius:15px;

            box-shadow:0 10px 30px rgba(0,0,0,.12);

        }

        .logo{

            width:110px;

            height:110px;

            object-fit:contain;

        }

        .system-title{

            font-size:22px;

            font-weight:700;

            color:#0d6efd;

            line-height:1.4;

        }

        .subtitle{

            font-size:15px;

            color:#6c757d;

        }

        .btn-primary{

            height:48px;

            font-weight:600;

        }

    </style>

</head>

<body>

    <div class="card login-card">

        <div class="card-body p-4">

            <!-- Logo -->
            <div class="text-center mb-4">

                <img
                    src="../assets/img/logo.png"
                    alt="Logo Bank Sampah"
                    class="logo mb-3">

                <h3 class="system-title">

                    Sistem Informasi Pengelolaan dan
                    Monitoring Bank Sampah Metro 46

                </h3>

                <p class="subtitle mt-2 mb-0">

                    Login Administrator

                </p>

            </div>

            <!-- Alert -->
            <?php if (isset($_GET['pesan'])) : ?>

                <?php if ($_GET['pesan'] == "gagal") : ?>

                    <div class="alert alert-danger">

                        <i class="bi bi-exclamation-circle-fill me-2"></i>

                        Username atau Password salah.
                        Silakan coba lagi.

                    </div>

                <?php elseif ($_GET['pesan'] == "kosong") : ?>

                    <div class="alert alert-warning">

                        <i class="bi bi-exclamation-circle-fill me-2"></i>

                        Username dan Password wajib diisi.

                    </div>

                <?php elseif ($_GET['pesan'] == "logout") : ?>

                    <div class="alert alert-success">

                        <i class="bi bi-check-circle-fill me-2"></i>

                        Anda berhasil logout.

                    </div>

                <?php endif; ?>

            <?php endif; ?>

                        <!-- Form Login -->

            <form
                action="proses_login.php"
                method="POST">

                <!-- Username -->

                <div class="mb-3">

                    <label
                        for="username"
                        class="form-label fw-semibold">

                        Username

                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-person-fill"></i>

                        </span>

                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            placeholder="Masukkan username"
                            autocomplete="off"
                            required>

                    </div>

                </div>

                <!-- Password -->

                <div class="mb-4">

                    <label
                        for="password"
                        class="form-label fw-semibold">

                        Password

                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-lock-fill"></i>

                        </span>

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            required>

                    </div>

                </div>

                <!-- Tombol Masuk -->

                <div class="d-grid">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="bi bi-box-arrow-in-right me-2"></i>

                        Masuk

                    </button>

                </div>

            </form>

            <!-- Copyright -->

            <div class="text-center mt-4">

                <small class="text-muted">

                    © 2026 Bank Sampah Metro 46ggit config --global user.name "lukman"

                </small>

            </div>

        </div>

    </div>

    <!-- Bootstrap JS -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>