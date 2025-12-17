-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2025 at 10:47 AM
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
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`) VALUES
(1, 'Computer Science'),
(2, 'Software Engineering'),
(3, 'Cybersecurity'),
(4, 'Information Technology');

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
-- Table structure for table `past_projects`
--

CREATE TABLE `past_projects` (
  `id` int(11) NOT NULL,
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

INSERT INTO `past_projects` (`id`, `topic`, `reg_no`, `student_name`, `session`, `supervisor_name`, `pdf_path`) VALUES
(1, 'Machine Learning in Business: Customer Behavior Prediction', 'FCP/CSC/19/1001', 'Amina Yusuf', '2025/2026', 'Dr. Bello Ahmed', 'uploads/ml_customer_behavior.pdf'),
(2, 'Machine Learning in Business: Sales Forecasting Models', 'FCP/CSC/19/1002', 'John Okeke', '2025/2026', 'Prof. Grace Obi', 'uploads/ml_sales_forecasting.pdf'),
(3, 'Machine Learning in Business', 'FCP/CSC/19/1003', 'Fatima Musa', '2025/2026', 'Dr. Ibrahim Lawal', 'uploads/ml_fraud_detection.pdf'),
(4, 'Machine Learning in Business: Personalized Marketing Strategies', 'FCP/CSC/19/1004', 'Michael James', '2025/2026', 'Dr. Hauwa Ali', 'uploads/ml_marketing_strategies.pdf'),
(5, 'Machine Learning in Business: Supply Chain Optimization', 'FCP/CSC/19/1005', 'Zainab Umar', '2025/2026', 'Prof. Musa Abdullahi', 'uploads/ml_supply_chain.pdf');

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
  `supervisor_id` int(11) NOT NULL,
  `supervisor_name` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `pdf_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_topics`
--

INSERT INTO `project_topics` (`id`, `topic`, `student_id`, `student_name`, `session`, `supervisor_id`, `supervisor_name`, `status`, `pdf_path`) VALUES
(1, 'AI in Healthcare', '1', 'Amina Yusuf', '2025/2026', 2, 'Prof. Grace Obi', 'approved', 'uploads/ai_healthcare.pdf'),
(2, 'Blockchain in Finance', '2', 'John Okeke', '2025/2026', 3, 'Dr. Ibrahim Lawal', 'approved', 'uploads/blockchain_finance.pdf'),
(3, 'E-Learning Platforms', '3', 'Fatima Musa', '2025/2026', 1, 'Dr. Bello Ahmed', 'approved', 'uploads/e_learning.pdf'),
(4, 'Cybersecurity Threats', '4', 'Michael James', '2025/2026', 1, 'Dr. Bello Ahmed', 'approved', 'uploads/cybersecurity.pdf'),
(5, 'IoT in Agriculture', '5', 'Zainab Umar', '2025/2026', 3, 'Dr. Ibrahim Lawal', 'approved', 'uploads/iot_agriculture.pdf'),
(6, 'Data Mining Techniques', '6', 'David Samuel', '2025/2026', 3, 'Dr. Ibrahim Lawal', 'approved', 'uploads/data_mining.pdf'),
(7, 'Cloud Computing Adoption', '7', 'Maryam Ibrahim', '2025/2026', 2, 'Prof. Grace Obi', 'approved', 'uploads/cloud_computing.pdf'),
(8, 'Smart Cities Development', '8', 'Emeka Nwosu', '2025/2026', 1, 'Dr. Bello Ahmed', 'approved', 'uploads/smart_cities.pdf'),
(9, 'Mobile Banking Security', '9', 'Hassan Abdulkareem', '2025/2026', 3, 'Dr. Ibrahim Lawal', 'approved', 'uploads/mobile_banking.pdf'),
(10, 'Natural Language Processing', '10', 'Joy Eze', '2025/2026', 1, 'Dr. Bello Ahmed', 'approved', 'uploads/nlp.pdf'),
(11, 'Renewable Energy Systems', '11', 'Usman Bello', '2025/2026', 5, 'Prof. Musa Abdullahi', 'approved', 'uploads/renewable_energy.pdf'),
(12, 'Virtual Reality in Education', '12', 'Halima Sani', '2025/2026', 5, 'Prof. Musa Abdullahi', 'approved', 'uploads/vr_education.pdf'),
(13, 'Big Data Analytics', '13', 'Samuel Johnson', '2025/2026', 6, 'Dr. Aisha Mohammed', 'approved', 'uploads/big_data.pdf'),
(14, 'E-Government Services', '14', 'Aisha Abdullahi', '2025/2026', 5, 'Prof. Musa Abdullahi', 'approved', 'uploads/e_government.pdf'),
(15, 'Machine Learning in Business', '15', 'Bashir Ali', '2025/2026', 6, 'Dr. Aisha Mohammed', 'approved', 'uploads/ml_business.pdf'),
(16, 'Digital Marketing Strategies', '16', 'Ngozi Umeh', '2025/2026', 4, 'Dr. Hauwa Ali', 'approved', 'uploads/digital_marketing.pdf'),
(17, 'Smart Healthcare Devices', '17', 'Tunde Adewale', '2025/2026', 6, 'Dr. Aisha Mohammed', 'approved', 'uploads/smart_healthcare.pdf'),
(18, 'Autonomous Vehicles', '18', 'Khadija Ibrahim', '2025/2026', 6, 'Dr. Aisha Mohammed', 'approved', 'uploads/autonomous_vehicles.pdf'),
(19, 'E-Commerce Security', '19', 'Chika Nnamdi', '2025/2026', 4, 'Dr. Hauwa Ali', 'approved', 'uploads/e_commerce.pdf'),
(20, 'Robotics in Manufacturing', '20', 'Abdulrahman Usman', '2025/2026', 6, 'Dr. Aisha Mohammed', 'approved', 'uploads/robotics.pdf'),
(21, 'Quantum Computing Applications', '21', 'Ibrahim Musa', '2025/2026', 8, 'Prof. Janet Okoro', 'approved', 'uploads/quantum_computing.pdf'),
(22, 'Green Technology Innovations', '22', 'Mary James', '2025/2026', 8, 'Prof. Janet Okoro', 'approved', 'uploads/green_tech.pdf'),
(23, 'Augmented Reality in Retail', '23', 'Ahmed Usman', '2025/2026', 9, 'Dr. Suleiman Adamu', 'approved', 'uploads/ar_retail.pdf'),
(24, 'Digital Identity Management', '24', 'Zainab Ali', '2025/2026', 9, 'Dr. Suleiman Adamu', 'approved', 'uploads/digital_identity.pdf'),
(25, 'Smart Transportation Systems', '25', 'Chinedu Okafor', '2025/2026', 7, 'Dr. Kabir Hassan', 'approved', 'uploads/smart_transport.pdf'),
(26, 'AI in Medical Diagnostics', '26', 'Halima Sani', '2025/2026', 9, 'Dr. Suleiman Adamu', 'approved', 'uploads/ai_diagnostics.pdf'),
(27, 'Blockchain for Supply Chain', '27', 'David Johnson', '2025/2026', 8, 'Prof. Janet Okoro', 'approved', 'uploads/blockchain_supplychain.pdf'),
(28, 'Mobile E-Learning Solutions', '28', 'Fatima Umar', '2025/2026', 8, 'Prof. Janet Okoro', 'approved', 'uploads/mobile_elearning.pdf'),
(29, 'Advanced Cybersecurity Frameworks', '29', 'Michael Okeke', '2025/2026', 8, 'Prof. Janet Okoro', 'approved', 'uploads/cybersecurity_frameworks.pdf'),
(30, 'Next-Generation Cybersecurity Frameworks', '30', 'Joy Musa', '2025/2026', 9, 'Dr. Suleiman Adamu', 'approved', 'uploads/iot_smartfarming.pdf'),
(31, 'Artificial Intelligence in Healthcare', '31', 'Amina Yusuf', '2025/2026', 12, 'Dr. Esther John', 'approved', 'uploads/ai_healthcare.pdf'),
(32, 'AI in Medical Diagnostics', '32', 'John Okeke', '2025/2026', 10, 'Dr. Peter Adeyemi', 'approved', 'uploads/ai_diagnostics.pdf'),
(33, 'AI-Powered Drug Discovery', '33', 'Fatima Musa', '2025/2026', 11, 'Prof. Ibrahim Danladi', 'approved', 'uploads/ai_drug_discovery.pdf'),
(34, 'AI in Public Health Surveillance', '34', 'Michael James', '2025/2026', 12, 'Dr. Esther John', 'approved', 'uploads/ai_public_health.pdf'),
(35, 'Online Result Processing System: Case Study of University of Maiduguri', '35', 'Usman Ibrahim', '2025/2026', 11, 'Prof. Ibrahim Danladi', 'approved', 'uploads/result_processing_unimaid.pdf'),
(36, 'Online Result Processing System: Case Study of Yobe State University', '36', 'Fatima Yusuf', '2025/2026', 11, 'Prof. Ibrahim Danladi', 'approved', 'uploads/result_processing_yobe.pdf'),
(37, 'Online Result Processing System: Case Study of Federal Polytechnic Damaturu', '37', 'Abdulrahman Musa', '2025/2026', 12, 'Dr. Esther John', 'approved', 'uploads/result_processing_fpdamaturu.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `reg_no` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` int(11) NOT NULL,
  `first_login` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `reg_no`, `name`, `password`, `department`, `first_login`) VALUES
(1, 'FCP/CSC/19/101', 'Amina Yusuf', '', 1, 127),
(2, 'FCP/CSC/19/102', 'John Okeke', '', 1, 127),
(3, 'FCP/CSC/19/103', 'Fatima Musa', '', 1, 127),
(4, 'FCP/CSC/19/104', 'Michael James', '', 1, 127),
(5, 'FCP/CSC/19/105', 'Zainab Umar', '', 1, 127),
(6, 'FCP/CSC/19/106', 'David Samuel', '', 1, 127),
(7, 'FCP/CSC/19/107', 'Maryam Ibrahim', '', 1, 127),
(8, 'FCP/CSC/19/108', 'Emeka Nwosu', '', 1, 127),
(9, 'FCP/CSC/19/109', 'Hassan Abdulkareem', '', 1, 127),
(10, 'FCP/CSC/19/110', 'Joy Eze', '', 1, 127),
(11, 'FCP/CSE/19/101', 'Usman Bello', '', 2, 127),
(12, 'FCP/CSE/19/102', 'Halima Sani', '', 2, 127),
(13, 'FCP/CSE/19/103', 'Samuel Johnson', '', 2, 127),
(14, 'FCP/CSE/19/104', 'Aisha Abdullahi', '', 2, 127),
(15, 'FCP/CSE/19/105', 'Bashir Ali', '', 2, 127),
(16, 'FCP/CSE/19/106', 'Ngozi Umeh', '', 2, 127),
(17, 'FCP/CSE/19/107', 'Tunde Adewale', '', 2, 127),
(18, 'FCP/CSE/19/108', 'Khadija Ibrahim', '', 2, 127),
(19, 'FCP/CSE/19/109', 'Chika Nnamdi', '', 2, 127),
(20, 'FCP/CSE/19/110', 'Abdulrahman Usman', '', 2, 127),
(21, 'FCP/CCS/19/101', 'Amina Sule', '', 3, 127),
(22, 'FCP/CCS/19/102', 'David Okoro', '', 3, 127),
(23, 'FCP/CCS/19/103', 'Mary James', '', 3, 127),
(24, 'FCP/CCS/19/104', 'Ahmed Usman', '', 3, 127),
(25, 'FCP/CCS/19/105', 'Zainab Ali', '', 3, 127),
(26, 'FCP/CCS/19/106', 'Chinedu Okafor', '', 3, 127),
(27, 'FCP/CCS/19/107', 'Halima Sani', '', 3, 127),
(28, 'FCP/CCS/19/108', 'David Johnson', '', 3, 127),
(29, 'FCP/CCS/19/109', 'Michael Okeke', '', 3, 127),
(30, 'FCP/CCS/19/110', 'Joy Musa', '', 3, 127),
(31, 'FCP/CIT/19/101', 'Ibrahim Musa', '', 4, 127),
(32, 'FCP/CIT/19/102', 'Mary James', '', 4, 127),
(33, 'FCP/CIT/19/103', 'Ahmed Usman', '', 4, 127),
(34, 'FCP/CIT/19/104', 'Zainab Ibrahim', '', 4, 127),
(35, 'FCP/CIT/19/105', 'Chinedu Obi', '', 4, 127),
(36, 'FCP/CIT/19/106', 'Fatima Yusuf', '', 4, 127),
(37, 'FCP/CIT/19/107', 'John Ibrahim', '', 4, 127),
(38, 'FCP/CIT/19/108', 'Halima Musa', '', 4, 127),
(39, 'FCP/CIT/19/109', 'Michael Okeke', '', 4, 127),
(40, 'FCP/CIT/19/110', 'Joy Musa', '', 4, 127);

-- --------------------------------------------------------

--
-- Table structure for table `supervision`
--

CREATE TABLE `supervision` (
  `allocation_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `allocation_date` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervision`
--

INSERT INTO `supervision` (`allocation_id`, `supervisor_id`, `student_id`, `project_id`, `allocation_date`, `status`) VALUES
(1, 6, 20, 20, '2025-12-14 16:05:09', 'active'),
(2, 9, 24, 24, '2025-12-14 16:05:09', 'active'),
(3, 11, 33, 33, '2025-12-14 16:05:09', 'active'),
(4, 5, 14, 14, '2025-12-14 16:05:09', 'active'),
(5, 8, 21, 21, '2025-12-14 16:05:09', 'active'),
(6, 2, 1, 1, '2025-12-14 16:05:09', 'active'),
(7, 6, 15, 15, '2025-12-14 16:05:09', 'active'),
(8, 4, 19, 19, '2025-12-14 16:05:09', 'active'),
(9, 11, 35, 35, '2025-12-14 16:05:09', 'active'),
(10, 9, 26, 26, '2025-12-14 16:05:09', 'active'),
(11, 8, 28, 28, '2025-12-14 16:05:09', 'active'),
(12, 8, 22, 22, '2025-12-14 16:05:09', 'active'),
(13, 3, 6, 6, '2025-12-14 16:05:09', 'active'),
(14, 1, 8, 8, '2025-12-14 16:05:09', 'active'),
(15, 1, 3, 3, '2025-12-14 16:05:09', 'active'),
(16, 11, 36, 36, '2025-12-14 16:05:09', 'active'),
(17, 8, 27, 27, '2025-12-14 16:05:09', 'active'),
(18, 5, 12, 12, '2025-12-14 16:05:09', 'active'),
(19, 3, 9, 9, '2025-12-14 16:05:09', 'active'),
(20, 12, 31, 31, '2025-12-14 16:05:09', 'active'),
(21, 12, 37, 37, '2025-12-14 16:05:09', 'active'),
(22, 3, 2, 2, '2025-12-14 16:05:09', 'active'),
(23, 1, 10, 10, '2025-12-14 16:05:09', 'active'),
(24, 9, 30, 30, '2025-12-14 16:05:09', 'active'),
(25, 6, 18, 18, '2025-12-14 16:05:09', 'active'),
(26, 9, 23, 23, '2025-12-14 16:05:09', 'active'),
(27, 10, 32, 32, '2025-12-14 16:05:09', 'active'),
(28, 2, 7, 7, '2025-12-14 16:05:09', 'active'),
(29, 1, 4, 4, '2025-12-14 16:05:09', 'active'),
(30, 8, 29, 29, '2025-12-14 16:05:09', 'active'),
(31, 4, 16, 16, '2025-12-14 16:05:09', 'active'),
(32, 6, 13, 13, '2025-12-14 16:05:09', 'active'),
(33, 6, 17, 17, '2025-12-14 16:05:09', 'active'),
(34, 5, 11, 11, '2025-12-14 16:05:09', 'active'),
(35, 7, 25, 25, '2025-12-14 16:05:09', 'active'),
(36, 12, 34, 34, '2025-12-14 16:05:09', 'active'),
(37, 3, 5, 5, '2025-12-14 16:05:09', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `supervisors`
--

CREATE TABLE `supervisors` (
  `id` int(11) NOT NULL,
  `staff_no` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `max_students` int(11) NOT NULL DEFAULT 10,
  `current_load` int(11) NOT NULL DEFAULT 0,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisors`
--

INSERT INTO `supervisors` (`id`, `staff_no`, `name`, `email`, `department`, `max_students`, `current_load`, `password`) VALUES
(1, 'SP/R/001', 'Dr. Bello Ahmed', 'bello.ahmed@university.edu', '1', 10, 10, ''),
(2, 'SP/R/002', 'Prof. Grace Obi', 'grace.obi@university.edu', '1', 12, 12, ''),
(3, 'SP/R/003', 'Dr. Ibrahim Lawal', 'ibrahim.lawal@university.edu', '1', 8, 8, ''),
(4, 'SP/R/004', 'Dr. Hauwa Ali', 'hauwa.ali@university.edu', '2', 10, 10, ''),
(5, 'SP/R/005', 'Prof. Musa Abdullahi', 'musa.abdullahi@university.edu', '2', 12, 9, ''),
(6, 'SP/R/006', 'Dr. Aisha Mohammed', 'aisha.mohammed@university.edu', '2', 9, 9, ''),
(7, 'SP/R/007', 'Dr. Kabir Hassan', 'kabir.hassan@university.edu', '3', 10, 6, ''),
(8, 'SP/R/008', 'Prof. Janet Okoro', 'janet.okoro@university.edu', '3', 11, 9, ''),
(9, 'SP/R/009', 'Dr. Suleiman Adamu', 'suleiman.adamu@university.edu', '3', 8, 6, ''),
(10, 'SP/R/010', 'Dr. Peter Adeyemi', 'peter.adeyemi@university.edu', '4', 10, 8, ''),
(11, 'SP/R/011', 'Prof. Ibrahim Danladi', 'ibrahim.danladi@university.edu', '4', 12, 12, ''),
(12, 'SP/R/012', 'Dr. Esther John', 'esther.john@university.edu', '4', 9, 6, '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('stu','dpc','fpc','sup') NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `first_login` datetime DEFAULT NULL,
  `session` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `email`, `department`, `is_active`, `first_login`, `session`) VALUES
(1, 'student1', '$2y$10$wH9uQwQwQwQwQwQwQwQwQeQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQw', 'stu', 'Student One', 'student1@example.com', 'Computer Science', 1, NULL, '2025/2026'),
(2, 'dpc1', '$2y$10$xETc9KbUT8gTxaXH.YdlMe9fb39F6Aj1Dx1UWSMJOSLV/gJPFoqXW', 'dpc', 'Dept Coordinator', 'dpc1@example.com', '1', 1, '2025-12-14 16:01:18', 'g5cmdr687j305507qkrdib7c0c'),
(3, 'fpc1', '$2y$10$wBg0XVuchohb94.ln4UI6.unAcWfGEhIw4ozpj9zaFMYYWSUnrClO', 'fpc', 'Faculty Coordinator', 'fpc1@example.com', '1', 1, '2025-12-14 16:00:25', 'g5cmdr687j305507qkrdib7c0c'),
(4, 'sup1', '$2y$10$wH9uQwQwQwQwQwQwQwQwQeQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQw', 'sup', 'Supervisor User', 'sup1@example.com', '1', 1, NULL, '2025/2026');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `supervision`
--
ALTER TABLE `supervision`
  ADD PRIMARY KEY (`allocation_id`),
  ADD KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `supervisors`
--
ALTER TABLE `supervisors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_no` (`staff_no`);

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
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `past_projects`
--
ALTER TABLE `past_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `project_topics`
--
ALTER TABLE `project_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `supervision`
--
ALTER TABLE `supervision`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `supervision`
--
ALTER TABLE `supervision`
  ADD CONSTRAINT `supervision_ibfk_1` FOREIGN KEY (`supervisor_id`) REFERENCES `supervisors` (`id`),
  ADD CONSTRAINT `supervision_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `supervision_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `project_topics` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
