<?php
date_default_timezone_set('Asia/Jakarta');
require_once "../config/koneksi.php";

// Ambil nama bank sampah
$namaBank = "Bank Sampah Metro 46";
$qProfil = mysqli_query($koneksi, "SELECT nama_bank_sampah FROM profil LIMIT 1");
if ($qProfil && mysqli_num_rows($qProfil) > 0) {
    $dProfil = mysqli_fetch_assoc($qProfil);
    if (!empty($dProfil['nama_bank_sampah'])) {
        $namaBank = $dProfil['nama_bank_sampah'];
    }
}

// Logika filter jadwal berdasarkan tanggal hari ini
$today = date('Y-m-d');
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Default ke hari ini jika ada kegiatan, atau ke 'akan_datang'
if (empty($filter)) {
    $qCekHariIni = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM jadwal WHERE tanggal = '$today'");
    $totalHariIni = ($qCekHariIni) ? mysqli_fetch_assoc($qCekHariIni)['total'] : 0;

    if ($totalHariIni > 0) {
        $filter = 'hari_ini';
    } else {
        $filter = 'akan_datang';
    }
}

// Query berdasarkan status filter
if ($filter == 'selesai') {
    $sqlJadwal = "SELECT * FROM jadwal WHERE tanggal < '$today' ORDER BY tanggal DESC, waktu ASC";
    $statusText = "Telah Selesai";
} elseif ($filter == 'akan_datang') {
    $sqlJadwal = "SELECT * FROM jadwal WHERE tanggal > '$today' ORDER BY tanggal ASC, waktu ASC";
    $statusText = "Akan Datang";
} else {
    $filter = 'hari_ini';
    $sqlJadwal = "SELECT * FROM jadwal WHERE tanggal = '$today' ORDER BY waktu ASC";
    $statusText = "Hari Ini (" . date('d-m-Y', strtotime($today)) . ")";
}

$resJadwal = mysqli_query($koneksi, $sqlJadwal);

// Helper format tanggal Indonesia
function formatTanggalIndo($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', $tanggal);
    if (count($split) < 3) return '-';
    return (int)$split[2] . ' ' . $bulanIndo[(int)$split[1]] . ' ' . $split[0];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kegiatan - <?= htmlspecialchars($namaBank); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="d-flex flex-column min-vh-100">

    <?php include "components/navbar.php"; ?>

    <!-- Header Section -->
    <section class="bg-light py-5">
        <div class="container text-center">
            <h2 class="fw-bold fs-2">Jadwal Kegiatan</h2>
            <p class="text-muted mb-0 fs-6">Informasi jadwal kegiatan <?= htmlspecialchars($namaBank); ?> yang dapat diikuti oleh masyarakat.</p>
        </div>
    </section>

    <!-- Content Section -->
    <section class="py-5">
        <div class="container">

            <!-- Alert Informasi -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-calendar-event text-success fs-1 me-3 flex-shrink-0"></i>
                        <div>
                            <h5 class="fw-bold mb-1 fs-5">Informasi</h5>
                            <p class="mb-0 text-muted fs-6">
                                Halaman ini menampilkan daftar jadwal kegiatan <?= htmlspecialchars($namaBank); ?>, seperti kegiatan sosialisasi, pengumpulan sampah, maupun pelatihan. Jadwal dapat berubah sesuai dengan kebutuhan dan kebijakan pengelola.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Status Jadwal -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center bg-white p-3 rounded-4 shadow-sm mb-4 gap-3">
                <div class="d-flex align-items-center">
                    <span class="fw-bold text-dark fs-6 me-2"><i class="bi bi-funnel-fill text-success me-1"></i> Status Jadwal:</span>
                    <span class="badge bg-success fs-6 px-3 py-2 rounded-pill"><?= $statusText; ?></span>
                </div>

                <form method="GET" action="jadwal.php" class="d-flex align-items-center">
                    <label for="filterSelect" class="me-2 fw-semibold text-secondary small text-nowrap">Pilih Kategori:</label>
                    <select name="filter" id="filterSelect" class="form-select form-select-md border-success rounded-3 fw-semibold text-dark" onchange="this.form.submit()">
                        <option value="hari_ini" <?= ($filter == 'hari_ini') ? 'selected' : ''; ?>>📅 Jadwal Hari Ini</option>
                        <option value="akan_datang" <?= ($filter == 'akan_datang') ? 'selected' : ''; ?>>🚀 Jadwal Akan Datang</option>
                        <option value="selesai" <?= ($filter == 'selesai') ? 'selected' : ''; ?>>✅ Jadwal Telah Selesai</option>
                    </select>
                </form>
            </div>

            <!-- Daftar Kartu Jadwal -->
            <div id="daftarJadwal" class="row g-4">
                <?php if ($resJadwal && mysqli_num_rows($resJadwal) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($resJadwal)): ?>
                        <?php
                        $namaKegiatan = $row['judul_kegiatan'] ?? $row['kegiatan'] ?? $row['nama_kegiatan'] ?? $row['judul'] ?? 'Kegiatan Bank Sampah';
                        $tglFormat    = formatTanggalIndo($row['tanggal'] ?? '');
                        
                        $waktu = '-';
                        if (!empty($row['waktu'])) {
                            $waktu = $row['waktu'];
                        } elseif (!empty($row['jam_mulai'])) {
                            $jamMulai = date('H:i', strtotime($row['jam_mulai']));
                            $jamSelesai = (!empty($row['jam_selesai'])) ? date('H:i', strtotime($row['jam_selesai'])) : 'Selesai';
                            $waktu = $jamMulai . ' - ' . $jamSelesai . ' WIB';
                        }

                        $lokasi     = $row['lokasi'] ?? $row['tempat'] ?? '-';
                        $keterangan = $row['keterangan'] ?? $row['deskripsi'] ?? 'Tidak ada keterangan tambahan.';
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-0 rounded-4">
                                <div class="card-body p-4">
                                    <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6 mb-3">
                                        <i class="bi bi-calendar3 me-1"></i> <?= $tglFormat; ?>
                                    </div>

                                    <h4 class="card-title fw-bold text-dark fs-4 mb-3" style="line-height: 1.3;">
                                        <?= htmlspecialchars($namaKegiatan, ENT_QUOTES, 'UTF-8'); ?>
                                    </h4>
                                    
                                    <ul class="list-unstyled text-dark mb-3 fs-6">
                                        <li class="mb-2 d-flex align-items-center">
                                            <i class="bi bi-clock text-primary me-2 fs-5"></i>
                                            <span class="fw-medium"><?= htmlspecialchars($waktu, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="bi bi-geo-alt text-danger me-2 fs-5"></i>
                                            <span class="fw-medium"><?= htmlspecialchars($lokasi, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </li>
                                    </ul>

                                    <p class="card-text text-secondary fs-6 border-top pt-3 mb-0" style="line-height: 1.6;">
                                        <?= nl2br(htmlspecialchars($keterangan, ENT_QUOTES, 'UTF-8')); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="bg-white p-5 rounded-4 shadow-sm d-inline-block" style="max-width: 500px;">
                            <i class="bi bi-calendar-x fs-1 text-secondary d-block mb-3"></i>
                            <h4 class="fw-bold text-dark fs-4 mb-2">Tidak Ada Jadwal Kegiatan</h4>
                            <p class="text-muted fs-6 mb-0">
                                <?php if ($filter == 'hari_ini'): ?>
                                    Hari ini tidak terdapat jadwal kegiatan di <?= htmlspecialchars($namaBank, ENT_QUOTES, 'UTF-8'); ?>. Silakan cek pilihan jadwal <strong>Akan Datang</strong> pada dropdown di atas.
                                <?php elseif ($filter == 'akan_datang'): ?>
                                    Belum ada agenda atau kegiatan mendatang yang dijadwalkan saat ini.
                                <?php else: ?>
                                    Belum ada riwayat kegiatan yang telah selesai.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <?php include "components/footer.php"; ?>

</body>

</html>