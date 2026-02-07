-- 1. Create System Settings Table (for Global Session Management)
CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` varchar(50) NOT NULL,
    `setting_value` text DEFAULT NULL,
    `description` text DEFAULT NULL,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default System Settings
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('current_session', '2024/2025', 'The active academic session for calculations and submissions'),
('max_proposals_per_student', '3', 'Limit on number of topics a student can submit'),
('allow_student_topic_edit', '1', '1 to allow editing pending topics, 0 to lock them'),
('system_announcement', '', 'Global message displayed on user dashboards'),
('similarity_threshold', '30', 'Auto-warning percentage for topic similarity check');

-- 2. Update Project Topics Table (for Supervisor Report Approvals)
-- Adding columns if they don't exist
ALTER TABLE `project_topics` 
ADD COLUMN IF NOT EXISTS `report_status` enum('pending','approved','rejected','not_submitted') DEFAULT 'not_submitted',
ADD COLUMN IF NOT EXISTS `report_feedback` text DEFAULT NULL;

-- 3. Create Assessment & Panel Management Tables
-- Defense Panels
CREATE TABLE IF NOT EXISTS `defense_panels` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `panel_name` varchar(100) NOT NULL,
    `panel_type` enum('proposal','internal','external') NOT NULL,
    `department_id` int(11) NOT NULL,
    `max_students` int(11) DEFAULT 10,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Panel Members (Supervisors assigned to Panels)
CREATE TABLE IF NOT EXISTS `panel_members` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `panel_id` int(11) NOT NULL,
    `supervisor_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY (`panel_id`),
    KEY (`supervisor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Panel Assignments
CREATE TABLE IF NOT EXISTS `student_panel_assignments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL,
    `panel_id` int(11) NOT NULL,
    `panel_type` enum('proposal','internal','external') NOT NULL,
    `academic_session` varchar(20) NOT NULL,
    `assigned_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY (`student_id`),
    KEY (`panel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Defense Scores (Grading by Panel Members)
CREATE TABLE IF NOT EXISTS `defense_scores` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL,
    `supervisor_id` int(11) NOT NULL,
    `panel_id` int(11) NOT NULL,
    `score` decimal(5,2) DEFAULT NULL,
    `comments` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_grading` (`student_id`, `supervisor_id`, `panel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supervisor Weekly/Effort Assessments
CREATE TABLE IF NOT EXISTS `supervisor_assessments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `student_id` int(11) NOT NULL,
    `supervisor_id` int(11) NOT NULL,
    `score` decimal(5,2) DEFAULT NULL,
    `comments` text DEFAULT NULL,
    `academic_session` varchar(20) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_sup_grading` (`student_id`, `supervisor_id`, `academic_session`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;