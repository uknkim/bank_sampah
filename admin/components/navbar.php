<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==========================================================
   HALAMAN AKTIF & TITLE MAPPER
========================================================== */
$currentPage = basename($_SERVER['PHP_SELF']);

$pageTitles = [
    'dashboard.php'         => 'Dashboard Overview',
    'nasabah.php'           => 'Data Nasabah',
    'detail_monitoring.php' => 'Detail Monitoring Nasabah',
    'jenis_sampah.php'      => 'Kelola Jenis Sampah',
    'transaksi.php'         => 'Transaksi Setoran Sampah',
    'jadwal.php'            => 'Jadwal & Agenda Kegiatan',
    'profil.php'            => 'Profil Bank Sampah'
];

$pageTitle = $pageTitles[$currentPage] ?? 'Dashboard Admin';

/* ==========================================================
   NAMA ADMIN & ROLE
========================================================== */
$namaAdmin = $_SESSION['nama_admin'] ?? 'Administrator';
$roleAdmin = $_SESSION['role_admin'] ?? 'Admin';

/* ==========================================================
   TANGGAL HARI INI (INDONESIA)
========================================================== */
date_default_timezone_set('Asia/Jakarta');

$namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$namaBulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

$tanggalHariIni = $namaHari[date('w')] . ', ' . date('d') . ' ' . $namaBulan[(int)date('n')] . ' ' . date('Y');
?>

<!-- CSS KHUSUS NAVBAR MODERN -->
<style>
.top-navbar {
    background: #ffffff;
    border-radius: 14px;
    padding: 12px 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0, 0, 0, 0.05);
    margin-bottom: 24px;
    transition: all 0.3s ease;
}

.navbar-page-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.3px;
}

.navbar-page-subtitle {
    font-size: 0.825rem;
    color: #64748b;
}

.nav-date-pill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 0.825rem;
    font-weight: 500;
    color: #475569;
}

.btn-nav-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.2s ease;
}

.btn-nav-icon:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.btn-nav-icon .pulse-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 8px;
    height: 8px;
    background-color: #ef4444;
    border-radius: 50%;
    border: 2px solid #ffffff;
}

.user-profile-btn {
    background: transparent;
    border: none;
    padding: 4px 8px;
    border-radius: 30px;
    transition: background 0.2s ease;
}

.user-profile-btn:hover {
    background: #f1f5f9;
}

.avatar-wrapper {
    position: relative;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #198754, #20c997);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    box-shadow: 0 2px 8px rgba(25, 135, 84, 0.25);
}

.status-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    background-color: #10b981;
    border: 2px solid #ffffff;
    border-radius: 50%;
}

.dropdown-menu-custom {
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    padding: 8px;
    min-width: 220px;
    margin-top: 10px !important;
}

.dropdown-menu-custom .dropdown-item {
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.875rem;
    color: #334155;
    font-weight: 500;
}

.dropdown-menu-custom .dropdown-item:hover {
    background-color: #f1f5f9;
    color: #198754;
}

.dropdown-menu-custom .dropdown-item.text-danger:hover {
    background-color: #fef2f2;
    color: #dc2626;
}
</style>

<!-- NAVBAR COMPONENT -->
<nav class="navbar navbar-expand top-navbar align-items-center justify-content-between">
    
    <!-- Title Area (Left) -->
    <div class="d-flex align-items-center">
        <div>
            <h4 class="navbar-page-title mb-0">
                <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>
            </h4>
            <span class="navbar-page-subtitle">
                Sistem Informasi Management Bank Sampah Metro 46
            </span>
        </div>
    </div>

    <!-- Actions Area (Right) -->
    <div class="d-flex align-items-center gap-3">
        
        <!-- Date Display -->
        <div class="nav-date-pill d-none d-md-flex align-items-center gap-2">
            <i class="bi bi-calendar-event text-success"></i>
            <span><?= htmlspecialchars($tanggalHariIni, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <!-- Vertical Divider -->
        <div class="vr opacity-25 d-none d-sm-block" style="height: 28px;"></div>

        <!-- Admin Profile Dropdown -->
        <div class="dropdown">
            <button class="user-profile-btn d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar-wrapper">
                    <?= strtoupper(substr($namaAdmin, 0, 1)); ?>
                    <span class="status-indicator"></span>
                </div>
                <div class="text-start d-none d-lg-block ms-1">
                    <div class="fw-bold text-dark lh-1 small" style="font-size: 0.875rem;">
                        <?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <small class="text-muted extra-small" style="font-size: 0.75rem;">
                        <?= htmlspecialchars($roleAdmin, ENT_QUOTES, 'UTF-8'); ?>
                    </small>
                </div>
                <i class="bi bi-chevron-down text-muted small ms-1 d-none d-lg-block"></i>
            </button>

            <!-- Dropdown Menu -->
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                <li class="px-3 py-2 border-bottom d-lg-none">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8'); ?></div>
                    <small class="text-muted"><?= htmlspecialchars($roleAdmin, ENT_QUOTES, 'UTF-8'); ?></small>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="./profil.php">
                        <i class="bi bi-person text-secondary"></i> Profil Bank Sampah
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                        <i class="bi bi-box-arrow-right"></i> Keluar / Logout
                    </a>
                </li>
            </ul>
        </div>

    </div>

</nav>