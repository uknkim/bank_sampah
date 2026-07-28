<?php

// ==========================================
// KONFIGURASI DATABASE
// ==========================================

$host     = "localhost";
$username = "root";
$password = "";
$database = "bank_sampah";

// ==========================================
// MEMBUAT KONEKSI DATABASE
// ==========================================

$koneksi = mysqli_connect($host, $username, $password, $database);

// ==========================================
// CEK KONEKSI DATABASE
// ==========================================

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// ==========================================
// MENGATUR CHARACTER SET
// ==========================================

mysqli_set_charset($koneksi, "utf8mb4");

?>