-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 01:04 AM
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
-- Database: `sharetoneighbour`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `registration_key` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `created_at`, `registration_key`, `is_active`) VALUES
(2, 'Chitraranjan', '$2y$10$oZoYyHL6eNhx1RNBJRQEKeQpZAWWHTYOWhNtB2Q9XHWVjsosqYR8a', 'Chitraranjan Yadav', '2026-03-19 11:37:04', '360', 1),
(3, 'Chitra', '$2y$10$Q56NnFT5RuI10FpxNfa8yerubQjFjtmzTgfBN9N7xKxpIdxnboZWO', 'Chitraranjan', '2026-04-27 09:17:46', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `admin_recovery_tokens`
--

CREATE TABLE `admin_recovery_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `issued_by_super_admin_id` int(11) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `furniture_items`
--

CREATE TABLE `furniture_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `category` enum('sofa','table','chair','bed','shelf','desk','wardrobe','other') NOT NULL DEFAULT 'other',
  `condition_level` enum('like_new','good','fair','needs_repair') NOT NULL DEFAULT 'good',
  `photo` varchar(255) DEFAULT NULL,
  `video_link` varchar(500) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('available','requested','taken') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `accepted_request_id` int(11) DEFAULT NULL,
  `taken_at` datetime DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `furniture_items`
--

INSERT INTO `furniture_items` (`id`, `user_id`, `title`, `description`, `category`, `condition_level`, `photo`, `video_link`, `latitude`, `longitude`, `status`, `created_at`, `updated_at`, `accepted_request_id`, `taken_at`, `is_deleted`, `deleted_at`) VALUES
(22, 10, 'SOFA', 'small, black , orignal', 'sofa', 'good', 'item_1773929213_6903.jpg', '', 55.67870000, 12.57160000, 'available', '2026-03-19 14:06:54', '2026-03-26 09:14:00', NULL, NULL, 0, NULL),
(23, 10, 'Chair', 'small, black , orignal', 'chair', 'good', 'item_1773929252_7628.jpg', '', 55.67870000, 12.57160000, 'taken', '2026-03-19 14:07:32', '2026-04-05 11:52:41', NULL, NULL, 0, NULL),
(24, 9, 'Sofa', 'large, free, strach', 'sofa', 'good', 'item_1773929307_6747.jpg', '', 55.66750000, 12.55590000, 'taken', '2026-03-19 14:08:27', '2026-04-29 08:29:21', NULL, NULL, 0, NULL),
(25, 9, 'Table', 'large, good', 'table', 'good', 'item_1773929351_8363.jpg', '', 55.66750000, 12.55590000, 'requested', '2026-03-19 14:09:11', '2026-03-26 11:48:13', NULL, NULL, 0, NULL),
(26, 12, 'King Sofa', 'Medium Size, white', 'sofa', 'like_new', 'item_1774522634_8132.jpg', '', 55.66250000, 12.56050000, 'available', '2026-03-26 10:57:14', '2026-03-26 10:57:14', NULL, NULL, 0, NULL),
(27, 12, 'Bed', 'Large, Authentic', 'bed', 'good', 'item_1774522814_9203.jpg', '', 55.66250000, 12.56050000, 'available', '2026-03-26 11:00:14', '2026-04-22 13:38:19', NULL, NULL, 0, NULL),
(30, 15, 'Dining Table', 'Medium size, Normal colour', 'other', 'like_new', 'item_1774784445_4761.jpg', '', 55.67600000, 12.58100000, 'available', '2026-03-29 11:40:45', '2026-04-05 09:23:16', NULL, NULL, 0, NULL),
(31, 12, 'Sofa', 'small, different sizes', 'sofa', 'good', 'item_1774902777_3517_1.jpg', '', 55.66513840, 12.45760480, 'taken', '2026-03-30 20:32:57', '2026-04-22 11:07:36', NULL, NULL, 0, NULL),
(32, 16, 'Daining Table', 'Normal, Local, Small', 'table', 'like_new', 'item_1775504760_4951_1.jpg', '', 55.66535141, 12.45659508, 'available', '2026-04-06 19:46:00', '2026-04-06 19:46:00', NULL, NULL, 0, NULL),
(33, 16, 'Living Hall Sofa', 'Large, Gery Colour', 'sofa', 'good', 'item_1775504843_5689_1.jpg', '', 55.66535141, 12.45659508, 'taken', '2026-04-06 19:47:23', '2026-04-19 15:36:05', NULL, NULL, 1, '2026-04-19 17:36:05'),
(34, 16, 'Gamming Chair', 'Small, Black and Flexible', 'chair', 'fair', 'item_1775504982_8595_1.jpg', '', 55.66535141, 12.45659508, 'available', '2026-04-06 19:49:42', '2026-04-06 19:49:42', NULL, NULL, 0, NULL),
(35, 17, 'Book Self', 'Small, better for Single Room', 'shelf', 'like_new', 'item_1775505384_1748_1.jpg', '', 55.66535141, 12.45659508, 'available', '2026-04-06 19:56:25', '2026-04-06 19:56:25', NULL, NULL, 0, NULL),
(36, 17, 'Authentic Chair', 'Small, Yellow, Super Comfortable', 'chair', 'good', 'item_1775505535_2433_1.jpg', '', 55.66535141, 12.45659508, 'available', '2026-04-06 19:58:55', '2026-04-06 19:58:55', NULL, NULL, 0, NULL),
(37, 14, 'Rack', 'Large, Premium, best of Living Room', 'other', 'like_new', 'item_1775600354_4533_1.jpg', '', 55.60556919, 12.31397199, 'taken', '2026-04-07 22:19:15', '2026-04-19 15:32:45', NULL, NULL, 1, '2026-04-19 17:32:45'),
(38, 14, 'Table', 'Small, wooden Table', 'table', 'fair', 'item_1776286038_5180_1.jpg', NULL, 55.60556919, 12.31397199, 'available', '2026-04-15 20:47:19', '2026-04-15 20:47:19', NULL, NULL, 0, NULL),
(39, 18, 'Chair leatest', 'small chair', 'chair', 'like_new', 'item_1777891036_8196_1.jpg', NULL, 55.60556919, 12.31397199, 'available', '2026-05-04 10:37:17', '2026-05-04 21:26:35', NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `furniture_item_images`
--

CREATE TABLE `furniture_item_images` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `sort_order` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `furniture_item_images`
--

INSERT INTO `furniture_item_images` (`id`, `item_id`, `filename`, `sort_order`, `created_at`) VALUES
(1, 31, 'item_1774902777_3517_1.jpg', 1, '2026-03-30 20:32:57'),
(2, 31, 'item_1774902777_7489_2.jpg', 2, '2026-03-30 20:32:57'),
(3, 31, 'item_1774902777_2009_3.jpg', 3, '2026-03-30 20:32:57'),
(4, 32, 'item_1775504760_4951_1.jpg', 1, '2026-04-06 19:46:00'),
(5, 32, 'item_1775504760_4450_2.jpg', 2, '2026-04-06 19:46:00'),
(6, 32, 'item_1775504760_2158_3.jpg', 3, '2026-04-06 19:46:00'),
(7, 33, 'item_1775504843_5689_1.jpg', 1, '2026-04-06 19:47:23'),
(8, 33, 'item_1775504843_7211_2.jpg', 2, '2026-04-06 19:47:23'),
(9, 33, 'item_1775504843_4325_3.jpg', 3, '2026-04-06 19:47:23'),
(10, 34, 'item_1775504982_8595_1.jpg', 1, '2026-04-06 19:49:42'),
(11, 34, 'item_1775504982_1880_2.jpg', 2, '2026-04-06 19:49:42'),
(12, 34, 'item_1775504982_9211_3.jpg', 3, '2026-04-06 19:49:42'),
(13, 35, 'item_1775505384_1748_1.jpg', 1, '2026-04-06 19:56:25'),
(14, 35, 'item_1775505385_3820_2.jpg', 2, '2026-04-06 19:56:25'),
(15, 35, 'item_1775505385_7198_3.jpg', 3, '2026-04-06 19:56:25'),
(16, 36, 'item_1775505535_2433_1.jpg', 1, '2026-04-06 19:58:55'),
(17, 36, 'item_1775505535_9355_2.jpg', 2, '2026-04-06 19:58:55'),
(18, 36, 'item_1775505535_7455_3.jpg', 3, '2026-04-06 19:58:55'),
(19, 37, 'item_1775600354_4533_1.jpg', 1, '2026-04-07 22:19:15'),
(20, 37, 'item_1775600354_9788_2.jpg', 2, '2026-04-07 22:19:15'),
(21, 37, 'item_1775600355_9537_3.jpg', 3, '2026-04-07 22:19:15'),
(22, 38, 'item_1776286038_5180_1.jpg', 1, '2026-04-15 20:47:19'),
(23, 38, 'item_1776286038_3994_2.jpg', 2, '2026-04-15 20:47:19'),
(24, 38, 'item_1776286038_7941_3.jpg', 3, '2026-04-15 20:47:19'),
(25, 39, 'item_1777891036_8196_1.jpg', 1, '2026-05-04 10:37:17'),
(26, 39, 'item_1777891036_3418_2.jpg', 2, '2026-05-04 10:37:17');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `item_id`, `subject`, `body`, `is_read`, `created_at`) VALUES
(3, 10, 9, 24, 'Re: Sofa', 'fggfg', 1, '2026-03-19 15:00:07'),
(4, 10, 9, 24, 'Item Request: Sofa', 'Bibash wants your item \"Sofa\".', 1, '2026-03-19 15:00:20'),
(5, 9, 10, 24, 'Re: Sofa', 'accept', 1, '2026-03-19 15:01:15'),
(6, 9, 10, 23, 'Item Request: Chair', 'Sunil wants your item \"Chair\".', 1, '2026-03-24 14:15:17'),
(7, 10, 9, 23, 'Request accepted: Chair', 'Good news!\n\nYour request for \"Chair\" was ACCEPTED by the owner.\n\nPlease message the owner to arrange pickup time.', 1, '2026-03-24 14:18:26'),
(8, 9, 10, 24, 'Request accepted: Sofa', 'Good news!\n\nYour request for \"Sofa\" was ACCEPTED by the owner.\n\nPlease message the owner to arrange pickup time.', 1, '2026-03-24 14:19:40'),
(9, 10, 9, 25, 'Item Request: Table', 'Bibash wants your item \"Table\".', 1, '2026-03-24 20:58:13'),
(10, 9, 10, 25, 'Request declined: Table', 'Hello,\n\nYour request for \"Table\" was DECLINED.\n\nYou can browse other items nearby.', 1, '2026-03-24 20:59:16'),
(11, 9, 10, 22, 'Item Request: SOFA', 'Sunil wants your item \"SOFA\".', 1, '2026-03-24 21:24:22'),
(12, 10, 9, 22, 'Request accepted: SOFA', 'Good news!\n\nYour request for \"SOFA\" was ACCEPTED.\n\nStatus is now: REQUESTED (reserved).\nPlease message the owner to arrange pickup time.\n\nAfter pickup, the owner will mark the item as TAKEN.', 1, '2026-03-24 21:25:17'),
(15, 10, 9, 24, 'Item Request: Sofa', 'Bibash wants your item \"Sofa\".', 1, '2026-03-26 10:21:53'),
(16, 9, 10, 24, 'Request accepted: Sofa', 'Good news!\n\nYour request for \"Sofa\" was ACCEPTED.\n\nStatus is now: REQUESTED (reserved).\nPlease message the owner to arrange pickup time.\n\nAfter pickup, the owner will mark the item as TAKEN.', 1, '2026-03-26 10:22:32'),
(17, 9, 10, 24, 'Item reopened: Sofa', 'Hello,\n\nThe owner reopened the item \"Sofa\" and set it back to AVAILABLE.\nYou may request it again if you still want it.', 1, '2026-03-26 10:24:11'),
(18, 9, 10, 24, 'Item reopened: Sofa', 'Hello,\n\nThe owner reopened the item \"Sofa\" and set it back to AVAILABLE.\nYou may request it again if you still want it.', 1, '2026-03-26 10:24:11'),
(19, 10, 9, 24, 'Item Request: Sofa', 'Bibash wants your item \"Sofa\".', 1, '2026-03-26 10:25:16'),
(22, 12, 9, 24, 'Item Request: Sofa', 'Chitraranjan wants your item \"Sofa\".\n\nMessage: That', 1, '2026-03-26 11:34:51'),
(23, 12, 9, 25, 'Item Request: Table', 'Chitraranjan wants your item \"Table\".', 1, '2026-03-26 11:36:18'),
(29, 15, 14, NULL, 'Item Request: Sofa', '@Rinku wants your item \"Sofa\".', 1, '2026-03-29 11:41:20'),
(37, 14, 15, 30, 'Item Request: Dining Table', 'Arvind wants your item \"Dining Table\".', 1, '2026-04-05 09:25:24'),
(38, 14, 15, NULL, 'Item marked as taken: Sofa', 'Hello,\n\nThe owner marked \"Sofa\" as TAKEN.\n\nThanks for using ShareToNeighbour!', 1, '2026-04-06 20:35:00'),
(39, 16, 12, 31, 'Re: Sofa', 'hey', 1, '2026-04-08 22:53:33'),
(40, 12, 16, 31, 'Re: Sofa', 'Hi bisnu', 1, '2026-04-08 22:56:57'),
(41, 16, 12, 31, 'Re: Sofa', 'yes chitra', 1, '2026-04-08 22:57:54'),
(42, 16, 12, 31, 'Re: Sofa', 'are you good?', 1, '2026-04-08 23:07:58'),
(44, 16, 12, NULL, 'Chat Message', 'hey', 1, '2026-04-08 23:17:38'),
(45, 12, 16, NULL, 'Chat Message', 'Yes', 1, '2026-04-08 23:18:00'),
(46, 12, 16, NULL, 'Chat Message', 'What', 1, '2026-04-08 23:18:39'),
(47, 16, 12, NULL, 'Chat Message', 'ys', 1, '2026-04-08 23:19:29'),
(48, 12, 16, NULL, 'Chat Message', 'Hey how are you ?', 1, '2026-04-08 23:29:03'),
(62, 12, 16, 26, 'Request declined: King Sofa', 'Hello,\n\nYour request for \"King Sofa\" was DECLINED.\n\nYou can browse other items nearby.', 1, '2026-04-09 23:50:38'),
(64, 17, 12, 27, 'Re: Bed', 'hey', 1, '2026-04-10 22:46:52'),
(65, 17, 12, 27, 'Re: Bed', 'hey', 1, '2026-04-10 22:47:33'),
(66, 17, 12, 27, 'Re: Bed', 'hey', 1, '2026-04-10 22:49:50'),
(67, 17, 12, 27, 'Item Request: Bed', 'Prashidhi wants your item \"Bed\".', 1, '2026-04-10 22:52:40'),
(68, 12, 17, 27, 'Request accepted: Bed', 'Good news!\n\nYour request for \"Bed\" was ACCEPTED.\n\nStatus is now: REQUESTED (reserved).\nPlease message the owner to arrange pickup time.\n\nAfter pickup, the owner will mark the item as TAKEN.', 1, '2026-04-10 22:55:20'),
(69, 12, 17, 27, 'Item reopened: Bed', 'Hello,\n\nThe owner reopened the item \"Bed\" and set it back to AVAILABLE.\nYou may request it again if you still want it.', 1, '2026-04-10 22:56:59'),
(70, 17, 12, 27, 'Re: Bed', 'i want yor bed?', 1, '2026-04-10 23:07:17'),
(71, 17, 12, NULL, 'Chat Message', 'lets talk', 1, '2026-04-10 23:08:37'),
(72, 17, 12, NULL, 'Chat Message', 'lets talk', 1, '2026-04-10 23:08:41'),
(73, 12, 17, NULL, 'Chat Message', 'Hey what happened?', 1, '2026-04-10 23:09:18'),
(74, 12, 17, NULL, 'Chat Message', 'Hey', 1, '2026-04-10 23:28:30'),
(75, 12, 17, NULL, 'Chat Message', 'Hey', 1, '2026-04-10 23:41:59'),
(76, 17, 12, NULL, 'Chat Message', 'good?', 1, '2026-04-10 23:42:21'),
(77, 17, 12, NULL, 'Chat Message', 'again', 1, '2026-04-11 00:24:13'),
(78, 12, 17, NULL, 'Chat Message', 'Yeh', 1, '2026-04-11 00:25:14'),
(79, 12, 17, NULL, 'Chat Message', 'No way', 1, '2026-04-11 00:26:16'),
(80, 12, 17, NULL, 'Chat Message', 'Realy', 1, '2026-04-11 00:27:24'),
(81, 17, 12, NULL, 'Chat Message', '?', 1, '2026-04-11 00:30:42'),
(82, 12, 17, NULL, 'Chat Message', 'Hey again', 1, '2026-04-11 00:31:32'),
(83, 12, 17, NULL, 'Chat Message', 'Good morning', 1, '2026-04-11 10:04:35'),
(84, 12, 17, NULL, 'Chat Message', 'Are you good?', 1, '2026-04-11 10:05:37'),
(88, 12, 16, NULL, 'Chat Message', 'Hey man', 1, '2026-04-11 10:09:50'),
(89, 12, 16, NULL, 'Chat Message', 'Are you?', 1, '2026-04-11 10:10:33'),
(90, 12, 16, NULL, 'Chat Message', 'Hey', 1, '2026-04-11 10:28:13'),
(91, 16, 12, NULL, 'Chat Message', 'yes', 1, '2026-04-11 10:28:38'),
(92, 12, 16, NULL, 'Chat Message', 'How you doing?', 1, '2026-04-11 10:28:57'),
(93, 12, 16, NULL, 'Chat Message', 'Sorry', 1, '2026-04-11 10:29:10'),
(94, 16, 12, NULL, 'Chat Message', 'yes', 1, '2026-04-11 10:29:37'),
(95, 12, 16, NULL, 'Chat Message', 'No w', 1, '2026-04-11 10:29:54'),
(96, 12, 16, NULL, 'Chat Message', 'Yem', 1, '2026-04-11 10:37:02'),
(97, 12, 16, NULL, 'Chat Message', 'H', 1, '2026-04-11 10:37:27'),
(98, 12, 16, NULL, 'Chat Message', 'Good', 1, '2026-04-11 10:37:38'),
(99, 12, 16, NULL, 'Chat Message', 'Hu', 1, '2026-04-11 10:39:19'),
(100, 12, 16, 33, 'Item Request: Living Hall Sofa', 'Chitraranjan wants your item \"Living Hall Sofa\".', 1, '2026-04-11 10:40:08'),
(101, 12, 16, NULL, 'Chat Message', 'Hey', 1, '2026-04-11 11:05:50'),
(102, 12, 16, NULL, 'Chat Message', 'Yep', 1, '2026-04-11 11:06:18'),
(103, 12, 16, NULL, 'Chat Message', 'Good', 1, '2026-04-11 11:06:33'),
(104, 16, 14, 37, 'Item Request: Rack', 'Bishnu wants your item \"Rack\".', 1, '2026-04-13 11:01:37'),
(105, 14, 16, NULL, 'Chat Message', 'okay,', 1, '2026-04-13 11:02:29'),
(106, 14, 16, 37, 'Request accepted: Rack', 'Good news!\n\nYour request for \"Rack\" was ACCEPTED.\n\nStatus is now: REQUESTED (reserved).\nPlease message the owner to arrange pickup time.\n\nAfter pickup, the owner will mark the item as TAKEN.', 1, '2026-04-13 11:02:47'),
(107, 14, 16, 37, 'Item marked as taken: Rack', 'Hello,\n\nThe owner marked \"Rack\" as TAKEN.\n\nThanks for using ShareToNeighbour!', 1, '2026-04-13 11:03:31'),
(108, 12, 16, 32, 'Re: Daining Table', 'hye', 1, '2026-04-14 13:15:56'),
(109, 16, 12, 33, 'Request accepted: Living Hall Sofa', 'Good news!\n\nYour request for \"Living Hall Sofa\" was ACCEPTED.\n\nStatus is now: REQUESTED (reserved).\nPlease message the owner to arrange pickup time.\n\nAfter pickup, the owner will mark the item as TAKEN.', 1, '2026-04-15 20:49:03'),
(110, 12, 16, NULL, 'Chat Message', 'Delivery is done!', 1, '2026-04-15 20:50:08'),
(111, 16, 12, 33, 'Item marked as taken: Living Hall Sofa', 'Hello,\n\nThe owner marked \"Living Hall Sofa\" as TAKEN.\n\nThanks for using ShareToNeighbour!', 1, '2026-04-15 20:51:21'),
(112, 12, 16, NULL, 'Chat Message', 'hello', 1, '2026-04-16 22:22:24'),
(113, 16, 12, NULL, 'Chat Message', 'Hy', 1, '2026-04-18 12:33:19'),
(114, 16, 12, NULL, 'Chat Message', 'How are you ?', 1, '2026-04-18 12:33:50'),
(115, 16, 12, NULL, 'Chat Message', 'Good', 1, '2026-04-18 12:34:24'),
(124, 12, 16, 32, 'Re: Daining Table', 'hey bro', 1, '2026-04-20 22:57:03'),
(125, 12, 17, 27, 'Item reopened: Bed', 'Hello,\n\nThe owner reopened the item \"Bed\" and set it back to AVAILABLE.\nYou may request it again if you still want it.', 0, '2026-04-22 11:27:25'),
(126, 12, 17, 27, 'Item reopened: Bed', 'Hello,\n\nThe owner reopened the item \"Bed\" and set it back to AVAILABLE.\nYou may request it again if you still want it.', 0, '2026-04-22 11:27:38'),
(127, 12, 17, 27, 'Item reopened: Bed', 'Hello,\n\nThe owner reopened the item \"Bed\" and set it back to AVAILABLE.\nYou may request it again if you still want it.', 0, '2026-04-22 12:05:24'),
(128, 12, 17, 27, 'Item reopened: Bed', 'Hello,\n\nThe owner reopened the item \"Bed\" and set it back to AVAILABLE.\nYou may request it again if you still want it.', 0, '2026-04-22 13:38:19'),
(129, 16, 12, NULL, 'Chat Message', 'Hey bro', 1, '2026-04-25 10:28:23'),
(130, 16, 12, NULL, 'Chat Message', 'How are you ?', 1, '2026-04-25 10:29:45'),
(131, 16, 14, NULL, 'Chat Message', 'Hey bro', 1, '2026-04-25 10:30:50'),
(132, 14, 16, NULL, 'Chat Message', 'hwy', 1, '2026-04-25 10:32:04'),
(133, 16, 14, NULL, 'Chat Message', 'Hy', 1, '2026-04-25 10:46:28'),
(134, 14, 16, NULL, 'Chat Message', 'yes', 1, '2026-04-25 10:47:34'),
(135, 16, 14, NULL, 'Chat Message', 'Hey', 1, '2026-04-25 11:04:16'),
(136, 16, 14, NULL, 'Chat Message', 'Yes man', 1, '2026-04-25 11:04:43'),
(137, 14, 16, NULL, 'Chat Message', 'good', 1, '2026-04-25 11:04:53'),
(138, 14, 9, 24, 'Item Request: Sofa', 'Arvind wants your item \"Sofa\".', 1, '2026-04-28 13:06:33'),
(139, 9, 14, NULL, 'Chat Message', 'hy there, if you are free you can visit this location on 12:00 oclock', 0, '2026-04-29 08:27:53'),
(140, 9, 14, 24, 'Request accepted: Sofa', 'Good news!\n\nYour request for \"Sofa\" was ACCEPTED.\n\nStatus is now: REQUESTED (reserved).\nPlease message the owner to arrange pickup time.\n\nAfter pickup, the owner will mark the item as TAKEN.', 0, '2026-04-29 08:28:32'),
(141, 9, 14, 24, 'Item marked as taken: Sofa', 'Hello,\n\nThe owner marked \"Sofa\" as TAKEN.\n\nThanks for using ShareToNeighbour!', 0, '2026-04-29 08:29:21'),
(142, 12, 16, 32, 'Re: Daining Table', 'is this item still availabe?', 0, '2026-05-04 22:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('message','request','request_accepted','request_declined','system') NOT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `ref_id`, `title`, `body`, `is_seen`, `created_at`) VALUES
(2, 17, 'message', 61, 'New message', 'hy', 1, '2026-04-10 01:33:13'),
(3, 12, 'request', 23, 'New item request', 'Someone requested your item: King Sofa', 1, '2026-04-10 01:48:20'),
(4, 16, 'request_declined', 23, 'Your request was declined', 'Item: King Sofa', 1, '2026-04-10 01:50:38'),
(5, 17, 'request_declined', 20, 'Your request was declined', 'Item: Living Hall Sofa', 1, '2026-04-10 01:52:58'),
(6, 12, 'request', 24, 'New item request', 'Someone requested your item: Bed', 1, '2026-04-11 00:52:40'),
(7, 17, 'request_accepted', 24, 'Your request was accepted', 'Item: Bed', 1, '2026-04-11 00:55:20'),
(8, 12, 'message', 70, 'New message', 'i want yor bed?', 1, '2026-04-11 01:07:17'),
(9, 12, 'message', 71, 'New message', 'lets talk', 1, '2026-04-11 01:08:37'),
(10, 12, 'message', 72, 'New message', 'lets talk', 1, '2026-04-11 01:08:41'),
(11, 17, 'message', 73, 'New message', 'Hey what happened?', 1, '2026-04-11 01:09:18'),
(12, 17, 'message', 74, 'New message', 'Hey', 1, '2026-04-11 01:28:30'),
(13, 17, 'message', 75, 'New message', 'Hey', 1, '2026-04-11 01:41:59'),
(14, 12, 'message', 76, 'New message', 'good?', 1, '2026-04-11 01:42:21'),
(15, 12, 'message', 77, 'New message', 'again', 1, '2026-04-11 02:24:13'),
(16, 17, 'message', 83, 'New message', 'Good morning', 1, '2026-04-11 12:04:35'),
(17, 16, 'message', 88, 'New message', 'Hey man', 1, '2026-04-11 12:09:50'),
(18, 16, 'message', 90, 'New message', 'Hey', 1, '2026-04-11 12:28:13'),
(19, 16, 'request', 25, 'New item request', 'Someone requested your item: Living Hall Sofa', 1, '2026-04-11 12:40:08'),
(20, 16, 'message', 101, 'New message', 'Hey', 1, '2026-04-11 13:05:50'),
(21, 14, 'request', 26, 'New item request', 'Someone requested your item: Rack', 1, '2026-04-13 13:01:37'),
(22, 16, 'message', 105, 'New message', 'okay,', 1, '2026-04-13 13:02:29'),
(23, 16, 'request_accepted', 26, 'Your request was accepted', 'Item: Rack', 1, '2026-04-13 13:02:47'),
(24, 16, 'message', 108, 'New message', 'hye', 1, '2026-04-14 15:15:56'),
(25, 12, 'request_accepted', 25, 'Your request was accepted', 'Item: Living Hall Sofa', 1, '2026-04-15 22:49:03'),
(26, 16, 'message', 110, 'New message', 'Delivery is done!', 1, '2026-04-15 22:50:08'),
(27, 16, 'message', 112, 'New message', 'hello', 1, '2026-04-17 00:22:24'),
(28, 12, 'message', 113, 'New message', 'Hy', 1, '2026-04-18 14:33:19'),
(29, 12, 'message', 114, 'New message', 'How are you ?', 1, '2026-04-18 14:33:50'),
(30, 12, 'message', 117, 'New message', 'Hey', 1, '2026-04-18 14:42:43'),
(31, 17, 'message', 118, 'New message', 'Hey', 1, '2026-04-18 14:44:24'),
(32, 16, 'message', 124, 'New message', 'hey bro', 1, '2026-04-21 00:57:03'),
(33, 12, 'message', 129, 'New message', 'Hey bro', 1, '2026-04-25 12:28:23'),
(34, 12, 'message', 130, 'New message', 'How are you ?', 1, '2026-04-25 12:29:45'),
(35, 14, 'message', 131, 'New message', 'Hey bro', 1, '2026-04-25 12:30:50'),
(36, 14, 'message', 133, 'New message', 'Hy', 1, '2026-04-25 12:46:28'),
(37, 14, 'message', 135, 'New message', 'Hey', 1, '2026-04-25 13:04:16'),
(38, 9, 'request', 27, 'New item request', 'Someone requested your item: Sofa', 1, '2026-04-28 15:06:33'),
(39, 14, 'message', 139, 'New message', 'hy there, if you are free you can visit this location on 12:00 oclock', 1, '2026-04-29 10:27:53'),
(40, 14, 'request_accepted', 27, 'Your request was accepted', 'Item: Sofa', 1, '2026-04-29 10:28:32'),
(41, 16, 'message', 142, 'New message', 'is this item still availabe?', 0, '2026-05-05 00:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `status` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `item_id`, `requester_id`, `owner_id`, `status`, `message`, `created_at`, `updated_at`) VALUES
(7, 24, 10, 9, 'declined', '', '2026-03-26 10:21:53', '2026-03-26 10:24:11'),
(8, 24, 10, 9, 'declined', '', '2026-03-26 10:25:16', '2026-04-29 08:28:32'),
(9, 24, 12, 9, 'declined', 'That', '2026-03-26 11:34:51', '2026-04-29 08:28:32'),
(10, 25, 12, 9, 'accepted', '', '2026-03-26 11:36:18', '2026-03-26 11:48:13'),
(12, 22, 12, 10, 'pending', '', '2026-03-26 11:39:18', '2026-03-26 11:39:18'),
(16, 30, 12, 15, 'declined', '', '2026-04-05 09:15:04', '2026-04-05 09:23:16'),
(17, 30, 12, 15, 'pending', '', '2026-04-05 09:23:41', '2026-04-05 09:23:41'),
(18, 30, 14, 15, 'pending', '', '2026-04-05 09:25:24', '2026-04-05 09:25:24'),
(19, 36, 16, 17, 'declined', '', '2026-04-09 20:51:12', '2026-04-09 21:05:45'),
(20, 33, 17, 16, 'declined', '', '2026-04-09 21:09:26', '2026-04-09 23:52:58'),
(21, 35, 16, 17, 'pending', '', '2026-04-09 21:34:52', '2026-04-09 21:34:52'),
(22, 34, 17, 16, 'pending', '', '2026-04-09 23:34:09', '2026-04-09 23:34:09'),
(23, 26, 16, 12, 'declined', '', '2026-04-09 23:48:20', '2026-04-09 23:50:38'),
(24, 27, 17, 12, 'declined', '', '2026-04-10 22:52:40', '2026-04-10 22:56:59'),
(25, 33, 12, 16, 'accepted', '', '2026-04-11 10:40:08', '2026-04-15 20:49:03'),
(26, 37, 16, 14, 'accepted', '', '2026-04-13 11:01:37', '2026-04-13 11:02:47'),
(27, 24, 14, 9, 'accepted', '', '2026-04-28 13:06:33', '2026-04-29 08:28:32');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewee_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` mediumblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `item_id`, `request_id`, `reviewer_id`, `reviewee_id`, `rating`, `comment`, `created_at`, `image`) VALUES
(2, 37, 26, 16, 14, 2, 'was old and broken', '2026-04-13 11:04:16', NULL),
(3, 33, 25, 12, 16, 5, 'The furniture was so good, and in very good condition, like new Thank You!', '2026-04-15 21:01:24', 0x75706c6f6164732f726576696577732f7265766965775f33335f31325f32653762316164646533313364303236366539393132326638376134373536342e6a7067);

-- --------------------------------------------------------

--
-- Table structure for table `super_admins`
--

CREATE TABLE `super_admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admins`
--

INSERT INTO `super_admins` (`id`, `username`, `email`, `password`, `full_name`, `is_active`, `last_login_at`, `created_at`) VALUES
(1, 'superuser', 'superuser123@gmail.com', '$2y$10$L3sb9O1IdvYj.NON2/r8ZOrff.epDPuIDiPJtfb48.BY1JnTUClIS', 'System Super Admin', 1, '2026-04-27 11:31:29', '2026-04-27 09:04:30');

-- --------------------------------------------------------

--
-- Table structure for table `super_admin_admin_map`
--

CREATE TABLE `super_admin_admin_map` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `super_admin_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_admin_audit_logs`
--

CREATE TABLE `super_admin_audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `super_admin_id` int(11) NOT NULL,
  `action` varchar(80) NOT NULL,
  `target_admin_id` int(11) DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `meta_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `super_admin_audit_logs`
--

INSERT INTO `super_admin_audit_logs` (`id`, `super_admin_id`, `action`, `target_admin_id`, `ip_address`, `user_agent`, `meta_json`, `created_at`) VALUES
(1, 1, 'CREATE_ADMIN', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '{\"username\":\"Chitra\"}', '2026-04-27 09:17:46'),
(2, 1, 'DISABLE_ADMIN', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', NULL, '2026-04-27 09:44:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(250) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT 55.67610000,
  `longitude` decimal(11,8) DEFAULT 12.56830000,
  `avatar` varchar(255) DEFAULT 'default_avatar.png',
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `postal_code` varchar(10) NOT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `disabled_at` datetime DEFAULT NULL,
  `disabled_reason` varchar(255) DEFAULT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `address`, `latitude`, `longitude`, `avatar`, `role`, `created_at`, `updated_at`, `email_notifications`, `postal_code`, `is_online`, `last_seen`, `is_active`, `disabled_at`, `disabled_reason`, `reset_token_hash`, `reset_token_expires_at`) VALUES
(9, 'Sunil', 'sunil@gmail.com', '$2y$10$frKSIxeMM9tJcmaf9WJb4uTtA8F1n.V.e.Sd.m6VpKSmoimrbuKJy', 'Sunil Magar', 'Norreport', 55.66750000, 12.55590000, 'default_avatar.png', 'user', '2026-03-19 14:02:00', '2026-03-29 21:30:52', 1, '0000', 0, NULL, 1, NULL, NULL, NULL, NULL),
(10, 'Bibash', 'bibash@gmail.com', '$2y$10$viDjV7j3s1HZQ4dquCprfegh72F4loEm5m5ONFJPQAw3RyDgh5PuG', 'Bibash Pandey', 'Amagerbrogade 13, København', 55.66734435, 12.59790857, 'default_avatar.png', 'user', '2026-03-19 14:02:50', '2026-05-04 09:50:32', 1, '2300', 0, NULL, 0, '2026-05-04 11:50:32', 'to many bad review', NULL, NULL),
(12, 'Chitraranjan', 'chitra@gmail.com', '$2y$10$OZVxO9.YixiCDEDdt0YbQO7bBfNF5/xLnhKVVsswCsZLfhL5wphVO', 'Chitraranjan Yadav', 'Rødovre Port 40', 55.66513840, 12.45760480, 'default_avatar.png', 'user', '2026-03-26 10:54:16', '2026-04-25 10:30:10', 1, '2610', 0, '2026-04-25 12:30:10', 1, NULL, NULL, NULL, NULL),
(14, 'Arvind', 'Chitraranjanyadavr360@gmail.com', '$2y$10$cPa70xy3BM579fOFTyqGq.obxsoW0TPi/137lS1EGo8zGM5zfBfeS', 'Arvind Yadav', 'Rosenlyparken 83, Greve', 55.60556919, 12.31397199, 'default_avatar.png', 'user', '2026-03-27 23:44:26', '2026-04-25 11:04:54', 1, '2670', 1, '2026-04-25 13:04:54', 1, NULL, NULL, NULL, NULL),
(15, '@Rinku', 'chitraranjanyadav2058@gmail.com', '$2y$10$odvwElRVcY8cBw7LYVxx1uxsNdvvw7w6Et9JqFAAOqwSL4z88Kfru', 'Rinku Yadav', 'Strandlinien 15, Dragør', 55.59371735, 12.67429734, 'default_avatar.png', 'user', '2026-03-29 11:37:21', '2026-04-06 20:05:37', 1, '2791', 0, NULL, 1, NULL, NULL, NULL, NULL),
(16, 'Bishnu', 'bishnu123@gmail.com', '$2y$10$P.d7GZLeDTK4SS4pYYbc/.FvwEZyAi642rB3Hje30Ld9w5oCh9wG2', 'Bishnu Shah', 'Rødovre Port 44, Rødovre', 55.66535141, 12.45659508, 'default_avatar.png', 'user', '2026-04-06 19:42:37', '2026-04-25 11:05:54', 1, '2610', 0, '2026-04-25 13:05:54', 1, NULL, NULL, NULL, NULL),
(17, 'Prashidhi', 'prashidhi123@gmail.com', '$2y$10$.muVM7Ro321hCGlzzhZwLuQ7rsLjig3qGh8lRRuiWiAH2SaKQ9VWC', 'Prashidhi Bhogoti', 'Rødovre Stationsvej 1, Hvidovre', 55.66495247, 12.45899189, 'default_avatar.png', 'user', '2026-04-06 19:53:01', '2026-04-18 12:59:28', 1, '2610', 0, '2026-04-18 14:59:28', 1, NULL, NULL, NULL, NULL),
(18, 'Niranjan', 'niranjan123@gmail.com', '$2y$10$cCrB7CZKxiYc94M95sygeOSeso0qnjovb1bLx5kd3z/7CQxFnOnqK', 'Niranjan GC', 'Rosenlyparken 83, Greve', 55.60556919, 12.31397199, 'default_avatar.png', 'user', '2026-05-04 10:14:13', '2026-05-04 10:14:13', 1, '2670', 0, NULL, 1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_chat_presence`
--

CREATE TABLE `user_chat_presence` (
  `user_id` int(11) NOT NULL,
  `with_user_id` int(11) NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_chat_presence`
--

INSERT INTO `user_chat_presence` (`user_id`, `with_user_id`, `updated_at`) VALUES
(14, 9, '2026-04-28 15:57:18'),
(16, 14, '2026-04-25 13:14:34'),
(9, 14, '2026-04-29 10:28:07'),
(17, 16, '2026-04-18 14:58:54'),
(12, 17, '2026-04-29 11:36:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_recovery_tokens`
--
ALTER TABLE `admin_recovery_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_recovery_token_hash` (`token_hash`),
  ADD KEY `idx_admin_recovery_admin_id` (`admin_id`),
  ADD KEY `idx_admin_recovery_expires_at` (`expires_at`),
  ADD KEY `fk_admin_recovery_issuer` (`issued_by_super_admin_id`);

--
-- Indexes for table `furniture_items`
--
ALTER TABLE `furniture_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_location` (`latitude`,`longitude`),
  ADD KEY `idx_furniture_items_accepted_request_id` (`accepted_request_id`);

--
-- Indexes for table `furniture_item_images`
--
ALTER TABLE `furniture_item_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_receiver` (`receiver_id`,`is_read`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_type_ref` (`user_id`,`type`,`ref_id`),
  ADD KEY `idx_notifications_user_seen` (`user_id`,`is_seen`,`created_at`),
  ADD KEY `idx_notifications_user` (`user_id`,`created_at`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `idx_owner` (`owner_id`,`status`),
  ADD KEY `idx_requests_item_status` (`item_id`,`status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_one_review` (`request_id`,`reviewer_id`),
  ADD KEY `reviewee_id` (`reviewee_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_super_admin_username` (`username`),
  ADD UNIQUE KEY `uq_super_admin_email` (`email`);

--
-- Indexes for table `super_admin_admin_map`
--
ALTER TABLE `super_admin_admin_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_super_admin_admin_map` (`super_admin_id`,`admin_id`),
  ADD KEY `fk_sa_map_admin` (`admin_id`);

--
-- Indexes for table `super_admin_audit_logs`
--
ALTER TABLE `super_admin_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_audit_super_admin_id` (`super_admin_id`),
  ADD KEY `idx_sa_audit_target_admin_id` (`target_admin_id`),
  ADD KEY `idx_sa_audit_action` (`action`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_online` (`is_online`,`last_seen`);

--
-- Indexes for table `user_chat_presence`
--
ALTER TABLE `user_chat_presence`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_with_updated` (`with_user_id`,`updated_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin_recovery_tokens`
--
ALTER TABLE `admin_recovery_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `furniture_items`
--
ALTER TABLE `furniture_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `furniture_item_images`
--
ALTER TABLE `furniture_item_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `super_admin_admin_map`
--
ALTER TABLE `super_admin_admin_map`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `super_admin_audit_logs`
--
ALTER TABLE `super_admin_audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_recovery_tokens`
--
ALTER TABLE `admin_recovery_tokens`
  ADD CONSTRAINT `fk_admin_recovery_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_admin_recovery_issuer` FOREIGN KEY (`issued_by_super_admin_id`) REFERENCES `super_admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `furniture_items`
--
ALTER TABLE `furniture_items`
  ADD CONSTRAINT `furniture_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `furniture_item_images`
--
ALTER TABLE `furniture_item_images`
  ADD CONSTRAINT `furniture_item_images_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `furniture_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `furniture_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `furniture_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `furniture_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `super_admin_admin_map`
--
ALTER TABLE `super_admin_admin_map`
  ADD CONSTRAINT `fk_sa_map_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_map_super_admin` FOREIGN KEY (`super_admin_id`) REFERENCES `super_admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `super_admin_audit_logs`
--
ALTER TABLE `super_admin_audit_logs`
  ADD CONSTRAINT `fk_sa_audit_super_admin` FOREIGN KEY (`super_admin_id`) REFERENCES `super_admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_audit_target_admin` FOREIGN KEY (`target_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
