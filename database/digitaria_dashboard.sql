-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2026 at 12:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `digitaria_dashboard`
--
CREATE DATABASE IF NOT EXISTS `digitaria_dashboard` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `digitaria_dashboard`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(120) NOT NULL,
  `module` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(80) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'create', 'roles', 'Menambahkan user level: Mandor', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 18:52:37'),
(2, 1, 'delete', 'products', 'Menghapus produk ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 18:54:50'),
(3, 1, 'create', 'contents', 'Menambahkan content: asoy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:14:02'),
(4, 1, 'update', 'website-settings', 'Mengubah konfigurasi website front.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:25:07'),
(5, 1, 'update', 'products', 'Mengubah produk: Meta Ads Setup 099', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:29:10'),
(6, 1, 'update', 'products', 'Mengubah produk: Meta Ads Setup 099', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:29:20'),
(7, 1, 'update', 'products', 'Mengubah produk: SEO Basic Package 098', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:29:35'),
(8, 1, 'create', 'products', 'Menambahkan produk: aremaa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:30:30'),
(9, 1, 'update', 'contents', 'Mengubah content: Sample Content 19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:30:52'),
(10, 1, 'update', 'contents', 'Mengubah content: Sample Content 19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:30:58'),
(11, 1, 'delete_image', 'contents', 'Menghapus gambar content ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:31:59'),
(12, 1, 'update', 'contents', 'Mengubah content: Sample Content 19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:32:07'),
(13, 1, 'update', 'products', 'Mengubah produk: Digital Campaign 093', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:32:57'),
(14, 1, 'save', 'brands', 'Menyimpan data brands.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:45:21'),
(15, 1, 'save', 'brands', 'Menyimpan data brands.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:45:29'),
(16, 1, 'create', 'users', 'Menambahkan user: jayus@digitaria.id', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:58:11'),
(17, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-14 19:58:16'),
(18, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:24:52'),
(19, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:40:14'),
(20, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:40:39'),
(21, 1, 'reset_password', 'users', 'Super Admin reset password user: jayus@digitaria.id', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:40:55'),
(22, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:40:59'),
(23, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:41:47'),
(24, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:41:56'),
(25, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:42:08'),
(26, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:42:13'),
(27, 4, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:42:34'),
(28, 4, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:43:10'),
(29, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:43:59'),
(30, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:52:08'),
(31, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 00:56:07'),
(32, 1, 'delete', 'products', 'Menghapus produk ID: 104', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:12:19'),
(33, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:20:32'),
(34, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 01:20:57'),
(35, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:33:18'),
(36, 1, 'update', 'products', 'Mengubah produk: SEO Basic Package 098', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:34:41'),
(37, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:35:10'),
(38, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:35:16'),
(39, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:35:19'),
(40, 1, 'update', 'products', 'Mengubah produk: Digital Campaign 093', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:35:32'),
(41, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:35:52'),
(42, 1, 'update', 'products', 'Mengubah produk: Digital Campaign 093', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:36:04'),
(43, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:36:08'),
(44, 1, 'update', 'website-settings', 'Mengubah konfigurasi website front.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:43:33'),
(45, 1, 'delete', 'banners', 'Menghapus data ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:45:12'),
(46, 1, 'update', 'products', 'Mengubah produk: Google Ads Setup 100', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 OPR/131.0.0.0', '2026-05-15 13:46:22'),
(47, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 13:53:28'),
(48, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 14:03:27'),
(49, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 14:04:26'),
(50, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 14:04:32'),
(51, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 14:04:34'),
(52, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 14:04:38'),
(53, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 14:23:14'),
(54, 1, 'update', 'contents', 'Mengubah content: Index - Hero Statistik', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 14:51:30'),
(55, 1, 'create', 'banners', 'Menambahkan banner: asoy', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:15:55'),
(56, 1, 'delete', 'banners', 'Menghapus banner ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:16:06'),
(57, 1, 'update', 'contents', 'Mengubah content: Index - Testimonials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:32:59'),
(58, 1, 'update', 'contents', 'Mengubah content: Index - Testimonials', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:40:05'),
(59, 1, 'update', 'contents', 'Mengubah content: Index - Hero Statistik', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:53:00'),
(60, 1, 'update', 'contents', 'Mengubah content: Index - Hero Statistik', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:53:12'),
(61, 1, 'update', 'contents', 'Mengubah content: Footer - Contact', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:53:37'),
(62, 1, 'update', 'contents', 'Mengubah content: Footer - Contact', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:54:04'),
(63, 1, 'update', 'contents', 'Mengubah content: Footer - Contact', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:54:09'),
(64, 1, 'update', 'contents', 'Mengubah content: Footer - Contact', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:54:33'),
(65, 1, 'update', 'contents', 'Mengubah content: Footer - Contact', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:55:24'),
(66, 1, 'update', 'contents', 'Mengubah content: Footer - Contact', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 15:55:43'),
(67, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 17:54:24'),
(68, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-08 02:00:57'),
(69, 1, 'update', 'profile', 'Mengubah profil user.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-08 03:40:32'),
(70, 1, 'logout', 'auth', 'User logout.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-08 03:40:37'),
(71, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 17:34:44'),
(72, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 17:37:57'),
(73, 1, 'update', 'products', 'Mengubah produk: VOL.TECH Access Point Pro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 17:38:08'),
(74, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 60', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 17:38:17'),
(75, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-09 17:38:25'),
(76, 1, 'login', 'auth', 'User login berhasil.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 15:34:50'),
(77, 1, 'create', 'products', 'Menambahkan produk: Test product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 15:35:47'),
(78, 1, 'update', 'profile', 'Mengubah profil user.', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 15:39:06'),
(79, 1, 'update', 'products', 'Mengubah status best seller produk ID: 55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 15:45:34'),
(80, 1, 'update', 'products', 'Mengubah status best seller produk ID: 55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 15:45:36'),
(81, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 61', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 16:36:02'),
(82, 1, 'update', 'products', 'Mengubah produk: Test product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 16:36:16'),
(83, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 62', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 17:03:48'),
(84, 1, 'upload_gallery_images', 'products', 'Upload gallery image produk: Test product', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 17:04:01'),
(85, 1, 'delete_image', 'products', 'Menghapus gambar produk ID: 66', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 17:04:23');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `placement` varchar(100) DEFAULT 'homepage',
  `status` enum('active','inactive') DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `image`, `link_url`, `placement`, `status`, `start_date`, `end_date`, `created_at`) VALUES
(1, 'Homepage Banner 1', '../assets/img/banner-home-1.png', '', 'homepage', 'active', NULL, NULL, '2026-05-15 14:52:08'),
(2, 'Homepage Banner 2', '../assets/img/banner-home-2.png', '', 'homepage', 'active', NULL, NULL, '2026-05-15 14:52:08'),
(3, 'Homepage Banner 3', '../assets/img/banner-home-3.png', '', 'homepage', 'active', NULL, NULL, '2026-05-15 14:52:08'),
(4, 'Profile Header Banner', '../assets/img/banner-profil.png', '', 'profile', 'active', NULL, NULL, '2026-05-15 14:52:08'),
(5, 'Product Header Banner', '../assets/img/banner-profil.png', '', 'product', 'active', NULL, NULL, '2026-05-15 14:52:08'),
(6, 'Contact Header Banner', '../assets/img/banner-kontak.png', '', 'contact', 'active', NULL, NULL, '2026-05-15 14:52:08');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(170) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `description`, `status`, `created_at`) VALUES
(1, 'Ubiquiti', 'ubiquiti', NULL, '', 'active', '2026-05-16 17:21:07'),
(2, 'V-SOL', 'v-sol', NULL, '', 'active', '2026-05-16 17:21:07'),
(3, 'Mikrotik', 'mikrotik', NULL, '', 'active', '2026-05-16 17:21:07'),
(4, 'VOL.TECH', 'vol-tech', NULL, '', 'active', '2026-05-16 17:21:07');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(170) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `status`, `created_at`) VALUES
(1, 'Access Point', 'access-point', '', 'active', '2026-05-16 17:21:07'),
(2, 'CCTV', 'cctv', '', 'active', '2026-05-16 17:21:07'),
(3, 'Fiber Accessories', 'fiber-accessories', '', 'active', '2026-05-16 17:21:07'),
(4, 'Media Converter', 'media-converter', '', 'active', '2026-05-16 17:21:07'),
(5, 'OLT', 'olt', '', 'active', '2026-05-16 17:21:07'),
(6, 'ONU / ONT', 'onu-ont', '', 'active', '2026-05-16 17:21:07'),
(7, 'Perangkat Jaringan', 'perangkat-jaringan', '', 'active', '2026-05-16 17:21:07'),
(8, 'Rack Accessories', 'rack-accessories', '', 'active', '2026-05-16 17:21:07'),
(9, 'Router / Gateway', 'router-gateway', '', 'active', '2026-05-16 17:21:07'),
(10, 'SFP Module', 'sfp-module', '', 'active', '2026-05-16 17:21:07'),
(11, 'Switch', 'switch', '', 'active', '2026-05-16 17:21:07'),
(12, 'Wireless Outdoor', 'wireless-outdoor', '', 'active', '2026-05-16 17:21:07');

-- --------------------------------------------------------

--
-- Table structure for table `contents`
--

DROP TABLE IF EXISTS `contents`;
CREATE TABLE `contents` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `type` varchar(80) DEFAULT 'page',
  `body` text DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contents`
--

INSERT INTO `contents` (`id`, `title`, `slug`, `type`, `body`, `status`, `created_at`) VALUES
(1, 'Index - Hero Statistik', 'index-hero-stats', 'section', '<p>10+ Tahun</p><p>Pengalaman</p><p>15.000+</p><p>Produk Terdistribusi</p><p>Distributor Resmi</p><p>Brand Global</p>', 'published', '2026-05-15 14:52:08'),
(2, 'Index - About Us', 'index-about', 'section', '<span class=\"section-label\">About Us</span>\n<h2 class=\"section-title mb-3\">Mengenal Teakwave</h2>\n<p>Teakwave adalah distributor perangkat jaringan yang berdiri sejak tahun 2014 dan berpusat di\n                        Jakarta.</p>\n<p>Kami menyediakan berbagai produk jaringan berkualitas dari brand terpercaya seperti Ubiquiti dan\n                        MikroTik. Hingga saat ini, Teakwave telah mendistribusikan lebih dari 15.000 perangkat jaringan\n                        ke berbagai wilayah di Indonesia.</p>', 'published', '2026-05-15 14:52:08'),
(3, 'Index - Mengapa Memilih Teakwave', 'index-why-choose', 'section', '<h2 class=\"section-title text-center mb-5\">Mengapa Memilih Teakwave?</h2>\n<div class=\"row g-4\">\n<div class=\"col-md-6 reveal slide-left\">\n<div class=\"feature-item\">\n<div class=\"feature-icon\"><img src=\"../assets/img/produk-original.png\"/></div>\n<div>\n<h5>Produk Original</h5>\n<p>Semua perangkat jaringan yang kami distribusikan merupakan produk asli dari brand\n                                    resmi.</p>\n</div>\n</div>\n</div>\n<div class=\"col-md-6 reveal slide-right\">\n<div class=\"feature-item\">\n<div class=\"feature-icon\"><img src=\"../assets/img/harga-kompetitif.png\"/></div>\n<div>\n<h5>Harga Kompetitif</h5>\n<p>Kami menyediakan harga terbaik bagi reseller, perusahaan, maupun pengguna akhir.</p>\n</div>\n</div>\n</div>\n<div class=\"col-md-6 reveal slide-left\">\n<div class=\"feature-item\">\n<div class=\"feature-icon\"><img src=\"../assets/img/garansi-resmi.png\"/></div>\n<div>\n<h5>Garansi Resmi</h5>\n<p>Setiap produk dilengkapi garansi dengan aturan dan ketentuan yang jelas.</p>\n</div>\n</div>\n</div>\n<div class=\"col-md-6 reveal slide-right\">\n<div class=\"feature-item\">\n<div class=\"feature-icon\"><img src=\"../assets/img/distribusi-nasional.png\"/></div>\n<div>\n<h5>Distribusi Nasional</h5>\n<p>Pengiriman produk menjangkau seluruh wilayah Indonesia.</p>\n</div>\n</div>\n</div>\n</div>', 'published', '2026-05-15 14:52:08'),
(4, 'Index - Judul Produk Best Seller', 'index-products-title', 'section', 'Produk Best Seller', 'published', '2026-05-15 14:52:08'),
(5, 'Index - Testimonials', 'index-testimonials', 'section', '<h2>TestimonialsApa Kata Mereka</h2><p><br></p><p><br></p><p><br></p><p>Banyak pelanggan telah mempercayakan kebutuhan perangkat jaringan mereka kepada Teakwave.</p><p><br></p><p><br></p><p><br></p><p>“Pelayanan cepat, RMA bagus, harga kompetitif.”</p><p><br></p><p><br></p><p><br></p><p>Bapak Donny — Gloria Nets</p><p><br></p><p><br></p><p><br></p><p>“Selain harga yang bersaing, RMA pun cepat dan bagus.”</p><p><br></p><p><br></p><p><br></p><p>Bapak David — DS Comp</p>', 'published', '2026-05-15 14:52:08'),
(6, 'Index - Marketplace', 'index-marketplace', 'section', '<span class=\"section-label reveal slide-left\">Marketplace</span>\n<h2 class=\"section-title mb-4 reveal slide-right\">Beli Produk Teakwave Secara Online</h2>\n<div class=\"marketplace-list text-start\">\n<div class=\"market-row reveal slide-left\">\n<div>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></div>\n<a class=\"market-btn tokopedia\" href=\"#\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/logo-tokopedia.png\"/></a>\n</div>\n<div class=\"market-row reveal slide-right\">\n<div>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong></div>\n<a class=\"market-btn shopee\" href=\"#\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/logo-shopee.png\"/></a>\n</div>\n<div class=\"market-row reveal slide-left\">\n<div>Ingin harga yang lebih kompetitif? Order by <strong>WhatsApp.</strong></div>\n<a class=\"market-btn whatsapp\" href=\"https://wa.me/6282112345678\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/logo-whatsapp.png\"/></a>\n</div>\n</div>', 'published', '2026-05-15 14:52:08'),
(7, 'Footer - Contact', 'footer-contact', 'page', '<h2>Hubungi Kami</h2><p>Kompleks Harco Elektronik Mangga Dua Blok H-6 Raya Jl. Mangga Dua Dalam Jakarta, DKI Jakarta 10730</p><p><a href=\"mailto:sales@teakwave.com\" rel=\"noopener noreferrer nofollow\" target=\"_blank\">sales@teakwave.com </a></p>', 'published', '2026-05-15 14:52:08'),
(8, 'Footer - Company', 'footer-company', 'footer', '<h2 class=\"fw-bold mb-3\">Teakwave</h2>\n<p>Distributor perangkat jaringan nirkabel dan internet berkualitas untuk berbagai kebutuhan\n                            jaringan di Indonesia.</p>\n<div class=\"social mt-3\">\n<a aria-label=\"Instagram\" href=\"#\"><i class=\"bi bi-instagram\"></i></a>\n<a aria-label=\"Facebook\" href=\"#\"><i class=\"bi bi-facebook\"></i></a>\n</div>', 'published', '2026-05-15 14:52:08'),
(9, 'Profil - Tentang Kami', 'tentang-kami', 'page', '<p>Didirikan pada tahun 2014, Teakwave adalah perusahaan spesialis di bidang perangkat jaringan dan\n                        nirkabel yang berbasis di Jakarta. Sebagai distributor resmi berbagai produk wireless, kami\n                        menyediakan beragam perangkat jaringan berkualitas tinggi dengan harga yang terjangkau.</p>\n<p>Selain itu, kami juga memberikan layanan konsultasi teknis untuk membantu pelanggan memahami\n                        fitur dan manfaat dari produk yang kami tawarkan. Tim kami siap membantu dalam proses\n                        troubleshooting apabila dibutuhkan. Untuk memastikan solusi yang tepat, kami juga dapat\n                        melakukan pengecekan awal menggunakan link planner guna menilai kesesuaian produk dengan\n                        kebutuhan sistem Anda.</p>\n<p>Dengan komitmen untuk menghadirkan solusi terbaik bagi kebutuhan jaringan dan nirkabel, Teakwave\n                        menjadi pilihan yang tepat untuk mengoptimalkan performa infrastruktur Anda.</p>', 'published', '2026-05-15 14:52:08'),
(10, 'Profil - Authorized Distributor', 'profile-authorized', 'section', '<div class=\"auth-label\">Authorized Distributor of:</div>\n<div aria-label=\"Brand distributor resmi\" class=\"auth-brand-row\">\n<span class=\"auth-logo voltech\"><img src=\"../assets/img/logo-voltech.png\"/></span>\n<span class=\"auth-logo voltech\"><img src=\"../assets/img/logo-vsol.png\"/></span>\n<span class=\"auth-logo voltech\"><img src=\"../assets/img/logo-ubiquiti.png\"/></span>\n<span class=\"auth-logo voltech\"><img src=\"../assets/img/logo-mikrotik.png\"/></span>\n</div>', 'published', '2026-05-15 14:52:08'),
(11, 'Kontak - Informasi Kontak', 'kontak', 'page', '<h2 class=\"contact-info-title\">Kami Siap Membantu Kebutuhan Jaringan Anda</h2>\n<p>Punya pertanyaan, butuh konsultasi produk, atau ingin mendapatkan penawaran terbaik?<br/>Tim\n                        Teakwave siap membantu Anda dengan pelayanan yang cepat dan profesional.</p>\n<div class=\"contact-company\">PT Makmur Jati Teknologi</div>\n<p>Kompleks Harco Elektronik Mangga Dua Blok H-6, Jl. Mangga Dua Raya, Jakarta Pusat 10730</p>\n<div class=\"contact-method-grid\">\n<div class=\"contact-method reveal slide-left\">\n<div class=\"contact-method-icon\"><i class=\"bi bi-envelope\"></i></div>\n<div>\n<strong>Email</strong>\n<a href=\"mailto:sales@teakwave.com\">sales@teakwave.com</a>\n</div>\n</div>\n<div class=\"contact-method reveal slide-right\">\n<div class=\"contact-method-icon\"><i class=\"bi bi-globe2\"></i></div>\n<div>\n<strong>Website</strong>\n<a href=\"https://teakwave.com\" rel=\"noopener\" target=\"_blank\">teakwave.com</a>\n</div>\n</div>\n<div class=\"contact-method reveal slide-left\">\n<div class=\"contact-method-icon\"><i class=\"bi bi-telephone-fill\"></i></div>\n<div>\n<strong>WhatsApp Sales</strong>\n<a href=\"https://wa.me/6289527932474\" rel=\"noopener\" target=\"_blank\">+6289527932474</a>\n</div>\n</div>\n<div class=\"contact-method reveal slide-right\">\n<div class=\"contact-method-icon\"><i class=\"bi bi-instagram\"></i></div>\n<div>\n<strong>Instagram</strong>\n<a href=\"https://instagram.com/teak.wave\" rel=\"noopener\" target=\"_blank\">teak.wave</a>\n</div>\n</div>\n</div>', 'published', '2026-05-15 14:52:08'),
(12, 'Produk - Marketplace', 'produk-marketplace', 'section', '<span class=\"market-title-pill reveal\">Marketplace</span>\n<h2 class=\"section-title mb-4 reveal\">Beli Produk Teakwave Secara Online</h2>\n<div class=\"market-mini-wrap\">\n<div class=\"row g-3\">\n<div class=\"col-md-4 reveal slide-left\">\n<div class=\"market-card-mini\">\n<p>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></p>\n<a class=\"market-btn\" href=\"#\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/logo-tokopedia.png\"/></a>\n</div>\n</div>\n<div class=\"col-md-4 reveal\">\n<div class=\"market-card-mini\">\n<p>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong>\n</p>\n<a class=\"market-btn\" href=\"#\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/logo-shopee.png\"/></a>\n</div>\n</div>\n<div class=\"col-md-4 reveal slide-right\">\n<div class=\"market-card-mini\">\n<p>Ingin harga yang lebih kompetitif? Order by <strong>WhatsApp.</strong></p>\n<a class=\"market-btn\" href=\"https://wa.me/6282112345678\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/logo-whatsapp.png\"/></a>\n</div>\n</div>\n</div>\n</div>', 'published', '2026-05-15 14:52:08'),
(13, 'Produk - Brands', 'produk-brands', 'section', '<span class=\"brand-title-pill reveal\">Our Brands</span>\n<div class=\"brand-grid\">\n<div class=\"row g-3\">\n<div class=\"col-md-6 reveal slide-left\">\n<div class=\"brand-card\">\n<div class=\"brand-logo-text ubiquiti\"><img src=\"../assets/img/logo-ubiquiti.png\"/>\n</div>\n<p>Brand global yang dikenal dengan performa tinggi dan stabilitas untuk jaringan\n                                    wireless, banyak digunakan oleh ISP dan enterprise.</p>\n</div>\n</div>\n<div class=\"col-md-6 reveal slide-right\">\n<div class=\"brand-card\">\n<div class=\"brand-logo-text vsol\"><img src=\"../assets/img/logo-vsol.png\"/></div>\n<p>Solusi perangkat GPON dan fiber optic yang handal dan efisien, cocok untuk kebutuhan\n                                    ISP dan pengembangan jaringan FTTH.</p>\n</div>\n</div>\n<div class=\"col-md-6 reveal slide-left\">\n<div class=\"brand-card\">\n<div class=\"brand-logo-text mikrotik\"><img src=\"../assets/img/logo-mikrotik.png\"/></div>\n<p>Perangkat jaringan dengan fleksibilitas tinggi, fitur lengkap, dan harga kompetitif,\n                                    menjadi pilihan utama para profesional jaringan.</p>\n</div>\n</div>\n<div class=\"col-md-6 reveal slide-right\">\n<div class=\"brand-card\">\n<div class=\"brand-logo-text voltech\"><img src=\"../assets/img/logo-voltech.png\"/></div>\n<p>Brand perangkat jaringan yang menghadirkan solusi modern untuk kebutuhan\n                                    konektivitas.</p>\n</div>\n</div>\n</div>\n</div>', 'published', '2026-05-15 14:52:08'),
(14, 'Produk Detail - Marketplace', 'produk-detail-marketplace', 'section', '<span class=\"market-title-pill reveal\">Marketplace</span>\n<h2 class=\"section-title mb-4 reveal\">Beli Produk Teakwave Secara Online</h2>\n<div class=\"market-mini-wrap\">\n<div class=\"row g-3\">\n<div class=\"col-md-4 reveal slide-left\">\n<div class=\"market-card-mini\">\n<p>Belanja produk jaringan resmi Teakwave melalui <strong>Tokopedia.</strong></p>\n<a class=\"market-btn\" href=\"#\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/logo-tokopedia.png\"/></a>\n</div>\n</div>\n<div class=\"col-md-4 reveal\">\n<div class=\"market-card-mini\">\n<p>Temukan berbagai perangkat jaringan dengan harga terbaik di <strong>Shopee.</strong>\n</p>\n<a class=\"market-btn\" href=\"#\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/logo-shopee.png\"/></a>\n</div>\n</div>\n<div class=\"col-md-4 reveal slide-right\">\n<div class=\"market-card-mini\">\n<p>Ingin harga yang lebih kompetitif? Order by <strong>WhatsApp.</strong></p>\n<a class=\"market-btn\" href=\"https://wa.me/6282112345678\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/logo-whatsapp.png\"/></a>\n</div>\n</div>\n</div>\n</div>', 'published', '2026-05-15 14:52:08'),
(15, 'Kontak - Pembelian Produk', 'kontak-purchase', 'section', '<div><h2>Pembelian Produk</h2><p>Anda dapat membeli produk Teakwave melalui marketplace resmi atau langsung menghubungi tim kami.</p></div>\n<div aria-label=\"Link pembelian produk\" class=\"purchase-actions\">\n<a aria-label=\"Tokopedia\" class=\"purchase-action tokopedia\" href=\"#\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/icon-tokopedia.png\"></a>\n<a aria-label=\"Shopee\" class=\"purchase-action shopee\" href=\"#\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/icon-shopee.png\"></a>\n<a aria-label=\"WhatsApp\" class=\"purchase-action whatsapp\" href=\"https://wa.me/6289527932474\" rel=\"noopener\" target=\"_blank\"><img src=\"../assets/img/icon-whatsapp.png\"></a>\n</div>', 'published', '2026-05-16 17:20:58');

-- --------------------------------------------------------

--
-- Table structure for table `content_images`
--

DROP TABLE IF EXISTS `content_images`;
CREATE TABLE `content_images` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_files`
--

DROP TABLE IF EXISTS `media_files`;
CREATE TABLE `media_files` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `brand_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_best_seller` int(1) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `sku`, `category`, `price`, `image`, `stock`, `status`, `description`, `created_at`, `brand_id`, `category_id`, `is_best_seller`, `updated_at`) VALUES
(1, 'UniFi U6 Lite Access Point', 'TW-UBIQUITI-001', NULL, 0.00, '../produk/1.png', 0, 'active', '<p>UniFi U6 Lite Access Point adalah produk access point dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 1, 1, NULL),
(2, 'UniFi U6 Plus Indoor AP', 'TW-UBIQUITI-002', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>UniFi U6 Plus Indoor AP adalah produk access point dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 1, 1, NULL),
(3, 'UniFi U7 Pro WiFi AP', 'TW-UBIQUITI-003', NULL, 0.00, '../produk/3.png', 0, 'active', '<p>UniFi U7 Pro WiFi AP adalah produk access point dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 1, 1, NULL),
(4, 'UniFi Dream Router', 'TW-UBIQUITI-004', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>UniFi Dream Router adalah produk router / gateway dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 9, 1, NULL),
(5, 'UniFi Cloud Gateway Ultra', 'TW-UBIQUITI-005', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>UniFi Cloud Gateway Ultra adalah produk router / gateway dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 9, 1, NULL),
(6, 'UniFi Switch Lite 8 PoE', 'TW-UBIQUITI-006', NULL, 0.00, '../produk/6.png', 0, 'active', '<p>UniFi Switch Lite 8 PoE adalah produk switch dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 11, 1, NULL),
(7, 'UniFi Switch 24 PoE', 'TW-UBIQUITI-007', NULL, 0.00, '../produk/7.png', 0, 'active', '<p>UniFi Switch 24 PoE adalah produk switch dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 11, 1, NULL),
(8, 'EdgeRouter X Gigabit', 'TW-UBIQUITI-008', NULL, 0.00, '../produk/1.png', 0, 'active', '<p>EdgeRouter X Gigabit adalah produk router / gateway dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 9, 1, NULL),
(9, 'LiteBeam 5AC Gen2', 'TW-UBIQUITI-009', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>LiteBeam 5AC Gen2 adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 12, 1, NULL),
(10, 'NanoBeam 5AC Bridge', 'TW-UBIQUITI-010', NULL, 0.00, '../produk/3.png', 0, 'active', '<p>NanoBeam 5AC Bridge adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 12, 1, NULL),
(11, 'PowerBeam 5AC ISO', 'TW-UBIQUITI-011', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>PowerBeam 5AC ISO adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 12, 0, NULL),
(12, 'Rocket Prism AC Radio', 'TW-UBIQUITI-012', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>Rocket Prism AC Radio adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 12, 0, NULL),
(13, 'airMAX Sector Antenna', 'TW-UBIQUITI-013', NULL, 0.00, '../produk/6.png', 0, 'active', '<p>airMAX Sector Antenna adalah produk wireless outdoor dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 12, 0, NULL),
(14, 'UniFi Protect G5 Dome', 'TW-UBIQUITI-014', NULL, 0.00, '../produk/7.png', 0, 'active', '<p>UniFi Protect G5 Dome adalah produk cctv dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 2, 0, NULL),
(15, 'UniFi Flex Mini Switch', 'TW-UBIQUITI-015', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>UniFi Flex Mini Switch adalah produk switch dari brand Ubiquiti yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 1, 11, 0, NULL),
(16, 'V-SOL GPON ONU 1GE', 'TW-VSOL-016', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>V-SOL GPON ONU 1GE adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 6, 0, NULL),
(17, 'V-SOL ONU WiFi AC1200', 'TW-VSOL-017', NULL, 0.00, '../produk/7.png', 0, 'active', '<p>V-SOL ONU WiFi AC1200 adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 6, 0, NULL),
(18, 'V-SOL HG323AC Dual Band', 'TW-VSOL-018', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>V-SOL HG323AC Dual Band adalah produk perangkat jaringan dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 7, 0, NULL),
(19, 'V-SOL V2802RH Optical Unit', 'TW-VSOL-019', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>V-SOL V2802RH Optical Unit adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 6, 0, NULL),
(20, 'V-SOL V2801SG Mini ONU', 'TW-VSOL-020', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>V-SOL V2801SG Mini ONU adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 6, 0, NULL),
(21, 'V-SOL OLT 4 Port GPON', 'TW-VSOL-021', NULL, 0.00, '../produk/1.png', 0, 'active', '<p>V-SOL OLT 4 Port GPON adalah produk olt dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 5, 0, NULL),
(22, 'V-SOL OLT 8 Port GPON', 'TW-VSOL-022', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>V-SOL OLT 8 Port GPON adalah produk olt dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 5, 0, NULL),
(23, 'V-SOL OLT 16 Port Rack', 'TW-VSOL-023', NULL, 0.00, '../produk/3.png', 0, 'active', '<p>V-SOL OLT 16 Port Rack adalah produk olt dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 5, 0, NULL),
(24, 'V-SOL XPON Router WiFi', 'TW-VSOL-024', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>V-SOL XPON Router WiFi adalah produk router / gateway dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 9, 0, NULL),
(25, 'V-SOL Fiber ONT Voice', 'TW-VSOL-025', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>V-SOL Fiber ONT Voice adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 6, 0, NULL),
(26, 'V-SOL PoE ONU Outdoor', 'TW-VSOL-026', NULL, 0.00, '../produk/6.png', 0, 'active', '<p>V-SOL PoE ONU Outdoor adalah produk onu / ont dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 6, 0, NULL),
(27, 'V-SOL CATV Optical Node', 'TW-VSOL-027', NULL, 0.00, '../produk/7.png', 0, 'active', '<p>V-SOL CATV Optical Node adalah produk perangkat jaringan dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 7, 0, NULL),
(28, 'V-SOL SFP GPON Module', 'TW-VSOL-028', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>V-SOL SFP GPON Module adalah produk sfp module dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 10, 0, NULL),
(29, 'V-SOL Optical Splitter 1:8', 'TW-VSOL-029', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>V-SOL Optical Splitter 1:8 adalah produk fiber accessories dari brand V-SOL yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 2, 3, 0, NULL),
(30, 'MikroTik hAP ax2 Router', 'TW-MIKROTIK-030', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>MikroTik hAP ax2 Router adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 9, 0, NULL),
(31, 'MikroTik hAP ax3 WiFi 6', 'TW-MIKROTIK-031', NULL, 0.00, '../produk/1.png', 0, 'active', '<p>MikroTik hAP ax3 WiFi 6 adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 9, 0, NULL),
(32, 'MikroTik RB750Gr3 hEX', 'TW-MIKROTIK-032', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>MikroTik RB750Gr3 hEX adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 9, 0, NULL),
(33, 'MikroTik RB5009 Router', 'TW-MIKROTIK-033', NULL, 0.00, '../produk/3.png', 0, 'active', '<p>MikroTik RB5009 Router adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 9, 0, NULL),
(34, 'MikroTik CRS326 Switch', 'TW-MIKROTIK-034', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>MikroTik CRS326 Switch adalah produk switch dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 11, 0, NULL),
(35, 'MikroTik CSS610 8G Switch', 'TW-MIKROTIK-035', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>MikroTik CSS610 8G Switch adalah produk switch dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 11, 0, NULL),
(36, 'MikroTik cAP ax Ceiling', 'TW-MIKROTIK-036', NULL, 0.00, '../produk/6.png', 0, 'active', '<p>MikroTik cAP ax Ceiling adalah produk access point dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 1, 0, NULL),
(37, 'MikroTik SXT LTE Kit', 'TW-MIKROTIK-037', NULL, 0.00, '../produk/7.png', 0, 'active', '<p>MikroTik SXT LTE Kit adalah produk wireless outdoor dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 12, 0, NULL),
(38, 'MikroTik LHG 5 Antenna', 'TW-MIKROTIK-038', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>MikroTik LHG 5 Antenna adalah produk wireless outdoor dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 12, 0, NULL),
(39, 'MikroTik mANTBox 15s', 'TW-MIKROTIK-039', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>MikroTik mANTBox 15s adalah produk wireless outdoor dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 12, 0, NULL),
(40, 'MikroTik NetMetal ac²', 'TW-MIKROTIK-040', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>MikroTik NetMetal ac² adalah produk perangkat jaringan dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 7, 0, NULL),
(41, 'MikroTik wAP ac Outdoor', 'TW-MIKROTIK-041', NULL, 0.00, '../produk/1.png', 0, 'active', '<p>MikroTik wAP ac Outdoor adalah produk wireless outdoor dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 12, 0, NULL),
(42, 'MikroTik RouterBOARD PoE', 'TW-MIKROTIK-042', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>MikroTik RouterBOARD PoE adalah produk router / gateway dari brand Mikrotik yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 3, 9, 0, NULL),
(43, 'VOL.TECH GPON ONT 1GE', 'TW-VOLTECH-043', NULL, 0.00, '../produk/3.png', 0, 'active', '<p>VOL.TECH GPON ONT 1GE adalah produk onu / ont dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 6, 0, NULL),
(44, 'VOL.TECH ONT Dual Band', 'TW-VOLTECH-044', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>VOL.TECH ONT Dual Band adalah produk onu / ont dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 6, 0, NULL),
(45, 'VOL.TECH OLT 4 Port', 'TW-VOLTECH-045', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>VOL.TECH OLT 4 Port adalah produk olt dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 5, 0, NULL),
(46, 'VOL.TECH OLT 8 Port', 'TW-VOLTECH-046', NULL, 0.00, '../produk/6.png', 0, 'active', '<p>VOL.TECH OLT 8 Port adalah produk olt dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 5, 0, NULL),
(47, 'VOL.TECH PoE Switch 8 Port', 'TW-VOLTECH-047', NULL, 0.00, '../produk/7.png', 0, 'active', '<p>VOL.TECH PoE Switch 8 Port adalah produk switch dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 11, 0, NULL),
(48, 'VOL.TECH Gigabit Switch 16', 'TW-VOLTECH-048', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>VOL.TECH Gigabit Switch 16 adalah produk switch dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 11, 0, NULL),
(49, 'VOL.TECH Media Converter', 'TW-VOLTECH-049', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>VOL.TECH Media Converter adalah produk media converter dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 4, 0, NULL),
(50, 'VOL.TECH SFP Module 1.25G', 'TW-VOLTECH-050', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>VOL.TECH SFP Module 1.25G adalah produk sfp module dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 10, 0, NULL),
(51, 'VOL.TECH Fiber Patch Cord', 'TW-VOLTECH-051', NULL, 0.00, '../produk/1.png', 0, 'active', '<p>VOL.TECH Fiber Patch Cord adalah produk fiber accessories dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 3, 0, NULL),
(52, 'VOL.TECH Optical Splitter 1:16', 'TW-VOLTECH-052', NULL, 0.00, '../produk/2.png', 0, 'active', '<p>VOL.TECH Optical Splitter 1:16 adalah produk fiber accessories dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 3, 0, NULL),
(53, 'VOL.TECH Outdoor CPE AC', 'TW-VOLTECH-053', NULL, 0.00, '../produk/3.png', 0, 'active', '<p>VOL.TECH Outdoor CPE AC adalah produk wireless outdoor dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 12, 0, NULL),
(54, 'VOL.TECH Access Point Pro', 'TW-VOLTECH-054', NULL, 0.00, '../produk/4.png', 0, 'active', '<p>VOL.TECH Access Point Pro adalah produk access point dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 1, 0, '2026-06-09 17:38:08'),
(55, 'VOL.TECH Rackmount PDU', 'TW-VOLTECH-055', NULL, 0.00, '../produk/5.png', 0, 'active', '<p>VOL.TECH Rackmount PDU adalah produk rack accessories dari brand VOL.TECH yang tersedia dalam katalog Teakwave.</p><p>Data produk ini disinkronkan dari listing frontend agar katalog di website dan dashboard backend sama.</p>', '2026-05-16 17:21:07', 4, 8, 0, '2026-06-14 15:45:36'),
(56, 'Test product', '', NULL, 800000.00, '../uploads/test-product-product-main-20260614183616-a378f7.webp', 200, 'active', '<p><br></p>', '2026-06-14 15:35:47', 1, 1, 1, '2026-06-14 16:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `created_at`) VALUES
(1, 1, '../produk/1.png', 1, '2026-05-16 17:21:08'),
(2, 2, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(3, 3, '../produk/3.png', 1, '2026-05-16 17:21:08'),
(4, 4, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(5, 5, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(6, 6, '../produk/6.png', 1, '2026-05-16 17:21:08'),
(7, 7, '../produk/7.png', 1, '2026-05-16 17:21:08'),
(8, 8, '../produk/1.png', 1, '2026-05-16 17:21:08'),
(9, 9, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(10, 10, '../produk/3.png', 1, '2026-05-16 17:21:08'),
(11, 11, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(12, 12, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(13, 13, '../produk/6.png', 1, '2026-05-16 17:21:08'),
(14, 14, '../produk/7.png', 1, '2026-05-16 17:21:08'),
(15, 15, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(16, 16, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(17, 17, '../produk/7.png', 1, '2026-05-16 17:21:08'),
(18, 18, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(19, 19, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(20, 20, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(21, 21, '../produk/1.png', 1, '2026-05-16 17:21:08'),
(22, 22, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(23, 23, '../produk/3.png', 1, '2026-05-16 17:21:08'),
(24, 24, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(25, 25, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(26, 26, '../produk/6.png', 1, '2026-05-16 17:21:08'),
(27, 27, '../produk/7.png', 1, '2026-05-16 17:21:08'),
(28, 28, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(29, 29, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(30, 30, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(31, 31, '../produk/1.png', 1, '2026-05-16 17:21:08'),
(32, 32, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(33, 33, '../produk/3.png', 1, '2026-05-16 17:21:08'),
(34, 34, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(35, 35, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(36, 36, '../produk/6.png', 1, '2026-05-16 17:21:08'),
(37, 37, '../produk/7.png', 1, '2026-05-16 17:21:08'),
(38, 38, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(39, 39, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(40, 40, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(41, 41, '../produk/1.png', 1, '2026-05-16 17:21:08'),
(42, 42, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(43, 43, '../produk/3.png', 1, '2026-05-16 17:21:08'),
(44, 44, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(45, 45, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(46, 46, '../produk/6.png', 1, '2026-05-16 17:21:08'),
(47, 47, '../produk/7.png', 1, '2026-05-16 17:21:08'),
(48, 48, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(49, 49, '../produk/4.png', 1, '2026-05-16 17:21:08'),
(50, 50, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(51, 51, '../produk/1.png', 1, '2026-05-16 17:21:08'),
(52, 52, '../produk/2.png', 1, '2026-05-16 17:21:08'),
(53, 53, '../produk/3.png', 1, '2026-05-16 17:21:08'),
(55, 55, '../produk/5.png', 1, '2026-05-16 17:21:08'),
(56, 54, '../uploads/vol-tech-access-point-pro-product-gallery-1-20260609193808-6e719a.webp', 0, '2026-06-09 17:38:08'),
(57, 54, '../uploads/vol-tech-access-point-pro-product-gallery-2-20260609193808-1c557a.webp', 0, '2026-06-09 17:38:08'),
(58, 54, '../uploads/vol-tech-access-point-pro-product-gallery-3-20260609193808-a8588a.webp', 0, '2026-06-09 17:38:08'),
(63, 56, '../uploads/test-product-product-gallery-2-20260614183616-5d8e4c.webp', 0, '2026-06-14 15:35:47'),
(64, 56, '../uploads/test-product-product-gallery-3-20260614183616-80b5cd.webp', 0, '2026-06-14 15:35:47'),
(65, 56, '../uploads/test-product-product-gallery-4-20260614183616-169e68.jpg', 0, '2026-06-14 16:36:16'),
(67, 56, '../uploads/test-product-product-gallery-2-20260614190401-b3bd36.png', 0, '2026-06-14 17:04:01'),
(68, 56, '../uploads/test-product-product-gallery-3-20260614190401-6f5460.png', 0, '2026-06-14 17:04:01');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `created_at`) VALUES
(1, 'Super Admin', 'super-admin', '2026-05-14 17:56:19'),
(2, 'Admin', 'admin', '2026-05-14 17:56:19'),
(3, 'Editor', 'editor', '2026-05-14 17:56:19'),
(4, 'Marketing', 'marketing', '2026-05-14 17:56:19'),
(6, 'Mandor', 'mandor', '2026-05-14 18:52:37');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `page_key` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `page_key`, `created_at`) VALUES
(1, 1, 'dashboard', '2026-05-14 18:28:03'),
(2, 1, 'users', '2026-05-14 18:28:03'),
(3, 1, 'roles', '2026-05-14 18:28:03'),
(5, 1, 'products-list', '2026-05-14 18:28:03'),
(6, 1, 'products-add', '2026-05-14 18:28:03'),
(7, 1, 'products-edit', '2026-05-14 18:28:03'),
(8, 1, 'brands', '2026-05-14 18:28:03'),
(9, 1, 'categories', '2026-05-14 18:28:03'),
(10, 1, 'files', '2026-05-14 18:28:03'),
(11, 1, 'banners', '2026-05-14 18:28:03'),
(12, 2, 'dashboard', '2026-05-14 18:28:03'),
(14, 2, 'products-list', '2026-05-14 18:28:03'),
(15, 2, 'products-add', '2026-05-14 18:28:03'),
(16, 2, 'products-edit', '2026-05-14 18:28:03'),
(17, 2, 'brands', '2026-05-14 18:28:03'),
(18, 2, 'categories', '2026-05-14 18:28:03'),
(19, 2, 'files', '2026-05-14 18:28:03'),
(20, 2, 'banners', '2026-05-14 18:28:03'),
(21, 3, 'dashboard', '2026-05-14 18:28:03'),
(23, 3, 'files', '2026-05-14 18:28:03'),
(27, 1, 'contents-list', '2026-05-14 18:38:49'),
(28, 2, 'contents-list', '2026-05-14 18:38:49'),
(29, 3, 'contents-list', '2026-05-14 18:38:49'),
(30, 1, 'contents-add', '2026-05-14 18:38:49'),
(31, 2, 'contents-add', '2026-05-14 18:38:49'),
(32, 3, 'contents-add', '2026-05-14 18:38:49'),
(33, 1, 'contents-edit', '2026-05-14 18:38:49'),
(34, 2, 'contents-edit', '2026-05-14 18:38:49'),
(35, 3, 'contents-edit', '2026-05-14 18:38:49'),
(45, 4, 'dashboard', '2026-05-14 18:50:00'),
(46, 4, 'products-list', '2026-05-14 18:50:00'),
(47, 4, 'products-add', '2026-05-14 18:50:00'),
(48, 4, 'products-edit', '2026-05-14 18:50:00'),
(49, 1, 'logs', '2026-05-14 18:51:58'),
(50, 2, 'logs', '2026-05-14 18:51:58'),
(51, 6, 'dashboard', '2026-05-14 18:52:37'),
(52, 6, 'contents-list', '2026-05-14 18:52:37'),
(53, 6, 'products-list', '2026-05-14 18:52:37'),
(54, 6, 'files', '2026-05-14 18:52:37'),
(55, 6, 'logs', '2026-05-14 18:52:37'),
(56, 1, 'website-settings', '2026-05-14 19:24:24'),
(57, 2, 'website-settings', '2026-05-14 19:24:24'),
(58, 1, 'backup-restore', '2026-07-17 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `avatar`, `status`, `created_at`) VALUES
(1, 1, 'Seira', 'admin@digitaria.id', '$2y$10$ds9qt.vDvwqi.T2ElQLVn.SKdz0Wh31qmecBQVrqpukLe.PW8rmkW', '../uploads/avatar_6a2ecb1a7d1fe6.64054107.jpg', 'active', '2026-05-14 17:56:19'),
(3, 4, 'Aries Riyanto', 'Syiqma84@gmail.com', '$2y$10$fcEHPYPLDL5laOF7zvUySOH.z5bhekPw7BlioZK.wV6OiXM12o3eC', NULL, 'active', '2026-05-14 18:09:50'),
(4, 4, 'jayus', 'jayus@digitaria.id', '$2y$10$OOADhUfhN7oLqVwXZcI1JeneYniTx4dV5HggqqTMDuhEjoMCtCiwS', NULL, 'active', '2026-05-14 19:58:11');

-- --------------------------------------------------------

--
-- Table structure for table `website_settings`
--

DROP TABLE IF EXISTS `website_settings`;
CREATE TABLE `website_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_settings`
--

INSERT INTO `website_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Teakwave', 'text', '2026-05-14 19:24:24', '2026-05-16 17:20:57'),
(2, 'meta_title', 'Teakwave - Solusi Perangkat Jaringan Nirkabel', 'text', '2026-05-14 19:24:24', '2026-05-16 17:20:57'),
(3, 'meta_description', 'Distributor perangkat jaringan nirkabel dan internet berkualitas untuk berbagai kebutuhan jaringan di Indonesia.', 'textarea', '2026-05-14 19:24:24', '2026-05-16 17:20:57'),
(4, 'meta_keywords', 'teakwave, perangkat jaringan, wireless, router, access point, mikrotik, ubiquiti, v-sol, vol.tech', 'textarea', '2026-05-14 19:24:24', '2026-05-16 17:20:57'),
(5, 'favicon', '../uploads/favicon_6a0621935cf2e5.50652961.png', 'file', '2026-05-14 19:24:24', '2026-05-14 19:25:07'),
(6, 'timezone', 'Asia/Jakarta', 'text', '2026-05-14 19:24:24', '2026-05-15 13:43:33'),
(7, 'date_format', 'd M Y', 'text', '2026-05-14 19:24:24', '2026-05-15 13:43:33'),
(8, 'time_format', 'H:i', 'text', '2026-05-14 19:24:24', '2026-05-15 13:43:33'),
(24, 'upload_max_filesize_mb', '1', 'number', '2026-05-15 13:43:33', '2026-05-15 13:43:33'),
(25, 'upload_allowed_extensions', 'jpg,jpeg,png,gif,webp,pdf,ico', 'textarea', '2026-05-15 13:43:33', '2026-05-15 13:43:33'),
(27, 'footer_label', 'Contact', 'text', '2026-05-16 17:53:49', '2026-05-16 17:53:49'),
(28, 'footer_title', 'Hubungi Kami', 'text', '2026-05-16 17:53:49', '2026-05-16 17:53:49'),
(29, 'footer_address', 'Kompleks Harco Elektronik Mangga Dua Blok H-6\nRaya Jl. Mangga Dua Dalam Jakarta, DKI Jakarta 10730', 'textarea', '2026-05-16 17:53:49', '2026-05-16 17:53:49'),
(30, 'footer_email', 'sales@teakwave.com', 'text', '2026-05-16 17:53:49', '2026-05-16 17:53:49'),
(31, 'footer_phone', '(021) 6121 005', 'text', '2026-05-16 17:53:49', '2026-05-16 17:53:49'),
(32, 'footer_phone_link', '0216121005', 'text', '2026-05-16 17:53:49', '2026-05-16 17:53:49'),
(33, 'footer_company_title', 'Teakwave', 'text', '2026-05-16 17:53:49', '2026-05-16 17:53:49'),
(34, 'footer_description', 'Distributor perangkat jaringan nirkabel dan internet berkualitas untuk berbagai kebutuhan jaringan di Indonesia.', 'textarea', '2026-05-16 17:53:49', '2026-05-16 17:53:49'),
(35, 'footer_instagram_url', '#', 'text', '2026-05-16 17:53:49', '2026-05-16 17:53:49'),
(36, 'footer_facebook_url', '#', 'text', '2026-05-16 17:53:49', '2026-05-16 17:53:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `contents`
--
ALTER TABLE `contents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `content_images`
--
ALTER TABLE `content_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`);

--
-- Indexes for table `media_files`
--
ALTER TABLE `media_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_page_unique` (`role_id`,`page_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `website_settings`
--
ALTER TABLE `website_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `contents`
--
ALTER TABLE `contents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `content_images`
--
ALTER TABLE `content_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_files`
--
ALTER TABLE `media_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `website_settings`
--
ALTER TABLE `website_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `content_images`
--
ALTER TABLE `content_images`
  ADD CONSTRAINT `content_images_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
