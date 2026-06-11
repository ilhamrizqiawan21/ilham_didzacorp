-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 24 Apr 2026 pada 06.11
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pembelajaran_quran`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) DEFAULT NULL,
  `pertemuan_id` int(11) DEFAULT NULL,
  `status` enum('hadir','sakit','izin','alpha') NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `absensi`
--

INSERT INTO `absensi` (`id`, `siswa_id`, `pertemuan_id`, `status`, `keterangan`) VALUES
(49, 108, 52, 'sakit', NULL),
(50, 109, 52, 'hadir', NULL),
(51, 110, 52, 'hadir', NULL),
(52, 111, 52, 'hadir', NULL),
(53, 140, 53, 'hadir', NULL),
(54, 141, 53, 'hadir', NULL),
(55, 142, 53, 'hadir', NULL),
(56, 143, 53, 'hadir', NULL),
(57, 144, 53, 'hadir', NULL),
(58, 145, 53, 'hadir', NULL),
(59, 146, 53, 'hadir', NULL),
(60, 147, 53, 'hadir', NULL),
(61, 148, 53, 'hadir', NULL),
(62, 149, 53, 'hadir', NULL),
(63, 150, 53, 'hadir', NULL),
(64, 151, 53, 'hadir', NULL),
(65, 152, 53, 'hadir', NULL),
(66, 153, 53, 'hadir', NULL),
(67, 155, 53, 'hadir', NULL),
(68, 159, 53, 'hadir', NULL),
(69, 154, 53, 'hadir', NULL),
(70, 156, 53, 'hadir', NULL),
(71, 157, 53, 'hadir', NULL),
(72, 158, 53, 'hadir', NULL),
(73, 160, 53, 'hadir', NULL),
(74, 161, 53, 'hadir', NULL),
(75, 162, 53, 'hadir', NULL),
(76, 163, 53, 'hadir', NULL),
(77, 164, 53, 'hadir', NULL),
(78, 165, 53, 'hadir', NULL),
(79, 166, 53, 'hadir', NULL),
(80, 167, 53, 'hadir', NULL),
(81, 168, 53, 'hadir', NULL),
(82, 169, 53, 'hadir', NULL),
(83, 170, 53, 'sakit', NULL),
(84, 171, 53, 'hadir', NULL),
(85, 30, 53, 'hadir', NULL),
(86, 31, 53, 'hadir', NULL),
(87, 32, 53, 'hadir', NULL),
(88, 33, 53, 'hadir', NULL),
(89, 34, 53, 'hadir', NULL),
(90, 35, 53, 'hadir', NULL),
(91, 36, 53, 'hadir', NULL),
(92, 37, 53, 'alpha', NULL),
(93, 38, 53, 'hadir', NULL),
(94, 39, 53, 'hadir', NULL),
(95, 40, 53, 'hadir', NULL),
(96, 41, 53, 'hadir', NULL),
(97, 42, 53, 'sakit', NULL),
(98, 43, 53, 'hadir', NULL),
(99, 45, 53, 'hadir', NULL),
(100, 46, 53, 'hadir', NULL),
(101, 47, 53, 'hadir', NULL),
(102, 48, 53, 'sakit', NULL),
(103, 49, 53, 'hadir', NULL),
(104, 50, 53, 'hadir', NULL),
(105, 51, 53, 'alpha', NULL),
(106, 52, 53, 'hadir', NULL),
(107, 53, 53, 'hadir', NULL),
(108, 54, 53, 'hadir', NULL),
(109, 55, 53, 'hadir', NULL),
(110, 56, 53, 'hadir', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `atp`
--

CREATE TABLE `atp` (
  `id` int(11) NOT NULL,
  `bab_id` int(11) DEFAULT NULL,
  `alur_tujuan` text NOT NULL,
  `materi` text NOT NULL,
  `alokasi_waktu` varchar(20) DEFAULT NULL,
  `semester` enum('1','2') DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `atp`
--

INSERT INTO `atp` (`id`, `bab_id`, `alur_tujuan`, `materi`, `alokasi_waktu`, `semester`) VALUES
(1, 1, 'tes', 'tes', '4 JP', '2');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bab`
--

CREATE TABLE `bab` (
  `id` int(11) NOT NULL,
  `judul_bab` varchar(100) NOT NULL,
  `tujuan_pembelajaran` text DEFAULT NULL,
  `materi_pokok` text DEFAULT NULL,
  `alokasi_waktu` varchar(20) DEFAULT NULL,
  `file_materi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bab`
--

INSERT INTO `bab` (`id`, `judul_bab`, `tujuan_pembelajaran`, `materi_pokok`, `alokasi_waktu`, `file_materi`) VALUES
(1, 'MENJAUHI GAYA HIDUP HEDONIS, KONSUMTIF DAN MATERIALISTIS', 'Peserta didik memahami arti gaya hidup hedonis, konsumtif dan materialistis', 'Q.S Al Qoshos 77 dan Q.S Asy Syuro 67', '4 JP', '1776003292.pdf');

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `kelas_id`, `message`, `created_at`, `is_read`) VALUES
(1, 109, 8, '𝘈𝘴𝘴𝘢𝘭𝘢𝘮𝘶𝘢𝘭𝘢𝘪𝘬𝘶𝘮', '2026-04-18 20:05:22', 0),
(2, 109, 8, '𝘛𝘦𝘴𝘵', '2026-04-18 20:17:13', 0),
(3, 1, 8, 'test', '2026-04-18 20:21:32', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `cp`
--

CREATE TABLE `cp` (
  `id` int(11) NOT NULL,
  `elemen` varchar(100) NOT NULL,
  `capaian_pembelajaran` text NOT NULL,
  `semester` enum('1','2') DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cp`
--

INSERT INTO `cp` (`id`, `elemen`, `capaian_pembelajaran`, `semester`) VALUES
(1, 'hadis', 'tes', '2');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jurnal`
--

CREATE TABLE `jurnal` (
  `id` int(11) NOT NULL,
  `bab_id` int(11) DEFAULT NULL,
  `alur_tujuan` text NOT NULL,
  `materi` varchar(200) NOT NULL,
  `hari_tanggal` date NOT NULL,
  `jam_ke` varchar(20) DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jurnal`
--

INSERT INTO `jurnal` (`id`, `bab_id`, `alur_tujuan`, `materi`, `hari_tanggal`, `jam_ke`, `keterangan`) VALUES
(1, 1, 'Siswa dapat memahami makna gaya hidup Materialistik, Hedonis, dan Konsumtif', 'Q.S Al Furqon ayat 67', '2026-04-16', '7-8', 'Siswa tidak hadir sebanyak 2 orang');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(20) NOT NULL,
  `tingkat` enum('VII','VIII','IX') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `tingkat`) VALUES
(1, 'VIII-C', 'VIII'),
(3, 'VIII-A', 'VIII'),
(7, 'VIII-E', 'VIII'),
(8, 'VII-A', 'VII'),
(9, 'VII-B', 'VII'),
(10, 'VII-C', 'VII'),
(11, 'VII-D', 'VII');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kktp`
--

CREATE TABLE `kktp` (
  `id` int(11) NOT NULL,
  `bab_id` int(11) DEFAULT NULL,
  `alur_tujuan` text NOT NULL,
  `skala_0_40` varchar(255) DEFAULT NULL,
  `skala_41_65` varchar(255) DEFAULT NULL,
  `skala_66_85` varchar(255) DEFAULT NULL,
  `skala_86_100` varchar(255) DEFAULT NULL,
  `semester` enum('1','2') DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kktp`
--

INSERT INTO `kktp` (`id`, `bab_id`, `alur_tujuan`, `skala_0_40`, `skala_41_65`, `skala_66_85`, `skala_86_100`, `semester`) VALUES
(1, 1, 'tes', 'tes', 'tes', 'tes', 'tes', '2');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lkpd`
--

CREATE TABLE `lkpd` (
  `id` int(11) NOT NULL,
  `bab_id` int(11) DEFAULT NULL,
  `judul` varchar(200) NOT NULL,
  `petunjuk_belajar` text DEFAULT NULL,
  `tujuan_pembelajaran` text DEFAULT NULL,
  `materi_pokok` text DEFAULT NULL,
  `tugas` text DEFAULT NULL,
  `rubrik` text DEFAULT NULL,
  `file_lampiran` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lkpd`
--

INSERT INTO `lkpd` (`id`, `bab_id`, `judul`, `petunjuk_belajar`, `tujuan_pembelajaran`, `materi_pokok`, `tugas`, `rubrik`, `file_lampiran`, `created_at`) VALUES
(1, 1, 'tes', 'tes', 'tes', 'tes', 'tes', 'tes', '', '2026-04-20 19:01:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `modul_ajar`
--

CREATE TABLE `modul_ajar` (
  `id` int(11) NOT NULL,
  `bab_id` int(11) DEFAULT NULL,
  `identitas_madrasah` varchar(200) DEFAULT NULL,
  `nama_penyusun` varchar(100) DEFAULT NULL,
  `nuptk` varchar(50) DEFAULT NULL,
  `mapel` varchar(100) DEFAULT NULL,
  `kelas_semester` varchar(50) DEFAULT NULL,
  `alokasi_waktu_total` varchar(20) DEFAULT NULL,
  `tahun_pelajaran` varchar(20) DEFAULT NULL,
  `pengetahuan_awal` text DEFAULT NULL,
  `minat` text DEFAULT NULL,
  `latar_belakang` text DEFAULT NULL,
  `kebutuhan_belajar` text DEFAULT NULL,
  `topik_panca_cinta` varchar(200) DEFAULT NULL,
  `materi_insersi` text DEFAULT NULL,
  `jenis_pengetahuan` text DEFAULT NULL,
  `relevansi` text DEFAULT NULL,
  `tingkat_kesulitan` varchar(50) DEFAULT NULL,
  `struktur_materi` text DEFAULT NULL,
  `integrasi_nilai` text DEFAULT NULL,
  `profil_keimanan` text DEFAULT NULL,
  `profil_kewargaan` text DEFAULT NULL,
  `profil_nalar_kritis` text DEFAULT NULL,
  `profil_kreativitas` text DEFAULT NULL,
  `profil_kolaborasi` text DEFAULT NULL,
  `profil_kemandirian` text DEFAULT NULL,
  `profil_kesehatan` text DEFAULT NULL,
  `profil_komunikasi` text DEFAULT NULL,
  `cp_tajwid` text DEFAULT NULL,
  `lintas_displin` text DEFAULT NULL,
  `tujuan_pembelajaran_1` text DEFAULT NULL,
  `tujuan_pembelajaran_2` text DEFAULT NULL,
  `tujuan_pembelajaran_3` text DEFAULT NULL,
  `tujuan_pembelajaran_4` text DEFAULT NULL,
  `indikator` text DEFAULT NULL,
  `iklim_budaya` text DEFAULT NULL,
  `topik_kontekstual` text DEFAULT NULL,
  `model_pembelajaran` text DEFAULT NULL,
  `pendekatan` text DEFAULT NULL,
  `metode` text DEFAULT NULL,
  `strategi_diferensiasi` text DEFAULT NULL,
  `kemitraan_sekolah` text DEFAULT NULL,
  `kemitraan_masyarakat` text DEFAULT NULL,
  `mitra_digital` text DEFAULT NULL,
  `lingkungan_fisik` text DEFAULT NULL,
  `lingkungan_virtual` text DEFAULT NULL,
  `budaya_belajar` text DEFAULT NULL,
  `pemanfaatan_digital` text DEFAULT NULL,
  `langkah_pertemuan_1` text DEFAULT NULL,
  `langkah_pertemuan_2` text DEFAULT NULL,
  `langkah_pertemuan_3` text DEFAULT NULL,
  `langkah_pertemuan_4` text DEFAULT NULL,
  `asesmen_diagnostik` text DEFAULT NULL,
  `asesmen_formatif` text DEFAULT NULL,
  `asesmen_sumatif` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `modul_ajar`
--

INSERT INTO `modul_ajar` (`id`, `bab_id`, `identitas_madrasah`, `nama_penyusun`, `nuptk`, `mapel`, `kelas_semester`, `alokasi_waktu_total`, `tahun_pelajaran`, `pengetahuan_awal`, `minat`, `latar_belakang`, `kebutuhan_belajar`, `topik_panca_cinta`, `materi_insersi`, `jenis_pengetahuan`, `relevansi`, `tingkat_kesulitan`, `struktur_materi`, `integrasi_nilai`, `profil_keimanan`, `profil_kewargaan`, `profil_nalar_kritis`, `profil_kreativitas`, `profil_kolaborasi`, `profil_kemandirian`, `profil_kesehatan`, `profil_komunikasi`, `cp_tajwid`, `lintas_displin`, `tujuan_pembelajaran_1`, `tujuan_pembelajaran_2`, `tujuan_pembelajaran_3`, `tujuan_pembelajaran_4`, `indikator`, `iklim_budaya`, `topik_kontekstual`, `model_pembelajaran`, `pendekatan`, `metode`, `strategi_diferensiasi`, `kemitraan_sekolah`, `kemitraan_masyarakat`, `mitra_digital`, `lingkungan_fisik`, `lingkungan_virtual`, `budaya_belajar`, `pemanfaatan_digital`, `langkah_pertemuan_1`, `langkah_pertemuan_2`, `langkah_pertemuan_3`, `langkah_pertemuan_4`, `asesmen_diagnostik`, `asesmen_formatif`, `asesmen_sumatif`, `created_at`) VALUES
(1, 1, 'MTs. Al-Ihsan Batujajar', 'ILHAM RIZQIAWAN, S.Pd.', '', 'Al-Qur\'an Hadis', 'VIII / Ganjil', '8 JP', '2025/2026', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'Sedang', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'PBL', 'Saintifik', 'Diskusi, Tanya Jawab', '', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', '2026-04-16 20:10:15');

-- --------------------------------------------------------

--
-- Struktur dari tabel `nilai_akhir`
--

CREATE TABLE `nilai_akhir` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `semester` enum('1','2') NOT NULL,
  `tahun_ajaran` varchar(10) NOT NULL,
  `sum1` decimal(5,2) DEFAULT NULL,
  `sum2` decimal(5,2) DEFAULT NULL,
  `sum3` decimal(5,2) DEFAULT NULL,
  `sum4` decimal(5,2) DEFAULT NULL,
  `sts` decimal(5,2) DEFAULT NULL,
  `sas_asli` decimal(5,2) DEFAULT NULL,
  `sas_jadi` decimal(5,2) DEFAULT NULL,
  `sat_asli` decimal(5,2) DEFAULT NULL,
  `sat_jadi` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `nilai_akhir`
--

INSERT INTO `nilai_akhir` (`id`, `siswa_id`, `semester`, `tahun_ajaran`, `sum1`, `sum2`, `sum3`, `sum4`, `sts`, `sas_asli`, `sas_jadi`, `sat_asli`, `sat_jadi`) VALUES
(27, 30, '2', '2025/2026', 80.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 31, '2', '2025/2026', 80.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, 32, '2', '2025/2026', 80.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `nilai_tugas`
--

CREATE TABLE `nilai_tugas` (
  `id` int(11) NOT NULL,
  `tugas_id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `status` enum('sudah','belum') DEFAULT 'belum',
  `nilai` decimal(5,2) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `key` varchar(50) NOT NULL,
  `value` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `key`, `value`) VALUES
(1, 'tahun_ajaran_aktif', '2025/2026'),
(2, 'semester_aktif', '2');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumpulan_tugas`
--

CREATE TABLE `pengumpulan_tugas` (
  `id` int(11) NOT NULL,
  `tugas_id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `status` enum('belum','sudah') DEFAULT 'belum',
  `nilai` decimal(5,2) DEFAULT NULL,
  `file_upload` varchar(255) DEFAULT NULL,
  `teks_jawaban` text DEFAULT NULL,
  `tanggal_kumpul` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `isi` text NOT NULL,
  `target` enum('semua','kelas') DEFAULT 'semua',
  `kelas_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `judul`, `isi`, `target`, `kelas_id`, `created_by`, `created_at`) VALUES
(1, 'test', 'test', 'kelas', 8, 1, '2026-04-18 20:06:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pertemuan`
--

CREATE TABLE `pertemuan` (
  `id` int(11) NOT NULL,
  `bab_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `topik` varchar(255) DEFAULT NULL,
  `jam_ke` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pertemuan`
--

INSERT INTO `pertemuan` (`id`, `bab_id`, `tanggal`, `topik`, `jam_ke`) VALUES
(4, NULL, '2026-07-01', 'Minggu ke-1 Bulan July', 1),
(5, NULL, '2026-07-08', 'Minggu ke-2 Bulan July', 1),
(6, NULL, '2026-07-15', 'Minggu ke-3 Bulan July', 1),
(7, NULL, '2026-07-22', 'Minggu ke-4 Bulan July', 1),
(8, NULL, '2026-08-01', 'Minggu ke-1 Bulan August', 1),
(9, NULL, '2026-08-08', 'Minggu ke-2 Bulan August', 1),
(10, NULL, '2026-08-15', 'Minggu ke-3 Bulan August', 1),
(11, NULL, '2026-08-22', 'Minggu ke-4 Bulan August', 1),
(12, NULL, '2026-09-01', 'Minggu ke-1 Bulan September', 1),
(13, NULL, '2026-09-08', 'Minggu ke-2 Bulan September', 1),
(14, NULL, '2026-09-15', 'Minggu ke-3 Bulan September', 1),
(15, NULL, '2026-09-22', 'Minggu ke-4 Bulan September', 1),
(16, NULL, '2026-10-01', 'Minggu ke-1 Bulan October', 1),
(17, NULL, '2026-10-08', 'Minggu ke-2 Bulan October', 1),
(18, NULL, '2026-10-15', 'Minggu ke-3 Bulan October', 1),
(19, NULL, '2026-10-22', 'Minggu ke-4 Bulan October', 1),
(20, NULL, '2026-11-01', 'Minggu ke-1 Bulan November', 1),
(21, NULL, '2026-11-08', 'Minggu ke-2 Bulan November', 1),
(22, NULL, '2026-11-15', 'Minggu ke-3 Bulan November', 1),
(23, NULL, '2026-11-22', 'Minggu ke-4 Bulan November', 1),
(24, NULL, '2026-12-01', 'Minggu ke-1 Bulan December', 1),
(25, NULL, '2026-12-08', 'Minggu ke-2 Bulan December', 1),
(26, NULL, '2026-12-15', 'Minggu ke-3 Bulan December', 1),
(27, NULL, '2026-12-22', 'Minggu ke-4 Bulan December', 1),
(28, NULL, '2027-01-01', 'Minggu ke-1 Bulan January', 1),
(29, NULL, '2027-01-08', 'Minggu ke-2 Bulan January', 1),
(30, NULL, '2027-01-15', 'Minggu ke-3 Bulan January', 1),
(31, NULL, '2027-01-22', 'Minggu ke-4 Bulan January', 1),
(32, NULL, '2027-02-01', 'Minggu ke-1 Bulan February', 1),
(33, NULL, '2027-02-08', 'Minggu ke-2 Bulan February', 1),
(34, NULL, '2027-02-15', 'Minggu ke-3 Bulan February', 1),
(35, NULL, '2027-02-22', 'Minggu ke-4 Bulan February', 1),
(36, NULL, '2027-03-01', 'Minggu ke-1 Bulan March', 1),
(37, NULL, '2027-03-08', 'Minggu ke-2 Bulan March', 1),
(38, NULL, '2027-03-15', 'Minggu ke-3 Bulan March', 1),
(39, NULL, '2027-03-22', 'Minggu ke-4 Bulan March', 1),
(40, NULL, '2027-04-01', 'Minggu ke-1 Bulan April', 1),
(41, NULL, '2027-04-08', 'Minggu ke-2 Bulan April', 1),
(42, NULL, '2027-04-15', 'Minggu ke-3 Bulan April', 1),
(43, NULL, '2027-04-22', 'Minggu ke-4 Bulan April', 1),
(44, NULL, '2027-05-01', 'Minggu ke-1 Bulan May', 1),
(45, NULL, '2027-05-08', 'Minggu ke-2 Bulan May', 1),
(46, NULL, '2027-05-15', 'Minggu ke-3 Bulan May', 1),
(47, NULL, '2027-05-22', 'Minggu ke-4 Bulan May', 1),
(48, NULL, '2027-06-01', 'Minggu ke-1 Bulan June', 1),
(49, NULL, '2027-06-08', 'Minggu ke-2 Bulan June', 1),
(50, NULL, '2027-06-15', 'Minggu ke-3 Bulan June', 1),
(51, NULL, '2027-06-22', 'Minggu ke-4 Bulan June', 1),
(52, NULL, '2026-04-01', 'Minggu ke-1 Bulan April', 1),
(53, NULL, '2026-04-15', 'Minggu ke-3 Bulan April', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `promes`
--

CREATE TABLE `promes` (
  `id` int(11) NOT NULL,
  `bab_id` int(11) DEFAULT NULL,
  `bulan` varchar(20) NOT NULL,
  `minggu_ke` int(11) NOT NULL,
  `topik_materi` varchar(200) NOT NULL,
  `alokasi_waktu` varchar(20) DEFAULT NULL,
  `semester` enum('1','2') DEFAULT '1',
  `tahun_pelajaran` varchar(20) DEFAULT '2025/2026'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `promes`
--

INSERT INTO `promes` (`id`, `bab_id`, `bulan`, `minggu_ke`, `topik_materi`, `alokasi_waktu`, `semester`, `tahun_pelajaran`) VALUES
(1, 1, 'Juli', 1, 'tes', '4 JP', '2', '2025/2026');

-- --------------------------------------------------------

--
-- Struktur dari tabel `prota`
--

CREATE TABLE `prota` (
  `id` int(11) NOT NULL,
  `bab_id` int(11) DEFAULT NULL,
  `alur_tujuan` text NOT NULL,
  `materi_pokok` varchar(200) DEFAULT NULL,
  `alokasi_waktu` varchar(20) DEFAULT NULL,
  `semester` enum('1','2') DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `prota`
--

INSERT INTO `prota` (`id`, `bab_id`, `alur_tujuan`, `materi_pokok`, `alokasi_waktu`, `semester`) VALUES
(1, 1, 'tes', 'tes', '4 JP', '2');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sikap_sosial`
--

CREATE TABLE `sikap_sosial` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `semester` enum('1','2') NOT NULL,
  `tahun_ajaran` varchar(10) NOT NULL,
  `empati` int(11) DEFAULT 3,
  `kerjasama` int(11) DEFAULT 3,
  `toleransi` int(11) DEFAULT 3,
  `percaya_diri` int(11) DEFAULT 3,
  `komunikasi` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sikap_sosial`
--

INSERT INTO `sikap_sosial` (`id`, `siswa_id`, `semester`, `tahun_ajaran`, `empati`, `kerjasama`, `toleransi`, `percaya_diri`, `komunikasi`) VALUES
(1, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(2, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(3, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(4, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(5, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(6, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(7, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(8, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(9, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(10, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(11, 108, '2', '2025/2026', 4, NULL, NULL, NULL, NULL),
(12, 109, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(13, 110, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(14, 111, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(15, 112, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(16, 113, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(17, 114, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(18, 115, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(19, 116, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(20, 117, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(21, 118, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(22, 119, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(23, 120, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(24, 124, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(25, 125, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(26, 121, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(27, 122, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(28, 123, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(29, 126, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(30, 127, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(31, 128, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(32, 129, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(33, 131, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(34, 237, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(35, 132, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(36, 133, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(37, 134, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(38, 135, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(39, 136, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(40, 137, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(41, 138, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL),
(42, 139, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sikap_spiritual`
--

CREATE TABLE `sikap_spiritual` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `semester` enum('1','2') NOT NULL,
  `tahun_ajaran` varchar(10) NOT NULL,
  `taqwa` int(11) DEFAULT 3,
  `kejujuran` int(11) DEFAULT 3,
  `disiplin` int(11) DEFAULT 3,
  `sabar` int(11) DEFAULT 3,
  `syukur` int(11) DEFAULT 3,
  `tawadhu` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sikap_spiritual`
--

INSERT INTO `sikap_spiritual` (`id`, `siswa_id`, `semester`, `tahun_ajaran`, `taqwa`, `kejujuran`, `disiplin`, `sabar`, `syukur`, `tawadhu`) VALUES
(1, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 3, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 108, '2', '2025/2026', 3, NULL, NULL, NULL, NULL, NULL),
(14, 109, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(15, 110, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(16, 111, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(17, 112, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(18, 113, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(19, 114, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(20, 115, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(21, 116, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(22, 117, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(23, 118, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(24, 119, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(25, 120, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(26, 124, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(27, 125, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(28, 121, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(29, 122, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(30, 123, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(31, 126, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(32, 127, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(33, 128, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(34, 129, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(35, 131, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(36, 237, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(37, 132, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(38, 133, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(39, 134, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(40, 135, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(41, 136, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(42, 137, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(43, 138, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL),
(44, 139, '2', '2025/2026', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `kelas` varchar(10) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id`, `nis`, `nama`, `jenis_kelamin`, `kelas`, `user_id`, `kelas_id`) VALUES
(2, '1', 'ADITYA RIZKI RAMDANI', 'L', '', 3, 7),
(3, '2', 'AHMAD YASIN SYAFI\'I', 'L', '', 4, 7),
(4, '3', 'ALAINA KAMILA', 'P', '', 5, 7),
(5, '4', 'ALI FAISHAL ARSYAD', 'L', '', 6, 7),
(6, '5', 'AQILA MUNAAZZAHRA', 'P', '', 7, 7),
(7, '6', 'ASYIFA RAMADHANI', 'P', '', 8, 7),
(8, '7', 'DELISTA PUTRI ANGGRAENI', 'P', '', 9, 7),
(9, '8', 'FADHILLAH NUR HAKIM', 'L', '', 10, 7),
(10, '9', 'FIKA APRILIANI', 'P', '', 11, 7),
(11, '10', 'KINANTI PRAMISWARI', 'P', '', 12, 7),
(12, '11', 'MAULIDINA RAISYA WIANA', 'P', '', 13, 7),
(13, '12', 'MOCH ABYL AL BARIQ', 'L', '', 14, 7),
(14, '13', 'MOCH DHIKA ERLANGGA', 'L', '', 15, 7),
(15, '14', 'MUHAMAD FAHRI FAUZI', 'L', '', 16, 7),
(16, '15', 'MUHAMAD RIZKY BAGJA ANUGRAH', 'L', '', 17, 7),
(17, '16', 'MUHAMMAD AJRUN KABIR', 'L', '', 18, 7),
(18, '17', 'MUHAMMAD AZKA ZAIDAN', 'L', '', 19, 7),
(19, '18', 'MUHAMMAD IKHSAN KAMIL', 'L', '', 20, 7),
(20, '19', 'NIRVANA OKTA PAMUNGKAS SUKASWO', 'P', '', 21, 7),
(21, '20', 'NOVIA NISMARA RATADEWATI', 'P', '', 22, 7),
(22, '21', 'PANDU ANUGRAH SAPUTRA', 'L', '', 23, 7),
(23, '22', 'RAIHAN WIDHY PRATAMA', 'L', '', 24, 7),
(24, '23', 'SALMA DESWITA MAHARANI', 'P', '', 25, 7),
(25, '24', 'SILMI KAFFAH', 'P', '', 26, 7),
(26, '25', 'SITI SAYYIDAH NUR RIZQIANTI', 'P', '', 27, 7),
(27, '26', 'TIRA ARTIKA SARI', 'P', '', 28, 7),
(28, '27', 'TRIAD M HANAFI', 'L', '', 29, 7),
(29, '28', 'YANI DAMAR ZAUHARIYAH', 'P', '', 30, 7),
(30, '8101', 'AHMAD RIFAL', 'L', '', 31, 3),
(31, '8102', 'AILA MAIZA SURYA', 'P', '', 32, 3),
(32, '8103', 'ALVIN PUTRA ARIEF BILLAH', 'L', '', 33, 3),
(33, '8104', 'AZRIN ALYA HUSNA', 'P', '', 34, 3),
(34, '8105', 'CESIKA QALESYA NUGRAHA', 'P', '', 35, 3),
(35, '8106', 'DHESYA SITI JULAIKHA', 'P', '', 36, 3),
(36, '8107', 'FADLAH NURUL FAUZIAH', 'P', '', 37, 3),
(37, '8108', 'FAKHRI ZAIDAN NUR ALAM', 'L', '', 38, 3),
(38, '8109', 'FARHAN JAYA HARTANA', 'L', '', 39, 3),
(39, '8110', 'FATIHA NABILA ALFITRIA', 'P', '', 40, 3),
(40, '8111', 'HERLAN PERI PERDIANTO', 'L', '', 41, 3),
(41, '8112', 'JULIAN RIZKY RABBANA', 'L', '', 42, 3),
(42, '8113', 'MOCHAMAD RIZKY PANGISTU SURACHMAN', 'L', '', 43, 3),
(43, '8114', 'MUHAMAD SYAHDAN ALHAJ', 'L', '', 44, 3),
(45, '8116', 'MUHAMMAD NAJMU SALIM AR RIFA\'I', 'L', '', 46, 3),
(46, '8117', 'NADILLA MUTIARA AYU', 'P', '', 47, 3),
(47, '8118', 'NAFHIZA DIVIE ALZAIRA', 'P', '', 48, 3),
(48, '8119', 'NIZAM MUZAKI', 'L', '', 49, 3),
(49, '8120', 'RAMLI ANUGRAH', 'L', '', 50, 3),
(50, '8121', 'RANGGA MAULANA', 'L', '', 51, 3),
(51, '8122', 'RAYHAN PUTRA SOFYAN', 'L', '', 52, 3),
(52, '8123', 'RIBKA MEYLIANA', 'P', '', 53, 3),
(53, '8124', 'SALMA SALSABILA AZAHRA', 'P', '', 54, 3),
(54, '8125', 'SALSA FADHILAH', 'P', '', 55, 3),
(55, '8126', 'VIRA NOVIANTI', 'P', '', 56, 3),
(56, '8127', 'YANI KARTIKA', 'P', '', 57, 3),
(79, '8323', 'SYAFIRA NUR ANDRIANI', 'P', '', 80, 1),
(83, '8301', 'ADI PUTRA PERMANA', 'L', '', 84, 1),
(84, '8302', 'AFHIKA RAHMA YUNIAR', 'P', '', 85, 1),
(85, '8303', 'ALIEFA RIZKIA FADILAH', 'P', '', 86, 1),
(86, '8304', 'ALIYA QURROTA AYUNI', 'P', '', 87, 1),
(87, '8305', 'ARSI SULISTIAWATI', 'P', '', 88, 1),
(88, '8306', 'AZKHA ALEZSYA APRILIANIE', 'P', '', 89, 1),
(89, '8307', 'INAYAH MUTHMAINAH', 'P', '', 90, 1),
(90, '8308', 'IRDZAN FIRDANSYAH', 'L', '', 91, 1),
(91, '8309', 'IRSAN ALDY RISANDY', 'L', '', 92, 1),
(92, '8310', 'JIHAN NABILLA ZAHRA', 'P', '', 93, 1),
(93, '8311', 'KANIA PEBRIYANI', 'P', '', 94, 1),
(94, '8312', 'KEISYA ANASTASYA RUSNANDIKA', 'P', '', 95, 1),
(95, '8313', 'MAHADIRGA BINTANG RAMADHAN', 'L', '', 96, 1),
(96, '8314', 'MOCHAMMAD DAFFA ALFAUZY', 'L', '', 97, 1),
(97, '8315', 'MUHAMAD BRILIAN', 'L', '', 98, 1),
(98, '8316', 'MUHAMAD REVALDI', 'L', '', 99, 1),
(99, '8317', 'MUHAMMAD NUR FADILAH', 'L', '', 100, 1),
(100, '8318', 'MUHAMMAD SABIT MAULANA SIDIK', 'L', '', 101, 1),
(101, '8319', 'MUHAMMAD SOFIAN HADI', 'L', '', 102, 1),
(102, '8320', 'NAISYA ANANDA FAUZI', 'P', '', 103, 1),
(103, '8321', 'REISYA AMELIA PUTRI', 'P', '', 104, 1),
(104, '8322', 'SANNY MUHAMAD FADILAH', 'L', '', 105, 1),
(105, '8324', 'YUSFI AL FATH BANUN ANTONI', 'L', '', 106, 1),
(106, '8325', 'ZAHRA AULIA DARYANTI PUTRI', 'P', '', 107, 1),
(107, '8326', 'ZAMZAM REVARIZA', 'L', '', 108, 1),
(108, '7101', 'ALIFIA NUR FAIZAH', 'P', '', 109, 8),
(109, '7102', 'AQILLA RAISYA PUTRI ', 'P', '', 110, 8),
(110, '7103', 'FADLI MUKSIN KHAMIL', 'L', '', 111, 8),
(111, '7104', 'FAREL ANANDA PUTRA', 'L', '', 112, 8),
(112, '7105', 'FIRYAL ASKANA SAKHI', 'P', '', 113, 8),
(113, '7106', 'HANUN NUR FADHILAH', 'P', '', 114, 8),
(114, '7107', 'ICHA LATIFA', 'P', '', 115, 8),
(115, '7108', 'KANAYA', 'P', '', 116, 8),
(116, '7109', 'KHALIFA AMISHA SAGIRA', 'P', '', 117, 8),
(117, '7110', 'KINARAISA AN-NAAFI WIRAWAN', 'P', '', 118, 8),
(118, '7111', 'LABIB MU\'AZAM ', 'L', '', 119, 8),
(119, '7112', 'M RIFKI KHOIRUL AKBAR', 'L', '', 120, 8),
(120, '7113', 'MAULANA IDRIS', 'L', '', 121, 8),
(121, '7114', 'MUHAMMAD DZAKIYY DZIKRULLAH', 'L', '', 122, 8),
(122, '7115', 'MUHAMMAD FADHILAH AL KHALIFI', 'L', '', 123, 8),
(123, '7116', 'MUHAMMAD FATAH AZRIANSYAH', 'L', '', 124, 8),
(124, '7117', 'MUHAMAD NURSATYA BOAN', 'L', '', 125, 8),
(125, '7118', 'MUHAMAD ZAYID RAMADHAN ', 'L', '', 126, 8),
(126, '7119', 'NAILA SEPTIANA SAKIRA', 'P', '', 127, 8),
(127, '7120', 'NAURA BILQIS ANSORI', 'P', '', 128, 8),
(128, '7121', 'NAURA FAUZAHRA PUTRI ', 'P', '', 129, 8),
(129, '7122', 'QITSHI NUR FADILLAH', 'P', '', 130, 8),
(131, '7124', 'RAISHA CHANTIKA APRILLIA', 'P', '', 132, 8),
(132, '7125', 'RAISYA TALITA ZAHRAN', 'P', '', 133, 8),
(133, '7126', 'SASMITA KIRANA S', 'P', '', 134, 8),
(134, '7127', 'SHAQUILLE FIDELYA AHMAD', 'P', '', 135, 8),
(135, '7128', 'TEUKU MUHAMMAD ISKANDAR', 'L', '', 136, 8),
(136, '7129', 'WILDANSYAH ALFAZIO MUHAMMAD', 'L', '', 137, 8),
(137, '7130', 'YASMIN ZALFA RAQILLA PANGESTU', 'L', '', 138, 8),
(138, '7131', 'YUHAN FAUSTINA ASGANI', 'L', '', 139, 8),
(139, '7132', 'ZAHRA NUR ASYIFA', 'P', '', 140, 8),
(140, '7201', 'AGNIA SALIMATUS SADIAH', 'P', '', 141, 9),
(141, '7202', 'ALBY ARKAN MUSYAFFA', 'L', '', 142, 9),
(142, '7203', 'ALISHA RAHMA ADISTIA', 'P', '', 143, 9),
(143, '7204', 'ANIZAM KHOIRIL NUR AZMI', 'L', '', 144, 9),
(144, '7205', 'ARSYA SARA AL ZAHIRA', 'P', '', 145, 9),
(145, '7206', 'AZZAM MUWALIED', 'L', '', 146, 9),
(146, '7207', 'ENZI FITRAH HARDIANSYAH', 'P', '', 147, 9),
(147, '7208', 'EUIS GHINA KHOIRUNISA', 'P', '', 148, 9),
(148, '7209', 'FAIZA NABIL SAPUTRA', 'L', '', 149, 9),
(149, '7210', 'HAYFA AZARIA DIELLA', 'P', '', 150, 9),
(150, '7211', 'KARINA NUR AZIZAH ', 'P', '', 151, 9),
(151, '7212', 'KHAIRA GHASSANY MAJID', 'P', '', 152, 9),
(152, '7213', 'M DAFA H R', 'L', '', 153, 9),
(153, '7214', 'MOCHAMMAD ALTAN MAHVIN DILARA', 'L', '', 154, 9),
(154, '7215', 'MUHAMMAD ARSYAD FAKHRULLAH', 'L', '', 155, 9),
(155, '7216', 'MUHAMAD ASRI ARYA AKBAR', 'L', '', 156, 9),
(156, '7217', 'MUHAMMAD FADLI AN-NAWAWI', 'L', '', 157, 9),
(157, '7218', 'MUHAMMAD FAZAR MUTTAQIN', 'L', '', 158, 9),
(158, '7219', 'MUHAMMAD RAFI AL RASYID', 'L', '', 159, 9),
(159, '7220', 'MUHAMAD ZEEREN ZUBAIR AZAM', 'L', '', 160, 9),
(160, '7221', 'NAFISA ARPIANI', 'P', '', 161, 9),
(161, '7222', 'NATASYA PUTRI SYAEPUDIN', 'P', '', 162, 9),
(162, '7223', 'NAYLA AZZAHRA', 'P', '', 163, 9),
(163, '7224', 'NAYLA RIFATUL ZAHRA', 'P', '', 164, 9),
(164, '7225', 'PRANANDA AHMAD FADILA', 'L', '', 165, 9),
(165, '7226', 'RAISA DEA ANANDA D', 'P', '', 166, 9),
(166, '7227', 'RAMDANI SAPUTRA', 'L', '', 167, 9),
(167, '7228', 'RASHDAN SYAM KOMARA', 'L', '', 168, 9),
(168, '7229', 'SALSABILA PUTRI FIRMANSYAH', 'P', '', 169, 9),
(169, '7230', 'SITI MARWAH KHAIRUNNISA', 'P', '', 170, 9),
(170, '7231', 'TASYA THALITA HASNA', 'P', '', 171, 9),
(171, '7232', 'ZAID AL-GHIFAR RAHARJA', 'L', '', 172, 9),
(172, '7301', 'ABBAS SYATHIR BAHRI ', 'L', '', 173, 10),
(173, '7302', 'AIZHAR RAINER ZAIDAN ', 'L', '', 174, 10),
(174, '7303', 'AMABEL JESSICA AZALIA', 'P', '', 175, 10),
(175, '7304', 'DITA AINUNNISA', 'P', '', 176, 10),
(176, '7305', 'FAISAL ZAKY AZ-ZAUHARI', 'L', '', 177, 10),
(177, '7306', 'FATHIR RIFFAD HARIRI', 'L', '', 178, 10),
(178, '7307', 'GAITSA JOAKIMA AUMAE', 'P', '', 179, 10),
(179, '7308', 'ILMAN RAHMAT FAUZAAN', 'L', '', 180, 10),
(180, '7309', 'KAELA AZZAHRA KHAIRUNISA', 'P', '', 181, 10),
(181, '7310', 'KEYSA PUTRI AISA', 'P', '', 182, 10),
(182, '7311', 'KEYSHA SHAKEELA ALI', 'P', '', 183, 10),
(183, '7312', 'KHANSA FATIHA ULFAH', 'P', '', 184, 10),
(184, '7313', 'LEIDA ACQUEELIA AFEEFA', 'P', '', 185, 10),
(185, '7314', 'MUHAMAD ALQA AL HAFIZT', 'L', '', 186, 10),
(186, '7315', 'MAHDA SYAKIRA RAMADHANI', 'P', '', 187, 10),
(187, '7316', 'MUHAMMAD FAISAL MUSTOFA', 'L', '', 188, 10),
(188, '7317', 'MUHAMMAD RAFFA EL IBRAHIM', 'L', '', 189, 10),
(189, '7318', 'MUHAMMAD ZAIDAN ', 'L', '', 190, 10),
(190, '7319', 'NADIRA AMANIA PERMADI', 'P', '', 191, 10),
(191, '7320', 'NAILA APRILIA SYAKIRA', 'P', '', 192, 10),
(192, '7321', 'NAUFAL ADITYA PRAWIRA', 'L', '', 193, 10),
(193, '7322', 'NAYLA AZALIA HAKIM ', 'P', '', 194, 10),
(194, '7323', 'NOVA MAHARANI NURFAJAR', 'P', '', 195, 10),
(195, '7324', 'NUHA AUFA ASHILA', 'P', '', 196, 10),
(196, '7325', 'PUTRI MUTIARA RAYA', 'P', '', 197, 10),
(197, '7326', 'QIANDRA OKTA MAULANA', 'P', '', 198, 10),
(198, '7327', 'RIFAA MARLIANI', 'P', '', 199, 10),
(199, '7328', 'SALWA NUR RASHIFAH', 'P', '', 200, 10),
(200, '7329', 'TEDYSYAH AHMAD FILANDO', 'L', '', 201, 10),
(201, '7330', 'WAFI NUR AIDAH', 'P', '', 202, 10),
(202, '7331', 'ZAHIRA RAMADHANI', 'P', '', 203, 10),
(203, '7332', 'ZAHRA ALMAIRA KHALISA', 'P', '', 204, 10),
(204, '7401', 'AIRA SALSABILA AZ ZAHRA', 'P', '', 205, 11),
(205, '7402', 'ALDIO KENZIE ERLANGGA', 'L', '', 206, 11),
(206, '7403', 'ARKA MAULANA RIZKI ', 'L', '', 207, 11),
(207, '7404', 'ATHARIZZ RIZKY PERMANA', 'L', '', 208, 11),
(208, '7405', 'AZKA SEPTIAN ZULFIKAR', 'L', '', 209, 11),
(209, '7406', 'DAFFA AL BUCHORI HERMAWAN', 'L', '', 210, 11),
(210, '7407', 'FABIAN RADITHYA', 'L', '', 211, 11),
(211, '7408', 'FAREEQ ZHAFIR MAULANA', 'L', '', 212, 11),
(212, '7409', 'HANNA SYAKILA SUDRAJAT', 'P', '', 213, 11),
(213, '7410', 'INDRA NURAN FADHILAH', 'L', '', 214, 11),
(214, '7411', 'KEISYA PUTRI AISA', 'P', '', 215, 11),
(215, '7412', 'KHAIZAN MUHAMMAD RAFKA ', 'L', '', 216, 11),
(216, '7413', 'KIRANA APRILLA ANZANI PUTRI', 'P', '', 217, 11),
(217, '7414', 'M MARCHEL GUSTA ZHAHIR PERMANA', 'L', '', 218, 11),
(218, '7415', 'MOHAMMAD AZKA ANUGRAH', 'L', '', 219, 11),
(219, '7416', 'MUHAMAD RAIHAN NATA PUTRA KUSUMA', 'L', '', 220, 11),
(220, '7417', 'MUHAMAD ADAM YUDISTIRA SEPTRIYANA', 'L', '', 221, 11),
(221, '7418', 'MUHAMMAD AZZAM PRADIPTA ZEROUN', 'L', '', 222, 11),
(222, '7419', 'MUHAMMAD RAFASYA AL FARIZHI', 'L', '', 223, 11),
(223, '7420', 'MUHAMAD RIZKI ADITYA PUTRA ', 'L', '', 224, 11),
(224, '7421', 'NABIL ALVERO APRILIO', 'P', '', 225, 11),
(225, '7422', 'PRABU SETIA ARYANTA', 'L', '', 226, 11),
(226, '7423', 'RAEHAN AL-KAHFI PUTRA', 'L', '', 227, 11),
(227, '7424', 'RAISHA AQILA ZAHRA', 'P', '', 228, 11),
(228, '7425', 'ROFI\'AH ADAWIYAH', 'P', '', 229, 11),
(229, '7426', 'SALMA FAUZIYAH', 'P', '', 230, 11),
(230, '7427', 'SAHWA PUTRI TIHARA', 'P', '', 231, 11),
(231, '7428', 'SILVIA', 'P', '', 232, 11),
(232, '7429', 'SITI MARIAM ', 'P', '', 233, 11),
(233, '7430', 'THORIQ PUTRA SURYA', 'P', '', 234, 11),
(234, '7431', 'ZAHRA NUR FAIQAH', 'P', '', 235, 11),
(235, '7432', 'ZAHRA ALMAIRA KHALISA', 'P', '', 236, 11),
(237, '7123', 'RAISSA AQILA PUTRI', 'P', '', 239, 8),
(239, '5446', 'Inna', 'P', '', 241, 8),
(240, '9999', 'testing', 'P', '', 242, 8);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tugas`
--

CREATE TABLE `tugas` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `jenis` enum('mandiri','kelompok') DEFAULT 'mandiri',
  `deskripsi` text DEFAULT NULL,
  `kategori_nilai` enum('SUM1','SUM2','SUM3','SUM4','STS','SAS','SAT') NOT NULL,
  `semester` enum('1','2') NOT NULL DEFAULT '1',
  `tahun_ajaran` varchar(10) NOT NULL DEFAULT '2025/2026',
  `durasi_mulai` date DEFAULT NULL,
  `durasi_selesai` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tugas`
--

INSERT INTO `tugas` (`id`, `judul`, `jenis`, `deskripsi`, `kategori_nilai`, `semester`, `tahun_ajaran`, `durasi_mulai`, `durasi_selesai`, `created_at`) VALUES
(7, 'Menghafalkan Q.S Al Qoshosh ayat 77', 'mandiri', 'test', 'SUM1', '2', '2025/2026', '2026-04-13', '2026-04-20', '2026-04-20 18:52:59'),
(8, 'MENJAUHI GAYA HIDUP HEDONIS, KONSUMTIF DAN MATERIALISTIS', 'mandiri', 'test', 'SUM1', '2', '2025/2026', '2026-04-13', '2026-04-27', '2026-04-20 18:53:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tugas_kelas`
--

CREATE TABLE `tugas_kelas` (
  `id` int(11) NOT NULL,
  `tugas_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tugas_kelas`
--

INSERT INTO `tugas_kelas` (`id`, `tugas_id`, `kelas_id`) VALUES
(1, 7, 3),
(2, 7, 1),
(3, 7, 7),
(4, 8, 8),
(5, 8, 9),
(6, 8, 10),
(7, 8, 11);

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','siswa') NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `role`, `nama_lengkap`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'admin', 'Ilham Rizqiawan, S.Pd.'),
(3, '1', 'c4ca4238a0b923820dcc509a6f75849b', 'siswa', 'ADITYA RIZKI RAMDANI'),
(4, '2', 'c81e728d9d4c2f636f067f89cc14862c', 'siswa', 'AHMAD YASIN SYAFI\'I'),
(5, '3', 'eccbc87e4b5ce2fe28308fd9f2a7baf3', 'siswa', 'ALAINA KAMILA'),
(6, '4', 'a87ff679a2f3e71d9181a67b7542122c', 'siswa', 'ALI FAISHAL ARSYAD'),
(7, '5', 'e4da3b7fbbce2345d7772b0674a318d5', 'siswa', 'AQILA MUNAAZZAHRA'),
(8, '6', '1679091c5a880faf6fb5e6087eb1b2dc', 'siswa', 'ASYIFA RAMADHANI'),
(9, '7', '8f14e45fceea167a5a36dedd4bea2543', 'siswa', 'DELISTA PUTRI ANGGRAENI'),
(10, '8', 'c9f0f895fb98ab9159f51fd0297e236d', 'siswa', 'FADHILLAH NUR HAKIM'),
(11, '9', '45c48cce2e2d7fbdea1afc51c7c6ad26', 'siswa', 'FIKA APRILIANI'),
(12, '10', 'd3d9446802a44259755d38e6d163e820', 'siswa', 'KINANTI PRAMISWARI'),
(13, '11', '6512bd43d9caa6e02c990b0a82652dca', 'siswa', 'MAULIDINA RAISYA WIANA'),
(14, '12', 'c20ad4d76fe97759aa27a0c99bff6710', 'siswa', 'MOCH ABYL AL BARIQ'),
(15, '13', 'c51ce410c124a10e0db5e4b97fc2af39', 'siswa', 'MOCH DHIKA ERLANGGA'),
(16, '14', 'aab3238922bcc25a6f606eb525ffdc56', 'siswa', 'MUHAMAD FAHRI FAUZI'),
(17, '15', '9bf31c7ff062936a96d3c8bd1f8f2ff3', 'siswa', 'MUHAMAD RIZKY BAGJA ANUGRAH'),
(18, '16', 'c74d97b01eae257e44aa9d5bade97baf', 'siswa', 'MUHAMMAD AJRUN KABIR'),
(19, '17', '70efdf2ec9b086079795c442636b55fb', 'siswa', 'MUHAMMAD AZKA ZAIDAN'),
(20, '18', '6f4922f45568161a8cdf4ad2299f6d23', 'siswa', 'MUHAMMAD IKHSAN KAMIL'),
(21, '19', '1f0e3dad99908345f7439f8ffabdffc4', 'siswa', 'NIRVANA OKTA PAMUNGKAS SUKASWO'),
(22, '20', '98f13708210194c475687be6106a3b84', 'siswa', 'NOVIA NISMARA RATADEWATI'),
(23, '21', '3c59dc048e8850243be8079a5c74d079', 'siswa', 'PANDU ANUGRAH SAPUTRA'),
(24, '22', 'b6d767d2f8ed5d21a44b0e5886680cb9', 'siswa', 'RAIHAN WIDHY PRATAMA'),
(25, '23', '37693cfc748049e45d87b8c7d8b9aacd', 'siswa', 'SALMA DESWITA MAHARANI'),
(26, '24', '1ff1de774005f8da13f42943881c655f', 'siswa', 'SILMI KAFFAH'),
(27, '25', '8e296a067a37563370ded05f5a3bf3ec', 'siswa', 'SITI SAYYIDAH NUR RIZQIANTI'),
(28, '26', '4e732ced3463d06de0ca9a15b6153677', 'siswa', 'TIRA ARTIKA SARI'),
(29, '27', '02e74f10e0327ad868d138f2b4fdd6f0', 'siswa', 'TRIAD M HANAFI'),
(30, '28', '33e75ff09dd601bbe69f351039152189', 'siswa', 'YANI DAMAR ZAUHARIYAH'),
(31, '8101', 'c035226640b6b89ffaf333f54e523c10', 'siswa', 'AHMAD RIFAL'),
(32, '8102', '9c693b040f150014937c0072d90c00db', 'siswa', 'AILA MAIZA SURYA'),
(33, '8103', '44151de6be734db545ec958e77b0f9df', 'siswa', 'ALVIN PUTRA ARIEF BILLAH'),
(34, '8104', '9078f2a8254704bd760460f027072e52', 'siswa', 'AZRIN ALYA HUSNA'),
(35, '8105', '247d87b085efdb305fa6583ccf1a9f54', 'siswa', 'CESIKA QALESYA NUGRAHA'),
(36, '8106', '1b742ae215adf18b75449c6e272fd92d', 'siswa', 'DHESYA SITI JULAIKHA'),
(37, '8107', '7706d2dc2da6837340effd985dc620b6', 'siswa', 'FADLAH NURUL FAUZIAH'),
(38, '8108', '25b07af81d8c74341f00dc139652fdb0', 'siswa', 'FAKHRI ZAIDAN NUR ALAM'),
(39, '8109', 'd30d0f522a86b3665d8e3a9a91472e28', 'siswa', 'FARHAN JAYA HARTANA'),
(40, '8110', '71b9e42fd1490c2ee83c1bc4c4e37da3', 'siswa', 'FATIHA NABILA ALFITRIA'),
(41, '8111', '30410be149e6771f60881182342452d5', 'siswa', 'HERLAN PERI PERDIANTO'),
(42, '8112', '45d6637b718d0f24a237069fe41b0db4', 'siswa', 'JULIAN RIZKY RABBANA'),
(43, '8113', 'b6417f112bd27848533e54885b66c288', 'siswa', 'MOCHAMAD RIZKY PANGISTU SURACHMAN'),
(44, '8114', 'b20fa060328b0cdf51b464ee37efe182', 'siswa', 'MUHAMAD SYAHDAN ALHAJ'),
(46, '8116', '8f6242793017047d373f29f270388ba9', 'siswa', 'MUHAMMAD NAJMU SALIM AR RIFA\'I'),
(47, '8117', '03492e99e42e7ea8480cdfb4899604f5', 'siswa', 'NADILLA MUTIARA AYU'),
(48, '8118', '1ddfa4ccdcea53130b500eaabb190e4b', 'siswa', 'NAFHIZA DIVIE ALZAIRA'),
(49, '8119', '2ea19e760aeeeeeb813a2406d0d31a25', 'siswa', 'NIZAM MUZAKI'),
(50, '8120', '1becf26e9f32353e30870060538746e7', 'siswa', 'RAMLI ANUGRAH'),
(51, '8121', 'afecc60f82be41c1b52f6705ec69e0f1', 'siswa', 'RANGGA MAULANA'),
(52, '8122', 'e9507053dd36cb9217816ffb566c8720', 'siswa', 'RAYHAN PUTRA SOFYAN'),
(53, '8123', '15c71b874531f45bba372bfc35e9b8cf', 'siswa', 'RIBKA MEYLIANA'),
(54, '8124', '9465ce9a7904ba9fa5a354804734cbc4', 'siswa', 'SALMA SALSABILA AZAHRA'),
(55, '8125', 'a34c46916c53099c8038cff641c9b127', 'siswa', 'SALSA FADHILAH'),
(56, '8126', '4fc66104f8ada6257fa55f29a2a567c7', 'siswa', 'VIRA NOVIANTI'),
(57, '8127', '5934c1ec0cd31e12bd9084d106bc2e32', 'siswa', 'YANI KARTIKA'),
(80, '8323', 'd882050bb9eeba930974f596931be527', 'siswa', 'SYAFIRA NUR ANDRIANI'),
(84, '8301', '1e79596878b2320cac26dd792a6c51c9', 'siswa', 'ADI PUTRA PERMANA'),
(85, '8302', 'c1bb2ec7a913e32a2d05cacf3b83cd1b', 'siswa', 'AFHIKA RAHMA YUNIAR'),
(86, '8303', 'b0c2b4c9093282097ead26fb94f6d113', 'siswa', 'ALIEFA RIZKIA FADILAH'),
(87, '8304', '2f014b914ea5e7c04fc6cbde68d02141', 'siswa', 'ALIYA QURROTA AYUNI'),
(88, '8305', 'd7488039246a405baf6a7cbc3613a56f', 'siswa', 'ARSI SULISTIAWATI'),
(89, '8306', '7fc9fd4c6654dea5be92c8ffd53bea1e', 'siswa', 'AZKHA ALEZSYA APRILIANIE'),
(90, '8307', '680f0fc378c4abff9d8f271c95e799c3', 'siswa', 'INAYAH MUTHMAINAH'),
(91, '8308', '79e3eb7e992b7f766bdd77cc502ff082', 'siswa', 'IRDZAN FIRDANSYAH'),
(92, '8309', '1aa7a8773e6a7fdacbcedf9999009a38', 'siswa', 'IRSAN ALDY RISANDY'),
(93, '8310', '85ef8e895264ae2dcab7bcd0f04d9bea', 'siswa', 'JIHAN NABILLA ZAHRA'),
(94, '8311', '1f5f6ad95cc908a20bb7e30ee28a5958', 'siswa', 'KANIA PEBRIYANI'),
(95, '8312', 'e046ede63264b10130007afca077877f', 'siswa', 'KEISYA ANASTASYA RUSNANDIKA'),
(96, '8313', 'bf2fe6582ed9ead9161a3d6f6b1d6858', 'siswa', 'MAHADIRGA BINTANG RAMADHAN'),
(97, '8314', '7a951116de2a4c23c74733d76046a5b4', 'siswa', 'MOCHAMMAD DAFFA ALFAUZY'),
(98, '8315', '68881d2246abebc3aa474b51ecd7773e', 'siswa', 'MUHAMAD BRILIAN'),
(99, '8316', 'cf011ff8d8380280133bce806e0f7bb1', 'siswa', 'MUHAMAD REVALDI'),
(100, '8317', '47a7f2c033801a8185243e6ca8df5fae', 'siswa', 'MUHAMMAD NUR FADILAH'),
(101, '8318', '3fe230348e9a12c13120749e3f9fa4cd', 'siswa', 'MUHAMMAD SABIT MAULANA SIDIK'),
(102, '8319', '3941c4358616274ac2436eacf67fae05', 'siswa', 'MUHAMMAD SOFIAN HADI'),
(103, '8320', '690f44c8c2b7ded579d01abe8fdb6110', 'siswa', 'NAISYA ANANDA FAUZI'),
(104, '8321', 'd428d070622e0f4363fceae11f4a3576', 'siswa', 'REISYA AMELIA PUTRI'),
(105, '8322', '71969a804c28c0961383008958497b1b', 'siswa', 'SANNY MUHAMAD FADILAH'),
(106, '8324', 'e5fc3b8d9510b02e42361c337597d9fa', 'siswa', 'YUSFI AL FATH BANUN ANTONI'),
(107, '8325', '4be49c79f233b4f4070794825c323733', 'siswa', 'ZAHRA AULIA DARYANTI PUTRI'),
(108, '8326', '19e21d13715b9720d8c00977145f1dd8', 'siswa', 'ZAMZAM REVARIZA'),
(109, '7101', 'b33128cb0089003ddfb5199e1b679652', 'siswa', 'ALIFIA NUR FAIZAH'),
(110, '7102', '81dc9bdb52d04dc20036dbd8313ed055', 'siswa', 'AQILLA RAISYA PUTRI '),
(111, '7103', '62b98e188905060143a433b1363b3266', 'siswa', 'FADLI MUKSIN KHAMIL'),
(112, '7104', '2be8328f41144106f7144802f2367487', 'siswa', 'FAREL ANANDA PUTRA'),
(113, '7105', '4cc05b35c2f937c5bd9e7d41d3686fff', 'siswa', 'FIRYAL ASKANA SAKHI'),
(114, '7106', 'a9d34fb66d81367590fdd5337324233a', 'siswa', 'HANUN NUR FADHILAH'),
(115, '7107', '09676fac73eda6cac726c43e43e86c58', 'siswa', 'ICHA LATIFA'),
(116, '7108', '5bca8566db79f3788be9efd96c9ed70d', 'siswa', 'KANAYA'),
(117, '7109', '51311013e51adebc3c34d2cc591fefee', 'siswa', 'KHALIFA AMISHA SAGIRA'),
(118, '7110', '7da9fd85999f583e3906f99a3ee58911', 'siswa', 'KINARAISA AN-NAAFI WIRAWAN'),
(119, '7111', '1ab60b5e8bd4eac8a7537abb5936aadc', 'siswa', 'LABIB MU\'AZAM '),
(120, '7112', 'ba7609ee5789cc4dff171045a693a65f', 'siswa', 'M RIFKI KHOIRUL AKBAR'),
(121, '7113', '3501672ebc68a5524629080e3ef60aef', 'siswa', 'MAULANA IDRIS'),
(122, '7114', '20d039f53b4a6786c21ee0dbcd2d2c5d', 'siswa', 'MUHAMMAD DZAKIYY DZIKRULLAH'),
(123, '7115', '5301c4d888f5204274439e6dcf5fdb54', 'siswa', 'MUHAMMAD FADHILAH AL KHALIFI'),
(124, '7116', '47267ca39f652c0de27a4b27c5e11c40', 'siswa', 'MUHAMMAD FATAH AZRIANSYAH'),
(125, '7117', '860b37e28ec7ba614f00f9246949561d', 'siswa', 'MUHAMAD NURSATYA BOAN'),
(126, '7118', 'bc19986e5c658d4135bd559a0b37c0bc', 'siswa', 'MUHAMAD ZAYID RAMADHAN '),
(127, '7119', '2adee8815dd939548ee6b2772524b6f2', 'siswa', 'NAILA SEPTIANA SAKIRA'),
(128, '7120', 'ac3870fcad1cfc367825cda0101eee62', 'siswa', 'NAURA BILQIS ANSORI'),
(129, '7121', '738a6457be8432bab553e21b4235dd97', 'siswa', 'NAURA FAUZAHRA PUTRI '),
(130, '7122', 'df334b223e699294764c2bb7ae40d8db', 'siswa', 'QITSHI NUR FADILLAH'),
(132, '7124', '64986d86a17424eeac96b08a6d519059', 'siswa', 'RAISHA CHANTIKA APRILLIA'),
(133, '7125', '80f7325fa857de62fafe85f7a30273cb', 'siswa', 'RAISYA TALITA ZAHRAN'),
(134, '7126', 'cb93980bc94a17e36d6de5da28b99785', 'siswa', 'SASMITA KIRANA S'),
(135, '7127', 'ffa1e107c6469dafa0016703450e26ed', 'siswa', 'SHAQUILLE FIDELYA AHMAD'),
(136, '7128', 'e3019767b1b23f82883c9850356b71d6', 'siswa', 'TEUKU MUHAMMAD ISKANDAR'),
(137, '7129', 'bcc2bdb799f873f02080ae277f291da1', 'siswa', 'WILDANSYAH ALFAZIO MUHAMMAD'),
(138, '7130', '9afbe998374ca7326d35d84180786096', 'siswa', 'YASMIN ZALFA RAQILLA PANGESTU'),
(139, '7131', '50e207ab6946b5d78b377ae0144b9e07', 'siswa', 'YUHAN FAUSTINA ASGANI'),
(140, '7132', '32b127307a606effdcc8e51f60a45922', 'siswa', 'ZAHRA NUR ASYIFA'),
(141, '7201', 'f9ab16852d455ce9203da64f4fc7f92d', 'siswa', 'AGNIA SALIMATUS SADIAH'),
(142, '7202', 'f862d13454fd267baa5fedfffb200567', 'siswa', 'ALBY ARKAN MUSYAFFA'),
(143, '7203', '118921efba23fc329e6560b27861f0c2', 'siswa', 'ALISHA RAHMA ADISTIA'),
(144, '7204', 'ae3d525daf92cee0003a7f2d92c34ea3', 'siswa', 'ANIZAM KHOIRIL NUR AZMI'),
(145, '7205', '75da5036f659fe64b53f3d9b39412967', 'siswa', 'ARSYA SARA AL ZAHIRA'),
(146, '7206', 'bd48f59a9f04aefd7708058b717453af', 'siswa', 'AZZAM MUWALIED'),
(147, '7207', '2d45cbe914655ca562553cb81fdfc464', 'siswa', 'ENZI FITRAH HARDIANSYAH'),
(148, '7208', '589f763b060f8c19170cdf5196e2bf87', 'siswa', 'EUIS GHINA KHOIRUNISA'),
(149, '7209', 'e904831f48e729f9ad8355a894334700', 'siswa', 'FAIZA NABIL SAPUTRA'),
(150, '7210', 'df308fd90635b28d82558cf580c73ed9', 'siswa', 'HAYFA AZARIA DIELLA'),
(151, '7211', 'f10f2da9a238b746d2bac55759915f0d', 'siswa', 'KARINA NUR AZIZAH '),
(152, '7212', 'e14e58299bc41e7fb10c701130c5cb27', 'siswa', 'KHAIRA GHASSANY MAJID'),
(153, '7213', '6213a8959a9a96589ca484dfd1e25053', 'siswa', 'M DAFA H R'),
(154, '7214', 'cdbc9bca0a9fd93852571cced0089c4d', 'siswa', 'MOCHAMMAD ALTAN MAHVIN DILARA'),
(155, '7215', 'b17446af05919be6e83500be7f5df5c4', 'siswa', 'MUHAMMAD ARSYAD FAKHRULLAH'),
(156, '7216', '994d1cad9132e48c993d58b492f71fc1', 'siswa', 'MUHAMAD ASRI ARYA AKBAR'),
(157, '7217', '29263a8cf61fb9addf5629769fac92b7', 'siswa', 'MUHAMMAD FADLI AN-NAWAWI'),
(158, '7218', 'e7d6e2e80f0955c01f3e043ee79abbb6', 'siswa', 'MUHAMMAD FAZAR MUTTAQIN'),
(159, '7219', '9c415bdd4dd66723ef7b38853ef35ddb', 'siswa', 'MUHAMMAD RAFI AL RASYID'),
(160, '7220', 'bd4828247647544af24a15ac79a1ef9f', 'siswa', 'MUHAMAD ZEEREN ZUBAIR AZAM'),
(161, '7221', '5ecf33fd9caf42c3bd39a3d9ee5f9ca3', 'siswa', 'NAFISA ARPIANI'),
(162, '7222', '3c5be6328b5f6a0a5980341230b8ac05', 'siswa', 'NATASYA PUTRI SYAEPUDIN'),
(163, '7223', 'f3067d687ee39c3cbfa75573457e479d', 'siswa', 'NAYLA AZZAHRA'),
(164, '7224', 'b11b7e3409b27e5c6e332399362105f8', 'siswa', 'NAYLA RIFATUL ZAHRA'),
(165, '7225', 'f629ed9325990b10543ab5946c1362fb', 'siswa', 'PRANANDA AHMAD FADILA'),
(166, '7226', '5ea363a74cddf7e0b3110d79212cc89c', 'siswa', 'RAISA DEA ANANDA D'),
(167, '7227', '032a01d83345f23883c98c540ff32fe7', 'siswa', 'RAMDANI SAPUTRA'),
(168, '7228', 'acd9bdac8824615154e7f1868f29acf6', 'siswa', 'RASHDAN SYAM KOMARA'),
(169, '7229', '2d290e496d16c9dcaa9b4ded5cac10cc', 'siswa', 'SALSABILA PUTRI FIRMANSYAH'),
(170, '7230', 'f0b76267fbe12b936bd65e203dc675c1', 'siswa', 'SITI MARWAH KHAIRUNNISA'),
(171, '7231', 'b522259710151f8cc7870b970b4e0930', 'siswa', 'TASYA THALITA HASNA'),
(172, '7232', 'd8ea5f53c1b1eb087ac2e356253395d8', 'siswa', 'ZAID AL-GHIFAR RAHARJA'),
(173, '7301', '9c779f56f336b3c812343434f57b6a0e', 'siswa', 'ABBAS SYATHIR BAHRI '),
(174, '7302', 'd9437926cc8d785a7bdb8578fd85d8e3', 'siswa', 'AIZHAR RAINER ZAIDAN '),
(175, '7303', '27debb435021eb68b3965290b5e24c49', 'siswa', 'AMABEL JESSICA AZALIA'),
(176, '7304', 'f52db9f7c0ae7017ee41f63c2a7353bc', 'siswa', 'DITA AINUNNISA'),
(177, '7305', '588da7a73a2e919a23cb9a419c4c6d44', 'siswa', 'FAISAL ZAKY AZ-ZAUHARI'),
(178, '7306', 'a523426cc585745318d5f6d91a9c0706', 'siswa', 'FATHIR RIFFAD HARIRI'),
(179, '7307', 'a1a527267c0d33a86382a03c4c721cd2', 'siswa', 'GAITSA JOAKIMA AUMAE'),
(180, '7308', 'c923d8f64e256dde7c28bf1614d53602', 'siswa', 'ILMAN RAHMAT FAUZAAN'),
(181, '7309', '6a83c731660fcc9f14e1ce0b62d45eb9', 'siswa', 'KAELA AZZAHRA KHAIRUNISA'),
(182, '7310', 'e5a90182cc81e12ab5e72d66e0b46fe3', 'siswa', 'KEYSA PUTRI AISA'),
(183, '7311', 'c7b03782920d35145eb4c97556d194a3', 'siswa', 'KEYSHA SHAKEELA ALI'),
(184, '7312', 'fc2022c89b61c76bbef978f1370660bf', 'siswa', 'KHANSA FATIHA ULFAH'),
(185, '7313', '3a0f19df72fef0495c54ced664735a7d', 'siswa', 'LEIDA ACQUEELIA AFEEFA'),
(186, '7314', '251dbb5e528421776ff6e17c87be507f', 'siswa', 'MUHAMAD ALQA AL HAFIZT'),
(187, '7315', 'f2c5b1f06bfe59954cb2a56858c2ed98', 'siswa', 'MAHDA SYAKIRA RAMADHANI'),
(188, '7316', '5cdf0f9533d6b4c0984fc5ae00913459', 'siswa', 'MUHAMMAD FAISAL MUSTOFA'),
(189, '7317', 'f005e17eabbb0d38b06b8a78f3637d85', 'siswa', 'MUHAMMAD RAFFA EL IBRAHIM'),
(190, '7318', '02c27682b80b462437ba4efc71267562', 'siswa', 'MUHAMMAD ZAIDAN '),
(191, '7319', '0beb34df7e9615cd43b9090989ca4848', 'siswa', 'NADIRA AMANIA PERMADI'),
(192, '7320', '8b1ecf6d8049bb062a356f1cc812e69e', 'siswa', 'NAILA APRILIA SYAKIRA'),
(193, '7321', 'c2c701fe341a7756ca7fd4eaa83ff63f', 'siswa', 'NAUFAL ADITYA PRAWIRA'),
(194, '7322', '182bd81ea25270b7d1c2fe8353d17fe6', 'siswa', 'NAYLA AZALIA HAKIM '),
(195, '7323', '3f24bb08a5741e4197af64e1f93a5029', 'siswa', 'NOVA MAHARANI NURFAJAR'),
(196, '7324', '7240b65810859cbf2a8d9f76a638c0a3', 'siswa', 'NUHA AUFA ASHILA'),
(197, '7325', '4e093aa7417fe0881bc5fbda7322a74e', 'siswa', 'PUTRI MUTIARA RAYA'),
(198, '7326', 'fbd85d9451c0d7555518534bcbac00e3', 'siswa', 'QIANDRA OKTA MAULANA'),
(199, '7327', 'cd7c230fc5deb01ff5f7b1be1acef9cf', 'siswa', 'RIFAA MARLIANI'),
(200, '7328', '7c250678f61f49092fa0d4040e5e54e9', 'siswa', 'SALWA NUR RASHIFAH'),
(201, '7329', '83dd3f9f97ef6533766c39d5b2e5e565', 'siswa', 'TEDYSYAH AHMAD FILANDO'),
(202, '7330', '5e18f86fad006a5846541997511989d5', 'siswa', 'WAFI NUR AIDAH'),
(203, '7331', 'ac64504cc249b070772848642cffe6ff', 'siswa', 'ZAHIRA RAMADHANI'),
(204, '7332', '3d1296c4b4b859ac2fb14019654a5f57', 'siswa', 'ZAHRA ALMAIRA KHALISA'),
(205, '7401', '9b91d245c953f54a7f10aba72a4d0022', 'siswa', 'AIRA SALSABILA AZ ZAHRA'),
(206, '7402', 'b6846b0186a035fcc76b1b1d26fd42fa', 'siswa', 'ALDIO KENZIE ERLANGGA'),
(207, '7403', '84e8ce7870f0eecd843366582bb95a28', 'siswa', 'ARKA MAULANA RIZKI '),
(208, '7404', 'dcc5c249e15c211f21e1da0f3ba66169', 'siswa', 'ATHARIZZ RIZKY PERMANA'),
(209, '7405', '445e24b5f22cacb9d51a837c10e91a3f', 'siswa', 'AZKA SEPTIAN ZULFIKAR'),
(210, '7406', '8252831b9fce7a49421e622c14ce0f65', 'siswa', 'DAFFA AL BUCHORI HERMAWAN'),
(211, '7407', '99a401435dcb65c4008d3ad22c8cdad0', 'siswa', 'FABIAN RADITHYA'),
(212, '7408', '545e91a14706478dc240764245cd4bd1', 'siswa', 'FAREEQ ZHAFIR MAULANA'),
(213, '7409', '06cdc05791b8af29eed72084f39143c7', 'siswa', 'HANNA SYAKILA SUDRAJAT'),
(214, '7410', '0c12278389532e91c601af4c8adef7fc', 'siswa', 'INDRA NURAN FADHILAH'),
(215, '7411', '8be6adae5ae0e157014d7d250870f212', 'siswa', 'KEISYA PUTRI AISA'),
(216, '7412', 'e13748298cfb23c19fdfd134a2221e7b', 'siswa', 'KHAIZAN MUHAMMAD RAFKA '),
(217, '7413', 'ba3c5fe1d6d6708b5bffaeb6942b7e04', 'siswa', 'KIRANA APRILLA ANZANI PUTRI'),
(218, '7414', '297b631a88835f8931aa5aabdd06ece9', 'siswa', 'M MARCHEL GUSTA ZHAHIR PERMANA'),
(219, '7415', 'e261489ab942429a6600c1c4121ac14d', 'siswa', 'MOHAMMAD AZKA ANUGRAH'),
(220, '7416', 'dc91cc9b6b3fc6325cf08272dbf9eb2f', 'siswa', 'MUHAMAD RAIHAN NATA PUTRA KUSUMA'),
(221, '7417', 'ce89f6b11bdc5b365085a84036e9365b', 'siswa', 'MUHAMAD ADAM YUDISTIRA SEPTRIYANA'),
(222, '7418', 'd5e705ceeeb7f7ece5dc5ee9bb5e148d', 'siswa', 'MUHAMMAD AZZAM PRADIPTA ZEROUN'),
(223, '7419', '54eea69746513c0b90bbe6227b6f46c3', 'siswa', 'MUHAMMAD RAFASYA AL FARIZHI'),
(224, '7420', '968c9b4f09cbb7d7925f38aea3484111', 'siswa', 'MUHAMAD RIZKI ADITYA PUTRA '),
(225, '7421', 'b9acb4ae6121c941324b2b1d3fac5c30', 'siswa', 'NABIL ALVERO APRILIO'),
(226, '7422', '52aaa62e71f829d41d74892a18a11d59', 'siswa', 'PRABU SETIA ARYANTA'),
(227, '7423', '8fe04df45a22b63156ebabbb064fcd5e', 'siswa', 'RAEHAN AL-KAHFI PUTRA'),
(228, '7424', '420824960f755f8721c47b6027ead6ab', 'siswa', 'RAISHA AQILA ZAHRA'),
(229, '7425', '966aad8981dcc75b5b8ab04427a833b2', 'siswa', 'ROFI\'AH ADAWIYAH'),
(230, '7426', 'd60743aab4b625940d39b3b51c3c6a78', 'siswa', 'SALMA FAUZIYAH'),
(231, '7427', 'aecad42329922dfc97eee948606e1f8e', 'siswa', 'SAHWA PUTRI TIHARA'),
(232, '7428', 'cc6ef8cb8df3af5693726838cd728163', 'siswa', 'SILVIA'),
(233, '7429', 'ff450ba01b0ca2695d62525505dd80eb', 'siswa', 'SITI MARIAM '),
(234, '7430', '9ab8a8a9349eb1dd73ce155ce64c80fa', 'siswa', 'THORIQ PUTRA SURYA'),
(235, '7431', '51c68dc084cb0b8467eafad1330bce66', 'siswa', 'ZAHRA NUR FAIQAH'),
(236, '7432', '48bea99c85bcbaaba618ba10a6f69e44', 'siswa', 'ZAHRA ALMAIRA KHALISA'),
(239, '7123', 'e3a54649aeec04cf1c13907bc6c5c8aa', 'siswa', 'RAISSA AQILA PUTRI'),
(241, '5446', '3d3d286a8d153a4a58156d0e02d8570c', 'siswa', 'Inna'),
(242, '9999', 'fa246d0262c3925617b0c72bb20eeb1d', 'siswa', 'testing');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_absensi_siswa` (`siswa_id`),
  ADD KEY `fk_absensi_pertemuan` (`pertemuan_id`);

--
-- Indeks untuk tabel `atp`
--
ALTER TABLE `atp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_atp_bab` (`bab_id`);

--
-- Indeks untuk tabel `bab`
--
ALTER TABLE `bab`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kelas_id` (`kelas_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `cp`
--
ALTER TABLE `cp`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jurnal`
--
ALTER TABLE `jurnal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_jurnal_bab` (`bab_id`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kktp`
--
ALTER TABLE `kktp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_kktp_bab` (`bab_id`);

--
-- Indeks untuk tabel `lkpd`
--
ALTER TABLE `lkpd`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lkpd_bab` (`bab_id`);

--
-- Indeks untuk tabel `modul_ajar`
--
ALTER TABLE `modul_ajar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_modul_ajar_bab` (`bab_id`);

--
-- Indeks untuk tabel `nilai_akhir`
--
ALTER TABLE `nilai_akhir`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `siswa_semester_tahun` (`siswa_id`,`semester`,`tahun_ajaran`);

--
-- Indeks untuk tabel `nilai_tugas`
--
ALTER TABLE `nilai_tugas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tugas_siswa` (`tugas_id`,`siswa_id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indeks untuk tabel `pengumpulan_tugas`
--
ALTER TABLE `pengumpulan_tugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tugas_id` (`tugas_id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indeks untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tanggal` (`tanggal`),
  ADD KEY `bab_id` (`bab_id`);

--
-- Indeks untuk tabel `promes`
--
ALTER TABLE `promes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_promes_bab` (`bab_id`);

--
-- Indeks untuk tabel `prota`
--
ALTER TABLE `prota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prota_bab` (`bab_id`);

--
-- Indeks untuk tabel `sikap_sosial`
--
ALTER TABLE `sikap_sosial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indeks untuk tabel `sikap_spiritual`
--
ALTER TABLE `sikap_spiritual`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_siswa_kelas` (`kelas_id`);

--
-- Indeks untuk tabel `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tugas_kelas`
--
ALTER TABLE `tugas_kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tugas_id` (`tugas_id`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT untuk tabel `atp`
--
ALTER TABLE `atp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `bab`
--
ALTER TABLE `bab`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `cp`
--
ALTER TABLE `cp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `jurnal`
--
ALTER TABLE `jurnal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `kktp`
--
ALTER TABLE `kktp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `lkpd`
--
ALTER TABLE `lkpd`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `modul_ajar`
--
ALTER TABLE `modul_ajar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `nilai_akhir`
--
ALTER TABLE `nilai_akhir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `nilai_tugas`
--
ALTER TABLE `nilai_tugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pengumpulan_tugas`
--
ALTER TABLE `pengumpulan_tugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pertemuan`
--
ALTER TABLE `pertemuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT untuk tabel `promes`
--
ALTER TABLE `promes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `prota`
--
ALTER TABLE `prota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `sikap_sosial`
--
ALTER TABLE `sikap_sosial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT untuk tabel `sikap_spiritual`
--
ALTER TABLE `sikap_spiritual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;

--
-- AUTO_INCREMENT untuk tabel `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `tugas_kelas`
--
ALTER TABLE `tugas_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=243;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`pertemuan_id`) REFERENCES `pertemuan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_absensi_pertemuan` FOREIGN KEY (`pertemuan_id`) REFERENCES `pertemuan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_absensi_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `atp`
--
ALTER TABLE `atp`
  ADD CONSTRAINT `fk_atp_bab` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `jurnal`
--
ALTER TABLE `jurnal`
  ADD CONSTRAINT `fk_jurnal_bab` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `kktp`
--
ALTER TABLE `kktp`
  ADD CONSTRAINT `fk_kktp_bab` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `lkpd`
--
ALTER TABLE `lkpd`
  ADD CONSTRAINT `fk_lkpd_bab` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `modul_ajar`
--
ALTER TABLE `modul_ajar`
  ADD CONSTRAINT `fk_modul_ajar_bab` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `nilai_tugas`
--
ALTER TABLE `nilai_tugas`
  ADD CONSTRAINT `nilai_tugas_ibfk_1` FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_tugas_ibfk_2` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD CONSTRAINT `pertemuan_ibfk_1` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `promes`
--
ALTER TABLE `promes`
  ADD CONSTRAINT `fk_promes_bab` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `prota`
--
ALTER TABLE `prota`
  ADD CONSTRAINT `fk_prota_bab` FOREIGN KEY (`bab_id`) REFERENCES `bab` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `sikap_sosial`
--
ALTER TABLE `sikap_sosial`
  ADD CONSTRAINT `sikap_sosial_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`);

--
-- Ketidakleluasaan untuk tabel `sikap_spiritual`
--
ALTER TABLE `sikap_spiritual`
  ADD CONSTRAINT `sikap_spiritual_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`);

--
-- Ketidakleluasaan untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
