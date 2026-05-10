-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 10, 2026 at 07:46 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_ukssmartcare`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int NOT NULL,
  `nama_lengkap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'default.png',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_expired` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `nama_lengkap`, `kelas`, `foto`, `username`, `password`, `reset_token`, `token_expired`, `created_at`) VALUES
(75, 'Muhammad Zaky Arrosyid', 'XI RPL 1', '1778312607_Zaky.jpeg', 'Zaky', '$2y$10$wYJehMVerWuFlWlklK8Vy.SaVoIaqgR3kvEU8weRCbcCDFWp8RiKq', NULL, NULL, '2026-05-09 07:43:27'),
(76, 'Lutviatu Shafira', 'XI TKJ 1', '1778314055_Pira.jpg', 'Pira', '$2y$10$RyaawzY7mdaVm0eo6D9OSOp3ye5XWvWFG3JYqb8UMUWnNDEFxoBE6', NULL, NULL, '2026-05-09 08:07:35'),
(77, 'Alysabella Zahirazeta Mylova', 'XI DKV 1', '1778340589_Bella.jpeg', 'Bella', '$2y$10$.c2Dm/pOOKpPbc8.bdrEAeyOYBf95QEtWhuyr5nyE3oz8HqMeCzuS', NULL, NULL, '2026-05-09 15:29:49'),
(78, 'Revan Oknanda', 'XI RPL 1', 'admin_78_1778376948.jpeg', 'Oknanda', '$2y$10$YENxZSFd6YrUtCVn6JzJtueSjwDdbWJpzUjyecrwch9MekUfa00.S', NULL, NULL, '2026-05-10 01:24:53'),
(79, 'Zaki Fairuz', 'XI TOI 2', 'admin_79_1778377699.png', 'Fairuz', '$2y$10$hMFpfrKZhvCSBVzY.i.04uqqn.u7tN8E.KKiosXISBLdqKRNPZe86', NULL, NULL, '2026-05-10 01:42:16');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int NOT NULL,
  `rating` int NOT NULL,
  `kategori` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pesan` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `rating`, `kategori`, `pesan`, `created_at`) VALUES
(1, 4, 'Pelayanan', 'Keren', '2026-03-04 04:53:47'),
(3, 5, 'Pelayanan, Ketersediaan Obat', 'Keren  Banget UKS', '2026-04-13 07:03:17'),
(4, 5, 'Ketersediaan Obat, Pelayanan', 'UKSnya bersih', '2026-04-16 02:54:12'),
(7, 5, 'Pelayanan', 'bagus', '2026-04-21 14:58:25'),
(8, 5, 'Laporan Bug, Lainnya', 'uks rusak', '2026-05-08 10:22:30'),
(10, 4, 'Pelayanan, Waktu Layanan', 'Petugas UKSnya ramah dan tepat waktu', '2026-05-09 12:54:05');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id_jadwal` int NOT NULL,
  `id_admin` int DEFAULT NULL,
  `minggu_ke` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kode_jaga` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id_jadwal`, `id_admin`, `minggu_ke`, `kode_jaga`) VALUES
(10, 75, '2', '3C'),
(11, 78, '2', '3A'),
(12, 79, '1', '4A');

-- --------------------------------------------------------

--
-- Table structure for table `obat`
--

CREATE TABLE `obat` (
  `id_obat` int NOT NULL,
  `nama_obat` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `stok` int NOT NULL,
  `kategori` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('tersedia','hampir_habis','habis') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `obat`
--

INSERT INTO `obat` (`id_obat`, `nama_obat`, `stok`, `kategori`, `status`, `created_at`) VALUES
(5, 'Paracetamol', 390, 'Tablet', 'tersedia', '2026-05-07 15:49:39'),
(6, 'Sangobion', 2500, 'Tablet', 'tersedia', '2026-05-07 15:49:39'),
(7, 'Etabion', 9, 'Tablet', 'hampir_habis', '2026-05-07 15:49:39'),
(8, 'Flutamol', 99, 'Tablet', 'tersedia', '2026-05-07 15:49:39'),
(9, 'Amlodipine Besilate', 18, 'Tablet', 'tersedia', '2026-05-07 15:49:39'),
(10, 'C2fit0Diapet', 4, 'Tablet', 'hampir_habis', '2026-05-07 15:49:39'),
(11, 'Komix Biru', 29, 'Sachet', 'tersedia', '2026-05-07 15:49:39'),
(12, 'Antangin', 12, 'Sachet', 'tersedia', '2026-05-07 15:49:39'),
(13, 'Thromecon Gel', 2, 'Tube', 'tersedia', '2026-05-07 15:49:39'),
(14, 'Bioplaceton', 1, 'Tube', 'hampir_habis', '2026-05-07 15:49:39'),
(15, 'Getamicin Sulfate', 2, 'Tube', 'tersedia', '2026-05-07 15:49:39'),
(16, 'Hot in Cream', 1, 'Tube', 'hampir_habis', '2026-05-07 15:49:39'),
(17, 'GPU', 2, 'Botol', 'tersedia', '2026-05-07 15:49:39'),
(18, 'Minyak Kayu Putih', 1, 'Tube', 'hampir_habis', '2026-05-07 15:49:39'),
(19, 'Salonpas', 20, 'Sachet', 'tersedia', '2026-05-07 15:49:39'),
(20, 'Rohto Obat Tetes Mata', 1, 'Tube', 'hampir_habis', '2026-05-07 15:49:39'),
(22, 'Oxycan', 5, 'Can', 'tersedia', '2026-05-07 15:49:39'),
(23, 'Betadine', 3, 'Tube', 'hampir_habis', '2026-05-07 15:49:39'),
(24, 'Kasa Steril', 60, 'Lembar', 'tersedia', '2026-05-07 15:49:39'),
(25, 'Kasa Gulung', 7, 'Gulung', 'tersedia', '2026-05-07 15:49:39'),
(26, 'Cataflam', 30, 'Tablet', 'tersedia', '2026-05-07 15:49:39'),
(27, 'Minyak Tawon', 1, 'Botol', 'hampir_habis', '2026-05-07 15:49:39'),
(28, 'Easy Touch', 1, 'Tube', 'hampir_habis', '2026-05-07 15:49:39'),
(29, 'Alcohol Swabs', 100, 'Swabs', 'tersedia', '2026-05-07 15:49:39'),
(30, 'Plester Bening', 2, 'Buah', 'tersedia', '2026-05-07 15:49:39'),
(31, 'Thrombopop', 1, 'Tube', 'hampir_habis', '2026-05-07 15:49:39'),
(32, 'Handsaplast', 200, 'Lembar', 'tersedia', '2026-05-07 15:49:39'),
(33, 'Panadol', 10, 'Tablet', 'hampir_habis', '2026-05-10 01:30:26'),
(34, 'Polycilane', 20, 'Tablet', 'tersedia', '2026-05-10 01:45:33');

-- --------------------------------------------------------

--
-- Table structure for table `permintaan_obat`
--

CREATE TABLE `permintaan_obat` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabatan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keluhan` text COLLATE utf8mb4_general_ci NOT NULL,
  `id_obat` int NOT NULL,
  `status_permintaan` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `waktu_pengajuan` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_pengambilan` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permintaan_obat`
--

INSERT INTO `permintaan_obat` (`id`, `nama`, `status`, `kelas`, `jabatan`, `keluhan`, `id_obat`, `status_permintaan`, `waktu_pengajuan`, `waktu_pengambilan`) VALUES
(38, 'Muhammad Zaky Arrosyid', 'siswa', 'XI RPL 1', '', 'Demam', 5, 'approved', '2026-05-09 05:00:05', '2026-05-09 05:02:04'),
(39, 'Muhammad Zaky Arrosyid', 'siswa', 'XI RPL 1', '', 'Demam', 5, 'approved', '2026-05-09 06:28:38', '2026-05-09 06:28:45'),
(40, 'Asa Enggal D', 'siswa', 'XI RPL 1', '', 'Panas', 5, 'rejected', '2026-05-09 08:21:50', NULL),
(41, 'Asa Enggal Daviyana', 'siswa', 'XI RPL 1', '', 'Panas', 5, 'expired', '2026-05-09 12:52:09', NULL),
(42, 'Muhammad Zaky Arrosyid', 'siswa', 'XI RPL 1', '', 'Demam', 5, 'approved', '2026-05-09 15:31:25', '2026-05-09 15:33:03'),
(43, 'Asa Enggal Daviana', 'siswa', 'XI RPL 1', '', 'Demam', 5, 'approved', '2026-05-10 01:26:12', '2026-05-10 01:26:28'),
(44, 'Muhammad Zaky Arrosyid', 'siswa', 'XI RPL 1', '', 'Flu', 8, 'approved', '2026-05-10 01:40:54', '2026-05-10 01:43:01'),
(45, 'Muhammad Zaky Arrosyid', 'siswa', 'XI RPL 1', '', 'Demam', 16, 'approved', '2026-05-10 04:08:53', '2026-05-10 04:09:18'),
(46, 'Siti Munipah', 'guru', '', 'Guru TOI', 'Luka', 23, 'approved', '2026-05-10 04:24:00', '2026-05-10 04:24:14');

-- --------------------------------------------------------

--
-- Table structure for table `saran_obat`
--

CREATE TABLE `saran_obat` (
  `id_saran` int NOT NULL,
  `id_obat` int NOT NULL,
  `penjelasan` text NOT NULL,
  `kategori` enum('Demam','Pencernaan','Luka','Nyeri','Vitamin','Umum') DEFAULT 'Umum',
  `jenis` enum('Non-Resep','Resep','Suplemen','Cairan') DEFAULT 'Non-Resep',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `saran_obat`
--

INSERT INTO `saran_obat` (`id_saran`, `id_obat`, `penjelasan`, `kategori`, `jenis`, `created_at`, `updated_at`) VALUES
(2, 5, 'Paracetamol adalah obat untuk meredakan penyakit nyeri kepala, demam, pusing, dan flu', 'Demam', 'Non-Resep', '2026-05-08 10:26:04', '2026-05-08 10:26:36'),
(3, 6, 'Sangobion digunakan untuk membantu mengatasi dan mencegah anemia atau kekurangan zat besi.', 'Vitamin', 'Suplemen', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(4, 7, 'Etabion membantu memenuhi kebutuhan vitamin dan menjaga daya tahan tubuh.', 'Vitamin', 'Suplemen', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(5, 8, 'Flutamol digunakan untuk meredakan gejala flu seperti demam, pilek, dan sakit kepala.', 'Demam', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(6, 9, 'Amlodipine Besilate digunakan untuk membantu menurunkan tekanan darah tinggi.', 'Nyeri', 'Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(7, 10, 'C2fit Diapet digunakan untuk membantu meredakan diare dan gangguan pencernaan.', 'Pencernaan', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(8, 26, 'Cataflam digunakan untuk meredakan nyeri dan peradangan.', 'Nyeri', 'Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(9, 11, 'Komix Biru digunakan untuk membantu meredakan batuk dan tenggorokan gatal.', 'Demam', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(10, 12, 'Antangin membantu meredakan masuk angin, mual, dan perut kembung.', 'Pencernaan', 'Cairan', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(11, 19, 'Salonpas digunakan untuk membantu meredakan pegal dan nyeri otot.', 'Nyeri', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(12, 13, 'Thromecon Gel digunakan untuk membantu mengurangi memar dan nyeri otot.', 'Nyeri', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(13, 14, 'Bioplacenton digunakan untuk membantu penyembuhan luka dan infeksi kulit ringan.', 'Luka', 'Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(14, 15, 'Gentamicin Sulfate digunakan untuk mengatasi infeksi kulit akibat bakteri.', 'Luka', 'Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(15, 16, 'Hot in Cream digunakan untuk membantu meredakan pegal dan nyeri otot.', 'Nyeri', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(16, 18, 'Minyak Kayu Putih digunakan untuk membantu menghangatkan tubuh dan meredakan masuk angin.', 'Pencernaan', 'Cairan', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(17, 20, 'Rohto Obat Tetes Mata digunakan untuk membantu meredakan mata merah dan iritasi ringan.', 'Luka', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(18, 23, 'Betadine digunakan sebagai antiseptik untuk membantu membersihkan luka.', 'Luka', 'Cairan', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(19, 28, 'Easy Touch digunakan untuk membantu perawatan luka ringan.', 'Luka', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(20, 17, 'GPU digunakan untuk membantu meredakan sakit perut dan gangguan pencernaan.', 'Pencernaan', 'Cairan', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(21, 27, 'Minyak Tawon digunakan untuk membantu meredakan pegal, gatal, dan masuk angin.', 'Nyeri', 'Cairan', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(22, 22, 'Oxycan membantu menyediakan oksigen tambahan untuk pernapasan.', 'Umum', 'Cairan', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(23, 24, 'Kasa Steril digunakan untuk membersihkan dan menutup luka agar tetap higienis.', 'Luka', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(24, 25, 'Kasa Gulung digunakan untuk membalut luka dan menghentikan pendarahan ringan.', 'Luka', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(25, 29, 'Alcohol Swabs digunakan untuk membarsihkan luka sebagai pengganti NACL', 'Luka', 'Cairan', '2026-05-09 06:59:33', '2026-05-10 01:31:34'),
(26, 30, 'Plester Bening digunakan untuk melindungi luka kecil dari kotoran dan bakteri.', 'Luka', 'Non-Resep', '2026-05-09 06:59:33', '2026-05-09 06:59:33'),
(27, 32, 'Handsaplast Digunakan untuk melapisi luka terbuka secara sementara', 'Luka', 'Non-Resep', '2026-05-09 08:09:50', '2026-05-09 08:09:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `user_id` (`id_admin`);

--
-- Indexes for table `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id_obat`);

--
-- Indexes for table `permintaan_obat`
--
ALTER TABLE `permintaan_obat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_obat` (`id_obat`);

--
-- Indexes for table `saran_obat`
--
ALTER TABLE `saran_obat`
  ADD PRIMARY KEY (`id_saran`),
  ADD KEY `id_obat` (`id_obat`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `obat`
--
ALTER TABLE `obat`
  MODIFY `id_obat` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `permintaan_obat`
--
ALTER TABLE `permintaan_obat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `saran_obat`
--
ALTER TABLE `saran_obat`
  MODIFY `id_saran` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE CASCADE;

--
-- Constraints for table `permintaan_obat`
--
ALTER TABLE `permintaan_obat`
  ADD CONSTRAINT `fk_obat` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id_obat`) ON UPDATE CASCADE;

--
-- Constraints for table `saran_obat`
--
ALTER TABLE `saran_obat`
  ADD CONSTRAINT `saran_obat_ibfk_1` FOREIGN KEY (`id_obat`) REFERENCES `obat` (`id_obat`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
