-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 05:05 PM
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

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `student_reg_no`, `message`, `created_at`) VALUES
(1, 'FCP/CSC/19/102', 'Your topic was approved. ', '2025-12-20 14:38:44'),
(2, 'FCP/CSC/19/103', 'Your topic was approved. ', '2025-12-20 14:38:56'),
(3, 'FCP/CSC/19/110', 'Your topic was rejected. Reason: Similarity found during validation.', '2025-12-20 14:39:14');

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
(1, 'Machine Learning in Business: Customer Behavior Prediction', 'FCP/CSC/19/1001', 'Amina Yusuf', '2024/2025', 'Dr. Bello Ahmed', 'uploads/ml_customer_behavior.pdf'),
(2, 'Machine Learning in Business: Sales Forecasting Models', 'FCP/CSC/19/1002', 'John Okeke', '2024/2025', 'Prof. Grace Obi', 'uploads/ml_sales_forecasting.pdf'),
(3, 'Machine Learning in Business', 'FCP/CSC/19/1003', 'Fatima Musa', '2024/2025', 'Dr. Ibrahim Lawal', 'uploads/ml_fraud_detection.pdf'),
(4, 'Machine Learning in Business: Personalized Marketing Strategies', 'FCP/CSC/19/1004', 'Michael James', '2024/2025', 'Dr. Hauwa Ali', 'uploads/ml_marketing_strategies.pdf'),
(5, 'Machine Learning in Business: Supply Chain Optimization', 'FCP/CSC/19/1005', 'Zainab Umar', '2024/2025', 'Prof. Musa Abdullahi', 'uploads/ml_supply_chain.pdf'),
(6, 'Sample Project Topic Title', 'FCP/CSC/16/101', 'John Doe', '2024/2025', 'Dr. Smith', ''),
(7, 'Machine Learning for Fraud Detection', 'FCP/CSC/18/101', 'Amina Yusuf', '2024/2025', 'Dr. Bello Ahmed', ''),
(8, 'Data Mining in E-Commerce', 'FCP/CSC/18/102', 'John Okeke', '2024/2025', 'Prof. Grace Obi', ''),
(9, 'Cloud Computing Security Models', 'FCP/CSC/18/103', 'Fatima Musa', '2024/2025', 'Dr. Ibrahim Lawal', ''),
(10, 'Artificial Intelligence in Healthcare', 'FCP/CSC/18/104', 'Michael James', '2024/2025', 'Dr. Hauwa Ali', ''),
(11, 'Blockchain Applications in Education', 'FCP/CSC/18/105', 'Zainab Umar', '2024/2025', 'Prof. Musa Abdullahi', ''),
(12, 'Natural Language Processing for Chatbots', 'FCP/CSC/18/106', 'David Samuel', '2024/2025', 'Dr. Aisha Mohammed', ''),
(13, 'Cybersecurity Threat Detection Systems', 'FCP/CSC/18/107', 'Maryam Ibrahim', '2024/2025', 'Dr. Kabir Hassan', ''),
(14, 'IoT Solutions for Smart Homes', 'FCP/CSC/18/108', 'Emeka Nwosu', '2024/2025', 'Prof. Janet Okoro', ''),
(15, 'Big Data Analytics in Business Intelligence', 'FCP/CSC/18/109', 'Hassan Abdulkareem', '2024/2025', 'Dr. Suleiman Adamu', ''),
(16, 'Virtual Reality in Education Platforms', 'FCP/CSC/18/110', 'Joy Eze', '2024/2025', 'Dr. Peter Adeyemi', 'assets/uploads/past_projects/project_16_1766072307.pdf'),
(17, 'Advanced Cybersecurity Frameworks', 'FCP/CSC/16/102', 'Sani Bala Ahmed', '2024/2025', 'Dr. Kabir Bello', ''),
(18, 'Autonomous Vehicles', 'FCP/CSC/15/1018', 'Khadija Ibrahim', '2024/2025', 'Dr. Aisha Mohammed', 'uploads/autonomous_vehicles.pdf'),
(19, 'E-Commerce Security', 'FCP/CSC/15/1019', 'Chika Nnamdi', '2024/2025', 'Dr. Hauwa Ali', 'uploads/e_commerce.pdf'),
(20, 'Robotics in Manufacturing', 'FCP/CSC/15/1020', 'Abdulrahman Usman', '2024/2025', 'Dr. Aisha Mohammed', 'uploads/robotics.pdf'),
(21, 'Quantum Computing Applications', 'FCP/CSC/15/1021', 'Ibrahim Musa', '2024/2025', 'Prof. Janet Okoro', 'uploads/quantum_computing.pdf'),
(22, 'Green Technology Innovations', 'FCP/CSC/15/1022', 'Mary James', '2024/2025', 'Prof. Janet Okoro', 'uploads/green_tech.pdf'),
(23, 'Augmented Reality in Retail', 'FCP/CSC/15/1023', 'Ahmed Usman', '2024/2025', 'Dr. Suleiman Adamu', 'uploads/ar_retail.pdf'),
(24, 'Digital Identity Management', 'FCP/CSC/15/1024', 'Zainab Ali', '2024/2025', 'Dr. Suleiman Adamu', 'uploads/digital_identity.pdf'),
(25, 'Smart Transportation Systems', 'FCP/CSC/15/1025', 'Chinedu Okafor', '2024/2025', 'Dr. Kabir Hassan', 'uploads/smart_transport.pdf'),
(26, 'AI in Medical Diagnostics', 'FCP/CSC/15/1026', 'Halima Sani', '2024/2025', 'Dr. Suleiman Adamu', 'uploads/ai_diagnostics.pdf'),
(27, 'Blockchain for Supply Chain', 'FCP/CSC/15/1027', 'David Johnson', '2024/2025', 'Prof. Janet Okoro', 'uploads/blockchain_supplychain.pdf'),
(28, 'Mobile E-Learning Solutions', 'FCP/CSC/15/1028', 'Fatima Umar', '2024/2025', 'Prof. Janet Okoro', 'uploads/mobile_elearning.pdf'),
(29, 'Advanced Cybersecurity Frameworks', 'FCP/CSC/15/1029', 'Michael Okeke', '2024/2025', 'Prof. Janet Okoro', 'uploads/cybersecurity_frameworks.pdf'),
(30, 'Next-Generation Cybersecurity Frameworks', 'FCP/CSC/15/1030', 'Joy Musa', '2024/2025', 'Dr. Suleiman Adamu', 'uploads/iot_smartfarming.pdf'),
(31, 'Artificial Intelligence in Healthcare', 'FCP/CSC/15/1031', 'Amina Yusuf', '2024/2025', 'Dr. Esther John', 'uploads/ai_healthcare.pdf'),
(32, 'AI in Medical Diagnostics', 'FCP/CSC/15/1032', 'John Okeke', '2024/2025', 'Dr. Peter Adeyemi', 'uploads/ai_diagnostics.pdf'),
(33, 'AI-Powered Drug Discovery', 'FCP/CSC/15/1033', 'Fatima Musa', '2024/2025', 'Prof. Ibrahim Danladi', 'uploads/ai_drug_discovery.pdf'),
(34, 'AI in Public Health Surveillance', 'FCP/CSC/15/1034', 'Michael James', '2024/2025', 'Dr. Esther John', 'uploads/ai_public_health.pdf'),
(35, 'Online Result Processing System: Case Study of University of Maiduguri', 'FCP/CSC/15/1035', 'Usman Ibrahim', '2024/2025', 'Prof. Ibrahim Danladi', 'uploads/result_processing_unimaid.pdf'),
(36, 'Online Result Processing System: Case Study of Yobe State University', 'FCP/CSC/15/1036', 'Fatima Yusuf', '2024/2025', 'Prof. Ibrahim Danladi', 'uploads/result_processing_yobe.pdf'),
(37, 'Online Result Processing System: Case Study of Federal Polytechnic Damaturu', 'FCP/CSC/15/1037', 'Abdulrahman Musa', '2024/2025', 'Dr. Esther John', 'uploads/result_processing_fpdamaturu.pdf'),
(38, 'Cloud Computing Security Models', 'FCP/CSC/15/1038', 'Amina Yusuf', '2024/2025', '', ''),
(39, 'Cybersecurity Threat Detection Systems', 'FCP/CSC/15/1039', 'Amina Yusuf', '2024/2025', '', ''),
(40, 'Asset Management System', 'FCP/CSC/15/1040', 'Joy Eze', '2024/2025', '', ''),
(41, 'Hopital Management System', 'FCP/CSC/15/1041', 'Joy Eze', '2024/2025', '', ''),
(42, 'AI in Healthcare', 'FCP/CSC/15/1001', 'Amina Yusuf', '2025/2026', 'Prof. Grace Obi', 'uploads/ai_healthcare.pdf'),
(43, 'Blockchain in Finance', 'FCP/CSC/15/1002', 'John Okeke', '2025/2026', 'Dr. Ibrahim Lawal', 'uploads/blockchain_finance.pdf'),
(44, 'E-Learning Platforms', 'FCP/CSC/15/1003', 'Fatima Musa', '2025/2026', 'Dr. Bello Ahmed', 'uploads/e_learning.pdf'),
(45, 'Cybersecurity Threats', 'FCP/CSC/15/1004', 'Michael James', '2025/2026', 'Dr. Bello Ahmed', 'uploads/cybersecurity.pdf'),
(46, 'IoT in Agriculture', 'FCP/CSC/15/1005', 'Zainab Umar', '2025/2026', 'Dr. Ibrahim Lawal', 'uploads/iot_agriculture.pdf'),
(47, 'Data Mining Techniques', 'FCP/CSC/15/1006', 'David Samuel', '2025/2026', 'Dr. Ibrahim Lawal', 'uploads/data_mining.pdf'),
(48, 'Cloud Computing Adoption', 'FCP/CSC/15/1007', 'Maryam Ibrahim', '2025/2026', 'Prof. Grace Obi', 'uploads/cloud_computing.pdf'),
(49, 'Smart Cities Development', 'FCP/CSC/15/1008', 'Emeka Nwosu', '2025/2026', 'Dr. Bello Ahmed', 'uploads/smart_cities.pdf'),
(50, 'Mobile Banking Security', 'FCP/CSC/15/1009', 'Hassan Abdulkareem', '2025/2026', 'Dr. Ibrahim Lawal', 'uploads/mobile_banking.pdf'),
(51, 'Natural Language Processing', 'FCP/CSC/15/1010', 'Joy Eze', '2025/2026', 'Dr. Bello Ahmed', 'uploads/nlp.pdf'),
(52, 'Renewable Energy Systems', 'FCP/CSC/15/1011', 'Usman Bello', '2025/2026', 'Prof. Musa Abdullahi', 'uploads/renewable_energy.pdf'),
(53, 'Virtual Reality in Education', 'FCP/CSC/15/1012', 'Halima Sani', '2025/2026', 'Prof. Musa Abdullahi', 'uploads/vr_education.pdf'),
(54, 'Big Data Analytics', 'FCP/CSC/15/1013', 'Samuel Johnson', '2025/2026', 'Dr. Aisha Mohammed', 'uploads/big_data.pdf'),
(55, 'E-Government Services', 'FCP/CSC/15/1014', 'Aisha Abdullahi', '2025/2026', 'Prof. Musa Abdullahi', 'uploads/e_government.pdf'),
(56, 'Machine Learning in Business', 'FCP/CSC/15/1015', 'Bashir Ali', '2025/2026', 'Dr. Aisha Mohammed', 'uploads/ml_business.pdf'),
(57, 'Digital Marketing Strategies', 'FCP/CSC/15/1016', 'Ngozi Umeh', '2025/2026', 'Dr. Hauwa Ali', 'uploads/digital_marketing.pdf'),
(58, 'Smart Healthcare Devices', 'FCP/CSC/15/1017', 'Tunde Adewale', '2025/2026', 'Dr. Aisha Mohammed', 'uploads/smart_healthcare.pdf'),
(59, 'Autonomous Vehicles', 'FCP/CSC/15/1018', 'Khadija Ibrahim', '2025/2026', 'Dr. Aisha Mohammed', 'uploads/autonomous_vehicles.pdf'),
(60, 'E-Commerce Security', 'FCP/CSC/15/1019', 'Chika Nnamdi', '2025/2026', 'Dr. Hauwa Ali', 'uploads/e_commerce.pdf'),
(61, 'Robotics in Manufacturing', 'FCP/CSC/15/1020', 'Abdulrahman Usman', '2025/2026', 'Dr. Aisha Mohammed', 'uploads/robotics.pdf');

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
(1, 'Totam excepturi ut e', '12', 'Jane Smith', '2025/2026', 0, NULL, 'pending', NULL),
(2, 'Est et enim asperna', '12', 'Jane Smith', '2025/2026', 0, NULL, 'pending', NULL),
(3, 'Sunt earum id et et', '12', 'Jane Smith', '2025/2026', 1, 'Dr. Bello Ahmed', 'approved', NULL);

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
  `password` varchar(255) NOT NULL,
  `department` int(11) NOT NULL,
  `first_login` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `reg_no`, `name`, `phone`, `email`, `password`, `department`, `first_login`) VALUES
(11, 'FCP/CSC/19/1001', 'John Doe', '08012345678', 'john@example.com', '$2y$10$S7iSyh23ZRdBZdnyozKwT.2BXriOjbO.iIsvgpeQlZq0DRb38nrdi', 1, 0),
(12, 'FCP/CSC/19/1002', 'Jane Smith', '08087654321', 'jane@example.com', '$2y$10$W1DreP9FIr8jbPm9tXf7Tuy/6ulCaI7.66NRbgmY.JsVLOJZuALoy', 1, 0),
(13, 'FCP/CSC/21/1001', 'Aliyu Musa', '08011112222', 'aliyu.musa@example.com', '$2y$10$XAUzSp0kMnx1Cr8KA2knquMsPMaEti4x.ULRYb4qI4ekBdn2xZ6zS', 1, 1),
(14, 'FCP/CSC/21/1002', 'Zainab Abdullahi', '08022223333', 'zainab.abdullahi@example.com', '$2y$10$7XmoLKpY2NmyjvwGeEgiXeSKAUNdF/AWIl7a.nW0dF/3AGn9/uTJy', 1, 1),
(15, 'FCP/CSC/21/1003', 'Emeka Okafor', '08033334444', 'emeka.okafor@example.com', '$2y$10$gr2qUvZ7NToB9iJkOK.arOQMjRg6LOxlmddaU5XgdfIfAv3qQatgm', 1, 1),
(16, 'FCP/CSC/21/1004', 'Maryam Sani', '08044445555', 'maryam.sani@example.com', '$2y$10$6mXv8DCpS9YlVuRQOjNqK.d07ybJpRMIXSblo/s2xx7xRvZFDnB5G', 1, 1),
(17, 'FCP/CSC/21/1005', 'David Johnson', '08055556666', 'david.johnson@example.com', '$2y$10$0wt5zxUmsEL5Pz3X3ovNmejKWU3z9b2CZH1oFWEiat17PTGyxEnbW', 1, 1),
(18, 'FCP/CSC/21/1006', 'Halima Yusuf', '08066667777', 'halima.yusuf@example.com', '$2y$10$b6epSizqHEw2EFxlCoEkveowJQ97P81.JQcCPGkaik5a4RNCqBxKy', 1, 1),
(19, 'FCP/CSC/21/1007', 'Samuel Adewale', '08077778888', 'samuel.adewale@example.com', '$2y$10$DBlHwe5IMrLh1LA9rliZeutLcQxaHqoi6DFnNzTHYk/X/wiSuDl/G', 1, 1),
(20, 'FCP/CSC/21/1008', 'Joy Eze', '08088889999', 'joy.eze@example.com', '$2y$10$mt12azg9a2BjUWsC2dHN4O/m0U1B71oKCq3m5sdnIZX7sKrPI.oiW', 1, 1),
(21, 'FCP/CSC/21/1009', 'Hassan Ibrahim', '08100001111', 'hassan.ibrahim@example.com', '$2y$10$cs0b97S3Mq10qRUX5C0UvOMgnAOhCIWPkyPQL.5qOug.rlcLqg9vu', 1, 1),
(22, 'FCP/CSC/21/1010', 'Chika Nnamdi', '08111112222', 'chika.nnamdi@example.com', '$2y$10$0HnMFnxLG/pmKVt.jW49muNJhUSt.B0C9YrqtLMXwttq4ng36DZWO', 1, 1),
(23, 'FCP/CSC/21/1011', 'Fatima Umar', '08122223333', 'fatima.umar@example.com', '$2y$10$4CTZW83SDuYg34qMkf0C3uzfXGwem7mLTybqgMaOBVzaw8GeTwKp6', 1, 1),
(24, 'FCP/CSC/21/1012', 'Michael James', '08133334444', 'michael.james@example.com', '$2y$10$e5bNLdIrm2mAXs.4JFDWye1v9xVsOOPmY9LOtDwXg4hLEXfkR2o1q', 1, 1),
(25, 'FCP/CSC/21/1013', 'Aisha Mohammed', '08144445555', 'aisha.mohammed@example.com', '$2y$10$0PBflOLGWwRouD/HJ5Rswu12NTxtRe2ekZKnE5raSu3v2BhpFJJ/G', 1, 1),
(26, 'FCP/CSC/21/1014', 'Usman Bello', '08155556666', 'usman.bello@example.com', '$2y$10$O307dJI5irmH/5Sqm.tRy.d5v2wVx2bzizBoXuAPoLXd9BNM4zWcW', 1, 1),
(27, 'FCP/CSC/21/1015', 'Ngozi Umeh', '08166667777', 'ngozi.umeh@example.com', '$2y$10$kw54yvppA0D6zlpctywpd.2znPb2uu1F/1oG0yJbN.U1sxP1bEYZu', 1, 1),
(28, 'FCP/CSC/21/1016', 'Tunde Adewale', '08177778888', 'tunde.adewale@example.com', '$2y$10$Swa3jgFHnyyJoIw.qZTBCuSIJKsTlzKIKER8z0QocdM7IM1B.BI4G', 1, 1),
(29, 'FCP/CSC/21/1017', 'Khadija Ibrahim', '08188889999', 'khadija.ibrahim@example.com', '$2y$10$dTSnKqel47AJL0JFWUZBjOg/J99DwsHycAPczzhpMI65gNkDWrTP2', 1, 1),
(30, 'FCP/CSC/21/1018', 'Bashir Ali', '08199990000', 'bashir.ali@example.com', '$2y$10$19VprDTRql/4srHZjeHHPOiDcWArMhcROt1b3HdJjRSBCfx0I0m3S', 1, 1),
(31, 'FCP/CSC/21/1019', 'Mary James', '08211112222', 'mary.james@example.com', '$2y$10$xRJ6e2sKmDaKy9bEKSR2q.XqMa34FRhPbYxQk7ZpL365McQWfHwtK', 1, 1),
(32, 'FCP/CSC/21/1020', 'Ahmed Usman', '08222223333', 'ahmed.usman@example.com', '$2y$10$gIOjkHRYwlWQor59MRTtPOd/ZklTPyBsoATIaD8BVFjUG9vDACsGO', 1, 1);

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
(1, 1, 12, 3, '2025-12-21 17:04:22', 'active');

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
(1, 'SP/R/001', 'Dr. Bello Ahmed', 'bello.ahmed@university.edu', '1', 10, 1, ''),
(2, 'SP/R/002', 'Prof. Grace Obi', 'grace.obi@university.edu', '1', 12, 0, ''),
(3, 'SP/R/003', 'Dr. Ibrahim Lawal', 'ibrahim.lawal@university.edu', '1', 8, 0, ''),
(4, 'SP/R/004', 'Dr. Hauwa Ali', 'hauwa.ali@university.edu', '2', 10, 0, ''),
(5, 'SP/R/005', 'Prof. Musa Abdullahi', 'musa.abdullahi@university.edu', '2', 12, 0, ''),
(6, 'SP/R/006', 'Dr. Aisha Mohammed', 'aisha.mohammed@university.edu', '2', 9, 0, ''),
(7, 'SP/R/007', 'Dr. Kabir Hassan', 'kabir.hassan@university.edu', '3', 10, 0, ''),
(8, 'SP/R/008', 'Prof. Janet Okoro', 'janet.okoro@university.edu', '3', 11, 0, ''),
(9, 'SP/R/009', 'Dr. Suleiman Adamu', 'suleiman.adamu@university.edu', '3', 8, 0, ''),
(10, 'SP/R/010', 'Dr. Peter Adeyemi', 'peter.adeyemi@university.edu', '4', 10, 0, ''),
(11, 'SP/R/011', 'Prof. Ibrahim Danladi', 'ibrahim.danladi@university.edu', '4', 12, 0, ''),
(12, 'SP/R/012', 'Dr. Esther John', 'esther.john@university.edu', '4', 9, 0, '');

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
  `password_changed` varchar(50) DEFAULT NULL,
  `session` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`, `email`, `department`, `is_active`, `first_login`, `password_changed`, `session`, `created_at`) VALUES
(1, 'FCP/CSC/19/101', '$2y$10$uBDgdOCNSDoRMkmzragD4OzZ4amfuZUxl3H3KIb3SQUMij8TxH1eW', 'stu', 'Student One', 'student1@example.com', '1', 1, '2025-12-20 16:51:03', NULL, 'n62buqsi0f5roliscosrsd2me5', '2025-12-20 15:51:03'),
(2, 'dpc1', '$2y$10$xETc9KbUT8gTxaXH.YdlMe9fb39F6Aj1Dx1UWSMJOSLV/gJPFoqXW', 'dpc', 'Dept Coordinator', 'dpc1@example.com', '1', 1, '2025-12-21 17:02:59', NULL, 'jcghk1jl7ft21ds9e03n61ee2t', '2025-12-21 16:02:59'),
(3, 'fpc1', '$2y$10$wBg0XVuchohb94.ln4UI6.unAcWfGEhIw4ozpj9zaFMYYWSUnrClO', 'fpc', 'Faculty Coordinator', 'fpc1@example.com', '1', 1, '2025-12-20 17:20:03', NULL, 'n62buqsi0f5roliscosrsd2me5', '2025-12-20 16:20:03'),
(4, 'sup1', '$2y$10$HgKu2trclqIg4hu5WJPK7OAZLA800FGvOLYI/8OPdVyGrg7SrFDsS', 'sup', 'Supervisor User', 'sup1@example.com', '1', 1, '2025-12-20 17:04:14', NULL, 'n62buqsi0f5roliscosrsd2me5', '2025-12-20 16:04:14'),
(5, 'dpc2', '$2y$10$FFykXVMDhoVUp/PKJI30jeZNr2rR0RaiyAST8c4SMarThMpsfs2DK', 'dpc', 'Sani Ahmed', 'cyber@fud.edu.ng', '3', 1, '2025-12-20 16:00:43', NULL, 'jcghk1jl7ft21ds9e03n61ee2t', '2025-12-20 15:00:43'),
(6, 'dpc3', '$2y$10$AtZnIJDymnBES9uUN/2rxubln1sPgZ6fTwUwAvW0gcIvsL3NmZ/Pa', 'dpc', 'Sani Ahmed', 'software@fud.edu.ng', '2', 1, NULL, '0', '2025/2026', '2025-12-20 14:45:30'),
(7, 'dpc4', '$2y$10$ktspq4pxGj9fpKCOItc.Wub5h3KBY0yza5lKMozJ5FwkRc6sAyBfG', 'dpc', 'Sani Ahmed', 'infortech@fud.edu.ng', '4', 1, NULL, NULL, '2025/2026', '2025-12-17 12:52:06'),
(8, 'ddpc1', '$2y$10$C4CM1COaqAh0AUPvOo8MQObEs3/jKdb9v.yRawwISayHOaAzzahGm', 'dpc', 'Deputy', 'deputy@fud.edu.ng', '1', 1, NULL, NULL, '2025/2026', '2025-12-17 12:58:43'),
(9, 'ddpc2', '$2y$10$DbZf41RPy0Xd/ELukCC2NegdJva4o.9.pZdM4Mm9kWZhSxjz.xw.C', 'dpc', 'Deputy', 'deputy2@fud.edu.ng', '2', 1, NULL, NULL, '2025/2026', '2025-12-17 12:59:14'),
(10, 'FCP/CSC/19/110', '$2y$10$I2Bptt4Kmr6dYjzVVaTlb.K2w9gFlmrB0yqZO3vjZGwiLYQ9Kq7j6', 'stu', 'Bala Abubakar', 'bala@fud.edu.ng', '1', 1, '2025-12-20 17:03:28', NULL, 'n62buqsi0f5roliscosrsd2me5', '2025-12-20 16:03:28'),
(11, 'FCP/CSC/19/1001', '$2y$10$S7iSyh23ZRdBZdnyozKwT.2BXriOjbO.iIsvgpeQlZq0DRb38nrdi', 'stu', 'John Doe', 'john@example.com', '1', 1, '2025-12-21 15:58:12', NULL, 'jcghk1jl7ft21ds9e03n61ee2t', '2025-12-21 14:58:23'),
(12, 'FCP/CSC/19/1002', '$2y$10$W1DreP9FIr8jbPm9tXf7Tuy/6ulCaI7.66NRbgmY.JsVLOJZuALoy', 'stu', 'Jane Smith', 'jane@example.com', '1', 1, '2025-12-21 15:48:44', NULL, 'jcghk1jl7ft21ds9e03n61ee2t', '2025-12-21 14:49:09'),
(13, 'FCP/CSC/21/1001', '$2y$10$XAUzSp0kMnx1Cr8KA2knquMsPMaEti4x.ULRYb4qI4ekBdn2xZ6zS', 'stu', 'Aliyu Musa', 'aliyu.musa@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:22'),
(14, 'FCP/CSC/21/1002', '$2y$10$7XmoLKpY2NmyjvwGeEgiXeSKAUNdF/AWIl7a.nW0dF/3AGn9/uTJy', 'stu', 'Zainab Abdullahi', 'zainab.abdullahi@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:22'),
(15, 'FCP/CSC/21/1003', '$2y$10$gr2qUvZ7NToB9iJkOK.arOQMjRg6LOxlmddaU5XgdfIfAv3qQatgm', 'stu', 'Emeka Okafor', 'emeka.okafor@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:22'),
(16, 'FCP/CSC/21/1004', '$2y$10$6mXv8DCpS9YlVuRQOjNqK.d07ybJpRMIXSblo/s2xx7xRvZFDnB5G', 'stu', 'Maryam Sani', 'maryam.sani@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:22'),
(17, 'FCP/CSC/21/1005', '$2y$10$0wt5zxUmsEL5Pz3X3ovNmejKWU3z9b2CZH1oFWEiat17PTGyxEnbW', 'stu', 'David Johnson', 'david.johnson@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:22'),
(18, 'FCP/CSC/21/1006', '$2y$10$b6epSizqHEw2EFxlCoEkveowJQ97P81.JQcCPGkaik5a4RNCqBxKy', 'stu', 'Halima Yusuf', 'halima.yusuf@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:22'),
(19, 'FCP/CSC/21/1007', '$2y$10$DBlHwe5IMrLh1LA9rliZeutLcQxaHqoi6DFnNzTHYk/X/wiSuDl/G', 'stu', 'Samuel Adewale', 'samuel.adewale@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:22'),
(20, 'FCP/CSC/21/1008', '$2y$10$mt12azg9a2BjUWsC2dHN4O/m0U1B71oKCq3m5sdnIZX7sKrPI.oiW', 'stu', 'Joy Eze', 'joy.eze@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:22'),
(21, 'FCP/CSC/21/1009', '$2y$10$cs0b97S3Mq10qRUX5C0UvOMgnAOhCIWPkyPQL.5qOug.rlcLqg9vu', 'stu', 'Hassan Ibrahim', 'hassan.ibrahim@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:22'),
(22, 'FCP/CSC/21/1010', '$2y$10$0HnMFnxLG/pmKVt.jW49muNJhUSt.B0C9YrqtLMXwttq4ng36DZWO', 'stu', 'Chika Nnamdi', 'chika.nnamdi@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(23, 'FCP/CSC/21/1011', '$2y$10$4CTZW83SDuYg34qMkf0C3uzfXGwem7mLTybqgMaOBVzaw8GeTwKp6', 'stu', 'Fatima Umar', 'fatima.umar@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(24, 'FCP/CSC/21/1012', '$2y$10$e5bNLdIrm2mAXs.4JFDWye1v9xVsOOPmY9LOtDwXg4hLEXfkR2o1q', 'stu', 'Michael James', 'michael.james@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(25, 'FCP/CSC/21/1013', '$2y$10$0PBflOLGWwRouD/HJ5Rswu12NTxtRe2ekZKnE5raSu3v2BhpFJJ/G', 'stu', 'Aisha Mohammed', 'aisha.mohammed@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(26, 'FCP/CSC/21/1014', '$2y$10$O307dJI5irmH/5Sqm.tRy.d5v2wVx2bzizBoXuAPoLXd9BNM4zWcW', 'stu', 'Usman Bello', 'usman.bello@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(27, 'FCP/CSC/21/1015', '$2y$10$kw54yvppA0D6zlpctywpd.2znPb2uu1F/1oG0yJbN.U1sxP1bEYZu', 'stu', 'Ngozi Umeh', 'ngozi.umeh@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(28, 'FCP/CSC/21/1016', '$2y$10$Swa3jgFHnyyJoIw.qZTBCuSIJKsTlzKIKER8z0QocdM7IM1B.BI4G', 'stu', 'Tunde Adewale', 'tunde.adewale@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(29, 'FCP/CSC/21/1017', '$2y$10$dTSnKqel47AJL0JFWUZBjOg/J99DwsHycAPczzhpMI65gNkDWrTP2', 'stu', 'Khadija Ibrahim', 'khadija.ibrahim@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(30, 'FCP/CSC/21/1018', '$2y$10$19VprDTRql/4srHZjeHHPOiDcWArMhcROt1b3HdJjRSBCfx0I0m3S', 'stu', 'Bashir Ali', 'bashir.ali@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(31, 'FCP/CSC/21/1019', '$2y$10$xRJ6e2sKmDaKy9bEKSR2q.XqMa34FRhPbYxQk7ZpL365McQWfHwtK', 'stu', 'Mary James', 'mary.james@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23'),
(32, 'FCP/CSC/21/1020', '$2y$10$gIOjkHRYwlWQor59MRTtPOd/ZklTPyBsoATIaD8BVFjUG9vDACsGO', 'stu', 'Ahmed Usman', 'ahmed.usman@example.com', '1', 1, NULL, NULL, NULL, '2025-12-21 15:02:23');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `past_projects`
--
ALTER TABLE `past_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `project_topics`
--
ALTER TABLE `project_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `supervision`
--
ALTER TABLE `supervision`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supervisors`
--
ALTER TABLE `supervisors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

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
