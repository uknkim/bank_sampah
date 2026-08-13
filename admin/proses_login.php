<?php

session_start();

require_once "../config/koneksi.php";

// ==========================================
// VALIDASI REQUEST METHOD
// ==========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// ==========================================
// AMBIL DATA DARI FORM
// ==========================================

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// ==========================================
// VALIDASI FIELD KOSONG
// ==========================================

if ($username == "" || $password == "") {
    header("Location: login.php?pesan=kosong");
    exit;
}

// ==========================================
// PREPARED STATEMENT
// MENCARI USERNAME
// ==========================================

$sql = "SELECT id_admin, username, password, nama_admin
        FROM admin
        WHERE username = ?";

$stmt = mysqli_prepare($koneksi, $sql);

// Jika prepare gagal
if (!$stmt) {
    die("Terjadi kesalahan pada sistem.");
}

// Bind parameter
mysqli_stmt_bind_param($stmt, "s", $username);

// Jalankan query
mysqli_stmt_execute($stmt);

// Ambil hasil query
$result = mysqli_stmt_get_result($stmt);

// ==========================================
// CEK APAKAH USERNAME DITEMUKAN
// ==========================================

if (mysqli_num_rows($result) == 1) {

    $admin = mysqli_fetch_assoc($result);

    // ======================================
    // VERIFIKASI PASSWORD
    // ======================================

    if (password_verify($password, $admin['password'])) {

        // ==================================
        // MEMBUAT SESSION
        // ==================================

        $_SESSION['id_admin'] = $admin['id_admin'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['nama_admin'] = $admin['nama_admin'];

        // Tutup statement
        mysqli_stmt_close($stmt);

        // Redirect ke Dashboard
        header("Location: index.php");
        exit;
    }
}

// ==========================================
// LOGIN GAGAL
// ==========================================

mysqli_stmt_close($stmt);

header("Location: login.php?pesan=gagal");
exit;

?>