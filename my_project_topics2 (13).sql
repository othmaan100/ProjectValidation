-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 07, 2026 at 07:28 AM
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
  `panel_type` enum('proposal','internal','external') DEFAULT 'proposal',
  `department_id` int(11) NOT NULL,
  `max_students` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `panel_members`
--

CREATE TABLE `panel_members` (
  `id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'superadmin', '$2y$10$5/Eo4JyrmJO6vsn550E.YOVmUvymNJ1VwTp9QTMMSLP1VaI1JqTTW', 'admin', 'Super Administrator', 'super@admin', 1, '1', 1, '2026-02-07 07:20:22', NULL, NULL, '2026-02-07 06:25:32'),
(2, 'fpc1', '$2y$10$/wa7vu7v2lZHUptkhl15/uSFjTipvS2q453RrJIFbHcXTb.01bl6K', 'fpc', 'Computing', 'computing@fud.edu.ng', 1, NULL, 1, '2026-02-07 07:26:08', NULL, NULL, '2026-02-07 06:26:28'),
(4, 'dpc1', '$2y$10$MP6QyO3UbM7IKzjf/Cp3K.TzxjoST/nYbc3pvIUXk03qZ3B1JUbUG', 'dpc', 'Compter Science', 'comuter@fud.edu.ng', 1, '1', 1, '2026-02-07 07:26:45', NULL, 'vmkamip629i4mb5dorcda4b5rh', '2026-02-07 06:27:10');

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
-- AUTO_INCREMENT for table `defense_panels`
--
ALTER TABLE `defense_panels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `defense_scores`
--
ALTER TABLE `defense_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `panel_members`
--
ALTER TABLE `panel_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `past_projects`
--
ALTER TABLE `past_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_topics`
--
ALTER TABLE `project_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_panel_assignments`
--
ALTER TABLE `student_panel_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_submission_overrides`
--
ALTER TABLE `student_submission_overrides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submission_schedules`
--
ALTER TABLE `submission_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supervision`
--
ALTER TABLE `supervision`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supervisor_assessments`
--
ALTER TABLE `supervisor_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
