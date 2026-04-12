-- ========================================================
-- Database Schema Updates for Final Report Schedules
-- Run this script on your online server database
-- ========================================================

-- 1. Create the general department schedule table for final reports
CREATE TABLE IF NOT EXISTS `report_schedules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `department_id` INT(11) DEFAULT NULL,
  `submission_start` DATETIME DEFAULT NULL,
  `submission_end` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_id` (`department_id`),
  CONSTRAINT `report_schedules_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Create the override table for individual student report submissions
CREATE TABLE IF NOT EXISTS `student_report_overrides` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `submission_start` DATETIME NOT NULL,
  `submission_end` DATETIME NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  CONSTRAINT `student_report_overrides_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
