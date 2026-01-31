-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 31, 2026 at 02:06 PM
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
-- Table structure for table `defense_panels`
--

CREATE TABLE `defense_panels` (
  `id` int(11) NOT NULL,
  `panel_name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `max_students` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `defense_panels`
--

INSERT INTO `defense_panels` (`id`, `panel_name`, `department_id`, `max_students`, `created_at`) VALUES
(1, 'Panel 1', 1, 7, '2026-01-29 06:18:25'),
(2, 'Panel 2', 1, 5, '2026-01-29 07:39:51');

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
(1, 4, 24, 1, 10.00, '', '2026-01-31 11:34:26'),
(2, 5, 24, 1, 5.00, '', '2026-01-31 11:34:32'),
(3, 7, 24, 1, 15.00, '', '2026-01-31 11:34:39'),
(4, 16, 24, 1, 20.00, '', '2026-01-31 11:34:46'),
(5, 14, 25, 2, 10.00, '', '2026-01-31 11:42:55'),
(6, 15, 25, 2, 20.00, '', '2026-01-31 11:43:02'),
(7, 17, 25, 2, 5.00, '', '2026-01-31 11:43:08'),
(8, 19, 25, 2, 8.00, '', '2026-01-31 11:43:14'),
(9, 23, 25, 2, 2.00, '', '2026-01-31 11:43:20'),
(10, 4, 25, 1, 10.00, '', '2026-01-31 11:43:30'),
(11, 5, 25, 1, 15.00, '', '2026-01-31 11:43:38'),
(12, 7, 25, 1, 25.00, '', '2026-01-31 11:43:46'),
(13, 16, 25, 1, 23.00, '', '2026-01-31 11:43:54');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `faculty_id`, `department_name`) VALUES
(1, 1, 'Computer Science'),
(2, 1, 'Cyber Security');

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

--
-- Dumping data for table `library_temp_access`
--

INSERT INTO `library_temp_access` (`id`, `username`, `passcode`, `created_by`, `created_at`, `is_used`, `used_at`) VALUES
(4, 'GUEST527958', '9832A048', 26, '2026-01-28 22:11:48', 0, NULL),
(5, 'GUEST888373', '95A3760C', 26, '2026-01-28 22:11:48', 0, NULL),
(6, 'GUEST380751', 'B3EF652F', 26, '2026-01-28 22:11:48', 0, NULL),
(7, 'GUEST374143', '95C5D4E9', 26, '2026-01-28 22:11:48', 0, NULL),
(8, 'GUEST306533', 'B0D763DF', 26, '2026-01-28 22:11:48', 0, NULL),
(9, 'GUEST979629', '7559FED8', 26, '2026-01-28 22:11:48', 0, NULL),
(10, 'GUEST813568', '5FC048DA', 26, '2026-01-28 22:11:48', 0, NULL),
(11, 'GUEST840870', '4BAD35BA', 26, '2026-01-28 22:11:48', 0, NULL),
(12, 'GUEST125042', 'BCC1EB21', 26, '2026-01-28 22:11:48', 0, NULL),
(13, 'GUEST776688', '09EED9CB', 26, '2026-01-28 22:11:48', 0, NULL);

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
(10, 2, 25),
(11, 1, 24),
(12, 1, 25);

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

--
-- Dumping data for table `past_projects`
--

INSERT INTO `past_projects` (`id`, `faculty_id`, `topic`, `reg_no`, `student_name`, `session`, `supervisor_name`, `pdf_path`) VALUES
(1, 1, 'Machine Learning for Fraud Detection', 'FCP/CSC/18/101', 'Amina Yusuf', '2018/2019', 'Dr. Bello Ahmed', ''),
(2, 1, 'Data Mining in E-Commerce', 'FCP/CSC/18/102', 'John Okeke', '2018/2019', 'Prof. Grace Obi', ''),
(3, 1, 'Cloud Computing Security Models', 'FCP/CSC/18/103', 'Fatima Musa', '2018/2019', 'Dr. Ibrahim Lawal', ''),
(4, 1, 'Artificial Intelligence in Healthcare', 'FCP/CSC/18/104', 'Michael James', '2018/2019', 'Dr. Hauwa Ali', ''),
(5, 1, 'Blockchain Applications in Education', 'FCP/CSC/18/105', 'Zainab Umar', '2018/2019', 'Prof. Musa Abdullahi', ''),
(6, 1, 'Natural Language Processing for Chatbots', 'FCP/CSC/18/106', 'David Samuel', '2018/2019', 'Dr. Aisha Mohammed', ''),
(7, 1, 'Cybersecurity Threat Detection Systems', 'FCP/CSC/18/107', 'Maryam Ibrahim', '2018/2019', 'Dr. Kabir Hassan', ''),
(8, 1, 'IoT Solutions for Smart Homes', 'FCP/CSC/18/108', 'Emeka Nwosu', '2018/2019', 'Prof. Janet Okoro', 'assets/uploads/past_projects/project_8_1769637635.pdf'),
(9, 1, 'Big Data Analytics in Business Intelligence', 'FCP/CSC/18/109', 'Hassan Abdulkareem', '2018/2019', 'Dr. Suleiman Adamu', 'assets/uploads/past_projects/project_9_1769637606.pdf'),
(10, 1, 'Virtual Reality in Education Platforms', 'FCP/CSC/18/110', 'Joy Eze', '2018/2019', 'Dr. Peter Adeyemi', 'assets/uploads/past_projects/project_10_1769637582.pdf');

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
  `pdf_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_topics`
--

INSERT INTO `project_topics` (`id`, `topic`, `student_id`, `student_name`, `session`, `status`, `pdf_path`) VALUES
(1, 'Asset Management System', '23', 'Ahmed Usman', '2025/2026', 'rejected', NULL),
(2, 'Result Processing System', '23', 'Ahmed Usman', '2025/2026', 'approved', NULL),
(3, 'IoT in Agriculture', '16', 'Aisha Mohammed', '2025/2026', 'approved', NULL),
(4, 'Virtual Reality in Education', '16', 'Aisha Mohammed', '2025/2026', 'rejected', NULL),
(5, 'Result Processing System', '13', 'Chika Nnamdi', '2025/2026', 'rejected', NULL),
(6, 'Asset Allocation system', '13', 'Chika Nnamdi', '2025/2026', 'rejected', NULL),
(7, 'Result Evaluation system', '13', 'Chika Nnamdi', '2025/2026', 'approved', NULL),
(8, 'Smart Cities Development', '11', 'Joy Eze', '2025/2026', 'pending', NULL),
(9, 'Robotics in Manufacturing', '11', 'Joy Eze', '2025/2026', 'pending', NULL),
(10, '\'E-Government Services', '11', 'Joy Eze', '2025/2026', 'pending', NULL);

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
(4, 'FCP/CCS/21/1001', 'Aliyu Musa', '8011112222', 'aliyu.musa@example.com', 0, 1, 1),
(5, 'FCP/CCS/21/1002', 'Zainab Abdullahi', '8022223333', 'zainab.abdullahi@example.com', 0, 1, 1),
(6, 'FCP/CCS/21/1003', 'Emeka Okafor', '8033334444', 'emeka.okafor@example.com', 0, 1, 1),
(7, 'FCP/CCS/21/1004', 'Maryam Sani', '8044445555', 'maryam.sani@example.com', 0, 1, 1),
(8, 'FCP/CCS/21/1005', 'David Johnson', '8055556666', 'david.johnson@example.com', 0, 1, 1),
(9, 'FCP/CCS/21/1006', 'Halima Yusuf', '8066667777', 'halima.yusuf@example.com', 0, 1, 1),
(10, 'FCP/CCS/21/1007', 'Samuel Adewale', '8077778888', 'samuel.adewale@example.com', 0, 1, 1),
(11, 'FCP/CCS/21/1008', 'Joy Eze', '8088889999', 'joy.eze@example.com', 0, 1, 0),
(12, 'FCP/CCS/21/1009', 'Hassan Ibrahim', '8100001111', 'hassan.ibrahim@example.com', 0, 1, 0),
(13, 'FCP/CCS/21/1010', 'Chika Nnamdi', '8111112222', 'chika.nnamdi@example.com', 0, 1, 0),
(14, 'FCP/CCS/21/1011', 'Fatima Umar', '8122223333', 'fatima.umar@example.com', 0, 1, 1),
(15, 'FCP/CCS/21/1012', 'Michael James', '8133334444', 'michael.james@example.com', 0, 1, 1),
(16, 'FCP/CCS/21/1013', 'Aisha Mohammed', '8144445555', 'aisha.mohammed@example.com', 0, 1, 0),
(17, 'FCP/CCS/21/1014', 'Usman Bello', '8155556666', 'usman.bello@example.com', 0, 1, 1),
(18, 'FCP/CCS/21/1015', 'Ngozi Umeh', '8166667777', 'ngozi.umeh@example.com', 0, 1, 1),
(19, 'FCP/CCS/21/1016', 'Tunde Adewale', '8177778888', 'tunde.adewale@example.com', 0, 1, 1),
(20, 'FCP/CCS/21/1017', 'Khadija Ibrahim', '8188889999', 'khadija.ibrahim@example.com', 0, 1, 1),
(21, 'FCP/CCS/21/1018', 'Bashir Ali', '8199990000', 'bashir.ali@example.com', 0, 1, 1),
(22, 'FCP/CCS/21/1019', 'Mary James', '8211112222', 'mary.james@example.com', 0, 1, 0),
(23, 'FCP/CCS/21/1020', 'Ahmed Usman', '8222223333', 'ahmed.usman@example.com', 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student_panel_assignments`
--

CREATE TABLE `student_panel_assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `academic_session` varchar(20) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_panel_assignments`
--

INSERT INTO `student_panel_assignments` (`id`, `student_id`, `panel_id`, `academic_session`, `assigned_at`) VALUES
(1, 16, 1, '2023/2024', '2026-01-29 06:18:48'),
(6, 4, 1, '2023/2024', '2026-01-29 12:12:28'),
(7, 5, 1, '2023/2024', '2026-01-29 12:12:28'),
(8, 7, 1, '2023/2024', '2026-01-29 12:12:28'),
(13, 14, 2, '2023/2024', '2026-01-29 12:12:28'),
(14, 15, 2, '2023/2024', '2026-01-29 12:12:28'),
(15, 17, 2, '2023/2024', '2026-01-29 12:12:28'),
(16, 19, 2, '2023/2024', '2026-01-29 12:12:28'),
(20, 23, 2, '2023/2024', '2026-01-29 12:12:28');

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

--
-- Dumping data for table `submission_schedules`
--

INSERT INTO `submission_schedules` (`id`, `department_id`, `submission_start`, `submission_end`, `is_active`, `created_at`) VALUES
(1, 1, '2025-12-29 16:27:00', '2026-01-31 16:28:00', 1, '2026-01-24 15:28:13');

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
(1, 24, 23, '2026-01-24 16:18:35', 'active'),
(2, 24, 16, '2026-01-24 16:18:35', 'active'),
(3, 25, 4, '2026-01-24 16:18:35', 'active'),
(4, 25, 21, '2026-01-24 16:18:35', 'active'),
(5, 24, 13, '2026-01-24 16:18:35', 'active'),
(6, 24, 8, '2026-01-24 16:18:35', 'active'),
(7, 24, 6, '2026-01-24 16:18:35', 'active'),
(8, 25, 14, '2026-01-24 16:18:35', 'active'),
(9, 25, 9, '2026-01-24 16:18:35', 'active'),
(10, 24, 12, '2026-01-24 16:18:35', 'active'),
(11, 24, 11, '2026-01-24 16:18:35', 'active'),
(12, 24, 20, '2026-01-24 16:18:35', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `supervisors`
--

CREATE TABLE `supervisors` (
  `id` int(11) NOT NULL,
  `staff_no` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `faculty_id` int(11) NOT NULL,
  `department` varchar(100) NOT NULL,
  `max_students` int(11) NOT NULL DEFAULT 10,
  `current_load` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisors`
--

INSERT INTO `supervisors` (`id`, `staff_no`, `name`, `email`, `faculty_id`, `department`, `max_students`, `current_load`) VALUES
(24, 'SP/R/1001', 'Dr. Sadiq Musa', 'ali@example.com', 0, '1', 8, 8),
(25, 'SP/CSC/1002', 'Dr.Zainab Abdullahi', 'zee@example.com', 0, '1', 4, 4);

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
('allow_student_topic_edit', '1', 'Allow students to edit pending topics (1=Yes, 0=No)', '2026-01-29 05:39:26'),
('current_session', '2023/2024', 'The active academic session for project submissions', '2026-01-29 05:39:26'),
('max_proposals_per_student', '3', 'Number of topics a student can propose initially', '2026-01-29 05:47:39'),
('similarity_threshold', '30', 'Minimum similarity percentage to trigger warning (0-100)', '2026-01-29 05:39:26'),
('system_announcement', 'Welcome to the Project Validation System of Federal University Dutse', 'General announcement displayed on dashboards', '2026-01-29 05:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('stu','dpc','fpc','sup','admin','lib') DEFAULT NULL,
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
(1, 'superadmin', '$2y$10$5/Eo4JyrmJO6vsn550E.YOVmUvymNJ1VwTp9QTMMSLP1VaI1JqTTW', 'admin', 'Super Administrator', 'super@admin', 1, '1', 1, '2026-01-29 06:47:26', NULL, NULL, '2026-01-29 05:48:13'),
(2, 'fpc1', '$2y$10$bJoSSBl7FVwRoNL067.Ix.8NeTNWNCuT0fN5k8/dbiBYsDOv5eaFS', 'fpc', 'Computing', 'computing@fud.edu.ng', 1, NULL, 1, '2026-01-29 06:06:28', NULL, NULL, '2026-01-29 05:09:38'),
(3, 'dpc1', '$2y$10$kcobC/LOPSnYqY.pDZtOJ.MI2TqoDuMpPJm3jHiD/JxoCOPkz/GWa', 'dpc', 'DPC', 'comuter@fud.edu.ng', 1, '1', 1, '2026-01-31 13:39:45', NULL, 'vmkamip629i4mb5dorcda4b5rh', '2026-01-31 12:39:45'),
(4, 'FCP/CCS/21/1001', '$2y$10$lvCxWM9amTAWosV3RuzNleDPU3DlMKQHeau5KStyhuJ/VHVstn8eu', 'stu', 'Aliyu Musa', 'aliyu.musa@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:22'),
(5, 'FCP/CCS/21/1002', '$2y$10$6ONqGr1EByQBgUpnEySpGOi.vWfdA7ybXWBK4WegQ/yn5RVDYgdvC', 'stu', 'Zainab Abdullahi', 'zainab.abdullahi@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:23'),
(6, 'FCP/CCS/21/1003', '$2y$10$ptpo0zrUXUgE3ax9Pmm7jumby523hdkQWREYw0d5lbGBZU6xSx5q6', 'stu', 'Emeka Okafor', 'emeka.okafor@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:23'),
(7, 'FCP/CCS/21/1004', '$2y$10$MF.oe8wQfVZJXzqAM0JaeuR8RIxEE75zaf5GuP.NLufAyLIVMD5Kq', 'stu', 'Maryam Sani', 'maryam.sani@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:23'),
(8, 'FCP/CCS/21/1005', '$2y$10$mDOKDkpmlj07h.r4Ff5fvu4AOvzlUtGVmsM/Aw15pbERzm8thzQ2O', 'stu', 'David Johnson', 'david.johnson@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:23'),
(9, 'FCP/CCS/21/1006', '$2y$10$6VnFFlztjXxNnfaYRx/dz.nR0/zYd9OSJo7auYj4Bp55W6aA3QUbq', 'stu', 'Halima Yusuf', 'halima.yusuf@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:24'),
(10, 'FCP/CCS/21/1007', '$2y$10$hKHbEqsEfDAVCXc9PYNyU.tt31e5UPFPLmR0Df7PD1mrzdNyFwA6C', 'stu', 'Samuel Adewale', 'samuel.adewale@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:24'),
(11, 'FCP/CCS/21/1008', '$2y$10$X7k2qCTM66Bktde6DllMt.Km7BzhIZcVwg95J1coMx.VnuMdevI0W', 'stu', 'Joy Eze', 'joy.eze@example.com', 0, '1', 1, '2026-01-28 22:40:26', NULL, NULL, '2026-01-28 21:43:45'),
(12, 'FCP/CCS/21/1009', '$2y$10$wPnVGQNC4spvXE2j8uBzPeNv6Fk1y8grRccY6lr3sO6bAXupDVJHW', 'stu', 'Hassan Ibrahim', 'hassan.ibrahim@example.com', 0, '1', 1, '2026-01-29 06:46:51', NULL, NULL, '2026-01-29 05:47:13'),
(13, 'FCP/CCS/21/1010', '$2y$10$65.Cb8DayvN1PaBSNfJvGO/XJW1xHDTS6qytf5O6HaUnbs9Z86rMa', 'stu', 'Chika Nnamdi', 'chika.nnamdi@example.com', 0, '1', 1, '2026-01-28 16:32:46', NULL, NULL, '2026-01-28 15:34:06'),
(14, 'FCP/CCS/21/1011', '$2y$10$Qrttn/mfe1R9Ph1K/S6xG.PqGIgEGMKwOUKC06GIf2gFTozzqjr3.', 'stu', 'Fatima Umar', 'fatima.umar@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:24'),
(15, 'FCP/CCS/21/1012', '$2y$10$2hBCzhos130o0CwLw6IdZ.Xz6p0JdiWA1ZZO3Q3hKQZ8ZcbP1W2ZC', 'stu', 'Michael James', 'michael.james@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:25'),
(16, 'FCP/CCS/21/1013', '$2y$10$ewyCjKyzcNuZIzY5b0sCYOEtpjhSdOf4Voaas72kRVW3Rq4ujEyoC', 'stu', 'Aisha Mohammed', 'aisha.mohammed@example.com', 0, '1', 1, '2026-01-28 14:57:37', NULL, NULL, '2026-01-28 15:01:01'),
(17, 'FCP/CCS/21/1014', '$2y$10$sYAevgZ2f8Cun92wwqYT2ur9nk3A5uHhf9wXqKb6RNMGz33dX0bzO', 'stu', 'Usman Bello', 'usman.bello@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:25'),
(18, 'FCP/CCS/21/1015', '$2y$10$VKddNDNoKTZcSCYthdr.1.Y3vxyxLYJqT9/7pqqVhHcPiu1t.S.j.', 'stu', 'Ngozi Umeh', 'ngozi.umeh@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:25'),
(19, 'FCP/CCS/21/1016', '$2y$10$SzyNp/HLb0q9C2aq5jqYl.AKoTxbyadTLgGUevTv50qN8uOH4mkpq', 'stu', 'Tunde Adewale', 'tunde.adewale@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:25'),
(20, 'FCP/CCS/21/1017', '$2y$10$Gy7370RoVi8HfejI38PFX.eg0b6d.oYFZhZCvhKDsXPUOYjo8.0k2', 'stu', 'Khadija Ibrahim', 'khadija.ibrahim@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:25'),
(21, 'FCP/CCS/21/1018', '$2y$10$.Rbd8R6jpHt3dUIb.ZY4quOveJDh4.gbkNunse6Z80dspad36hWqW', 'stu', 'Bashir Ali', 'bashir.ali@example.com', 0, '1', 1, NULL, NULL, NULL, '2026-01-24 14:58:25'),
(22, 'FCP/CCS/21/1019', '$2y$10$4Vvwnx2jzf7Xi1PA4/S/X.dJvMuoKlqyJmQumMsOC4XD.5BhOOX6G', 'stu', 'Mary James', 'mary.james@example.com', 0, '1', 1, '2026-01-24 16:52:08', NULL, NULL, '2026-01-24 16:03:03'),
(23, 'FCP/CCS/21/1020', '$2y$10$toBihp/EfevWdXAh309xAe2qVodXFIBnhOAU/YJVg0BmZxCVrXZl6', 'stu', 'Ahmed Usman', 'ahmed.usman@example.com', 0, '1', 1, '2026-01-24 16:23:16', NULL, NULL, '2026-01-24 15:27:45'),
(24, 'SP/R/1001', '$2y$10$6u7fpQFjJUMVuTtgWjU3GOHLg/yg2QaLk/IyxkIp8BvxC6cpemV9a', 'sup', 'Dr. Sadiq Musa', 'ali@example.com', 0, '1', 1, '2026-01-29 13:27:24', NULL, NULL, '2026-01-31 11:34:57'),
(25, 'SP/CSC/1002', '$2y$10$EPqLOfMI6nu4w5hfwJpSROU9VWTW1wQ1xHDDYpY7BXRR6RHlwZFsK', 'sup', 'Dr.Zainab Abdullahi', 'zee@example.com', 0, '1', 1, '2026-01-31 12:42:42', NULL, NULL, '2026-01-31 11:44:00'),
(26, 'lib1', '$2y$10$xd2zvgfTZMrEMOljPWG9f.RkhiKBYqBUYMaFvWTI9BtMLRzUHA9W.', 'lib', 'Library', 'lib@fud.edu.ng', 0, NULL, 1, '2026-01-28 23:03:46', NULL, NULL, '2026-01-28 22:20:27');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `defense_panels`
--
ALTER TABLE `defense_panels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `defense_scores`
--
ALTER TABLE `defense_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `panel_members`
--
ALTER TABLE `panel_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `past_projects`
--
ALTER TABLE `past_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `project_topics`
--
ALTER TABLE `project_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `student_panel_assignments`
--
ALTER TABLE `student_panel_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `student_submission_overrides`
--
ALTER TABLE `student_submission_overrides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submission_schedules`
--
ALTER TABLE `submission_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supervision`
--
ALTER TABLE `supervision`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

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
