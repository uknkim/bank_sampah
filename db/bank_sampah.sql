-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 11, 2026 at 06:22 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bank_sampah`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_admin` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama_admin`) VALUES
(1, 'admin', '$2y$10$Tr9kXUdx2Mh4h6aXs2s3GOyK2YHgvqmfcbJ6wiuJaQyMLz4jZzElS', 'Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` int(10) UNSIGNED NOT NULL,
  `id_transaksi` int(10) UNSIGNED NOT NULL,
  `id_jenis` int(10) UNSIGNED NOT NULL,
  `berat` decimal(8,2) NOT NULL DEFAULT 0.00,
  `harga_per_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_jenis`, `berat`, `harga_per_kg`, `subtotal`) VALUES
(14, 6, 4, 4.00, 5000.00, 20000.00),
(15, 6, 5, 3.00, 6500.00, 19500.00),
(27, 7, 4, 3.00, 5000.00, 15000.00),
(28, 7, 1, 4.00, 4500.00, 18000.00),
(29, 7, 2, 2.00, 5000.00, 10000.00),
(30, 7, 5, 5.00, 6500.00, 32500.00),
(31, 8, 4, 1.00, 5000.00, 5000.00),
(32, 8, 5, 2.00, 6500.00, 13000.00),
(33, 9, 1, 2.00, 4500.00, 9000.00),
(34, 9, 2, 3.00, 5000.00, 15000.00),
(35, 10, 4, 15.00, 5000.00, 75000.00),
(36, 10, 1, 2.00, 4500.00, 9000.00),
(37, 10, 2, 4.00, 5000.00, 20000.00),
(38, 11, 4, 2.00, 5000.00, 10000.00),
(39, 12, 4, 3.00, 4000.00, 12000.00),
(40, 13, 4, 2.00, 4000.00, 8000.00),
(41, 14, 1, 2.00, 4500.00, 9000.00),
(43, 16, 5, 1.50, 6500.00, 9750.00),
(44, 17, 4, 1.00, 4000.00, 4000.00),
(45, 17, 1, 1.00, 4500.00, 4500.00),
(46, 18, 4, 2.00, 4000.00, 8000.00),
(47, 18, 2, 3.00, 5000.00, 15000.00),
(50, 20, 5, 1.00, 6500.00, 6500.00),
(51, 20, 4, 1.00, 4000.00, 4000.00),
(52, 21, 8, 3.00, 2000.00, 6000.00),
(53, 19, 5, 3.00, 6500.00, 19500.00),
(54, 19, 4, 1.00, 4000.00, 4000.00),
(57, 22, 4, 3.00, 4000.00, 12000.00),
(58, 22, 1, 1.50, 4500.00, 6750.00),
(59, 22, 5, 2.00, 6500.00, 13000.00),
(60, 23, 4, 5.00, 4000.00, 20000.00),
(61, 23, 8, 5.00, 2000.00, 10000.00),
(62, 24, 4, 2.00, 4500.00, 9000.00);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id_jadwal` int(10) UNSIGNED NOT NULL,
  `judul` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id_jadwal`, `judul`, `tanggal`, `waktu`, `lokasi`, `deskripsi`) VALUES
(1, 'Sosialisasi Pemungutan Sampah', '2026-07-31', '09:00:00', 'Gedung Serbaguna Metro', 'Menghimbau untuk seluruh Warga Metro Serpong mengikuti kegiatan pemungutan sampah bersama di wilayah Metro Serpong 2.'),
(3, 'setoran sampah', '2026-07-29', '14:23:00', 'bank smpah', 'dafafaaf'),
(5, 'setoran sampah', '2026-07-30', '09:00:00', 'bank sampah metro 46', 'idaidnaknackac'),
(7, 'Pemberitahuan kegiatan tahunan', '2026-08-03', '14:00:00', 'Balai Warga', 'Melakukan kordinasi kepada seluruh warga'),
(8, 'Sosialisasi Pemilahan Sampah Yang Baik Dan Benar', '2026-08-30', '10:00:00', 'Balai Warga Metro Serpong', 'Membantu warga cara memilah sampah yang tepat.');

-- --------------------------------------------------------

--
-- Table structure for table `jenis_sampah`
--

CREATE TABLE `jenis_sampah` (
  `id_jenis` int(10) UNSIGNED NOT NULL,
  `nama_jenis` varchar(50) NOT NULL,
  `harga_per_kg` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jenis_sampah`
--

INSERT INTO `jenis_sampah` (`id_jenis`, `nama_jenis`, `harga_per_kg`) VALUES
(1, 'Botol Plastik', 4500.00),
(2, 'Kardus', 5000.00),
(4, 'Botol Kaca', 4500.00),
(5, 'Minyak Jelantah', 6500.00),
(8, 'Kertas', 2000.00);

-- --------------------------------------------------------

--
-- Table structure for table `nasabah`
--

CREATE TABLE `nasabah` (
  `id_nasabah` int(10) UNSIGNED NOT NULL,
  `kode_nasabah` varchar(20) DEFAULT NULL,
  `nama` varchar(255) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `telepon` varchar(20) NOT NULL,
  `tanggal_bergabung` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nasabah`
--

INSERT INTO `nasabah` (`id_nasabah`, `kode_nasabah`, `nama`, `alamat`, `telepon`, `tanggal_bergabung`) VALUES
(1, 'BS001', 'Hadi', 'Cibelut Rt 002', '08976543212', '2026-07-28'),
(2, 'BS002', 'Andi', 'Cibelut Rt 001', '089888999777', '2026-07-28'),
(3, 'BS003', 'Tati', 'Cibelut Rt 002', '0888999888', '2026-07-28'),
(4, 'BS004', 'Tegar', 'Bintaro', '08123456789', '2026-07-28'),
(8, 'BS005', 'Reyhan Apriansyah', 'Cibelut Rt 002', '0891827464', '2026-07-30'),
(9, 'BS006', 'Reza', 'Cibelut Rt001', '01987712424', '2026-07-30'),
(10, 'BS007', 'Reyhan', 'Cibelut Rt. 001', '0139139913', '2026-07-30'),
(11, 'BS008', 'RENDY', 'Blok 4 A', '098755434777876', '2026-08-03');

-- --------------------------------------------------------

--
-- Table structure for table `penarikan`
--

CREATE TABLE `penarikan` (
  `id_penarikan` int(10) UNSIGNED NOT NULL,
  `id_nasabah` int(10) UNSIGNED NOT NULL,
  `id_admin` int(10) UNSIGNED NOT NULL,
  `tanggal_penarikan` date NOT NULL,
  `nominal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penarikan`
--

INSERT INTO `penarikan` (`id_penarikan`, `id_nasabah`, `id_admin`, `tanggal_penarikan`, `nominal`, `keterangan`, `created_at`) VALUES
(2, 2, 1, '2026-07-30', 100000.00, 'pencairan tabungan pada saat tutup buku', '2026-07-30 08:24:29'),
(3, 2, 1, '2026-07-30', 45000.00, 'keperluan mendadak', '2026-07-30 08:25:31'),
(4, 3, 1, '2026-07-30', 25500.00, 'pencairan tabungan nasabah', '2026-07-30 08:40:27'),
(5, 2, 1, '2026-08-03', 20000.00, 'penari,kan lebaran', '2026-08-03 02:39:07'),
(8, 4, 1, '2026-08-11', 23000.00, 'Penarikan untuk acara 17 Agustus', '2026-08-11 10:04:14'),
(9, 2, 1, '2026-08-11', 10000.00, 'penarikan tunai', '2026-08-11 14:43:40'),
(11, 3, 1, '2026-08-11', 20000.00, 'Penarikan Uang', '2026-08-11 15:03:46');

-- --------------------------------------------------------

--
-- Table structure for table `profil`
--

CREATE TABLE `profil` (
  `id_profil` int(10) UNSIGNED NOT NULL,
  `nama_bank_sampah` varchar(100) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `telepon` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profil`
--

INSERT INTO `profil` (`id_profil`, `nama_bank_sampah`, `alamat`, `telepon`, `email`, `deskripsi`, `logo`) VALUES
(1, 'Bank Sampah Metro 46', 'cibogo kecamatan cisauk kabupaten tangerang', '08888999777', 'banksampah46@gmail.com', 'bank sampah untuk menampung sampah yg masih bernilai', 'logo_20260811175231_f82be931.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(10) UNSIGNED NOT NULL,
  `id_nasabah` int(10) UNSIGNED NOT NULL,
  `tanggal_setoran` date NOT NULL,
  `total_berat` decimal(8,2) NOT NULL DEFAULT 0.00,
  `total_saldo` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_nasabah`, `tanggal_setoran`, `total_berat`, `total_saldo`) VALUES
(6, 2, '2026-07-30', 7.00, 39500.00),
(7, 3, '2026-07-30', 14.00, 75500.00),
(8, 8, '2026-07-30', 3.00, 18000.00),
(9, 2, '2026-07-29', 5.00, 24000.00),
(10, 2, '2026-07-30', 21.00, 104000.00),
(11, 4, '2026-07-30', 2.00, 10000.00),
(12, 4, '2026-07-30', 3.00, 12000.00),
(13, 2, '2026-07-30', 2.00, 8000.00),
(14, 2, '2026-07-30', 2.00, 9000.00),
(16, 2, '2026-07-30', 1.50, 9750.00),
(17, 2, '2026-07-30', 2.00, 8500.00),
(18, 2, '2026-07-30', 5.00, 23000.00),
(19, 2, '2026-07-31', 4.00, 23500.00),
(20, 2, '2026-07-28', 2.00, 10500.00),
(21, 2, '2026-07-27', 3.00, 6000.00),
(22, 4, '2026-07-30', 6.50, 31750.00),
(23, 2, '2026-08-11', 10.00, 30000.00),
(24, 4, '2026-08-11', 2.00, 9000.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `uk_username` (`username`);

--
-- Indexes for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `fk_detail_transaksi` (`id_transaksi`),
  ADD KEY `fk_detail_jenis` (`id_jenis`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id_jadwal`);

--
-- Indexes for table `jenis_sampah`
--
ALTER TABLE `jenis_sampah`
  ADD PRIMARY KEY (`id_jenis`);

--
-- Indexes for table `nasabah`
--
ALTER TABLE `nasabah`
  ADD PRIMARY KEY (`id_nasabah`),
  ADD UNIQUE KEY `kode_nasabah` (`kode_nasabah`);

--
-- Indexes for table `penarikan`
--
ALTER TABLE `penarikan`
  ADD PRIMARY KEY (`id_penarikan`),
  ADD KEY `fk_penarikan_nasabah` (`id_nasabah`),
  ADD KEY `fk_penarikan_admin` (`id_admin`);

--
-- Indexes for table `profil`
--
ALTER TABLE `profil`
  ADD PRIMARY KEY (`id_profil`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `fk_transaksi_nasabah` (`id_nasabah`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `jenis_sampah`
--
ALTER TABLE `jenis_sampah`
  MODIFY `id_jenis` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `nasabah`
--
ALTER TABLE `nasabah`
  MODIFY `id_nasabah` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `penarikan`
--
ALTER TABLE `penarikan`
  MODIFY `id_penarikan` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `profil`
--
ALTER TABLE `profil`
  MODIFY `id_profil` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `fk_detail_jenis` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_sampah` (`id_jenis`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `penarikan`
--
ALTER TABLE `penarikan`
  ADD CONSTRAINT `fk_penarikan_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penarikan_nasabah` FOREIGN KEY (`id_nasabah`) REFERENCES `nasabah` (`id_nasabah`) ON UPDATE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_nasabah` FOREIGN KEY (`id_nasabah`) REFERENCES `nasabah` (`id_nasabah`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
