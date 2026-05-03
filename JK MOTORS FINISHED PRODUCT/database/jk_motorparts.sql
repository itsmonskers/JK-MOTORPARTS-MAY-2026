-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 03, 2026 at 05:23 PM
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
-- Database: `jk_motorparts`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(2, 1, 'login', 'User logged in', '::1', '2025-12-03 09:16:23'),
(3, 1, 'logout', 'User logged out', '::1', '2025-12-03 09:16:30'),
(4, 3, 'registration', 'New user registered', '::1', '2025-12-03 09:16:56'),
(5, 3, 'login', 'User logged in', '::1', '2025-12-03 09:17:04'),
(6, 3, 'rsa_request', 'Created RSA request: RSA-20251203-9F41E3', '::1', '2025-12-03 09:59:38'),
(7, 3, 'logout', 'User logged out', '::1', '2025-12-03 10:46:14'),
(8, 1, 'login', 'User logged in', '::1', '2025-12-03 10:46:17'),
(9, 3, 'login', 'User logged in', '192.168.1.2', '2025-12-03 10:46:32'),
(10, 1, 'rsa_update', 'Updated RSA request ID 1 to status: assigned', '::1', '2025-12-03 11:14:28'),
(11, 1, 'rsa_update', 'Updated RSA request ID 1 to status: assigned', '::1', '2025-12-03 11:14:43'),
(12, 3, 'rsa_request', 'Created RSA request: RSA-20251203-98F188', '192.168.1.2', '2025-12-03 11:15:37'),
(13, 1, 'rsa_update', 'Updated RSA request ID 2 to status: assigned', '::1', '2025-12-03 11:15:56'),
(14, 3, 'rsa_request', 'Created RSA request: RSA-20251203-0D82E1', '192.168.1.2', '2025-12-03 11:16:00'),
(15, 3, 'rsa_request', 'Created RSA request: RSA-20251203-794EF1', '192.168.1.2', '2025-12-03 11:16:55'),
(16, 1, 'rsa_update', 'Updated RSA request ID 4 to status: assigned', '::1', '2025-12-03 11:17:23'),
(17, 1, 'logout', 'User logged out', '::1', '2025-12-03 11:19:11'),
(18, 3, 'login', 'User logged in', '::1', '2025-12-03 11:19:14'),
(19, 3, 'logout', 'User logged out', '::1', '2025-12-03 11:37:09'),
(20, 1, 'login', 'User logged in', '::1', '2025-12-03 11:37:11'),
(21, 1, 'logout', 'User logged out', '::1', '2025-12-03 11:37:38'),
(22, 3, 'login', 'User logged in', '::1', '2025-12-03 11:37:41'),
(23, 3, 'rsa_cancel', 'Cancelled RSA request: RSA-20251203-0D82E1', '::1', '2025-12-03 11:45:34'),
(24, 3, 'logout', 'User logged out', '::1', '2025-12-03 11:45:45'),
(25, 1, 'login', 'User logged in', '::1', '2025-12-03 11:45:48'),
(26, 1, 'logout', 'User logged out', '::1', '2025-12-03 11:50:05'),
(27, 2, 'login', 'User logged in', '::1', '2025-12-03 11:50:08'),
(28, 2, 'logout', 'User logged out', '::1', '2025-12-03 12:26:56'),
(29, 3, 'login', 'User logged in', '::1', '2025-12-03 12:27:00'),
(30, 3, 'logout', 'User logged out', '::1', '2025-12-03 12:27:26'),
(31, 2, 'login', 'User logged in', '::1', '2025-12-03 12:27:46'),
(32, 2, 'logout', 'User logged out', '::1', '2025-12-03 12:27:48'),
(33, 1, 'login', 'User logged in', '::1', '2025-12-03 12:27:53'),
(34, 3, 'rsa_request', 'Created RSA request: RSA-20251203-550609', '192.168.1.2', '2025-12-03 12:29:09'),
(35, 1, 'rsa_update', 'Updated RSA request ID 5 to status: assigned', '::1', '2025-12-03 12:29:45'),
(36, 1, 'logout', 'User logged out', '::1', '2025-12-03 12:29:47'),
(37, 2, 'login', 'User logged in', '::1', '2025-12-03 12:29:50'),
(38, 2, 'rsa_diagnostic', 'Added diagnostic for RSA request ID 5', '::1', '2025-12-03 12:30:29'),
(39, 2, 'logout', 'User logged out', '::1', '2025-12-03 12:30:56'),
(40, 3, 'login', 'User logged in', '::1', '2025-12-03 12:31:00'),
(41, 3, 'logout', 'User logged out', '::1', '2025-12-03 12:41:15'),
(42, 1, 'login', 'User logged in', '::1', '2025-12-03 12:41:18'),
(43, 1, 'logout', 'User logged out', '::1', '2025-12-03 12:41:29'),
(44, 2, 'login', 'User logged in', '::1', '2025-12-03 12:41:41'),
(45, 2, 'rsa_update', 'Updated RSA request ID 5 to status: completed', '::1', '2025-12-03 12:41:56'),
(46, 2, 'logout', 'User logged out', '::1', '2025-12-03 12:42:06'),
(47, 1, 'login', 'User logged in', '::1', '2025-12-03 12:42:09'),
(48, 1, 'logout', 'User logged out', '::1', '2025-12-03 12:42:51'),
(49, 2, 'login', 'User logged in', '::1', '2025-12-03 12:42:53'),
(50, 2, 'logout', 'User logged out', '::1', '2025-12-03 12:43:01'),
(51, 3, 'login', 'User logged in', '::1', '2025-12-03 12:43:11'),
(52, 3, 'rsa_request', 'Created RSA request: RSA-20251203-0D102F', '::1', '2025-12-03 12:43:44'),
(53, 3, 'rsa_cancel', 'Cancelled RSA request: RSA-20251203-0D102F', '::1', '2025-12-03 12:50:04'),
(54, 3, 'rsa_request', 'Created RSA request: RSA-20251203-A03E83', '::1', '2025-12-03 12:50:18'),
(55, 3, 'rsa_cancel', 'Cancelled RSA request: RSA-20251203-A03E83', '::1', '2025-12-03 12:56:18'),
(56, 3, 'rsa_request', 'Created RSA request: RSA-20251203-34E7B4', '::1', '2025-12-03 12:56:35'),
(57, 3, 'rsa_cancel', 'Cancelled RSA request: RSA-20251203-34E7B4', '::1', '2025-12-03 13:00:59'),
(58, 3, 'logout', 'User logged out', '::1', '2025-12-03 13:01:03'),
(59, 1, 'login', 'User logged in', '::1', '2025-12-03 13:01:06'),
(60, 1, 'logout', 'User logged out', '::1', '2025-12-03 13:01:17'),
(61, 3, 'login', 'User logged in', '::1', '2025-12-03 13:01:23'),
(62, 3, 'rsa_request', 'Created RSA request: RSA-20251203-BC52FB', '::1', '2025-12-03 13:01:31'),
(63, 3, 'logout', 'User logged out', '::1', '2025-12-03 13:01:44'),
(64, 2, 'login', 'User logged in', '::1', '2025-12-03 13:01:46'),
(65, 2, 'rsa_diagnostic', 'Added diagnostic for RSA request ID 4', '::1', '2025-12-03 13:02:00'),
(66, 2, 'logout', 'User logged out', '::1', '2025-12-03 13:10:37'),
(67, 3, 'login', 'User logged in', '::1', '2025-12-03 13:10:39'),
(68, 3, 'rsa_request', 'Created RSA request: RSA-20251203-E17219', '::1', '2025-12-03 13:10:54'),
(69, 3, 'logout', 'User logged out', '::1', '2025-12-03 13:11:37'),
(70, 2, 'login', 'User logged in', '::1', '2025-12-03 13:11:39'),
(71, 2, 'logout', 'User logged out', '::1', '2025-12-03 13:11:45'),
(72, 1, 'login', 'User logged in', '::1', '2025-12-03 13:11:49'),
(73, 1, 'rsa_update', 'Updated RSA request ID 10 to status: assigned', '::1', '2025-12-03 13:12:04'),
(74, 1, 'logout', 'User logged out', '::1', '2025-12-03 13:12:05'),
(75, 2, 'login', 'User logged in', '::1', '2025-12-03 13:12:08'),
(76, 1, 'login', 'User logged in', '::1', '2025-12-04 04:09:59'),
(77, 1, 'logout', 'User logged out', '::1', '2025-12-04 04:10:06'),
(78, 2, 'login', 'User logged in', '::1', '2025-12-04 04:10:09'),
(79, 2, 'logout', 'User logged out', '::1', '2025-12-04 04:10:32'),
(80, 3, 'login', 'User logged in', '::1', '2025-12-04 04:10:35'),
(81, 3, 'logout', 'User logged out', '::1', '2025-12-04 04:10:47'),
(82, 3, 'login', 'User logged in', '::1', '2025-12-04 04:11:11'),
(83, 3, 'rsa_cancel', 'Cancelled RSA request: RSA-20251203-BC52FB', '::1', '2025-12-04 04:12:02'),
(84, 3, 'rsa_request', 'Created RSA request: RSA-20251204-FBC970', '::1', '2025-12-04 04:12:31'),
(85, 3, 'logout', 'User logged out', '::1', '2025-12-04 04:16:13'),
(86, 1, 'login', 'User logged in', '::1', '2025-12-04 04:16:16'),
(87, 1, 'rsa_update', 'Updated RSA request ID 11 to status: assigned', '::1', '2025-12-04 04:16:23'),
(88, 1, 'logout', 'User logged out', '::1', '2025-12-04 04:16:25'),
(89, 2, 'login', 'User logged in', '::1', '2025-12-04 04:16:27'),
(90, 2, 'rsa_diagnostic', 'Added diagnostic for RSA request ID 11', '::1', '2025-12-04 04:16:43'),
(91, 2, 'logout', 'User logged out', '::1', '2025-12-04 04:16:47'),
(92, 3, 'login', 'User logged in', '::1', '2025-12-04 04:16:50'),
(93, 3, 'rsa_request', 'Created RSA request: RSA-20251204-80AED3', '::1', '2025-12-04 04:22:00'),
(94, 3, 'rsa_request', 'Created RSA request: RSA-20251204-CF282A', '::1', '2025-12-04 04:24:44'),
(95, 3, 'rsa_cancel', 'Cancelled RSA request: RSA-20251204-CF282A', '::1', '2025-12-04 04:47:36'),
(96, 3, 'rsa_cancel', 'Cancelled RSA request: RSA-20251204-80AED3', '::1', '2025-12-04 04:47:41'),
(97, 3, 'rsa_request', 'Created RSA request: RSA-20251204-BC491A', '::1', '2025-12-04 04:47:55'),
(98, 3, 'logout', 'User logged out', '::1', '2025-12-04 04:49:34'),
(99, 2, 'login', 'User logged in', '::1', '2025-12-04 04:49:37'),
(100, 2, 'logout', 'User logged out', '::1', '2025-12-04 04:49:54'),
(101, 3, 'login', 'User logged in', '::1', '2025-12-04 04:49:58'),
(102, 3, 'rsa_request', 'Created RSA request: RSA-20251204-5975F2', '::1', '2025-12-04 04:57:57'),
(103, 3, 'rsa_request', 'Created RSA request: RSA-20251204-FB821C', '::1', '2025-12-04 05:05:03'),
(104, 3, 'rsa_request', 'Created RSA request: RSA-20251204-ED0563', '::1', '2025-12-04 05:07:26'),
(105, 3, 'logout', 'User logged out', '::1', '2025-12-04 05:11:09'),
(106, 2, 'login', 'User logged in', '::1', '2025-12-04 05:11:11'),
(107, 2, 'logout', 'User logged out', '::1', '2025-12-04 05:22:42'),
(108, 3, 'login', 'User logged in', '::1', '2025-12-04 05:22:45'),
(109, 3, 'rsa_request', 'Created RSA request: RSA-20251204-C9DB2E', '::1', '2025-12-04 05:23:40'),
(110, 3, 'logout', 'User logged out', '::1', '2025-12-04 05:33:27'),
(111, 2, 'login', 'User logged in', '::1', '2025-12-04 05:33:29'),
(112, 2, 'logout', 'User logged out', '::1', '2025-12-04 05:33:40'),
(113, 1, 'login', 'User logged in', '::1', '2025-12-04 05:33:42'),
(114, 1, 'rsa_update', 'Updated RSA request ID 18 to status: assigned', '::1', '2025-12-04 05:33:54'),
(115, 1, 'logout', 'User logged out', '::1', '2025-12-04 05:34:01'),
(116, 2, 'login', 'User logged in', '::1', '2025-12-04 05:34:03'),
(117, 2, 'logout', 'User logged out', '::1', '2025-12-04 05:43:25'),
(118, 1, 'login', 'User logged in', '::1', '2025-12-04 05:43:27'),
(119, 3, 'login', 'User logged in', '192.168.1.6', '2025-12-04 05:43:36'),
(120, 3, 'rsa_request', 'Created RSA request: RSA-20251204-271259', '192.168.1.6', '2025-12-04 05:44:18'),
(121, 1, 'rsa_update', 'Updated RSA request ID 19 to status: assigned', '::1', '2025-12-04 05:44:31'),
(122, 3, 'rsa_request', 'Created RSA request: RSA-20251204-469A43', '192.168.1.6', '2025-12-04 05:44:36'),
(123, 1, 'logout', 'User logged out', '::1', '2025-12-04 05:44:44'),
(124, 2, 'login', 'User logged in', '::1', '2025-12-04 05:44:46'),
(125, 2, 'logout', 'User logged out', '::1', '2025-12-04 06:27:24'),
(126, 2, 'login', 'User logged in', '::1', '2025-12-04 06:27:27'),
(127, 2, 'logout', 'User logged out', '::1', '2025-12-04 06:27:29'),
(128, 3, 'login', 'User logged in', '::1', '2025-12-04 06:27:32'),
(129, 3, 'logout', 'User logged out', '::1', '2025-12-04 06:28:59'),
(130, 2, 'login', 'User logged in', '::1', '2025-12-04 06:29:02'),
(131, 2, 'logout', 'User logged out', '::1', '2025-12-04 06:38:24'),
(132, 1, 'login', 'User logged in', '::1', '2025-12-04 06:38:28'),
(133, 3, 'rsa_request', 'Created RSA request: RSA-20251204-2B3D0D', '192.168.1.6', '2025-12-04 06:39:46'),
(134, 1, 'rsa_update', 'Updated RSA request ID 21 to status: assigned', '::1', '2025-12-04 06:40:10'),
(135, 1, 'logout', 'User logged out', '::1', '2025-12-04 06:40:14'),
(136, 2, 'login', 'User logged in', '::1', '2025-12-04 06:40:16'),
(137, 3, 'rsa_request', 'Created RSA request: RSA-20251204-4CAD69', '192.168.1.6', '2025-12-04 06:46:12'),
(138, 2, 'logout', 'User logged out', '::1', '2025-12-04 06:46:27'),
(139, 1, 'login', 'User logged in', '::1', '2025-12-04 06:46:30'),
(140, 1, 'rsa_update', 'Updated RSA request ID 22 to status: assigned', '::1', '2025-12-04 06:46:45'),
(141, 1, 'logout', 'User logged out', '::1', '2025-12-04 06:46:46'),
(142, 2, 'login', 'User logged in', '::1', '2025-12-04 06:46:49'),
(143, 2, 'logout', 'User logged out', '::1', '2025-12-04 06:50:55'),
(144, 3, 'login', 'User logged in', '::1', '2025-12-04 06:50:58'),
(145, 3, 'rsa_request', 'Created RSA request: RSA-20251204-16FEF3', '::1', '2025-12-04 06:51:13'),
(146, 3, 'logout', 'User logged out', '::1', '2025-12-04 06:51:18'),
(147, 2, 'login', 'User logged in', '::1', '2025-12-04 06:51:20'),
(148, 2, 'logout', 'User logged out', '::1', '2025-12-04 06:55:51'),
(149, 3, 'login', 'User logged in', '::1', '2025-12-04 06:55:54'),
(150, 3, 'rsa_request', 'Created RSA request: RSA-20251204-BF289E', '::1', '2025-12-04 06:56:11'),
(151, 3, 'logout', 'User logged out', '::1', '2025-12-04 06:56:25'),
(152, 1, 'login', 'User logged in', '::1', '2025-12-04 06:56:28'),
(153, 1, 'rsa_update', 'Updated RSA request ID 24 to status: assigned', '::1', '2025-12-04 06:56:41'),
(154, 1, 'logout', 'User logged out', '::1', '2025-12-04 06:56:42'),
(155, 2, 'login', 'User logged in', '::1', '2025-12-04 06:56:47'),
(156, 2, 'logout', 'User logged out', '::1', '2025-12-04 06:57:35'),
(157, 3, 'login', 'User logged in', '::1', '2025-12-04 06:57:37'),
(158, 3, 'rsa_request', 'Created RSA request: RSA-20251204-71EB1F', '::1', '2025-12-04 07:00:55'),
(159, 3, 'logout', 'User logged out', '::1', '2025-12-04 07:01:02'),
(160, 1, 'login', 'User logged in', '::1', '2025-12-04 07:01:04'),
(161, 1, 'rsa_update', 'Updated RSA request ID 25 to status: assigned', '::1', '2025-12-04 07:01:20'),
(162, 1, 'logout', 'User logged out', '::1', '2025-12-04 07:01:23'),
(163, 2, 'login', 'User logged in', '::1', '2025-12-04 07:01:26'),
(164, 2, 'logout', 'User logged out', '::1', '2025-12-04 07:09:14'),
(165, 3, 'login', 'User logged in', '::1', '2025-12-04 07:09:16'),
(166, 3, 'rsa_request', 'Created RSA request: RSA-20251204-4C3D77', '::1', '2025-12-04 07:13:40'),
(167, 3, 'rsa_request', 'Created RSA request: RSA-20251204-FE6C16', '::1', '2025-12-04 07:14:07'),
(168, 3, 'logout', 'User logged out', '::1', '2025-12-04 07:18:47'),
(169, 2, 'login', 'User logged in', '::1', '2025-12-04 07:18:49'),
(170, 2, 'logout', 'User logged out', '::1', '2025-12-04 07:19:02'),
(171, 1, 'login', 'User logged in', '::1', '2025-12-04 07:19:05'),
(172, 1, 'rsa_update', 'Updated RSA request ID 27 to status: assigned', '::1', '2025-12-04 07:19:16'),
(173, 1, 'logout', 'User logged out', '::1', '2025-12-04 07:19:17'),
(174, 2, 'login', 'User logged in', '::1', '2025-12-04 07:19:20'),
(175, 2, 'logout', 'User logged out', '::1', '2025-12-04 07:24:47'),
(176, 3, 'login', 'User logged in', '::1', '2025-12-04 07:24:49'),
(177, 3, 'rsa_request', 'Created RSA request: RSA-20251204-15EE51', '::1', '2025-12-04 07:25:05'),
(178, 3, 'logout', 'User logged out', '::1', '2025-12-04 07:25:12'),
(179, 2, 'login', 'User logged in', '::1', '2025-12-04 07:25:14'),
(180, 2, 'logout', 'User logged out', '::1', '2025-12-04 07:25:50'),
(181, 3, 'login', 'User logged in', '::1', '2025-12-04 07:25:52'),
(182, 3, 'rsa_request', 'Created RSA request: RSA-20251204-5A2435', '::1', '2025-12-04 07:26:13'),
(183, 3, 'rsa_request', 'Created RSA request: RSA-20251204-7BE526', '::1', '2025-12-04 07:26:47'),
(184, 3, 'rsa_request', 'Created RSA request: RSA-20251204-7E7E71', '::1', '2025-12-04 07:28:39'),
(185, 3, 'logout', 'User logged out', '::1', '2025-12-04 07:28:48'),
(186, 2, 'login', 'User logged in', '::1', '2025-12-04 07:28:50'),
(187, 2, 'logout', 'User logged out', '::1', '2025-12-04 07:30:43'),
(188, 3, 'login', 'User logged in', '::1', '2025-12-04 07:30:47'),
(189, 3, 'rsa_request', 'Created RSA request: RSA-20251204-FB7411', '::1', '2025-12-04 07:30:55'),
(190, 3, 'logout', 'User logged out', '::1', '2025-12-04 07:32:17'),
(191, 1, 'login', 'User logged in', '::1', '2025-12-04 07:32:19'),
(192, 1, 'rsa_update', 'Updated RSA request ID 32 to status: assigned', '::1', '2025-12-04 07:32:26'),
(193, 1, 'logout', 'User logged out', '::1', '2025-12-04 07:32:27'),
(194, 2, 'login', 'User logged in', '::1', '2025-12-04 07:32:29'),
(195, 2, 'logout', 'User logged out', '::1', '2025-12-04 07:32:37'),
(196, 3, 'login', 'User logged in', '::1', '2025-12-04 08:15:00'),
(197, 3, 'rsa_request', 'Created RSA request: RSA-20251204-36253A', '::1', '2025-12-04 08:15:31'),
(198, 3, 'logout', 'User logged out', '::1', '2025-12-04 08:15:49'),
(199, 1, 'login', 'User logged in', '::1', '2025-12-04 08:15:54'),
(200, 1, 'rsa_update', 'Updated RSA request ID 33 to status: pending', '::1', '2025-12-04 08:16:06'),
(201, 1, 'rsa_update', 'Updated RSA request ID 33 to status: assigned', '::1', '2025-12-04 08:16:12'),
(202, 1, 'logout', 'User logged out', '::1', '2025-12-04 08:16:13'),
(203, 2, 'login', 'User logged in', '::1', '2025-12-04 08:16:16'),
(204, 2, 'rsa_diagnostic', 'Added diagnostic for RSA request ID 33', '::1', '2025-12-04 08:16:31'),
(205, 2, 'logout', 'User logged out', '::1', '2025-12-04 08:16:38'),
(206, 3, 'login', 'User logged in', '::1', '2025-12-04 08:16:47'),
(207, 3, 'logout', 'User logged out', '::1', '2025-12-04 08:18:29'),
(208, 1, 'login', 'User logged in', '::1', '2025-12-04 08:18:35'),
(209, 3, 'login', 'User logged in', '192.168.1.6', '2025-12-04 08:23:22'),
(210, 3, 'rsa_request', 'Created RSA request: RSA-20251204-B9321E', '192.168.1.6', '2025-12-04 08:24:43'),
(211, 1, 'rsa_update', 'Updated RSA request ID 34 to status: assigned', '::1', '2025-12-04 08:25:10'),
(212, 1, 'logout', 'User logged out', '::1', '2025-12-04 08:25:13'),
(213, 2, 'login', 'User logged in', '::1', '2025-12-04 08:25:15'),
(214, 3, 'logout', 'User logged out', '192.168.1.6', '2025-12-04 08:26:29'),
(215, 2, 'logout', 'User logged out', '::1', '2025-12-04 08:26:43'),
(216, 2, 'login', 'User logged in', '192.168.1.6', '2025-12-04 08:27:02'),
(217, 2, 'rsa_diagnostic', 'Added diagnostic for RSA request ID 34', '192.168.1.6', '2025-12-04 08:28:04'),
(218, 2, 'logout', 'User logged out', '192.168.1.6', '2025-12-04 08:28:24'),
(219, 1, 'login', 'User logged in', '::1', '2025-12-04 08:28:40'),
(220, 3, 'login', 'User logged in', '192.168.1.6', '2025-12-04 08:29:25'),
(221, 3, 'rsa_request', 'Created RSA request: RSA-20251204-4D9D89', '192.168.1.6', '2025-12-04 08:31:00'),
(222, 1, 'rsa_update', 'Updated RSA request ID 35 to status: assigned', '::1', '2025-12-04 08:31:52'),
(223, 1, 'logout', 'User logged out', '::1', '2025-12-04 08:32:13'),
(224, 2, 'login', 'User logged in', '::1', '2025-12-04 08:32:15'),
(225, 2, 'rsa_diagnostic', 'Added diagnostic for RSA request ID 35', '::1', '2025-12-04 08:32:58'),
(226, 2, 'rsa_update', 'Updated RSA request ID 35 to status: completed', '::1', '2025-12-04 08:33:05'),
(227, 2, 'logout', 'User logged out', '::1', '2025-12-04 08:35:02'),
(228, 2, 'login', 'User logged in', '::1', '2025-12-04 08:35:05'),
(229, 2, 'logout', 'User logged out', '::1', '2025-12-04 08:35:07'),
(230, 1, 'login', 'User logged in', '::1', '2025-12-04 08:35:09'),
(231, 1, 'logout', 'User logged out', '::1', '2025-12-04 08:35:38'),
(232, 2, 'login', 'User logged in', '::1', '2025-12-04 08:35:40'),
(233, 2, 'rsa_update', 'Updated RSA request ID 35 to status: completed', '::1', '2025-12-04 08:40:25'),
(234, 2, 'rsa_update', 'Updated RSA request ID 35 to status: in_progress', '::1', '2025-12-04 08:42:41'),
(235, 2, 'rsa_update', 'Updated RSA request ID 35 to status: completed', '::1', '2025-12-04 08:42:50'),
(236, 2, 'rsa_update', 'Updated RSA request ID 35 to status: completed', '::1', '2025-12-04 08:43:54'),
(237, 2, 'rsa_update', 'Updated RSA request ID 35 to status: pending', '::1', '2025-12-04 09:47:51'),
(238, 2, 'rsa_update', 'Updated RSA request ID 35 to status: completed', '::1', '2025-12-04 09:47:55'),
(239, 2, 'rsa_update', 'Updated RSA request ID 34 to status: completed', '::1', '2025-12-04 10:01:00'),
(240, 2, 'rsa_update', 'Updated RSA request ID 35 to status: pending', '::1', '2025-12-04 10:01:24'),
(241, 2, 'rsa_update', 'Updated RSA request ID 35 to status: completed', '::1', '2025-12-04 10:01:28'),
(242, 2, 'rsa_update', 'Updated RSA request ID 33 to status: completed', '::1', '2025-12-04 10:01:35'),
(243, 2, 'logout', 'User logged out', '::1', '2025-12-04 10:03:19'),
(244, 1, 'login', 'User logged in', '::1', '2025-12-04 10:03:22'),
(245, 3, 'rsa_request', 'Created RSA request: RSA-20251205-750D48', '192.168.1.6', '2025-12-04 18:05:59'),
(246, 3, 'rsa_request', 'Created RSA request: RSA-20251205-BB8A29', '192.168.1.6', '2025-12-04 18:07:07'),
(247, 1, 'rsa_update', 'Updated RSA request ID 37 to status: assigned', '::1', '2025-12-04 18:08:23'),
(248, 1, 'logout', 'User logged out', '::1', '2025-12-04 18:08:27'),
(249, 2, 'login', 'User logged in', '::1', '2025-12-04 18:08:30'),
(250, 2, 'rsa_diagnostic', 'Added diagnostic for RSA request ID 37', '::1', '2025-12-04 18:09:01'),
(251, 2, 'logout', 'User logged out', '::1', '2025-12-04 18:09:14'),
(252, 3, 'login', 'User logged in', '::1', '2025-12-04 18:09:17'),
(253, 3, 'logout', 'User logged out', '::1', '2025-12-04 18:09:28'),
(254, 2, 'login', 'User logged in', '::1', '2025-12-04 18:09:31'),
(255, 2, 'rsa_update', 'Updated RSA request ID 37 to status: completed', '::1', '2025-12-04 18:09:39'),
(256, 2, 'logout', 'User logged out', '::1', '2025-12-04 18:14:32'),
(257, 1, 'login', 'User logged in', '::1', '2025-12-04 18:14:34'),
(258, 1, 'transaction', 'Processed transaction #TXN-20260503232057-9D73 for customer ID 3', '::1', '2026-05-03 15:20:57');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `barcode` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock`, `barcode`, `category`, `is_archived`, `archived_at`, `date_added`, `updated_at`) VALUES
(1, 'Motor Oil 1L', 'High-quality motor oil for all vehicle types', 350.00, 100, 'PROD001', 'Lubricants', 0, NULL, '2025-12-03 09:16:15', '2025-12-03 09:16:15'),
(2, 'Brake Pad Set', 'Premium brake pad set front and rear', 1200.00, 50, 'PROD002', 'Brake System', 0, NULL, '2025-12-03 09:16:15', '2025-12-03 09:16:15'),
(3, 'Air Filter', 'Standard air filter replacement', 450.00, 74, 'PROD003', 'Filters', 0, NULL, '2025-12-03 09:16:15', '2026-05-03 15:20:57'),
(4, 'Spark Plug', 'Iridium spark plug set of 4', 800.00, 60, 'PROD004', 'Ignition', 0, NULL, '2025-12-03 09:16:15', '2025-12-03 09:16:15'),
(5, 'Battery 12V', 'Car battery 12V 60Ah', 3500.00, 30, 'PROD005', 'Electrical', 0, NULL, '2025-12-03 09:16:15', '2025-12-03 09:16:15');

-- --------------------------------------------------------

--
-- Table structure for table `rewards_catalog`
--

CREATE TABLE `rewards_catalog` (
  `id` int(11) NOT NULL,
  `reward_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `required_points` int(11) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `is_archived` tinyint(1) DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards_catalog`
--

INSERT INTO `rewards_catalog` (`id`, `reward_name`, `description`, `required_points`, `discount_percentage`, `is_active`, `is_archived`, `archived_at`, `created_at`, `updated_at`) VALUES
(1, '5% Discount', 'Get 5% discount on your next purchase', 100, 5.00, 1, 0, NULL, '2025-12-03 09:16:15', '2025-12-03 09:16:15'),
(2, '10% Discount', 'Get 10% discount on your next purchase', 200, 10.00, 1, 0, NULL, '2025-12-03 09:16:15', '2025-12-03 09:16:15'),
(3, 'Free Oil Change', 'Free motor oil change service', 500, 0.00, 1, 0, NULL, '2025-12-03 09:16:15', '2025-12-03 09:16:15'),
(4, '15% Discount', 'Get 15% discount on your next purchase', 300, 15.00, 1, 0, NULL, '2025-12-03 09:16:15', '2025-12-03 09:16:15'),
(5, 'Free Towing Service', 'Free roadside towing service', 1000, 0.00, 1, 0, NULL, '2025-12-03 09:16:15', '2025-12-03 09:16:15');

-- --------------------------------------------------------

--
-- Table structure for table `rewards_redemptions`
--

CREATE TABLE `rewards_redemptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `points_used` int(11) NOT NULL,
  `status` enum('pending','approved','redeemed','cancelled') DEFAULT 'pending',
  `redemption_code` varchar(50) DEFAULT NULL,
  `date_redeemed` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rsa_diagnostics`
--

CREATE TABLE `rsa_diagnostics` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `issue_confirmed` varchar(255) DEFAULT NULL,
  `problem_description` text DEFAULT NULL,
  `parts_needed` varchar(500) DEFAULT NULL,
  `estimated_resolution` varchar(100) DEFAULT NULL,
  `diagnostic_notes` text DEFAULT NULL,
  `media_files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media_files`)),
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rsa_diagnostics`
--

INSERT INTO `rsa_diagnostics` (`id`, `request_id`, `issue_confirmed`, `problem_description`, `parts_needed`, `estimated_resolution`, `diagnostic_notes`, `media_files`, `created_by`, `created_at`) VALUES
(7, 37, 'Battery Issue', 'Need mo double A', 'Double A', '15-30 minutes', 'Yun lang naman', '[\"6931ce3d4e3bb_1705465-3840x2160-desktop-4k-honda-rebel-wallpaper-image.jpg\"]', 2, '2025-12-05 02:09:01');

-- --------------------------------------------------------

--
-- Table structure for table `rsa_requests`
--

CREATE TABLE `rsa_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `issue_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `location` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `contact_number` varchar(20) NOT NULL,
  `status` enum('pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `date_requested` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_completed` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `cancellation_note` text DEFAULT NULL,
  `date_cancelled` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rsa_requests`
--

INSERT INTO `rsa_requests` (`id`, `user_id`, `ticket_number`, `issue_type`, `description`, `location`, `latitude`, `longitude`, `contact_number`, `status`, `assigned_to`, `admin_notes`, `technician_notes`, `date_requested`, `date_completed`, `cancellation_reason`, `cancellation_note`, `date_cancelled`) VALUES
(37, 3, 'RSA-20251205-BB8A29', 'Battery Dead', 'Battery', 'Zafiro Street, San Andres Bukid, Fifth District, Manila, Capital District, Metro Manila, 1017, Philippines', 14.57260600, 121.00210630, '09701945544', 'completed', 2, '', 'Done EZ', '2025-12-04 18:07:07', '2025-12-04 18:09:39', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rsa_request_media`
--

CREATE TABLE `rsa_request_media` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rsa_request_media`
--

INSERT INTO `rsa_request_media` (`id`, `request_id`, `media_type`, `file_path`, `uploaded_by`, `uploaded_at`) VALUES
(33, 37, 'image', '/uploads/customer/6931cdcbb9579_6c774309-7.png', 3, '2025-12-05 02:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `rsa_response_times`
--

CREATE TABLE `rsa_response_times` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `submission_time` datetime NOT NULL,
  `received_time` datetime DEFAULT NULL,
  `response_time_seconds` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rsa_response_times`
--

INSERT INTO `rsa_response_times` (`id`, `request_id`, `submission_time`, `received_time`, `response_time_seconds`) VALUES
(36, 37, '2025-12-05 02:07:07', '2025-12-05 02:08:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','gcash') DEFAULT 'cash',
  `points_earned` int(11) DEFAULT 0,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `amount_received` decimal(10,2) DEFAULT 0.00,
  `change_due` decimal(10,2) DEFAULT 0.00,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `transaction_number`, `subtotal`, `total_amount`, `payment_method`, `points_earned`, `discount_amount`, `amount_received`, `change_due`, `transaction_date`) VALUES
(1, 3, 'TXN-20260503232057-9D73', 0.00, 450.00, '', 450, 0.00, 450.00, 0.00, '2026-05-03 15:20:57');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_discounts`
--

CREATE TABLE `transaction_discounts` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `discount_type` varchar(50) NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_items`
--

CREATE TABLE `transaction_items` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_items`
--

INSERT INTO `transaction_items` (`id`, `transaction_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 3, 1, 450.00, 450.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer','technician') DEFAULT 'customer',
  `contact` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `contact`, `address`, `points`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@jkmotorparts.com', '$2y$10$JAXrPu.iIdIbh9bOADB8vuWv4./Uuhox1nvmNWBZ7NYaIwcko2qWm', 'admin', '09123456789', NULL, 0, '2025-12-03 09:16:15', '2026-05-03 15:22:33'),
(2, 'John Technician', 'technician@jkmotorparts.com', '$2y$10$CBKLmSjh7rR7Seb9.6uHwefhLR6Yaf4Vvxcbj4NbE90fE9AKNhqo.', 'technician', '09123456790', NULL, 0, '2025-12-03 09:16:16', '2026-05-03 15:22:33'),
(3, 'Simon', 'monotuazon@gmail.com', '$2y$10$b0J1F.cg8CpRu2GOpB.CQ.0xXjGgHLNU6RotPZhjLDolS4Yh7hEH6', 'customer', 'monotuazon@gmail.com', 'San Andres Bukid Manila', 450, '2025-12-03 09:16:56', '2026-05-03 15:20:57');

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
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`);

--
-- Indexes for table `rewards_catalog`
--
ALTER TABLE `rewards_catalog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rewards_redemptions`
--
ALTER TABLE `rewards_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `redemption_code` (`redemption_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reward_id` (`reward_id`);

--
-- Indexes for table `rsa_diagnostics`
--
ALTER TABLE `rsa_diagnostics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `rsa_requests`
--
ALTER TABLE `rsa_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `rsa_request_media`
--
ALTER TABLE `rsa_request_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `rsa_response_times`
--
ALTER TABLE `rsa_response_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rsa_response_times_request_id` (`request_id`),
  ADD KEY `idx_rsa_response_times_submission` (`submission_time`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transaction_discounts`
--
ALTER TABLE `transaction_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transaction_id` (`transaction_id`);

--
-- Indexes for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=259;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4596;

--
-- AUTO_INCREMENT for table `rewards_catalog`
--
ALTER TABLE `rewards_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rewards_redemptions`
--
ALTER TABLE `rewards_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rsa_diagnostics`
--
ALTER TABLE `rsa_diagnostics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rsa_requests`
--
ALTER TABLE `rsa_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `rsa_request_media`
--
ALTER TABLE `rsa_request_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `rsa_response_times`
--
ALTER TABLE `rsa_response_times`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transaction_discounts`
--
ALTER TABLE `transaction_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction_items`
--
ALTER TABLE `transaction_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rewards_redemptions`
--
ALTER TABLE `rewards_redemptions`
  ADD CONSTRAINT `rewards_redemptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rewards_redemptions_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `rewards_catalog` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rsa_diagnostics`
--
ALTER TABLE `rsa_diagnostics`
  ADD CONSTRAINT `rsa_diagnostics_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `rsa_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rsa_diagnostics_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `rsa_requests`
--
ALTER TABLE `rsa_requests`
  ADD CONSTRAINT `rsa_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rsa_requests_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rsa_request_media`
--
ALTER TABLE `rsa_request_media`
  ADD CONSTRAINT `rsa_request_media_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `rsa_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rsa_request_media_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `rsa_response_times`
--
ALTER TABLE `rsa_response_times`
  ADD CONSTRAINT `rsa_response_times_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `rsa_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_discounts`
--
ALTER TABLE `transaction_discounts`
  ADD CONSTRAINT `fk_transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaction_items`
--
ALTER TABLE `transaction_items`
  ADD CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
