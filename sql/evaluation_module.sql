-- System Evaluation Module - SQL Migration
-- Run this against the `my_project_topics` (or fallback) database.
-- For a one-click remote install instead, use /install_evaluation_module.php.

-- 1. Flags on the users table
ALTER TABLE `users` ADD COLUMN `evaluation_due` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `evaluation_submitted` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `evaluation_remind_count` TINYINT(1) NOT NULL DEFAULT 0;

-- 2. One row per submitted questionnaire
CREATE TABLE IF NOT EXISTS `evaluations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `respondent_role` VARCHAR(20) NOT NULL COMMENT 'system role at time of submission: stu/sup/dpc/fpc/hod',
    `role` VARCHAR(20) NOT NULL COMMENT 'questionnaire type: student/supervisor/coordinator',
    `questionnaire_version` VARCHAR(30) NOT NULL,
    `department_id` INT(11) DEFAULT NULL,
    `department_name` VARCHAR(150) DEFAULT NULL,
    `faculty_id` INT(11) DEFAULT NULL,
    `faculty_name` VARCHAR(150) DEFAULT NULL,
    `academic_session` VARCHAR(20) DEFAULT NULL,
    `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_user_questionnaire` (`user_id`, `questionnaire_version`),
    KEY `idx_role` (`role`),
    KEY `idx_department` (`department_id`),
    KEY `idx_faculty` (`faculty_id`),
    CONSTRAINT `fk_evaluations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. One row per answered question (normalized - safe against future
--    revisions of any of the three questionnaires)
CREATE TABLE IF NOT EXISTS `evaluation_responses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `evaluation_id` INT(11) NOT NULL,
    `section` VARCHAR(150) NOT NULL,
    `question_no` VARCHAR(10) NOT NULL,
    `question_text` TEXT NOT NULL,
    `answer_value` TINYINT(1) DEFAULT NULL COMMENT '1-5 Likert scale',
    `answer_text` TEXT DEFAULT NULL COMMENT 'open-ended / single-choice answer',
    PRIMARY KEY (`id`),
    KEY `idx_evaluation` (`evaluation_id`),
    CONSTRAINT `fk_responses_evaluation` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
