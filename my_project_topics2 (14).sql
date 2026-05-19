-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2026 at 10:14 AM
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
-- Database: `my_project_topics2`
--

-- --------------------------------------------------------

--
-- Table structure for table `chapter_approvals`
--

CREATE TABLE `chapter_approvals` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `chapter_number` int(11) NOT NULL,
  `status` enum('pending','approved') DEFAULT 'pending',
  `approval_date` datetime DEFAULT NULL,
  `academic_session` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chapter_approvals`
--

INSERT INTO `chapter_approvals` (`id`, `student_id`, `supervisor_id`, `chapter_number`, `status`, `approval_date`, `academic_session`) VALUES
(1, 14, 51, 1, 'approved', '2026-02-10 07:02:48', '2025/2026'),
(2, 18, 51, 2, 'approved', '2026-02-10 07:02:53', '2025/2026'),
(3, 41, 51, 3, 'approved', '2026-02-10 07:02:57', '2025/2026'),
(4, 27, 51, 5, 'approved', '2026-02-10 07:03:02', '2025/2026'),
(5, 27, 51, 2, 'approved', '2026-02-10 07:03:08', '2025/2026'),
(6, 41, 51, 1, 'approved', '2026-02-10 07:03:13', '2025/2026'),
(7, 18, 51, 1, 'approved', '2026-02-10 07:03:17', '2025/2026'),
(8, 41, 51, 2, 'approved', '2026-02-10 07:03:22', '2025/2026'),
(9, 27, 51, 3, 'approved', '2026-02-10 07:03:27', '2025/2026'),
(10, 14, 51, 3, 'approved', '2026-02-10 08:55:34', '2025/2026');

-- --------------------------------------------------------

--
-- Table structure for table `defense_panels`
--

CREATE TABLE `defense_panels` (
  `id` int(11) NOT NULL,
  `panel_name` varchar(100) NOT NULL,
  `panel_type` enum('proposal','internal','external') DEFAULT 'proposal',
  `department_id` int(11) NOT NULL,
  `max_students` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `defense_panels`
--

INSERT INTO `defense_panels` (`id`, `panel_name`, `panel_type`, `department_id`, `max_students`, `created_at`) VALUES
(1, 'Panel 1', 'external', 1, 100, '2026-02-16 15:59:02');

-- --------------------------------------------------------

--
-- Table structure for table `defense_scores`
--

CREATE TABLE `defense_scores` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `defense_scores`
--

INSERT INTO `defense_scores` (`id`, `student_id`, `supervisor_id`, `panel_id`, `score`, `comments`, `created_at`) VALUES
(1, 14, 63, 1, 33.00, 'Impedit dolore est ', '2026-02-16 17:08:18'),
(2, 18, 63, 1, 68.00, 'Et nihil eum officia', '2026-02-16 17:08:18'),
(3, 23, 63, 1, 4.00, 'Tempore maiores qui', '2026-02-16 17:08:18'),
(4, 45, 63, 1, 10.00, 'Quasi non delectus ', '2026-02-16 17:08:18'),
(5, 26, 63, 1, 37.00, 'In ullamco maxime do', '2026-02-16 17:08:18'),
(6, 11, 63, 1, 46.00, 'Natus minima nobis i', '2026-02-16 17:08:18'),
(7, 46, 63, 1, 0.00, 'Quidem neque dolorem', '2026-02-16 17:08:18'),
(8, 41, 63, 1, 34.00, 'Ab sed officia quasi', '2026-02-16 17:08:18'),
(9, 47, 63, 1, 14.00, 'Quisquam quis ut eiu', '2026-02-16 17:08:18'),
(10, 27, 63, 1, 30.00, 'Ut sint non enim har', '2026-02-16 17:08:18'),
(11, 31, 63, 1, 23.00, 'Minim dolorum quia v', '2026-02-16 17:08:18'),
(12, 24, 63, 1, 23.00, 'Amet sunt alias ve', '2026-02-16 17:08:18'),
(13, 28, 63, 1, 86.00, 'Vel minim consequunt', '2026-02-16 17:08:18'),
(14, 22, 63, 1, 22.00, 'Sunt autem ut sint ', '2026-02-16 17:08:18'),
(15, 37, 63, 1, 95.00, 'Sed excepturi reicie', '2026-02-16 17:08:18'),
(16, 5, 63, 1, 56.00, 'A asperiores ipsum a', '2026-02-16 17:08:18'),
(17, 33, 63, 1, 99.00, 'Laboris reprehenderi', '2026-02-16 17:08:18'),
(18, 40, 63, 1, 24.00, 'Inventore elit fugi', '2026-02-16 17:08:18'),
(19, 19, 63, 1, 84.00, 'Rerum nulla quia dui', '2026-02-16 17:08:18'),
(20, 9, 63, 1, 12.00, 'Dolore aliquid cum a', '2026-02-16 17:08:18'),
(21, 34, 63, 1, 69.00, 'Vitae voluptas conse', '2026-02-16 17:08:18'),
(22, 29, 63, 1, 10.00, 'Occaecat possimus b', '2026-02-16 17:08:18'),
(23, 12, 63, 1, 24.00, 'Atque molestiae volu', '2026-02-16 17:08:18'),
(24, 16, 63, 1, 75.00, 'Animi dolor aut do ', '2026-02-16 17:08:18'),
(25, 6, 63, 1, 49.00, 'Blanditiis ut volupt', '2026-02-16 17:08:18'),
(26, 13, 63, 1, 3.00, 'Esse quia excepturi ', '2026-02-16 17:08:18'),
(27, 8, 63, 1, 30.00, 'Blanditiis in evenie', '2026-02-16 17:08:18'),
(28, 17, 63, 1, 29.00, 'Modi vero culpa cum ', '2026-02-16 17:08:18'),
(29, 21, 63, 1, 3.00, 'Quo nobis dolore del', '2026-02-16 17:08:18'),
(30, 25, 63, 1, 57.00, 'Provident reiciendi', '2026-02-16 17:08:18'),
(31, 43, 63, 1, 43.00, 'Saepe laudantium im', '2026-02-16 17:08:18'),
(32, 38, 63, 1, 46.00, 'Rerum velit optio ', '2026-02-16 17:08:18'),
(33, 15, 63, 1, 73.00, 'Elit natus voluptat', '2026-02-16 17:08:18'),
(34, 20, 63, 1, 25.00, 'Doloribus dolores do', '2026-02-16 17:08:18'),
(35, 32, 63, 1, 36.00, 'Doloremque sit dolor', '2026-02-16 17:08:18'),
(36, 48, 63, 1, 71.00, 'Aliquid laborum iure', '2026-02-16 17:08:18'),
(37, 44, 63, 1, 47.00, 'Consectetur dolorum', '2026-02-16 17:08:18'),
(38, 42, 63, 1, 19.00, 'Ad quo non qui in se', '2026-02-16 17:08:18'),
(39, 39, 63, 1, 91.00, 'Ea exercitation faci', '2026-02-16 17:08:18'),
(40, 35, 63, 1, 0.00, 'Anim officia saepe p', '2026-02-16 17:08:18'),
(41, 49, 63, 1, 87.00, 'Eveniet dolorum est', '2026-02-16 17:08:18'),
(42, 10, 63, 1, 59.00, 'Rerum in aute ipsum', '2026-02-16 17:08:18'),
(43, 7, 63, 1, 62.00, 'Cum saepe nihil quis', '2026-02-16 17:08:18'),
(44, 30, 63, 1, 27.00, 'A unde placeat quia', '2026-02-16 17:08:18'),
(45, 36, 63, 1, 60.00, 'Aliquip iure deserun', '2026-02-16 17:08:18');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `project_guideline` varchar(255) DEFAULT NULL,
  `num_chapters` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `faculty_id`, `department_name`, `project_guideline`, `num_chapters`) VALUES
(1, 1, 'Computer Science', 'assets/uploads/guidelines/guideline_1_1770664575.pdf', 5),
(2, 1, 'Cyber Security', NULL, 5);

-- --------------------------------------------------------

--
-- Table structure for table `external_examiners`
--

CREATE TABLE `external_examiners` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `external_examiners`
--

INSERT INTO `external_examiners` (`id`, `name`, `email`, `phone`, `affiliation`, `department_id`, `created_at`) VALUES
(63, 'Prof Gital', 'gital@atbu.edu.ng', '08034352452', 'ATBU', 1, '2026-02-16 15:58:34');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(11) NOT NULL,
  `faculty` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `faculty`) VALUES
(1, 'Computing');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `student_reg_no` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_temp_access`
--

CREATE TABLE `library_temp_access` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `passcode` varchar(50) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_session` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `subject`, `message_text`, `is_read`, `sent_at`, `academic_session`) VALUES
(1, 53, 4, NULL, 'hello dpc', 1, '2026-02-16 10:24:19', '2025/2026'),
(2, 4, 53, NULL, 'Hi', 0, '2026-02-16 10:24:49', '2025/2026'),
(3, 4, 53, NULL, 'What do you need ?', 0, '2026-02-16 10:25:02', '2025/2026'),
(4, 51, 4, NULL, 'How far?', 1, '2026-02-16 10:26:34', '2025/2026'),
(5, 51, 14, NULL, 'What are the challanges that you are facing ?', 1, '2026-02-16 10:27:00', '2025/2026'),
(6, 51, 14, NULL, 'ABDULAZIZ Umar Abbas', 1, '2026-02-16 10:27:26', '2025/2026'),
(7, 51, 18, NULL, 'What are the challanges that you are facing ?\r\nABDULLAHI Ahmed Samaila', 0, '2026-02-16 10:28:12', '2025/2026'),
(8, 51, 41, NULL, 'ADAMU Aliyu Danhassan', 0, '2026-02-16 10:28:36', '2025/2026'),
(9, 51, 27, NULL, 'ALI Yakub', 0, '2026-02-16 10:28:47', '2025/2026'),
(10, 4, 50, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(11, 4, 51, NULL, '[BROADCAST]: Hello All', 1, '2026-02-16 15:41:05', '2023/2024'),
(12, 4, 52, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(13, 4, 53, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(14, 4, 54, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(15, 4, 55, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(16, 4, 56, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(17, 4, 57, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(18, 4, 58, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(19, 4, 59, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(20, 4, 60, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(21, 4, 61, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(22, 4, 62, NULL, '[BROADCAST]: Hello All', 0, '2026-02-16 15:41:05', '2023/2024'),
(23, 4, 5, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(24, 4, 6, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(25, 4, 7, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(26, 4, 8, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(27, 4, 9, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(28, 4, 10, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(29, 4, 11, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(30, 4, 12, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(31, 4, 13, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(32, 4, 14, NULL, '[BROADCAST]: Ensure you submit Your project in time', 1, '2026-02-16 15:41:28', '2023/2024'),
(33, 4, 15, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(34, 4, 16, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(35, 4, 17, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(36, 4, 18, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(37, 4, 19, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(38, 4, 20, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(39, 4, 21, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(40, 4, 22, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(41, 4, 23, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(42, 4, 24, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(43, 4, 25, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(44, 4, 26, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(45, 4, 27, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(46, 4, 28, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(47, 4, 29, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(48, 4, 30, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(49, 4, 31, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(50, 4, 32, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(51, 4, 33, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(52, 4, 34, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(53, 4, 35, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(54, 4, 36, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(55, 4, 37, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(56, 4, 38, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(57, 4, 39, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(58, 4, 40, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(59, 4, 41, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(60, 4, 42, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(61, 4, 43, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(62, 4, 44, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(63, 4, 45, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(64, 4, 46, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(65, 4, 47, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(66, 4, 48, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(67, 4, 49, NULL, '[BROADCAST]: Ensure you submit Your project in time', 0, '2026-02-16 15:41:28', '2023/2024'),
(68, 51, 14, NULL, '[BROADCAST]: Hello Guys', 1, '2026-02-16 15:42:30', '2023/2024'),
(69, 51, 18, NULL, '[BROADCAST]: Hello Guys', 0, '2026-02-16 15:42:30', '2023/2024'),
(70, 51, 41, NULL, '[BROADCAST]: Hello Guys', 0, '2026-02-16 15:42:30', '2023/2024'),
(71, 51, 27, NULL, '[BROADCAST]: Hello Guys', 0, '2026-02-16 15:42:30', '2023/2024'),
(72, 4, 5, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(73, 4, 6, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(74, 4, 7, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(75, 4, 8, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(76, 4, 9, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(77, 4, 10, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(78, 4, 11, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(79, 4, 12, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(80, 4, 13, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(81, 4, 14, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 1, '2026-02-16 15:47:12', '2023/2024'),
(82, 4, 15, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(83, 4, 16, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(84, 4, 17, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(85, 4, 18, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(86, 4, 19, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(87, 4, 20, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(88, 4, 21, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(89, 4, 22, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(90, 4, 23, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(91, 4, 24, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(92, 4, 25, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(93, 4, 26, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(94, 4, 27, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(95, 4, 28, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(96, 4, 29, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(97, 4, 30, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(98, 4, 31, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(99, 4, 32, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(100, 4, 33, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(101, 4, 34, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(102, 4, 35, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(103, 4, 36, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(104, 4, 37, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(105, 4, 38, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(106, 4, 39, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(107, 4, 40, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(108, 4, 41, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(109, 4, 42, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(110, 4, 43, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(111, 4, 44, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(112, 4, 45, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(113, 4, 46, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(114, 4, 47, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(115, 4, 48, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(116, 4, 49, NULL, '[BROADCAST]: Hello U13CS students.\r\nEnsure you submit your projects', 0, '2026-02-16 15:47:12', '2023/2024'),
(117, 14, 4, NULL, 'Ok sir\r\nI have Upload problem', 1, '2026-02-16 15:48:05', '2023/2024');

-- --------------------------------------------------------

--
-- Table structure for table `panel_members`
--

CREATE TABLE `panel_members` (
  `id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `panel_members`
--

INSERT INTO `panel_members` (`id`, `panel_id`, `supervisor_id`) VALUES
(1, 1, 63);

-- --------------------------------------------------------

--
-- Table structure for table `past_projects`
--

CREATE TABLE `past_projects` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `reg_no` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `session` varchar(20) NOT NULL,
  `supervisor_name` varchar(50) NOT NULL,
  `pdf_path` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_topics`
--

CREATE TABLE `project_topics` (
  `id` int(11) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `session` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `pdf_path` varchar(255) DEFAULT NULL,
  `report_status` enum('pending','approved','rejected','not_submitted') DEFAULT 'not_submitted',
  `report_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_topics`
--

INSERT INTO `project_topics` (`id`, `topic`, `student_id`, `student_name`, `session`, `status`, `pdf_path`, `report_status`, `report_feedback`) VALUES
(1, 'Asset Management System', '14', 'ABDULAZIZ   Umar Abbas', '2025/2026', 'approved', NULL, 'not_submitted', NULL),
(2, 'Result Processing System', '14', 'ABDULAZIZ   Umar Abbas', '2025/2026', 'rejected', NULL, 'not_submitted', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(60) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `department` int(11) NOT NULL,
  `first_login` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `reg_no`, `name`, `phone`, `email`, `faculty_id`, `department`, `first_login`) VALUES
(5, 'FCP/CIT/18/1044', 'BELLO   Kabir Musa', '7068459403', '', 0, 1, 1),
(6, 'FCP/CIT/18/1045', 'KHALIL   Nabila Abdussalam', '7062230185', '', 0, 1, 1),
(7, 'FCP/CIT/19/1001', 'YAU   Abdulrauf', '7038507123', '', 0, 1, 1),
(8, 'FCP/CIT/19/1002', 'MUDASIR   Muzakir Alade', '9030759084', '', 0, 1, 1),
(9, 'FCP/CIT/19/1003', 'HUSSAINI   Ladidi Abdullahi', '8099696743', '', 0, 1, 1),
(10, 'FCP/CIT/19/1004', 'YARI   Bajju Markus', '7084836536', '', 0, 1, 1),
(11, 'FCP/CIT/19/1005', 'ADAMOLEKUN   John Enitan', '8168066715', '', 0, 1, 1),
(12, 'FCP/CIT/19/1006', 'JATTO   Ransom Agson', '9029639475', '', 0, 1, 1),
(13, 'FCP/CIT/19/1007', 'M.   Lawan Saddam', '7088515618', '', 0, 1, 1),
(14, 'FCP/CIT/19/1008', 'ABDULAZIZ   Umar Abbas', '8063612678', '', 0, 1, 0),
(15, 'FCP/CIT/19/1009', 'NDANDOK   Jotham', '9014580620', '', 0, 1, 1),
(16, 'FCP/CIT/19/1010', 'KABIR   Fatima Mohammad', '8149932460', '', 0, 1, 1),
(17, 'FCP/CIT/19/1011', 'MUHAMMAD   Akilu Wakil', '8081457879', '', 0, 1, 1),
(18, 'FCP/CIT/19/1012', 'ABDULLAHI   Ahmed Samaila', '9075092883', '', 0, 1, 0),
(19, 'FCP/CIT/19/1013', 'FRIDAY   Adakole Ejembi', '9027367289', '', 0, 1, 1),
(20, 'FCP/CIT/19/1014', 'NURAYN   Abdulqudus Olawuyi', '8126064823', '', 0, 1, 1),
(21, 'FCP/CIT/19/1015', 'MUSA   Abas', '8133349445', '', 0, 1, 1),
(22, 'FCP/CIT/19/1027', 'BATURE   Usman', '7069362725', '', 0, 1, 1),
(23, 'FCP/CIT/19/1030', 'ABDULLAHI   Aminu Abba', '7067693074', '', 0, 1, 1),
(24, 'FCP/CIT/19/1016', 'ANYANASO   Chigozie Emmanuella', '7033995117', '', 0, 1, 1),
(25, 'FCP/CIT/19/1017', 'MUSA   Ahmad Silwan', '9063180094', '', 0, 1, 1),
(26, 'FCP/CIT/19/1018', 'ABUBAKAR   Salim', '8101103738', '', 0, 1, 1),
(27, 'FCP/CIT/19/1019', 'ALI   Yakub', '7030103739', '', 0, 1, 1),
(28, 'FSC/MTH/19/1088', 'BADAMASI   Sulaiman', '7036094261', '', 0, 1, 1),
(29, 'FCP/CIT/19/1026', 'IDRIS   Aminu', '8135193155', '', 0, 1, 1),
(30, 'FCP/CIT/19/1028', 'YUSUF   Fatima Chedi', '8186963377', '', 0, 1, 1),
(31, 'FCP/CIT/19/1020', 'ALIYU   Musa Ismail', '8061619173', '', 0, 1, 1),
(32, 'FCP/CIT/19/1021', 'SAKARIYAH   Yahya Olamilekan', '8184740274', '', 0, 1, 1),
(33, 'FCP/CIT/20/2001', 'DAHIRU   Abdulhamid', '9079044499', '', 0, 1, 1),
(34, 'FCP/CIT/20/2002', 'IBRAHIM   Kadariya Abdullahi', '8137814937', '', 0, 1, 1),
(35, 'FCP/CIT/20/2003', 'UMAR   Husaina', '8107979643', '', 0, 1, 1),
(36, 'FCP/CIT/20/2004', 'YUSUF   Mustapha Garkuwa', '8064620406', '', 0, 1, 1),
(37, 'FCP/CIT/20/2005', 'BELLO   Abdullahi', '8143482908', '', 0, 1, 1),
(38, 'FCP/CIT/20/2006', 'NAFIU   Muhammad Abubakar', '8163905031', '', 0, 1, 1),
(39, 'FCP/CIT/20/2007', 'UMAR   Auwal Alhassan', '8167882538', '', 0, 1, 1),
(40, 'FCP/CIT/20/2008', 'FAROUK   Hauwa Ibrahim', '8065104047', '', 0, 1, 1),
(41, 'FCP/CIT/20/2009', 'ADAMU   Aliyu Danhassan', '9033831127', '', 0, 1, 1),
(42, 'FCP/CIT/20/2010', 'TIJJANI   Isah', '7067371791', '', 0, 1, 1),
(43, 'FCP/CIT/20/2011', 'MUSA   Ummisalma Bako', '7046026796', '', 0, 1, 1),
(44, 'FCP/CIT/20/2012', 'SANI   Usman', '7066252332', '', 0, 1, 1),
(45, 'FCP/CIT/20/2013', 'ABDULLAHI   Idris', '8134369317', '', 0, 1, 1),
(46, 'FCP/CIT/20/2014', 'ADAMU   Ali Baba', '8168595941', '', 0, 1, 1),
(47, 'FCP/CIT/20/2015', 'ADAMU   Muhammad Yusuf', '8080788908', '', 0, 1, 1),
(48, 'FCP/CIT/20/2016', 'SALE   Ubah Rimi', '8060909090', '', 0, 1, 1),
(49, 'FCP/CIT/20/2017', 'UMAR   Musa Tanimu', '8069062759', '', 0, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_panel_assignments`
--

CREATE TABLE `student_panel_assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `panel_type` enum('proposal','internal','external') NOT NULL DEFAULT 'proposal',
  `academic_session` varchar(20) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_panel_assignments`
--

INSERT INTO `student_panel_assignments` (`id`, `student_id`, `panel_id`, `panel_type`, `academic_session`, `assigned_at`) VALUES
(1, 5, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(2, 6, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(3, 7, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(4, 8, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(5, 9, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(6, 10, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(7, 11, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(8, 12, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(9, 13, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(10, 14, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(11, 15, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(12, 16, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(13, 17, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(14, 18, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(15, 19, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(16, 20, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(17, 21, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(18, 22, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(19, 23, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(20, 24, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(21, 25, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(22, 26, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(23, 27, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(24, 28, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(25, 29, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(26, 30, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(27, 31, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(28, 32, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(29, 33, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(30, 34, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(31, 35, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(32, 36, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(33, 37, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(34, 38, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(35, 39, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(36, 40, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(37, 41, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(38, 42, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(39, 43, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(40, 44, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(41, 45, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(42, 46, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(43, 47, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(44, 48, 1, 'external', '2025/2026', '2026-02-16 15:59:21'),
(45, 49, 1, 'external', '2025/2026', '2026-02-16 15:59:21');

-- --------------------------------------------------------

--
-- Table structure for table `student_submission_overrides`
--

CREATE TABLE `student_submission_overrides` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submission_start` datetime NOT NULL,
  `submission_end` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_submission_overrides`
--

INSERT INTO `student_submission_overrides` (`id`, `student_id`, `submission_start`, `submission_end`, `is_active`) VALUES
(1, 18, '2026-02-09 22:06:00', '2026-02-12 20:06:00', 1),
(2, 14, '2026-02-09 21:07:00', '2026-02-10 21:07:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `submission_schedules`
--

CREATE TABLE `submission_schedules` (
  `id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `submission_start` datetime DEFAULT NULL,
  `submission_end` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supervision`
--

CREATE TABLE `supervision` (
  `allocation_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `allocation_date` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervision`
--

INSERT INTO `supervision` (`allocation_id`, `supervisor_id`, `student_id`, `allocation_date`, `status`) VALUES
(1, 51, 14, '2026-02-08 13:03:57', 'active'),
(2, 51, 18, '2026-02-08 13:03:57', 'active'),
(3, 55, 23, '2026-02-08 13:03:57', 'active'),
(4, 52, 45, '2026-02-08 13:03:57', 'active'),
(5, 57, 26, '2026-02-08 13:03:57', 'active'),
(6, 58, 11, '2026-02-08 13:03:57', 'active'),
(7, 58, 46, '2026-02-08 13:03:57', 'active'),
(8, 51, 41, '2026-02-08 13:03:57', 'active'),
(9, 57, 47, '2026-02-08 13:03:57', 'active'),
(10, 51, 27, '2026-02-08 13:03:57', 'active'),
(11, 52, 31, '2026-02-08 13:03:57', 'active'),
(12, 60, 24, '2026-02-08 13:03:57', 'active'),
(13, 60, 28, '2026-02-08 13:03:57', 'active'),
(14, 57, 22, '2026-02-08 13:03:57', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `supervisors`
--

CREATE TABLE `supervisors` (
  `id` int(11) NOT NULL,
  `staff_no` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `faculty_id` int(11) NOT NULL,
  `department` varchar(100) NOT NULL,
  `max_students` int(11) NOT NULL DEFAULT 10,
  `current_load` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisors`
--

INSERT INTO `supervisors` (`id`, `staff_no`, `name`, `email`, `phone`, `faculty_id`, `department`, `max_students`, `current_load`) VALUES
(50, 'SP/R/1011', 'PROF. A. M. MIYIM', '', NULL, 0, '1', 0, 0),
(51, 'SP/R/1012', 'DR. SALIM AHMAD', 'salim@fud.edu.ng', '08065254574', 0, '1', 4, 4),
(52, 'SP/R/1013', 'DR. SULEIMAN IBRAHIM', '', NULL, 0, '1', 4, 2),
(53, 'SP/R/1014', 'DR. ISMA\'IL  SUNUSI', 'BASHIRU@fud.edu.ng', '08034398046', 0, '1', 0, 0),
(54, 'SP/R/1015', 'MAL. AHMAD SANI KAZAURE', '', NULL, 0, '1', 0, 0),
(55, 'SP/R/1016', 'MAL. ABDUSSALAM ABBA TUKUR', '', NULL, 0, '1', 4, 1),
(56, 'SP/R/1017', 'MAL. AMINU ABUBAKAR MUHAMMAD', '', NULL, 0, '1', 10, 0),
(57, 'SP/R/1018', 'MAL. AHMAD ABDULLAHI IBRAHIM', '', NULL, 0, '1', 5, 3),
(58, 'SP/R/1019', 'MAL. YAKUBU MUHAMMAD', '', NULL, 0, '1', 3, 2),
(59, 'SP/R/1020', 'MALAMA FA\'IZATU IDRIS', '', NULL, 0, '1', 0, 0),
(60, 'SP/R/1021', 'MAL. ABDURRAHMAN H. GALADIMA', '', NULL, 0, '1', 3, 2),
(61, 'SP/R/1022', 'MAL. ABBA DAUDA', '', NULL, 0, '1', 0, 0),
(62, 'SP/R/1023', 'MALAMA MARYAM SALISU', '', NULL, 0, '1', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_assessments`
--

CREATE TABLE `supervisor_assessments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `academic_session` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES
('allow_student_topic_edit', '1', NULL, '2026-02-07 06:25:24'),
('current_session', '2025/2026', NULL, '2026-02-07 06:25:24'),
('max_proposals_per_student', '3', NULL, '2026-02-07 06:25:24'),
('similarity_threshold', '30', NULL, '2026-02-07 06:25:24'),
('system_announcement', '', NULL, '2026-02-07 06:25:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('stu','dpc','fpc','sup','ext') NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `faculty_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `first_login` datetime DEFAULT NULL,
  `password_changed` varchar(50) DEFAULT NULL,
  `session` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `email`, `faculty_id`, `department`, `is_active`, `first_login`, `password_changed`, `session`, `created_at`) VALUES
(1, 'superadmin', '$2y$10$5/Eo4JyrmJO6vsn550E.YOVmUvymNJ1VwTp9QTMMSLP1VaI1JqTTW', '', 'Super Administrator', 'super@admin', 1, '1', 1, '2026-02-08 12:49:41', NULL, NULL, '2026-02-08 11:49:55'),
(2, 'fpc1', '$2y$10$/wa7vu7v2lZHUptkhl15/uSFjTipvS2q453RrJIFbHcXTb.01bl6K', 'fpc', 'Computing', 'computing@fud.edu.ng', 1, NULL, 1, '2026-02-08 12:50:01', NULL, NULL, '2026-02-08 11:50:16'),
(4, 'dpc1', '$2y$10$MP6QyO3UbM7IKzjf/Cp3K.TzxjoST/nYbc3pvIUXk03qZ3B1JUbUG', 'dpc', 'Compter Science', 'comuter@fud.edu.ng', 1, '1', 1, '2026-02-17 10:13:30', NULL, 'vmkamip629i4mb5dorcda4b5rh', '2026-02-17 09:13:30'),
(5, 'FCP/CIT/18/1044', '$2y$10$xx9HnceUvGQsyS2V0QDX7Or0y5/3LcHHJRSV73WsSSQm8ErtGyei2', 'stu', 'BELLO   Kabir Musa', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:29'),
(6, 'FCP/CIT/18/1045', '$2y$10$xCjuTHtIkCqbz74OBue6yeEthIljL9BADb8zMSBUJPtrU1GKysfOG', 'stu', 'KHALIL   Nabila Abdussalam', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:29'),
(7, 'FCP/CIT/19/1001', '$2y$10$jXmUybpAnWV5EpHHzY2fiueBXnlzDBR4s4H1nFJuJAKe0n2kbX.SC', 'stu', 'YAU   Abdulrauf', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:29'),
(8, 'FCP/CIT/19/1002', '$2y$10$hz32TyGnkoM2C3ROXU2bOOToy98UbrTsK5eXx2WLFPqqSMvSgElEm', 'stu', 'MUDASIR   Muzakir Alade', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:29'),
(9, 'FCP/CIT/19/1003', '$2y$10$8RBGJ/E5UPCmM.CC/zjcqeJRVhufcspyaW43mQbEtPtKGFieOugq6', 'stu', 'HUSSAINI   Ladidi Abdullahi', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:30'),
(10, 'FCP/CIT/19/1004', '$2y$10$BKHuUjFBdXh8i2BUHfz1WOuwOOqfqvC0U2yqihYpHcs5BAIOFvUf6', 'stu', 'YARI   Bajju Markus', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:30'),
(11, 'FCP/CIT/19/1005', '$2y$10$Q5ZvcmQVzsKZbP4waGiUheyHWHTUlFIvoWp8hi/wzMPRYGdxMQSu.', 'stu', 'ADAMOLEKUN   John Enitan', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:30'),
(12, 'FCP/CIT/19/1006', '$2y$10$OC8Eu5eZFfpc4RoHpImgduIRysfxXki7UCrqbUmWoiWDFE3kNSqp.', 'stu', 'JATTO   Ransom Agson', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:30'),
(13, 'FCP/CIT/19/1007', '$2y$10$iAOMkyEKqVDNhQFsBLahJegNftCOB4Yb.spi73.YhMeRtp8fioO.q', 'stu', 'M.   Lawan Saddam', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:30'),
(14, 'FCP/CIT/19/1008', '$2y$10$MeZ1dFUcaAW6mVtWf/bn/uQuLSaXpHyUigMIwB0ePRdpj6eLER8g6', 'stu', 'ABDULAZIZ   Umar Abbas', '', 0, '1', 1, '2026-02-16 16:59:39', NULL, NULL, '2026-02-16 16:00:05'),
(15, 'FCP/CIT/19/1009', '$2y$10$6DqKBJ6lZLh.w0faio5.9uiwKYEaUuh1G1Bq6ukpT4jJSx7rJwXvy', 'stu', 'NDANDOK   Jotham', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:31'),
(16, 'FCP/CIT/19/1010', '$2y$10$5wu6fjOhQ9a9LYFX3xm1pOi5ZnkPed9ERJlbHRZ9BPJN8hcNxeaGu', 'stu', 'KABIR   Fatima Mohammad', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:31'),
(17, 'FCP/CIT/19/1011', '$2y$10$9vw7t3Nxd1IOMn8Byo9I/ODwsgR43L0N/s/ja2tHI1PHTXCNU7KoK', 'stu', 'MUHAMMAD   Akilu Wakil', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:31'),
(18, 'FCP/CIT/19/1012', '$2y$10$v/4Z.UjyjzhOneQFvbzqo.VijyrS8nUU6lKbRGapeGI3A5CrU86Cq', 'stu', 'ABDULLAHI   Ahmed Samaila', '', 0, '1', 1, '2026-02-09 20:33:55', NULL, NULL, '2026-02-09 20:04:26'),
(19, 'FCP/CIT/19/1013', '$2y$10$WPChunXKzwHuKIge69FqDeS7q/U0oHDGl9zyNRcaruhCp0Nt/ODmy', 'stu', 'FRIDAY   Adakole Ejembi', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:31'),
(20, 'FCP/CIT/19/1014', '$2y$10$ljwk1Pbd0sPJmKEHKwNbPuM.jOPGYNxWRlkQ10OIVePVe0EhgG7e6', 'stu', 'NURAYN   Abdulqudus Olawuyi', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:32'),
(21, 'FCP/CIT/19/1015', '$2y$10$sAuTKV3U73AKYFcgDAEd.e630w0fCbqeg4sTw3HpMBrOHhCFZN/5W', 'stu', 'MUSA   Abas', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:32'),
(22, 'FCP/CIT/19/1027', '$2y$10$0milNG0oisiQNI6jJbuUiuXhA0yvmAtGjDtTZs5zVmzVSg3PvhGT.', 'stu', 'BATURE   Usman', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:32'),
(23, 'FCP/CIT/19/1030', '$2y$10$ea.phgP5XEXB40i35Lhu3uQ5WfIJlEL3BQ3xDi0pMra1rWNtgzkEW', 'stu', 'ABDULLAHI   Aminu Abba', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:32'),
(24, 'FCP/CIT/19/1016', '$2y$10$48mQMpGboD.Jwvjx8KeaTupzKiWnNx5.0lF1MDHxUs0BXmJgjgTLa', 'stu', 'ANYANASO   Chigozie Emmanuella', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:32'),
(25, 'FCP/CIT/19/1017', '$2y$10$wjgUekHSt3gzfuraSQTyEu1xf5bDfErInIZj9Mc4reUCZcP7Uo1Gy', 'stu', 'MUSA   Ahmad Silwan', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:32'),
(26, 'FCP/CIT/19/1018', '$2y$10$IBvHOUVZNmX5CC/h5AkIpOKT2QpFalJ8g.tncQvgrI.J16RBvrriW', 'stu', 'ABUBAKAR   Salim', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:32'),
(27, 'FCP/CIT/19/1019', '$2y$10$4iwHlEn0BVzXKfZCBdaoNeaanTXuLy601tDeSXBUoYH0Huu8OFzWi', 'stu', 'ALI   Yakub', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:32'),
(28, 'FSC/MTH/19/1088', '$2y$10$dvf1cQc12PPVISew8rw3r.E6U13J8Y558Klh9ImAq/3qvukYBw6Vy', 'stu', 'BADAMASI   Sulaiman', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:33'),
(29, 'FCP/CIT/19/1026', '$2y$10$TU3PCsyBsKYnjDxu8Dd9Q.taO9v6iLf8GRGaAi8QeZ2G64ExAXXO.', 'stu', 'IDRIS   Aminu', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:33'),
(30, 'FCP/CIT/19/1028', '$2y$10$IwPCFyD4YT9knGK.jl2gfeyXq.WtrFQFYMnI9rTGQ6tDK329mBfFW', 'stu', 'YUSUF   Fatima Chedi', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:33'),
(31, 'FCP/CIT/19/1020', '$2y$10$mr9ZLop6H7u169G6jcMSFe..ZhWX2jlW.2iv3RGuEOdtVZNDEqyzq', 'stu', 'ALIYU   Musa Ismail', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:33'),
(32, 'FCP/CIT/19/1021', '$2y$10$dSUFOmAHZJsrqvtwsM5T/.PhcJk6AdJXFxxA0KJwJElip.mU5Y51C', 'stu', 'SAKARIYAH   Yahya Olamilekan', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:33'),
(33, 'FCP/CIT/20/2001', '$2y$10$zTdbtfmFwTG4uK6Qg1i4ueEeWVWWIMyRRg2zbKOH5J6jD5/uKFHda', 'stu', 'DAHIRU   Abdulhamid', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:33'),
(34, 'FCP/CIT/20/2002', '$2y$10$FCSrRHWsdWUTJeRvE4WoY.MPO0Zs6hREAZdM.VLqcxjh9O0RnjKI6', 'stu', 'IBRAHIM   Kadariya Abdullahi', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(35, 'FCP/CIT/20/2003', '$2y$10$Fw3CnNIF3Uir1ibZHjqd9uqGps0pb8o38E3GtBNrCipeck4P7qSDW', 'stu', 'UMAR   Husaina', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(36, 'FCP/CIT/20/2004', '$2y$10$HlFOAx95D/MErLQ4ZaEP7O6IQh/ta8ts49tpUT39t1bc.zUoWcCpK', 'stu', 'YUSUF   Mustapha Garkuwa', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(37, 'FCP/CIT/20/2005', '$2y$10$zo0qtfKIbFtJg78lBgns5.6DRO8z62K7BBRQz1jlM9K6Tbiz1JKlu', 'stu', 'BELLO   Abdullahi', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(38, 'FCP/CIT/20/2006', '$2y$10$eHVoBNHdEf/DpLmeV0vx5uJHQjcuDfyGFIttsgmugeUSCjZ5edR3i', 'stu', 'NAFIU   Muhammad Abubakar', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(39, 'FCP/CIT/20/2007', '$2y$10$DcUu.f2KfcEc27dNjFZEC.zqPRv7KMn7cZzVs/yurRpvoM3hgs43q', 'stu', 'UMAR   Auwal Alhassan', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(40, 'FCP/CIT/20/2008', '$2y$10$5kGE6ozmeCaKFbNvvQ110uZypsydpUHQaEzYOUbiXstK2tiQVy/OK', 'stu', 'FAROUK   Hauwa Ibrahim', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(41, 'FCP/CIT/20/2009', '$2y$10$wGwKQQ.Mm8ywZHBkINpBwuLrGWeppVpx5n6rRxCUjOnZGnTXP.2xm', 'stu', 'ADAMU   Aliyu Danhassan', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(42, 'FCP/CIT/20/2010', '$2y$10$T0wCWb5yZ3IwwvbcRRfRT.MCqubAuH700C/JWxq1uMK5LOy.EkEQy', 'stu', 'TIJJANI   Isah', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(43, 'FCP/CIT/20/2011', '$2y$10$PJaTWWSSK67GvHKEKFafT.01HiZxfyfqWjjRmwwRHIMj8U6Se1Wa2', 'stu', 'MUSA   Ummisalma Bako', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:34'),
(44, 'FCP/CIT/20/2012', '$2y$10$PYP7NKbaua3owZkzjXCKHe6GJe7L.nomExsIlOdPIN.OhDX9XQLgG', 'stu', 'SANI   Usman', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:35'),
(45, 'FCP/CIT/20/2013', '$2y$10$uo1W7Jisfwz2gcJ67Bd5LONqAraSGnefyHmpYJkduOKwKL/zfYzJu', 'stu', 'ABDULLAHI   Idris', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:35'),
(46, 'FCP/CIT/20/2014', '$2y$10$qVlkKc85xW9ee5YXBNudgemUQWY9YAdtgyhFAvS5Z6JTV7mj09RMS', 'stu', 'ADAMU   Ali Baba', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:35'),
(47, 'FCP/CIT/20/2015', '$2y$10$uj.eGHobBhc1WFqh/tdDpuPsl1jTJzVpy8lvePON8oRQZ4KbgMP4u', 'stu', 'ADAMU   Muhammad Yusuf', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:35'),
(48, 'FCP/CIT/20/2016', '$2y$10$OBUTO5LYvK65FNMOt6qz3eLouPn6Vw1/ZPoDCdt.K.DCnPa69EGfm', 'stu', 'SALE   Ubah Rimi', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:35'),
(49, 'FCP/CIT/20/2017', '$2y$10$o3hzKAFbS.J2DNfIiefZaOr4iPXl.XYurUurbXOM//CwntEvy4Hg6', 'stu', 'UMAR   Musa Tanimu', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:57:35'),
(50, 'SP/R/1011', '$2y$10$F9om45CuLy0zqUuX3zj5WOuBR3LGFjqymYDpG8/K9UR9uq7JBE7Mq', 'sup', 'PROF. A. M. MIYIM', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:15'),
(51, 'SP/R/1012', '$2y$10$aYAuxoL34HChVbzhuKciru9mV4Z1xWOr4in6i9owJXpMjIdn/fv/2', 'sup', 'DR. SALIM AHMAD', 'salim@fud.edu.ng', 0, '1', 1, '2026-02-16 16:41:46', NULL, NULL, '2026-02-16 15:42:59'),
(52, 'SP/R/1013', '$2y$10$gxbaqnv3OiT2DNdWWPLLKO7JTo1PCe0WfX0LPd0dwRm92jSqq5a2G', 'sup', 'DR. SULEIMAN IBRAHIM', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:15'),
(53, 'SP/R/1014', '$2y$10$bE7ylVwWPmq9b2UBVydfnexapnPFqaRpFCRCcuHGmFF289lic/t8K', 'sup', 'DR. ISMA\'IL  SUNUSI', 'BASHIRU@fud.edu.ng', 0, '1', 1, '2026-02-16 10:48:34', NULL, NULL, '2026-02-16 10:24:34'),
(54, 'SP/R/1015', '$2y$10$CrkjC6lolNFE8qGCVGTMZety9pKJ/LjG6VsZeqPZ/pIy09P4XhfBS', 'sup', 'MAL. AHMAD SANI KAZAURE', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:15'),
(55, 'SP/R/1016', '$2y$10$LSstYTX2PjnrThx2P0j.AOGTqmv1Bo3FdBqNg3QBjCEc24J4raznG', 'sup', 'MAL. ABDUSSALAM ABBA TUKUR', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:15'),
(56, 'SP/R/1017', '$2y$10$AfF9cJkByvM3fSTxvd9d2OJrBL3fOr8dnjgE4xOuAl9Y1eCWyl5kS', 'sup', 'MAL. AMINU ABUBAKAR MUHAMMAD', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:15'),
(57, 'SP/R/1018', '$2y$10$yWvlBXj6HG0qdE.M0IMC3O4tyspNw1Q8j/UxU7RZHrm7R.tqiGEqe', 'sup', 'MAL. AHMAD ABDULLAHI IBRAHIM', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:16'),
(58, 'SP/R/1019', '$2y$10$ZDjaW5.NmmeBVyZWKbrap.3o00FLMlg7ucbNNzp7p/VzLZN92Ejsa', 'sup', 'MAL. YAKUBU MUHAMMAD', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:16'),
(59, 'SP/R/1020', '$2y$10$JLG6MhC5gEoCQlpHNhuTUu12EN455xY3EhV.OcvrNepda9PDDFDIS', 'sup', 'MALAMA FA\'IZATU IDRIS', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:16'),
(60, 'SP/R/1021', '$2y$10$bCSUV1jwZ5pTV6oq4L2gAuyDLUumf73qPBTvi.AFjf4GdLsn2BgwK', 'sup', 'MAL. ABDURRAHMAN H. GALADIMA', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:16'),
(61, 'SP/R/1022', '$2y$10$zboOgTFgz4MmsFtoz95jTORgd5xuoVhdK67v/qyFRgVkTwq0ey71m', 'sup', 'MAL. ABBA DAUDA', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:16'),
(62, 'SP/R/1023', '$2y$10$JMjViLyJSRYpj5CeyeV6Iu51ctYpC8QlkQzWhJKQEGYHESyfQCAdC', 'sup', 'MALAMA MARYAM SALISU', '', 0, '1', 1, NULL, NULL, NULL, '2026-02-08 11:58:16'),
(63, 'gital', '$2y$10$hreTotB8Pn4wOpTe4UeJnuPz6ri6dsiIhU6AaazCDmyRldy0jJ0mC', 'ext', 'Prof Gital', 'gital@atbu.edu.ng', 0, '1', 1, '2026-02-16 18:07:52', NULL, NULL, '2026-02-16 17:08:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chapter_approvals`
--
ALTER TABLE `chapter_approvals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_chapter` (`student_id`,`chapter_number`);

--
-- Indexes for table `defense_panels`
--
ALTER TABLE `defense_panels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `defense_scores`
--
ALTER TABLE `defense_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_sup_panel` (`student_id`,`supervisor_id`,`panel_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `external_examiners`
--
ALTER TABLE `external_examiners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_temp_access`
--
ALTER TABLE `library_temp_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sender` (`sender_id`),
  ADD KEY `fk_receiver` (`receiver_id`);

--
-- Indexes for table `panel_members`
--
ALTER TABLE `panel_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `panel_id` (`panel_id`);

--
-- Indexes for table `past_projects`
--
ALTER TABLE `past_projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_topics`
--
ALTER TABLE `project_topics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reg_no` (`reg_no`);

--
-- Indexes for table `student_panel_assignments`
--
ALTER TABLE `student_panel_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_panel_type` (`student_id`,`academic_session`,`panel_type`),
  ADD KEY `panel_id` (`panel_id`);

--
-- Indexes for table `student_submission_overrides`
--
ALTER TABLE `student_submission_overrides`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `submission_schedules`
--
ALTER TABLE `submission_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_id` (`department_id`);

--
-- Indexes for table `supervision`
--
ALTER TABLE `supervision`
  ADD PRIMARY KEY (`allocation_id`),
  ADD KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_no` (`staff_no`);

--
-- Indexes for table `supervisor_assessments`
--
ALTER TABLE `supervisor_assessments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_sup_session` (`student_id`,`supervisor_id`,`academic_session`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chapter_approvals`
--
ALTER TABLE `chapter_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `defense_panels`
--
ALTER TABLE `defense_panels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `defense_scores`
--
ALTER TABLE `defense_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_temp_access`
--
ALTER TABLE `library_temp_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `panel_members`
--
ALTER TABLE `panel_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `past_projects`
--
ALTER TABLE `past_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_topics`
--
ALTER TABLE `project_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `student_panel_assignments`
--
ALTER TABLE `student_panel_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `student_submission_overrides`
--
ALTER TABLE `student_submission_overrides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `submission_schedules`
--
ALTER TABLE `submission_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supervision`
--
ALTER TABLE `supervision`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `supervisor_assessments`
--
ALTER TABLE `supervisor_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `external_examiners`
--
ALTER TABLE `external_examiners`
  ADD CONSTRAINT `external_examiners_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `panel_members`
--
ALTER TABLE `panel_members`
  ADD CONSTRAINT `panel_members_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `defense_panels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_panel_assignments`
--
ALTER TABLE `student_panel_assignments`
  ADD CONSTRAINT `student_panel_assignments_ibfk_1` FOREIGN KEY (`panel_id`) REFERENCES `defense_panels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submission_schedules`
--
ALTER TABLE `submission_schedules`
  ADD CONSTRAINT `submission_schedules_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `supervision`
--
ALTER TABLE `supervision`
  ADD CONSTRAINT `supervision_ibfk_1` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisors` (`id`),
  ADD CONSTRAINT `supervision_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
