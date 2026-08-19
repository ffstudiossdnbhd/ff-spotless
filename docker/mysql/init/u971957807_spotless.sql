-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 19, 2026 at 02:40 AM
-- Server version: 11.8.8-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u971957807_spotless`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `actor_type` varchar(30) NOT NULL,
  `actor_label` varchar(100) NOT NULL,
  `subject_type` varchar(100) DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `occurred_at` timestamp(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `action`, `actor_type`, `actor_label`, `subject_type`, `subject_id`, `metadata`, `occurred_at`) VALUES
(1, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 04:27:58.000000'),
(2, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 2, '{\"name\":\"A\"}', '2026-08-05 04:29:12.000000'),
(3, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 2, '{\"name\":\"A\"}', '2026-08-05 04:29:38.000000'),
(4, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 3, '{\"name\":\"A\"}', '2026-08-05 04:29:45.000000'),
(5, 'task_template.created', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 24, '{\"task_type\":\"daily\",\"task_name\":\"test\"}', '2026-08-05 04:30:04.000000'),
(6, 'admin.logout', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 04:30:59.000000'),
(7, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 04:31:55.000000'),
(8, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 4, '{\"name\":\"B\"}', '2026-08-05 04:31:58.000000'),
(9, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 5, '{\"name\":\"c\"}', '2026-08-05 04:32:03.000000'),
(10, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 6, '{\"name\":\"D\"}', '2026-08-05 04:32:08.000000'),
(11, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 7, '{\"name\":\"E\"}', '2026-08-05 04:32:14.000000'),
(12, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 8, '{\"name\":\"F\"}', '2026-08-05 04:32:41.000000'),
(13, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 8, '{\"name\":\"F\"}', '2026-08-05 04:33:35.000000'),
(14, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 7, '{\"name\":\"E\"}', '2026-08-05 04:33:38.000000'),
(15, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 5, '{\"name\":\"c\"}', '2026-08-05 04:33:47.000000'),
(16, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 6, '{\"name\":\"D\"}', '2026-08-05 04:33:53.000000'),
(17, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 9, '{\"name\":\"GGGGG\"}', '2026-08-05 04:34:00.000000'),
(18, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 4, '{\"name\":\"B\"}', '2026-08-05 04:34:03.000000'),
(19, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 10, '{\"name\":\"B\"}', '2026-08-05 04:34:11.000000'),
(20, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 9, '{\"name\":\"GGGGG\"}', '2026-08-05 04:34:35.000000'),
(21, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 24, '{\"task_type\":\"daily\",\"task_name\":\"test\"}', '2026-08-05 04:34:50.000000'),
(22, 'availability.marked_unavailable', 'cleaner', 'Cleaner (anonymous)', NULL, NULL, '{\"date\":\"2026-08-05\"}', '2026-08-05 04:36:00.000000'),
(23, 'availability.marked_available', 'cleaner', 'Cleaner (anonymous)', NULL, NULL, '{\"date\":\"2026-08-05\"}', '2026-08-05 04:36:01.000000'),
(24, 'availability.marked_unavailable', 'cleaner', 'Cleaner (anonymous)', NULL, NULL, '{\"date\":\"2026-08-05\"}', '2026-08-05 04:36:01.000000'),
(25, 'availability.marked_available', 'cleaner', 'Cleaner (anonymous)', NULL, NULL, '{\"date\":\"2026-08-05\"}', '2026-08-05 04:36:02.000000'),
(26, 'task_template.archived', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 24, '{\"task_type\":\"daily\",\"task_name\":\"test\"}', '2026-08-05 04:36:27.000000'),
(27, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 10, '{\"name\":\"B\"}', '2026-08-05 04:37:40.000000'),
(28, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 3, '{\"name\":\"A\"}', '2026-08-05 04:37:42.000000'),
(29, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 11, '{\"name\":\"A\"}', '2026-08-05 04:39:00.000000'),
(30, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 11, '{\"name\":\"A\"}', '2026-08-05 04:39:09.000000'),
(31, 'admin.logout', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 04:40:38.000000'),
(32, 'checklist.reordered', 'cleaner', 'Cleaner (anonymous)', NULL, NULL, '{\"date\":\"2026-08-05\",\"task_session_id\":1,\"item_count\":5}', '2026-08-05 04:42:23.000000'),
(33, 'checklist.reordered', 'cleaner', 'Cleaner (anonymous)', NULL, NULL, '{\"date\":\"2026-08-05\",\"task_session_id\":1,\"item_count\":5}', '2026-08-05 04:42:24.000000'),
(34, 'checklist.reordered', 'cleaner', 'Cleaner (anonymous)', NULL, NULL, '{\"date\":\"2026-08-05\",\"task_session_id\":1,\"item_count\":5}', '2026-08-05 04:42:26.000000'),
(35, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 04:43:16.000000'),
(36, 'task_template.created', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 25, '{\"task_type\":\"daily\",\"task_name\":\"test\"}', '2026-08-05 04:43:47.000000'),
(37, 'task.completed', 'cleaner', 'Cleaner (anonymous)', 'App\\Models\\DailyChecklist', 568, '{\"task_type\":\"daily\",\"task_name\":\"test\",\"task_date\":\"2026-08-05\"}', '2026-08-05 04:44:32.000000'),
(38, 'task.reopened', 'admin', 'System administrator', 'App\\Models\\DailyChecklist', 568, '{\"task_type\":\"daily\",\"task_id\":568,\"task_name\":\"test\",\"session_name\":\"9:00 AM - 11:00 AM\",\"task_date\":\"2026-08-05\",\"reason\":\"why not\",\"invalidated_evidence_count\":1}', '2026-08-05 04:44:58.000000'),
(39, 'task_template.archived', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 25, '{\"task_type\":\"daily\",\"task_name\":\"test\"}', '2026-08-05 04:45:34.000000'),
(40, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 06:30:48.000000'),
(41, 'admin.logout', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 06:30:54.000000'),
(42, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 06:31:00.000000'),
(43, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 08:50:55.000000'),
(44, 'admin.logout', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 08:51:24.000000'),
(45, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 08:54:42.000000'),
(46, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 12, '{\"name\":\"A\"}', '2026-08-05 09:02:00.000000'),
(47, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 13, '{\"name\":\"B\"}', '2026-08-05 09:02:02.000000'),
(48, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 14, '{\"name\":\"C\"}', '2026-08-05 09:02:04.000000'),
(49, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 15, '{\"name\":\"D\"}', '2026-08-05 09:02:05.000000'),
(50, 'rotation.created', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 16, '{\"name\":\"E\"}', '2026-08-05 09:02:07.000000'),
(51, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 15, '{\"name\":\"D\"}', '2026-08-05 09:02:10.000000'),
(52, 'rotation.deleted', 'admin', 'System administrator', 'App\\Models\\TaskCollection', 16, '{\"name\":\"E\"}', '2026-08-05 09:02:12.000000'),
(53, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 8, '{\"task_type\":\"daily\",\"task_name\":\"Cuci Tandas\"}', '2026-08-05 09:02:26.000000'),
(54, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 13, '{\"task_type\":\"daily\",\"task_name\":\"Mop Lantai\"}', '2026-08-05 09:02:28.000000'),
(55, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 12, '{\"task_type\":\"daily\",\"task_name\":\"Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)\"}', '2026-08-05 09:02:31.000000'),
(56, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\WeeklyTaskTemplate', 2, '{\"task_type\":\"weekly\",\"task_name\":\"Lap Cermin\",\"due_weekday\":1}', '2026-08-05 09:02:34.000000'),
(57, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\WeeklyTaskTemplate', 3, '{\"task_type\":\"weekly\",\"task_name\":\"Vacuum Carpet\",\"due_weekday\":2}', '2026-08-05 09:02:38.000000'),
(58, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 9, '{\"task_type\":\"daily\",\"task_name\":\"Cuci Tandas\"}', '2026-08-05 09:02:42.000000'),
(59, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 14, '{\"task_type\":\"daily\",\"task_name\":\"Masak Nasi\"}', '2026-08-05 09:02:44.000000'),
(60, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 16, '{\"task_type\":\"daily\",\"task_name\":\"Prepare Lunch\"}', '2026-08-05 09:02:48.000000'),
(61, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 15, '{\"task_type\":\"daily\",\"task_name\":\"Restock Air & Kudapan\"}', '2026-08-05 09:02:51.000000'),
(62, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\WeeklyTaskTemplate', 1, '{\"task_type\":\"weekly\",\"task_name\":\"Lap Kerusi Urut\",\"due_weekday\":3}', '2026-08-05 09:02:53.000000'),
(63, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 18, '{\"task_type\":\"daily\",\"task_name\":\"Bersihkan Lunch Area\"}', '2026-08-05 09:02:56.000000'),
(64, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 18, '{\"task_type\":\"daily\",\"task_name\":\"Bersihkan Lunch Area\"}', '2026-08-05 09:02:58.000000'),
(65, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 10, '{\"task_type\":\"daily\",\"task_name\":\"Cuci Tandas\"}', '2026-08-05 09:03:01.000000'),
(66, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 19, '{\"task_type\":\"daily\",\"task_name\":\"Kemas (tempat rehat dan solat)\"}', '2026-08-05 09:03:05.000000'),
(67, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 17, '{\"task_type\":\"daily\",\"task_name\":\"Sapu & Mop (staff area)\"}', '2026-08-05 09:03:08.000000'),
(68, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 20, '{\"task_type\":\"daily\",\"task_name\":\"Update Inventory Pantry\"}', '2026-08-05 09:03:11.000000'),
(69, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 23, '{\"task_type\":\"daily\",\"task_name\":\"Buang Sampah\"}', '2026-08-05 09:03:14.000000'),
(70, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 11, '{\"task_type\":\"daily\",\"task_name\":\"Cuci Tandas\"}', '2026-08-05 09:03:17.000000'),
(71, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 21, '{\"task_type\":\"daily\",\"task_name\":\"Double Check Kebersihan\"}', '2026-08-05 09:03:20.000000'),
(72, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\TaskTemplate', 22, '{\"task_type\":\"daily\",\"task_name\":\"Pack Baki Makanan Lunch\"}', '2026-08-05 09:03:22.000000'),
(73, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\WeeklyTaskTemplate', 4, '{\"task_type\":\"weekly\",\"task_name\":\"Tukar Sarung Bantal\",\"due_weekday\":5}', '2026-08-05 09:03:26.000000'),
(74, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\WeeklyTaskTemplate', 2, '{\"task_type\":\"weekly\",\"task_name\":\"Lap Cermin\",\"due_weekday\":1}', '2026-08-05 09:04:01.000000'),
(75, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\WeeklyTaskTemplate', 3, '{\"task_type\":\"weekly\",\"task_name\":\"Vacuum Carpet\",\"due_weekday\":2}', '2026-08-05 09:04:06.000000'),
(76, 'task_template.updated', 'admin', 'System administrator', 'App\\Models\\WeeklyTaskTemplate', 3, '{\"task_type\":\"weekly\",\"task_name\":\"Vacuum Carpet\",\"due_weekday\":2}', '2026-08-05 09:04:13.000000'),
(77, 'admin.logout', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 09:04:48.000000'),
(78, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 09:05:16.000000'),
(79, 'admin.logout', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 09:16:56.000000'),
(80, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-05 09:16:59.000000'),
(81, 'admin.login_succeeded', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-11 03:19:02.000000'),
(82, 'public_holiday.created', 'admin', 'System administrator', 'App\\Models\\PublicHoliday', 1, '{\"date\":\"2026-08-31\",\"name\":\"Malaysia\'s Independence Day\"}', '2026-08-11 03:20:48.000000'),
(83, 'public_holiday.created', 'admin', 'System administrator', 'App\\Models\\PublicHoliday', 2, '{\"date\":\"2026-09-16\",\"name\":\"Malaysia Day\"}', '2026-08-11 03:20:58.000000'),
(84, 'public_holiday.created', 'admin', 'System administrator', 'App\\Models\\PublicHoliday', 3, '{\"date\":\"2026-12-11\",\"name\":\"Sultan of Selangor\'s Birthday\"}', '2026-08-11 03:21:15.000000'),
(85, 'public_holiday.created', 'admin', 'System administrator', 'App\\Models\\PublicHoliday', 4, '{\"date\":\"2026-12-25\",\"name\":\"Christmas Day\"}', '2026-08-11 03:21:30.000000'),
(86, 'admin.logout', 'admin', 'System administrator', NULL, NULL, '[]', '2026-08-11 03:21:40.000000');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('ff-spotless-cache-131348545d3e79bd73dc96df141664e1d2211f44', 'i:1;', 1786418402),
('ff-spotless-cache-131348545d3e79bd73dc96df141664e1d2211f44:timer', 'i:1786418402;', 1786418402),
('ff-spotless-cache-b2829f9b329dfe5be87e455b64b0c11a0733e409', 'i:1;', 1785921479),
('ff-spotless-cache-b2829f9b329dfe5be87e455b64b0c11a0733e409:timer', 'i:1785921479;', 1785921479);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checklist_day_statuses`
--

CREATE TABLE `checklist_day_statuses` (
  `date` date NOT NULL,
  `is_unavailable` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `checklist_day_statuses`
--

INSERT INTO `checklist_day_statuses` (`date`, `is_unavailable`, `created_at`, `updated_at`) VALUES
('2026-07-29', 0, '2026-07-29 05:48:56', '2026-07-29 10:29:11'),
('2026-07-30', 0, '2026-07-30 02:26:04', '2026-07-30 08:25:53'),
('2026-08-04', 0, '2026-08-04 04:12:34', '2026-08-04 04:12:44'),
('2026-08-05', 0, '2026-08-05 04:36:00', '2026-08-05 04:36:02');

-- --------------------------------------------------------

--
-- Table structure for table `checklist_item_positions`
--

CREATE TABLE `checklist_item_positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `task_session_id` bigint(20) UNSIGNED NOT NULL,
  `item_type` varchar(20) NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `checklist_item_positions`
--

INSERT INTO `checklist_item_positions` (`id`, `date`, `task_session_id`, `item_type`, `item_id`, `position`, `created_at`, `updated_at`) VALUES
(1, '2026-07-27', 1, 'daily', 112, 1, '2026-07-27 09:20:12', '2026-07-27 09:20:12'),
(2, '2026-07-27', 1, 'daily', 156, 2, '2026-07-27 09:20:12', '2026-07-27 09:20:12'),
(3, '2026-07-27', 1, 'daily', 167, 3, '2026-07-27 09:20:12', '2026-07-27 09:20:12'),
(4, '2026-07-27', 1, 'weekly', 5, 4, '2026-07-27 09:20:12', '2026-07-27 09:20:12'),
(5, '2026-07-27', 1, 'weekly', 12, 5, '2026-07-27 09:20:12', '2026-07-27 09:20:12'),
(11, '2026-07-28', 1, 'daily', 113, 1, '2026-07-28 07:29:24', '2026-07-28 07:29:24'),
(12, '2026-07-28', 1, 'daily', 157, 2, '2026-07-28 07:29:24', '2026-07-28 07:29:24'),
(13, '2026-07-28', 1, 'daily', 168, 3, '2026-07-28 07:29:24', '2026-07-28 07:29:24'),
(14, '2026-07-28', 1, 'weekly', 5, 4, '2026-07-28 07:29:24', '2026-07-28 07:29:24'),
(15, '2026-07-28', 1, 'weekly', 12, 5, '2026-07-28 07:29:24', '2026-07-28 07:29:24'),
(16, '2026-07-29', 1, 'daily', 114, 1, '2026-07-29 05:49:03', '2026-07-29 05:49:03'),
(17, '2026-07-29', 1, 'daily', 158, 2, '2026-07-29 05:49:03', '2026-07-29 05:49:03'),
(18, '2026-07-29', 1, 'daily', 169, 3, '2026-07-29 05:49:03', '2026-07-29 05:49:03'),
(19, '2026-07-29', 1, 'weekly', 5, 4, '2026-07-29 05:49:03', '2026-07-29 05:49:03'),
(20, '2026-07-29', 1, 'weekly', 12, 5, '2026-07-29 05:49:03', '2026-07-29 05:49:03'),
(21, '2026-07-31', 1, 'daily', 160, 1, '2026-07-31 08:31:28', '2026-07-31 08:31:28'),
(22, '2026-07-31', 1, 'daily', 171, 2, '2026-07-31 08:31:28', '2026-07-31 08:31:28'),
(23, '2026-07-31', 1, 'weekly', 5, 3, '2026-07-31 08:31:28', '2026-07-31 08:31:28'),
(24, '2026-07-31', 1, 'weekly', 12, 4, '2026-07-31 08:31:28', '2026-07-31 08:31:28'),
(25, '2026-07-31', 1, 'daily', 116, 5, '2026-07-31 08:31:28', '2026-07-31 08:31:28');

-- --------------------------------------------------------

--
-- Table structure for table `checklist_materializations`
--

CREATE TABLE `checklist_materializations` (
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `checklist_materializations`
--

INSERT INTO `checklist_materializations` (`date`) VALUES
('2026-07-02'),
('2026-07-15'),
('2026-07-16'),
('2026-07-17'),
('2026-07-18'),
('2026-07-19'),
('2026-07-20'),
('2026-07-21'),
('2026-07-22'),
('2026-07-23'),
('2026-07-24'),
('2026-07-25'),
('2026-07-26'),
('2026-07-27'),
('2026-07-28'),
('2026-07-29'),
('2026-07-30'),
('2026-07-31'),
('2026-08-01'),
('2026-08-02'),
('2026-08-03'),
('2026-08-04'),
('2026-08-05'),
('2026-08-06'),
('2026-08-07'),
('2026-08-08'),
('2026-08-09'),
('2026-08-10'),
('2026-08-11'),
('2026-08-12'),
('2026-08-13'),
('2026-08-14'),
('2026-08-15'),
('2026-08-16'),
('2026-08-17'),
('2026-08-18'),
('2026-08-19'),
('2026-08-20'),
('2026-08-21'),
('2026-08-22'),
('2026-08-23'),
('2026-08-24'),
('2026-08-25'),
('2026-08-26'),
('2026-08-27'),
('2026-08-28'),
('2026-08-29'),
('2026-08-30'),
('2026-08-31'),
('2026-09-01');

-- --------------------------------------------------------

--
-- Table structure for table `checklist_sync_locks`
--

CREATE TABLE `checklist_sync_locks` (
  `name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `checklist_sync_locks`
--

INSERT INTO `checklist_sync_locks` (`name`) VALUES
('template-synchronization');

-- --------------------------------------------------------

--
-- Table structure for table `daily_checklists`
--

CREATE TABLE `daily_checklists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `task_template_id` bigint(20) UNSIGNED NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_session_id` bigint(20) UNSIGNED NOT NULL,
  `session_name` varchar(100) NOT NULL,
  `credit_hours` decimal(6,2) NOT NULL DEFAULT 1.00,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp(6) NULL DEFAULT NULL,
  `completion_note` varchar(500) DEFAULT NULL,
  `completed_by_user_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_checklists`
--

INSERT INTO `daily_checklists` (`id`, `date`, `task_template_id`, `task_name`, `task_session_id`, `session_name`, `credit_hours`, `is_completed`, `completed_at`, `completion_note`, `completed_by_user_id`) VALUES
(16, '2026-07-22', 2, 'Sapu sampah', 1, 'Pagi', 1.00, 1, '2026-07-22 06:29:00.737418', NULL, NULL),
(17, '2026-07-23', 2, 'Sapu sampah', 1, 'Pagi', 1.00, 0, NULL, NULL, NULL),
(18, '2026-07-24', 2, 'Sapu sampah', 1, 'Pagi', 1.00, 0, NULL, NULL, NULL),
(19, '2026-07-25', 2, 'Sapu sampah', 1, 'Pagi', 1.00, 0, NULL, NULL, NULL),
(20, '2026-07-26', 2, 'Sapu sampah', 1, 'Pagi', 1.00, 0, NULL, NULL, NULL),
(31, '2026-07-22', 3, 'Kemas Pantry', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(32, '2026-07-23', 3, 'Kemas Pantry', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(33, '2026-07-24', 3, 'Kemas Pantry', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(34, '2026-07-25', 3, 'Kemas Pantry', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(35, '2026-07-26', 3, 'Kemas Pantry', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(46, '2026-07-22', 4, 'Cuci tandas', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(47, '2026-07-23', 4, 'Cuci tandas', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(48, '2026-07-24', 4, 'Cuci tandas', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(49, '2026-07-25', 4, 'Cuci tandas', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(50, '2026-07-26', 4, 'Cuci tandas', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(61, '2026-07-22', 5, 'Masak nasi', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(62, '2026-07-23', 5, 'Masak nasi', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(63, '2026-07-24', 5, 'Masak nasi', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(64, '2026-07-25', 5, 'Masak nasi', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(65, '2026-07-26', 5, 'Masak nasi', 2, 'Tengah Hari', 1.00, 0, NULL, NULL, NULL),
(76, '2026-07-22', 6, 'Lap cermin', 1, 'Pagi', 1.00, 0, NULL, NULL, NULL),
(77, '2026-07-23', 6, 'Lap cermin', 1, 'Pagi', 1.00, 0, NULL, NULL, NULL),
(78, '2026-07-24', 6, 'Lap cermin', 1, 'Pagi', 1.00, 0, NULL, NULL, NULL),
(79, '2026-07-25', 6, 'Lap cermin', 1, 'Pagi', 1.00, 0, NULL, NULL, NULL),
(80, '2026-07-26', 6, 'Lap cermin', 1, 'Pagi', 1.00, 0, NULL, NULL, NULL),
(91, '2026-07-22', 7, 'Cuci bekas makanan', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(92, '2026-07-23', 7, 'Cuci bekas makanan', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(93, '2026-07-24', 7, 'Cuci bekas makanan', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(94, '2026-07-25', 7, 'Cuci bekas makanan', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(95, '2026-07-26', 7, 'Cuci bekas makanan', 3, 'Petang', 1.00, 0, NULL, NULL, NULL),
(112, '2026-07-27', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 1, '2026-07-27 09:17:33.406092', NULL, NULL),
(113, '2026-07-28', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 1, '2026-07-28 07:28:36.694061', NULL, NULL),
(114, '2026-07-29', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(115, '2026-07-30', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(116, '2026-07-31', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(117, '2026-08-01', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(118, '2026-08-02', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(119, '2026-08-03', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(120, '2026-08-04', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(123, '2026-07-27', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(124, '2026-07-28', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(125, '2026-07-29', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(126, '2026-07-30', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(127, '2026-07-31', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(128, '2026-08-01', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(129, '2026-08-02', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(130, '2026-08-03', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(131, '2026-08-04', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(134, '2026-07-27', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(135, '2026-07-28', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(136, '2026-07-29', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(137, '2026-07-30', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(138, '2026-07-31', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(139, '2026-08-01', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(140, '2026-08-02', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(141, '2026-08-03', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(142, '2026-08-04', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(145, '2026-07-27', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(146, '2026-07-28', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(147, '2026-07-29', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(148, '2026-07-30', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(149, '2026-07-31', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(150, '2026-08-01', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(151, '2026-08-02', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(152, '2026-08-03', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(153, '2026-08-04', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(156, '2026-07-27', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 1, '2026-07-27 09:17:53.678599', NULL, NULL),
(157, '2026-07-28', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(158, '2026-07-29', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(159, '2026-07-30', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(160, '2026-07-31', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(161, '2026-08-01', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(162, '2026-08-02', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(163, '2026-08-03', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(164, '2026-08-04', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(167, '2026-07-27', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 1, '2026-07-27 09:28:49.015301', NULL, NULL),
(168, '2026-07-28', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(169, '2026-07-29', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(170, '2026-07-30', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(171, '2026-07-31', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(172, '2026-08-01', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(173, '2026-08-02', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(174, '2026-08-03', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(175, '2026-08-04', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(178, '2026-07-27', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 1, '2026-07-27 09:33:29.407230', NULL, NULL),
(179, '2026-07-28', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(180, '2026-07-29', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(181, '2026-07-30', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(182, '2026-07-31', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(183, '2026-08-01', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(184, '2026-08-02', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(185, '2026-08-03', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(186, '2026-08-04', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(189, '2026-07-27', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(190, '2026-07-28', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(191, '2026-07-29', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(192, '2026-07-30', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(193, '2026-07-31', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(194, '2026-08-01', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(195, '2026-08-02', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(196, '2026-08-03', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(197, '2026-08-04', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(200, '2026-07-27', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(201, '2026-07-28', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 1, '2026-07-28 08:18:44.722880', NULL, NULL),
(202, '2026-07-29', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(203, '2026-07-30', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(204, '2026-07-31', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(205, '2026-08-01', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(206, '2026-08-02', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(207, '2026-08-03', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(208, '2026-08-04', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(211, '2026-07-27', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(212, '2026-07-28', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(213, '2026-07-29', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(214, '2026-07-30', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(215, '2026-07-31', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(216, '2026-08-01', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(217, '2026-08-02', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(218, '2026-08-03', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(219, '2026-08-04', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(222, '2026-07-27', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(223, '2026-07-28', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(224, '2026-07-29', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(225, '2026-07-30', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(226, '2026-07-31', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(227, '2026-08-01', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(228, '2026-08-02', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(229, '2026-08-03', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(230, '2026-08-04', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(233, '2026-07-27', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(234, '2026-07-28', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(235, '2026-07-29', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(236, '2026-07-30', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(237, '2026-07-31', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(238, '2026-08-01', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(239, '2026-08-02', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(240, '2026-08-03', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(241, '2026-08-04', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(244, '2026-07-27', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(245, '2026-07-28', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(246, '2026-07-29', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(247, '2026-07-30', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(248, '2026-07-31', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(249, '2026-08-01', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(250, '2026-08-02', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(251, '2026-08-03', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(252, '2026-08-04', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(255, '2026-07-27', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(256, '2026-07-28', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(257, '2026-07-29', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(258, '2026-07-30', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(259, '2026-07-31', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(260, '2026-08-01', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(261, '2026-08-02', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(262, '2026-08-03', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(263, '2026-08-04', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(266, '2026-07-27', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(267, '2026-07-28', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(268, '2026-07-29', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(269, '2026-07-30', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(270, '2026-07-31', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(271, '2026-08-01', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(272, '2026-08-02', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(273, '2026-08-03', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(274, '2026-08-04', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(277, '2026-07-27', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(278, '2026-07-28', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(279, '2026-07-29', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(280, '2026-07-30', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(281, '2026-07-31', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(282, '2026-08-01', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(283, '2026-08-02', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(284, '2026-08-03', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(285, '2026-08-04', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(288, '2026-07-02', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(289, '2026-07-02', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(290, '2026-07-02', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(291, '2026-07-02', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(292, '2026-07-02', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(293, '2026-07-02', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(294, '2026-07-02', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(295, '2026-07-02', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(296, '2026-07-02', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(297, '2026-07-02', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(298, '2026-07-02', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(299, '2026-07-02', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(300, '2026-07-02', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(301, '2026-07-02', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(302, '2026-07-02', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00PM', 1.00, 0, NULL, NULL, NULL),
(303, '2026-07-02', 23, 'Buang Sampah', 4, '4:00 PM - 6:00PM', 0.50, 0, NULL, NULL, NULL),
(589, '2026-08-05', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(590, '2026-08-05', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(592, '2026-08-05', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(593, '2026-08-05', 23, 'Buang Sampah', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(594, '2026-08-05', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00 PM', 1.00, 0, NULL, NULL, NULL),
(595, '2026-08-06', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(596, '2026-08-06', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(597, '2026-08-06', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(598, '2026-08-06', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00 PM', 1.00, 0, NULL, NULL, NULL),
(599, '2026-08-06', 23, 'Buang Sampah', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(600, '2026-08-07', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(601, '2026-08-07', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(602, '2026-08-07', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(603, '2026-08-07', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00 PM', 1.00, 0, NULL, NULL, NULL),
(604, '2026-08-07', 23, 'Buang Sampah', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(605, '2026-08-08', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(606, '2026-08-08', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(607, '2026-08-08', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(608, '2026-08-08', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00 PM', 1.00, 0, NULL, NULL, NULL),
(609, '2026-08-08', 23, 'Buang Sampah', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(610, '2026-08-09', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(611, '2026-08-09', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(612, '2026-08-09', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(613, '2026-08-09', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(614, '2026-08-09', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(615, '2026-08-10', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(616, '2026-08-10', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(617, '2026-08-10', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(618, '2026-08-10', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(619, '2026-08-10', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(620, '2026-08-11', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(621, '2026-08-11', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(622, '2026-08-11', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(623, '2026-08-11', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(624, '2026-08-11', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(625, '2026-08-12', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(626, '2026-08-12', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(627, '2026-08-12', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(628, '2026-08-12', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(629, '2026-08-12', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(630, '2026-08-13', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(631, '2026-08-13', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(632, '2026-08-13', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(633, '2026-08-13', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(634, '2026-08-13', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(635, '2026-08-14', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(636, '2026-08-14', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(637, '2026-08-14', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(638, '2026-08-14', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(639, '2026-08-14', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(640, '2026-08-17', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(641, '2026-08-17', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(642, '2026-08-17', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(643, '2026-08-17', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(644, '2026-08-17', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(645, '2026-08-17', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(646, '2026-08-18', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(647, '2026-08-18', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(648, '2026-08-18', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(649, '2026-08-18', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(650, '2026-08-18', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(651, '2026-08-18', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(652, '2026-08-19', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(653, '2026-08-19', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(654, '2026-08-19', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(655, '2026-08-19', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(656, '2026-08-19', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(657, '2026-08-19', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(658, '2026-08-20', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(659, '2026-08-20', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(660, '2026-08-20', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(661, '2026-08-20', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(662, '2026-08-20', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(663, '2026-08-20', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(664, '2026-08-21', 9, 'Cuci Tandas', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(665, '2026-08-21', 10, 'Cuci Tandas', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(666, '2026-08-21', 12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, '9:00 AM - 11:00 AM', 0.50, 0, NULL, NULL, NULL),
(667, '2026-08-21', 15, 'Restock Air & Kudapan', 2, '11:00 AM - 1:00 PM', 1.00, 0, NULL, NULL, NULL),
(668, '2026-08-21', 20, 'Update Inventory Pantry', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(669, '2026-08-21', 21, 'Double Check Kebersihan', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(670, '2026-08-24', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(671, '2026-08-24', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(672, '2026-08-24', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(673, '2026-08-24', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00 PM', 1.00, 0, NULL, NULL, NULL),
(674, '2026-08-24', 23, 'Buang Sampah', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(675, '2026-08-25', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(676, '2026-08-25', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(677, '2026-08-25', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(678, '2026-08-25', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00 PM', 1.00, 0, NULL, NULL, NULL),
(679, '2026-08-25', 23, 'Buang Sampah', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(680, '2026-08-26', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(681, '2026-08-26', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(682, '2026-08-26', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(683, '2026-08-26', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00 PM', 1.00, 0, NULL, NULL, NULL),
(684, '2026-08-26', 23, 'Buang Sampah', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(685, '2026-08-27', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(686, '2026-08-27', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(687, '2026-08-27', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(688, '2026-08-27', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00 PM', 1.00, 0, NULL, NULL, NULL),
(689, '2026-08-27', 23, 'Buang Sampah', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(690, '2026-08-28', 8, 'Cuci Tandas', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(691, '2026-08-28', 14, 'Masak Nasi', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(692, '2026-08-28', 19, 'Kemas (tempat rehat dan solat)', 3, '2:00 PM - 4:00 PM', 1.00, 0, NULL, NULL, NULL),
(693, '2026-08-28', 22, 'Pack Baki Makanan Lunch', 4, '4:00 PM - 6:00 PM', 1.00, 0, NULL, NULL, NULL),
(694, '2026-08-28', 23, 'Buang Sampah', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(695, '2026-09-01', 11, 'Cuci Tandas', 4, '4:00 PM - 6:00 PM', 0.50, 0, NULL, NULL, NULL),
(696, '2026-09-01', 13, 'Mop Lantai', 1, '9:00 AM - 11:00 AM', 1.00, 0, NULL, NULL, NULL),
(697, '2026-09-01', 16, 'Prepare Lunch', 2, '11:00 AM - 1:00 PM', 0.50, 0, NULL, NULL, NULL),
(698, '2026-09-01', 17, 'Sapu & Mop (staff area)', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL),
(699, '2026-09-01', 18, 'Bersihkan Lunch Area', 3, '2:00 PM - 4:00 PM', 0.50, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `daily_task_evidence`
--

CREATE TABLE `daily_task_evidence` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `daily_checklist_id` bigint(20) UNSIGNED NOT NULL,
  `disk` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL,
  `invalidated_at` timestamp(6) NULL DEFAULT NULL,
  `invalidated_by` varchar(100) DEFAULT NULL,
  `invalidation_reason` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_task_evidence`
--

INSERT INTO `daily_task_evidence` (`id`, `daily_checklist_id`, `disk`, `path`, `mime_type`, `size_bytes`, `invalidated_at`, `invalidated_by`, `invalidation_reason`, `created_at`, `updated_at`) VALUES
(1, 112, 'local', 'evidence/2026-07-27/daily/8e/8ac18b577ae22cd568a3b5bb1e10e61ac27ba95430381eab.jpg', 'image/jpeg', 2094889, NULL, NULL, NULL, '2026-07-27 09:17:33', '2026-07-27 09:17:33'),
(2, 156, 'local', 'evidence/2026-07-27/daily/a2/4c1bb1bad24c78a1c05c9876d341e2705c15c146cfffe41c.jpg', 'image/jpeg', 2368799, NULL, NULL, NULL, '2026-07-27 09:17:53', '2026-07-27 09:17:53'),
(3, 167, 'local', 'evidence/2026-07-27/daily/ae/1b778888de151a7b7374c28377147851d3d521c4b24a0397.jpg', 'image/jpeg', 2969614, NULL, NULL, NULL, '2026-07-27 09:28:49', '2026-07-27 09:28:49'),
(4, 178, 'local', 'evidence/2026-07-27/daily/ce/6870c5878e59a34bd337fe6148aff106a6c0dc1946ec6b65.jpg', 'image/jpeg', 2119864, NULL, NULL, NULL, '2026-07-27 09:33:29', '2026-07-27 09:33:29'),
(5, 113, 'local', 'evidence/2026-07-28/daily/14/81af9e9eac4dc9d0beb2939c98747a4d2b753c21f1dbda6b.jpg', 'image/jpeg', 1941740, NULL, NULL, NULL, '2026-07-28 07:28:36', '2026-07-28 07:28:36'),
(6, 201, 'local', 'evidence/2026-07-28/daily/5f/7c2607e50d168e4c538849b0a4bc23eb90b513bd2d30d16d.jpg', 'image/jpeg', 1882890, NULL, NULL, NULL, '2026-07-28 08:18:44', '2026-07-28 08:18:44');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_07_15_000001_create_task_templates_table', 1),
(5, '2026_07_15_000002_create_daily_checklists_table', 1),
(6, '2026_07_15_000003_create_checklist_materialization_tables', 1),
(7, '2026_07_27_000004_add_configurable_sessions_weekly_tasks_and_evidence', 2),
(8, '2026_07_30_000005_add_task_notes_and_reopen_audits', 3),
(9, '2026_08_03_000006_add_repeat_every_weeks_to_weekly_task_templates', 4),
(10, '2026_08_03_000007_add_task_collections_and_schedules', 4),
(11, '2026_08_03_000008_add_collection_scopes_to_task_templates', 4),
(12, '2026_08_05_000009_fix_legacy_session_time_spacing', 4),
(13, '2026_08_05_000010_add_rotation_cycle_and_audit_logs', 4),
(14, '2026_08_05_000011_rebuild_rotation_snapshots_after_anchor_timezone_fix', 5),
(15, '2026_08_11_000012_create_public_holidays_table', 6);

-- --------------------------------------------------------

--
-- Table structure for table `public_holidays`
--

CREATE TABLE `public_holidays` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `public_holidays`
--

INSERT INTO `public_holidays` (`id`, `date`, `name`, `created_at`, `updated_at`) VALUES
(1, '2026-08-31', 'Malaysia\'s Independence Day', '2026-08-11 03:20:48', '2026-08-11 03:20:48'),
(2, '2026-09-16', 'Malaysia Day', '2026-08-11 03:20:58', '2026-08-11 03:20:58'),
(3, '2026-12-11', 'Sultan of Selangor\'s Birthday', '2026-08-11 03:21:15', '2026-08-11 03:21:15'),
(4, '2026-12-25', 'Christmas Day', '2026-08-11 03:21:30', '2026-08-11 03:21:30');

-- --------------------------------------------------------

--
-- Table structure for table `rotation_cycle_settings`
--

CREATE TABLE `rotation_cycle_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `anchor_week_start` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rotation_cycle_settings`
--

INSERT INTO `rotation_cycle_settings` (`id`, `anchor_week_start`, `created_at`, `updated_at`) VALUES
(1, '2026-08-02', '2026-08-05 04:27:38', '2026-08-05 04:27:38');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

-- Sessions seed data was removed for privacy.
-- If you need session fixtures, add sanitized rows here with non-sensitive values.

-- --------------------------------------------------------

--
-- Table structure for table `statistics_tracking`
--

CREATE TABLE `statistics_tracking` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `started_on` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statistics_tracking`
--

INSERT INTO `statistics_tracking` (`id`, `started_on`, `created_at`, `updated_at`) VALUES
(1, '2026-07-27', '2026-07-27 08:06:54', '2026-07-27 08:06:54');

-- --------------------------------------------------------

--
-- Table structure for table `task_collections`
--

CREATE TABLE `task_collections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `rotation_order` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_collections`
--

INSERT INTO `task_collections` (`id`, `name`, `is_default`, `rotation_order`, `created_at`, `updated_at`) VALUES
(1, 'General', 1, NULL, '2026-08-05 04:27:38', '2026-08-05 04:27:38'),
(12, 'A', 0, 1, '2026-08-05 09:02:00', '2026-08-05 09:02:00'),
(13, 'B', 0, 2, '2026-08-05 09:02:02', '2026-08-05 09:02:02'),
(14, 'C', 0, 3, '2026-08-05 09:02:04', '2026-08-05 09:02:04');

-- --------------------------------------------------------

--
-- Table structure for table `task_collection_schedules`
--

CREATE TABLE `task_collection_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_collection_id` bigint(20) UNSIGNED NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_reopen_audits`
--

CREATE TABLE `task_reopen_audits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_type` varchar(20) NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `session_name` varchar(100) NOT NULL,
  `task_date` date NOT NULL,
  `previous_completed_at` timestamp(6) NULL DEFAULT NULL,
  `completion_note` varchar(500) DEFAULT NULL,
  `invalidated_evidence_count` int(10) UNSIGNED NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `occurred_at` timestamp(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_reopen_audits`
--

INSERT INTO `task_reopen_audits` (`id`, `task_type`, `task_id`, `task_name`, `session_name`, `task_date`, `previous_completed_at`, `completion_note`, `invalidated_evidence_count`, `reason`, `performed_by`, `occurred_at`) VALUES
(1, 'daily', 568, 'test', '9:00 AM - 11:00 AM', '2026-08-05', '2026-08-05 04:44:32.000000', 'hehe', 1, 'why not', 'System administrator', '2026-08-05 04:44:58.000000');

-- --------------------------------------------------------

--
-- Table structure for table `task_sessions`
--

CREATE TABLE `task_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_sessions`
--

INSERT INTO `task_sessions` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '9:00 AM - 11:00 AM', 1, 1, '2026-07-27 08:06:54', '2026-07-27 09:02:49'),
(2, '11:00 AM - 1:00 PM', 2, 1, '2026-07-27 08:06:54', '2026-07-27 09:02:49'),
(3, '2:00 PM - 4:00 PM', 3, 1, '2026-07-27 08:06:54', '2026-07-27 09:02:49'),
(4, '4:00 PM - 6:00 PM', 4, 1, '2026-07-27 09:02:35', '2026-07-27 09:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `task_templates`
--

CREATE TABLE `task_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_session_id` bigint(20) UNSIGNED NOT NULL,
  `task_collection_id` bigint(20) UNSIGNED DEFAULT NULL,
  `applies_to_all_collections` tinyint(1) NOT NULL DEFAULT 0,
  `credit_hours` decimal(6,2) NOT NULL DEFAULT 1.00,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_templates`
--

INSERT INTO `task_templates` (`id`, `task_name`, `task_session_id`, `task_collection_id`, `applies_to_all_collections`, `credit_hours`, `sort_order`, `is_active`) VALUES
(1, 'asda', 1, 1, 0, 1.00, 0, 0),
(2, 'Sapu sampah', 1, 1, 0, 1.00, 0, 0),
(3, 'Kemas Pantry', 2, 1, 0, 1.00, 0, 0),
(4, 'Cuci tandas', 3, 1, 0, 1.00, 0, 0),
(5, 'Masak nasi', 2, 1, 0, 1.00, 0, 0),
(6, 'Lap cermin', 1, 1, 0, 1.00, 0, 0),
(7, 'Cuci bekas makanan', 3, 1, 0, 1.00, 0, 0),
(8, 'Cuci Tandas', 1, 12, 0, 1.00, 1, 1),
(9, 'Cuci Tandas', 2, 14, 0, 0.50, 2, 1),
(10, 'Cuci Tandas', 3, 14, 0, 0.50, 3, 1),
(11, 'Cuci Tandas', 4, 13, 0, 0.50, 4, 1),
(12, 'Sapu Sampah (entrance, hall, pantry, bilik sir, tempat solat)', 1, 14, 0, 0.50, 5, 1),
(13, 'Mop Lantai', 1, 13, 0, 1.00, 6, 1),
(14, 'Masak Nasi', 2, 12, 0, 0.50, 7, 1),
(15, 'Restock Air & Kudapan', 2, 14, 0, 1.00, 8, 1),
(16, 'Prepare Lunch', 2, 13, 0, 0.50, 9, 1),
(17, 'Sapu & Mop (staff area)', 3, 13, 0, 0.50, 10, 1),
(18, 'Bersihkan Lunch Area', 3, 13, 0, 0.50, 11, 1),
(19, 'Kemas (tempat rehat dan solat)', 3, 12, 0, 1.00, 12, 1),
(20, 'Update Inventory Pantry', 3, 14, 0, 1.00, 13, 1),
(21, 'Double Check Kebersihan', 4, 14, 0, 0.50, 14, 1),
(22, 'Pack Baki Makanan Lunch', 4, 12, 0, 1.00, 15, 1),
(23, 'Buang Sampah', 4, 12, 0, 0.50, 16, 1),
(24, 'test', 1, NULL, 1, 1.00, 17, 0),
(25, 'test', 1, 1, 0, 1.00, 18, 0);

-- --------------------------------------------------------

--
-- Table structure for table `task_template_task_collection`
--

CREATE TABLE `task_template_task_collection` (
  `task_template_id` bigint(20) UNSIGNED NOT NULL,
  `task_collection_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_template_task_collection`
--

INSERT INTO `task_template_task_collection` (`task_template_id`, `task_collection_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(25, 1),
(8, 12),
(14, 12),
(19, 12),
(22, 12),
(23, 12),
(11, 13),
(13, 13),
(16, 13),
(17, 13),
(18, 13),
(9, 14),
(10, 14),
(12, 14),
(15, 14),
(20, 14),
(21, 14);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekly_materializations`
--

CREATE TABLE `weekly_materializations` (
  `week_start` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weekly_materializations`
--

INSERT INTO `weekly_materializations` (`week_start`) VALUES
('2026-06-29'),
('2026-07-20'),
('2026-07-27'),
('2026-08-03'),
('2026-08-10'),
('2026-08-17'),
('2026-08-24'),
('2026-08-31');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_task_evidence`
--

CREATE TABLE `weekly_task_evidence` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `weekly_task_occurrence_id` bigint(20) UNSIGNED NOT NULL,
  `disk` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL,
  `invalidated_at` timestamp(6) NULL DEFAULT NULL,
  `invalidated_by` varchar(100) DEFAULT NULL,
  `invalidation_reason` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weekly_task_evidence`
--

INSERT INTO `weekly_task_evidence` (`id`, `weekly_task_occurrence_id`, `disk`, `path`, `mime_type`, `size_bytes`, `invalidated_at`, `invalidated_by`, `invalidation_reason`, `created_at`, `updated_at`) VALUES
(1, 5, 'local', 'evidence/2026-07-31/weekly/e3/718b209cd75ea423759b8cda4035bcd129d293ceb8fa9afb.png', 'image/png', 12303, NULL, NULL, NULL, '2026-07-31 08:31:07', '2026-07-31 08:31:07');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_task_occurrences`
--

CREATE TABLE `weekly_task_occurrences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `week_start` date NOT NULL,
  `weekly_task_template_id` bigint(20) UNSIGNED NOT NULL,
  `task_session_id` bigint(20) UNSIGNED NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `session_name` varchar(100) NOT NULL,
  `credit_hours` decimal(6,2) NOT NULL,
  `original_due_date` date NOT NULL,
  `scheduled_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `missed_reason` varchar(20) DEFAULT NULL,
  `completed_at` timestamp(6) NULL DEFAULT NULL,
  `completed_on` date DEFAULT NULL,
  `completion_note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weekly_task_occurrences`
--

INSERT INTO `weekly_task_occurrences` (`id`, `week_start`, `weekly_task_template_id`, `task_session_id`, `task_name`, `session_name`, `credit_hours`, `original_due_date`, `scheduled_date`, `status`, `missed_reason`, `completed_at`, `completed_on`, `completion_note`, `created_at`, `updated_at`) VALUES
(1, '2026-07-27', 1, 2, 'Lap Kerusi Urut', '11:00 AM - 1:00 PM', 1.00, '2026-07-29', '2026-08-02', 'missed', 'incomplete', NULL, NULL, NULL, '2026-07-27 09:14:16', '2026-08-04 03:43:31'),
(5, '2026-07-27', 2, 1, 'Lap Cermin', '9:00 AM - 11:00 AM', 1.00, '2026-07-27', '2026-07-31', 'completed', NULL, '2026-07-31 08:31:07.000000', '2026-07-31', 'sss', '2026-07-27 09:15:10', '2026-07-31 08:31:07'),
(12, '2026-07-27', 3, 1, 'Vacuum Carpet', '9:00 AM - 11:00 AM', 1.00, '2026-07-28', '2026-08-02', 'missed', 'incomplete', NULL, NULL, NULL, '2026-07-27 09:15:28', '2026-08-04 03:43:31'),
(22, '2026-07-27', 4, 4, 'Tukar Sarung Bantal', '4:00 PM - 6:00PM', 1.00, '2026-07-31', '2026-08-02', 'missed', 'incomplete', NULL, NULL, NULL, '2026-07-27 09:16:54', '2026-08-04 03:43:31'),
(63, '2026-08-03', 2, 1, 'Lap Cermin', '9:00 AM - 11:00 AM', 1.00, '2026-08-03', '2026-08-07', 'missed', 'incomplete', NULL, NULL, NULL, '2026-08-05 09:02:34', '2026-08-10 09:13:58'),
(64, '2026-08-03', 1, 2, 'Lap Kerusi Urut', '11:00 AM - 1:00 PM', 1.00, '2026-08-05', '2026-08-07', 'missed', 'incomplete', NULL, NULL, NULL, '2026-08-05 09:02:53', '2026-08-10 09:13:58'),
(65, '2026-08-03', 3, 1, 'Vacuum Carpet', '9:00 AM - 11:00 AM', 1.00, '2026-08-04', '2026-08-07', 'missed', 'incomplete', NULL, NULL, NULL, '2026-08-05 09:04:06', '2026-08-10 09:13:58'),
(66, '2026-08-10', 3, 1, 'Vacuum Carpet', '9:00 AM - 11:00 AM', 1.00, '2026-08-11', '2026-08-11', 'pending', NULL, NULL, NULL, NULL, '2026-08-10 09:13:58', '2026-08-10 09:13:58'),
(67, '2026-08-10', 4, 4, 'Tukar Sarung Bantal', '4:00 PM - 6:00 PM', 1.00, '2026-08-14', '2026-08-14', 'pending', NULL, NULL, NULL, NULL, '2026-08-10 09:13:58', '2026-08-10 09:13:58'),
(68, '2026-08-17', 2, 1, 'Lap Cermin', '9:00 AM - 11:00 AM', 1.00, '2026-08-17', '2026-08-17', 'pending', NULL, NULL, NULL, NULL, '2026-08-11 03:21:44', '2026-08-11 03:21:44'),
(69, '2026-08-17', 3, 1, 'Vacuum Carpet', '9:00 AM - 11:00 AM', 1.00, '2026-08-18', '2026-08-18', 'pending', NULL, NULL, NULL, NULL, '2026-08-11 03:21:44', '2026-08-11 03:21:44'),
(70, '2026-08-24', 1, 2, 'Lap Kerusi Urut', '11:00 AM - 1:00 PM', 1.00, '2026-08-26', '2026-08-26', 'pending', NULL, NULL, NULL, NULL, '2026-08-11 03:21:46', '2026-08-11 03:21:46'),
(71, '2026-08-24', 2, 1, 'Lap Cermin', '9:00 AM - 11:00 AM', 1.00, '2026-08-24', '2026-08-24', 'pending', NULL, NULL, NULL, NULL, '2026-08-11 03:21:46', '2026-08-11 03:21:46'),
(72, '2026-08-24', 3, 1, 'Vacuum Carpet', '9:00 AM - 11:00 AM', 1.00, '2026-08-25', '2026-08-25', 'pending', NULL, NULL, NULL, NULL, '2026-08-11 03:21:46', '2026-08-11 03:21:46'),
(73, '2026-08-31', 3, 1, 'Vacuum Carpet', '9:00 AM - 11:00 AM', 1.00, '2026-09-01', '2026-09-01', 'pending', NULL, NULL, NULL, NULL, '2026-08-11 03:21:54', '2026-08-11 03:21:54'),
(74, '2026-08-31', 4, 4, 'Tukar Sarung Bantal', '4:00 PM - 6:00 PM', 1.00, '2026-09-04', '2026-09-04', 'pending', NULL, NULL, NULL, NULL, '2026-08-11 03:21:54', '2026-08-11 03:21:54');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_task_postponements`
--

CREATE TABLE `weekly_task_postponements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `weekly_task_occurrence_id` bigint(20) UNSIGNED NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `reason` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weekly_task_postponements`
--

INSERT INTO `weekly_task_postponements` (`id`, `weekly_task_occurrence_id`, `from_date`, `to_date`, `reason`, `created_at`, `updated_at`) VALUES
(2, 5, '2026-07-27', '2026-07-28', 'incomplete', '2026-07-28 01:17:17', '2026-07-28 01:17:17'),
(5, 5, '2026-07-28', '2026-07-29', 'incomplete', '2026-07-29 01:11:24', '2026-07-29 01:11:24'),
(38, 5, '2026-07-29', '2026-07-30', 'incomplete', '2026-07-30 02:25:52', '2026-07-30 02:25:52'),
(49, 12, '2026-07-28', '2026-07-29', 'incomplete', '2026-07-30 08:33:54', '2026-07-30 08:33:54'),
(50, 12, '2026-07-29', '2026-07-30', 'incomplete', '2026-07-30 08:33:54', '2026-07-30 08:33:54'),
(51, 1, '2026-07-29', '2026-07-30', 'incomplete', '2026-07-30 08:34:03', '2026-07-30 08:34:03'),
(52, 1, '2026-07-30', '2026-07-31', 'incomplete', '2026-07-31 08:30:23', '2026-07-31 08:30:23'),
(53, 5, '2026-07-30', '2026-07-31', 'incomplete', '2026-07-31 08:30:23', '2026-07-31 08:30:23'),
(54, 12, '2026-07-30', '2026-07-31', 'incomplete', '2026-07-31 08:30:23', '2026-07-31 08:30:23'),
(55, 1, '2026-07-31', '2026-08-01', 'incomplete', '2026-08-02 15:11:47', '2026-08-02 15:11:47'),
(56, 1, '2026-08-01', '2026-08-02', 'incomplete', '2026-08-02 15:11:47', '2026-08-02 15:11:47'),
(57, 12, '2026-07-31', '2026-08-01', 'incomplete', '2026-08-02 15:11:47', '2026-08-02 15:11:47'),
(58, 12, '2026-08-01', '2026-08-02', 'incomplete', '2026-08-02 15:11:47', '2026-08-02 15:11:47'),
(59, 22, '2026-07-31', '2026-08-01', 'incomplete', '2026-08-02 15:11:47', '2026-08-02 15:11:47'),
(60, 22, '2026-08-01', '2026-08-02', 'incomplete', '2026-08-02 15:11:47', '2026-08-02 15:11:47'),
(85, 63, '2026-08-03', '2026-08-04', 'incomplete', '2026-08-05 09:02:34', '2026-08-05 09:02:34'),
(86, 63, '2026-08-04', '2026-08-05', 'incomplete', '2026-08-05 09:02:34', '2026-08-05 09:02:34'),
(87, 65, '2026-08-04', '2026-08-05', 'incomplete', '2026-08-05 09:04:06', '2026-08-05 09:04:06'),
(88, 63, '2026-08-05', '2026-08-06', 'incomplete', '2026-08-07 02:28:04', '2026-08-07 02:28:04'),
(89, 63, '2026-08-06', '2026-08-07', 'incomplete', '2026-08-07 02:28:04', '2026-08-07 02:28:04'),
(90, 64, '2026-08-05', '2026-08-06', 'incomplete', '2026-08-07 02:28:04', '2026-08-07 02:28:04'),
(91, 64, '2026-08-06', '2026-08-07', 'incomplete', '2026-08-07 02:28:04', '2026-08-07 02:28:04'),
(92, 65, '2026-08-05', '2026-08-06', 'incomplete', '2026-08-07 02:28:04', '2026-08-07 02:28:04'),
(93, 65, '2026-08-06', '2026-08-07', 'incomplete', '2026-08-07 02:28:04', '2026-08-07 02:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_task_templates`
--

CREATE TABLE `weekly_task_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_session_id` bigint(20) UNSIGNED NOT NULL,
  `task_collection_id` bigint(20) UNSIGNED DEFAULT NULL,
  `applies_to_all_collections` tinyint(1) NOT NULL DEFAULT 0,
  `due_weekday` tinyint(3) UNSIGNED NOT NULL,
  `credit_hours` decimal(6,2) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `starts_on` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weekly_task_templates`
--

INSERT INTO `weekly_task_templates` (`id`, `task_name`, `task_session_id`, `task_collection_id`, `applies_to_all_collections`, `due_weekday`, `credit_hours`, `sort_order`, `starts_on`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Lap Kerusi Urut', 2, 12, 0, 3, 1.00, 1, '2026-07-27', 1, '2026-07-27 09:14:16', '2026-08-05 09:02:53'),
(2, 'Lap Cermin', 1, 12, 0, 1, 1.00, 2, '2026-07-27', 1, '2026-07-27 09:15:10', '2026-08-05 09:02:34'),
(3, 'Vacuum Carpet', 1, 12, 0, 2, 1.00, 3, '2026-07-27', 1, '2026-07-27 09:15:28', '2026-08-05 09:04:13'),
(4, 'Tukar Sarung Bantal', 4, 13, 0, 5, 1.00, 4, '2026-07-27', 1, '2026-07-27 09:16:54', '2026-08-05 09:03:26');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_task_template_task_collection`
--

CREATE TABLE `weekly_task_template_task_collection` (
  `weekly_task_template_id` bigint(20) UNSIGNED NOT NULL,
  `task_collection_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weekly_task_template_task_collection`
--

INSERT INTO `weekly_task_template_task_collection` (`weekly_task_template_id`, `task_collection_id`) VALUES
(1, 12),
(2, 12),
(3, 12),
(3, 13),
(4, 13),
(2, 14),
(3, 14);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_occurred_at_index` (`occurred_at`),
  ADD KEY `audit_logs_action_occurred_at_index` (`action`,`occurred_at`),
  ADD KEY `audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `checklist_day_statuses`
--
ALTER TABLE `checklist_day_statuses`
  ADD PRIMARY KEY (`date`),
  ADD KEY `checklist_day_statuses_date_is_unavailable_index` (`date`,`is_unavailable`);

--
-- Indexes for table `checklist_item_positions`
--
ALTER TABLE `checklist_item_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `checklist_item_position_unique` (`date`,`item_type`,`item_id`),
  ADD UNIQUE KEY `checklist_session_position_unique` (`date`,`task_session_id`,`position`),
  ADD KEY `checklist_item_positions_task_session_id_foreign` (`task_session_id`);

--
-- Indexes for table `checklist_materializations`
--
ALTER TABLE `checklist_materializations`
  ADD PRIMARY KEY (`date`);

--
-- Indexes for table `checklist_sync_locks`
--
ALTER TABLE `checklist_sync_locks`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `daily_checklists`
--
ALTER TABLE `daily_checklists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `daily_checklists_date_task_template_id_unique` (`date`,`task_template_id`),
  ADD KEY `daily_checklists_task_template_id_foreign` (`task_template_id`),
  ADD KEY `daily_checklists_completed_by_user_id_foreign` (`completed_by_user_id`),
  ADD KEY `daily_checklists_date_index` (`date`),
  ADD KEY `daily_checklists_date_is_completed_index` (`date`,`is_completed`),
  ADD KEY `daily_checklists_is_completed_completed_at_index` (`is_completed`,`completed_at`),
  ADD KEY `daily_checklists_task_session_id_foreign` (`task_session_id`),
  ADD KEY `daily_checklists_date_task_session_id_index` (`date`,`task_session_id`);

--
-- Indexes for table `daily_task_evidence`
--
ALTER TABLE `daily_task_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `daily_task_evidence_daily_checklist_id_index` (`daily_checklist_id`),
  ADD KEY `daily_task_evidence_invalidated_at_index` (`invalidated_at`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `public_holidays`
--
ALTER TABLE `public_holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `public_holidays_date_unique` (`date`);

--
-- Indexes for table `rotation_cycle_settings`
--
ALTER TABLE `rotation_cycle_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `statistics_tracking`
--
ALTER TABLE `statistics_tracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task_collections`
--
ALTER TABLE `task_collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_collections_name_unique` (`name`),
  ADD KEY `task_collections_is_default_index` (`is_default`),
  ADD KEY `task_collections_rotation_order_index` (`rotation_order`);

--
-- Indexes for table `task_collection_schedules`
--
ALTER TABLE `task_collection_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_collection_schedules_starts_on_ends_on_index` (`starts_on`,`ends_on`),
  ADD KEY `task_collection_schedules_task_collection_id_index` (`task_collection_id`);

--
-- Indexes for table `task_reopen_audits`
--
ALTER TABLE `task_reopen_audits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_reopen_audits_task_type_task_id_occurred_at_index` (`task_type`,`task_id`,`occurred_at`),
  ADD KEY `task_reopen_audits_occurred_at_index` (`occurred_at`);

--
-- Indexes for table `task_sessions`
--
ALTER TABLE `task_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_sessions_name_unique` (`name`),
  ADD KEY `task_sessions_sort_order_index` (`sort_order`),
  ADD KEY `task_sessions_is_active_sort_order_index` (`is_active`,`sort_order`);

--
-- Indexes for table `task_templates`
--
ALTER TABLE `task_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_templates_task_session_id_foreign` (`task_session_id`),
  ADD KEY `task_templates_is_active_task_session_id_index` (`is_active`,`task_session_id`),
  ADD KEY `task_templates_task_collection_id_foreign` (`task_collection_id`),
  ADD KEY `task_templates_is_active_task_collection_id_index` (`is_active`,`task_collection_id`),
  ADD KEY `task_templates_all_collections_index` (`applies_to_all_collections`);

--
-- Indexes for table `task_template_task_collection`
--
ALTER TABLE `task_template_task_collection`
  ADD PRIMARY KEY (`task_template_id`,`task_collection_id`),
  ADD KEY `tttc_collection_fk` (`task_collection_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`);

--
-- Indexes for table `weekly_materializations`
--
ALTER TABLE `weekly_materializations`
  ADD PRIMARY KEY (`week_start`);

--
-- Indexes for table `weekly_task_evidence`
--
ALTER TABLE `weekly_task_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `weekly_task_evidence_weekly_task_occurrence_id_index` (`weekly_task_occurrence_id`),
  ADD KEY `weekly_task_evidence_invalidated_at_index` (`invalidated_at`);

--
-- Indexes for table `weekly_task_occurrences`
--
ALTER TABLE `weekly_task_occurrences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `weekly_occurrence_unique` (`week_start`,`weekly_task_template_id`),
  ADD KEY `weekly_task_occurrences_weekly_task_template_id_foreign` (`weekly_task_template_id`),
  ADD KEY `weekly_task_occurrences_task_session_id_foreign` (`task_session_id`),
  ADD KEY `weekly_task_occurrences_scheduled_date_status_index` (`scheduled_date`,`status`),
  ADD KEY `weekly_task_occurrences_completed_on_status_index` (`completed_on`,`status`),
  ADD KEY `weekly_task_occurrences_week_start_status_index` (`week_start`,`status`);

--
-- Indexes for table `weekly_task_postponements`
--
ALTER TABLE `weekly_task_postponements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `weekly_postponement_unique` (`weekly_task_occurrence_id`,`from_date`),
  ADD KEY `weekly_task_postponements_from_date_reason_index` (`from_date`,`reason`);

--
-- Indexes for table `weekly_task_templates`
--
ALTER TABLE `weekly_task_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `weekly_task_templates_task_session_id_foreign` (`task_session_id`),
  ADD KEY `weekly_task_templates_is_active_task_session_id_index` (`is_active`,`task_session_id`),
  ADD KEY `weekly_task_templates_is_active_starts_on_index` (`is_active`,`starts_on`),
  ADD KEY `weekly_task_templates_task_collection_id_foreign` (`task_collection_id`),
  ADD KEY `weekly_task_templates_is_active_task_collection_id_index` (`is_active`,`task_collection_id`),
  ADD KEY `weekly_task_templates_all_collections_index` (`applies_to_all_collections`);

--
-- Indexes for table `weekly_task_template_task_collection`
--
ALTER TABLE `weekly_task_template_task_collection`
  ADD PRIMARY KEY (`weekly_task_template_id`,`task_collection_id`),
  ADD KEY `wtttc_collection_fk` (`task_collection_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `checklist_item_positions`
--
ALTER TABLE `checklist_item_positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `daily_checklists`
--
ALTER TABLE `daily_checklists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=700;

--
-- AUTO_INCREMENT for table `daily_task_evidence`
--
ALTER TABLE `daily_task_evidence`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `public_holidays`
--
ALTER TABLE `public_holidays`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `statistics_tracking`
--
ALTER TABLE `statistics_tracking`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task_collections`
--
ALTER TABLE `task_collections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `task_collection_schedules`
--
ALTER TABLE `task_collection_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_reopen_audits`
--
ALTER TABLE `task_reopen_audits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task_sessions`
--
ALTER TABLE `task_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `task_templates`
--
ALTER TABLE `task_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weekly_task_evidence`
--
ALTER TABLE `weekly_task_evidence`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `weekly_task_occurrences`
--
ALTER TABLE `weekly_task_occurrences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `weekly_task_postponements`
--
ALTER TABLE `weekly_task_postponements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `weekly_task_templates`
--
ALTER TABLE `weekly_task_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `checklist_item_positions`
--
ALTER TABLE `checklist_item_positions`
  ADD CONSTRAINT `checklist_item_positions_task_session_id_foreign` FOREIGN KEY (`task_session_id`) REFERENCES `task_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_checklists`
--
ALTER TABLE `daily_checklists`
  ADD CONSTRAINT `daily_checklists_completed_by_user_id_foreign` FOREIGN KEY (`completed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `daily_checklists_task_session_id_foreign` FOREIGN KEY (`task_session_id`) REFERENCES `task_sessions` (`id`),
  ADD CONSTRAINT `daily_checklists_task_template_id_foreign` FOREIGN KEY (`task_template_id`) REFERENCES `task_templates` (`id`);

--
-- Constraints for table `daily_task_evidence`
--
ALTER TABLE `daily_task_evidence`
  ADD CONSTRAINT `daily_task_evidence_daily_checklist_id_foreign` FOREIGN KEY (`daily_checklist_id`) REFERENCES `daily_checklists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_collection_schedules`
--
ALTER TABLE `task_collection_schedules`
  ADD CONSTRAINT `task_collection_schedules_task_collection_id_foreign` FOREIGN KEY (`task_collection_id`) REFERENCES `task_collections` (`id`);

--
-- Constraints for table `task_templates`
--
ALTER TABLE `task_templates`
  ADD CONSTRAINT `task_templates_task_collection_id_foreign` FOREIGN KEY (`task_collection_id`) REFERENCES `task_collections` (`id`),
  ADD CONSTRAINT `task_templates_task_session_id_foreign` FOREIGN KEY (`task_session_id`) REFERENCES `task_sessions` (`id`);

--
-- Constraints for table `task_template_task_collection`
--
ALTER TABLE `task_template_task_collection`
  ADD CONSTRAINT `tttc_collection_fk` FOREIGN KEY (`task_collection_id`) REFERENCES `task_collections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tttc_template_fk` FOREIGN KEY (`task_template_id`) REFERENCES `task_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_task_evidence`
--
ALTER TABLE `weekly_task_evidence`
  ADD CONSTRAINT `weekly_task_evidence_weekly_task_occurrence_id_foreign` FOREIGN KEY (`weekly_task_occurrence_id`) REFERENCES `weekly_task_occurrences` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_task_occurrences`
--
ALTER TABLE `weekly_task_occurrences`
  ADD CONSTRAINT `weekly_task_occurrences_task_session_id_foreign` FOREIGN KEY (`task_session_id`) REFERENCES `task_sessions` (`id`),
  ADD CONSTRAINT `weekly_task_occurrences_weekly_task_template_id_foreign` FOREIGN KEY (`weekly_task_template_id`) REFERENCES `weekly_task_templates` (`id`);

--
-- Constraints for table `weekly_task_postponements`
--
ALTER TABLE `weekly_task_postponements`
  ADD CONSTRAINT `weekly_task_postponements_weekly_task_occurrence_id_foreign` FOREIGN KEY (`weekly_task_occurrence_id`) REFERENCES `weekly_task_occurrences` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_task_templates`
--
ALTER TABLE `weekly_task_templates`
  ADD CONSTRAINT `weekly_task_templates_task_collection_id_foreign` FOREIGN KEY (`task_collection_id`) REFERENCES `task_collections` (`id`),
  ADD CONSTRAINT `weekly_task_templates_task_session_id_foreign` FOREIGN KEY (`task_session_id`) REFERENCES `task_sessions` (`id`);

--
-- Constraints for table `weekly_task_template_task_collection`
--
ALTER TABLE `weekly_task_template_task_collection`
  ADD CONSTRAINT `wtttc_collection_fk` FOREIGN KEY (`task_collection_id`) REFERENCES `task_collections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wtttc_template_fk` FOREIGN KEY (`weekly_task_template_id`) REFERENCES `weekly_task_templates` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
