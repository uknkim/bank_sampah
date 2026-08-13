<?php
// Pastikan session sudah berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan ke database jika koneksi belum dipanggil di file utama
if (!isset($koneksi)) {
    $pathKoneksi = "../config/koneksi.php";
    if (file_exists($pathKoneksi)) {
        require_once $pathKoneksi;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);

//AMBIL DATA PROFIL LOGO & NAMA DARI DATABASE
$logoSidebar = "../assets/img/logo.png"; // Default Fallback
$namaBankSampahSidebar = "Bank Sampah";

if (isset($koneksi)) {
    $queryProfilSidebar = "SELECT nama_bank_sampah, logo FROM profil LIMIT 1";
    $resultProfilSidebar = mysqli_query($koneksi, $queryProfilSidebar);

    if ($resultProfilSidebar && mysqli_num_rows($resultProfilSidebar) > 0) {
        $dataProfilSidebar = mysqli_fetch_assoc($resultProfilSidebar);
        
        // Cek jika kolom logo tidak kosong dan file-nya wujud
        if (!empty($dataProfilSidebar['logo'])) {
            $pathLogoDB = "../assets/img/" . $dataProfilSidebar['logo'];
            if (file_exists($pathLogoDB)) {
                $logoSidebar = $pathLogoDB;
            }
        }

        if (!empty($dataProfilSidebar['nama_bank_sampah'])) {
            $namaBankSampahSidebar = $dataProfilSidebar['nama_bank_sampah'];
        }
    }
}
?>

<!-- STYLES KHUSUS SIDEBAR MODERN EMERALD -->
<style>
.sidebar {
    width: 260px;
    min-height: 100vh;
    background: linear-gradient(180deg, #0b3d26 0%, #0f5132 50%, #146c43 100%);
    color: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.08);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    transition: all 0.3s ease;
}

/* Brand / Logo Header */
.sidebar-brand {
    padding: 20px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-logo-img {
    width: 46px;
    height: 46px;
    object-fit: contain;
    background: #ffffff;
    padding: 4px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.sidebar-brand-text h5 {
    font-size: 0.98rem;
    font-weight: 700;
    margin: 0;
    color: #ffffff;
    letter-spacing: -0.2px;
    line-height: 1.2;
}

.sidebar-brand-text span {
    font-size: 0.75rem;
    color: #a3e635; /* Accent Neon Green */
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    display: block;
    margin-top: 2px;
}

.sidebar-brand-text small {
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.6);
    display: block;
}

/* Navigation Section */
.sidebar-body {
    padding: 16px 12px;
    flex-grow: 1;
    overflow-y: auto;
}

.sidebar-heading {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.45);
    padding: 12px 14px 6px 14px;
    letter-spacing: 0.8px;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0 0 12px 0;
}

.sidebar-item {
    margin-bottom: 4px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s ease;
    position: relative;
}

.sidebar-link i {
    font-size: 1.15rem;
    color: rgba(255, 255, 255, 0.7);
    transition: all 0.2s ease;
}

.sidebar-link:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.12);
}

.sidebar-link:hover i {
    color: #a3e635;
    transform: translateX(2px);
}

/* Active State */
.sidebar-link.active {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.18);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sidebar-link.active i {
    color: #a3e635;
}

.sidebar-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 6px;
    bottom: 6px;
    width: 4px;
    background-color: #a3e635;
    border-radius: 0 4px 4px 0;
}

/* Footer / Logout Area */
.sidebar-footer {
    padding: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.12);
}

.btn-logout-sidebar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 10px;
    background: rgba(220, 53, 69, 0.2);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #ff8484;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-logout-sidebar:hover {
    background: #dc3545;
    color: #ffffff;
    border-color: #dc3545;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}
</style>

<!-- SIDEBAR COMPONENT -->
<aside class="sidebar" id="adminSidebar">

    <!-- Header Logo & Brand (Dinamis DB) -->
    <div class="sidebar-brand">
        <img src="<?= htmlspecialchars($logoSidebar, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo Bank Sampah" class="sidebar-logo-img">
        <div class="sidebar-brand-text">
            <h5><?= htmlspecialchars($namaBankSampahSidebar, ENT_QUOTES, 'UTF-8'); ?></h5>
            <small>Administrator Panel</small>
        </div>
    </div>

    <!-- Main Navigation Body -->
    <div class="sidebar-body">
        
        <!-- Menu Utama Section -->
        <div class="sidebar-heading">Menu Utama</div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="index.php" class="sidebar-link <?= ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- Kelola Data Section -->
        <div class="sidebar-heading">Kelola & Monitoring</div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="nasabah.php" class="sidebar-link <?= in_array($currentPage, ['nasabah.php', 'detail_monitoring.php']) ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Data Nasabah</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="jenis_sampah.php" class="sidebar-link <?= ($currentPage == 'jenis_sampah.php') ? 'active' : ''; ?>">
                    <i class="bi bi-recycle"></i>
                    <span>Jenis Sampah</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="transaksi.php" class="sidebar-link <?= ($currentPage == 'transaksi.php') ? 'active' : ''; ?>">
                    <i class="bi bi-arrow-down-left-circle-fill"></i>
                    <span>Setoran Sampah</span>
                </a>
            </li>
            <!-- MENU PENARIKAN SALDO DITAMBAHKAN DI SINI -->
            <li class="sidebar-item">
                <a href="penarikan.php" class="sidebar-link <?= ($currentPage == 'penarikan.php') ? 'active' : ''; ?>">
                    <i class="bi bi-cash-stack"></i>
                    <span>Penarikan Saldo</span>
                </a>
            </li>
        </ul>

        <!-- Informasi Section -->
        <div class="sidebar-heading">Informasi & Kegiatan</div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="jadwal.php" class="sidebar-link <?= ($currentPage == 'jadwal.php') ? 'active' : ''; ?>">
                    <i class="bi bi-calendar3-event-fill"></i>
                    <span>Jadwal Kegiatan</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="profil.php" class="sidebar-link <?= ($currentPage == 'profil.php') ? 'active' : ''; ?>">
                    <i class="bi bi-building-fill"></i>
                    <span>Profil Bank Sampah</span>
                </a>
            </li>
        </ul>

    </div>

    <!-- Footer Logout Area -->
    <div class="sidebar-footer">
        <a href="logout.php" class="btn-logout-sidebar" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
            <i class="bi bi-box-arrow-right"></i>
            <span>Keluar System</span>
        </a>
    </div>

</aside>