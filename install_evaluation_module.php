<?php
/**
 * System Evaluation Module - Database Installer
 *
 * Instructions:
 * 1. Upload this file to the root of your project directory on the live server.
 * 2. Navigate to this file in your browser (e.g. https://yourdomain.com/install_evaluation_module.php).
 * 3. Confirm every step below shows green.
 * 4. Delete this file immediately after it runs successfully for security.
 */

require_once __DIR__ . '/includes/db.php';

echo "<h1>System Evaluation Module - Database Installer</h1>";
echo "<ul>";

function step($label, $callback) {
    global $conn;
    try {
        $callback($conn);
        echo "<li style='color: green;'>OK - $label</li>";
    } catch (PDOException $e) {
        echo "<li style='color: orange;'>Skipped - $label (" . htmlspecialchars($e->getMessage()) . ")</li>";
    }
}

// 1. users table flags
step("Add users.evaluation_due column", function ($conn) {
    $conn->exec("ALTER TABLE users ADD COLUMN evaluation_due TINYINT(1) NOT NULL DEFAULT 0");
});
step("Add users.evaluation_submitted column", function ($conn) {
    $conn->exec("ALTER TABLE users ADD COLUMN evaluation_submitted TINYINT(1) NOT NULL DEFAULT 0");
});
step("Add users.evaluation_remind_count column", function ($conn) {
    $conn->exec("ALTER TABLE users ADD COLUMN evaluation_remind_count TINYINT(1) NOT NULL DEFAULT 0");
});

// 2. evaluations table
step("Create evaluations table", function ($conn) {
    $conn->exec("
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
    ");
});

// 3. evaluation_responses table
step("Create evaluation_responses table", function ($conn) {
    $conn->exec("
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
    ");
});

echo "</ul>";
echo "<h2 style='color: blue;'>Installation Complete!</h2>";
echo "<p><strong>Important:</strong> Please delete this file (<code>install_evaluation_module.php</code>) from your server immediately for security reasons.</p>";
?>
