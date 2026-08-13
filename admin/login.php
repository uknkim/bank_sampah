<?php
session_start();

// Jika admin sudah login, langsung arahkan ke index.php
if (isset($_SESSION['id_admin'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator | Bank Sampah Metro 46</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, Helvetica, sans-serif;
        }

        .login-card {
            width: 420px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .12);
        }

        .logo {
            width: 110px;
            height: 110px;
            object-fit: contain;
        }

        .system-title {
            font-size: 22px;
            font-weight: 700;
            color: #0d6efd;
            line-height: 1.4;
        }

        .subtitle {
            font-size: 15px;
            color: #6c757d;
        }

        .btn-primary {
            height: 48px;
            font-weight: 600;
        }

        .toggle-password {
            cursor: pointer;
            background-color: #ffffff;
        }

        .toggle-password:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>

    <div class="card login-card">
        <div class="card-body p-4">

            <!-- Logo & Title -->
            <div class="text-center mb-4">
                <img src="../assets/img/logo.png" alt="Logo Bank Sampah" class="logo mb-3">
                <h3 class="system-title">
                    Sistem Informasi Pengelolaan dan<br>Monitoring Bank Sampah Metro 46
                </h3>
                <p class="subtitle mt-2 mb-0">Login Administrator</p>
            </div>

            <!-- Alert Pesan dengan Tombol Close (X) -->
            <?php if (isset($_GET['pesan'])) : ?>
                <?php if ($_GET['pesan'] == "gagal") : ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        Username atau Password salah.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == "kosong") : ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        Username dan Password wajib diisi.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == "logout") : ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Anda berhasil logout.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="proses_login.php" method="POST">

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" autocomplete="off" required>
                    </div>
                </div>

                <!-- Password + Toggle Eye Icon -->
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                        <span class="input-group-text toggle-password" id="togglePassword">
                            <i class="bi bi-eye-slash-fill" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Tombol Masuk -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Masuk
                    </button>
                </div>

            </form>

            <!-- Copyright -->
            <div class="text-center mt-4">
                <small class="text-muted">© 2026 Bank Sampah Metro 46</small>
            </div>

        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Script: Eye Icon & Refresh Cleaner -->
    <script>
        // 1. Script Toggle Show/Hide Password
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            // Toggle tipe input antara 'password' dan 'text'
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle Icon Mata
            if (type === 'text') {
                eyeIcon.classList.remove('bi-eye-slash-fill');
                eyeIcon.classList.add('bi-eye-fill');
            } else {
                eyeIcon.classList.remove('bi-eye-fill');
                eyeIcon.classList.add('bi-eye-slash-fill');
            }
        });

        // 2. Clear URL Parameter saat di-refresh agar Alert tidak muncul terus
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }
    </script>

</body>

</html>