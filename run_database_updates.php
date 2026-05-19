<?php
/**
 * Database Updater for Final Report Schedules
 * 
 * Instructions:
 * 1. Upload this file to the root of your project directory on the live server.
 * 2. Navigate to this file in your browser (e.g. https://yourdomain.com/run_database_updates.php).
 * 3. Delete this file immediately after it runs successfully for security.
 */

require_once __DIR__ . '/includes/db.php';

echo "<h1>Database Updates</h1>";
echo "<ul>";

try {
    // 1. Create report_schedules table
    $sql1 = "
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
    ";
    $conn->exec($sql1);
    echo "<li style='color: green;'>Successfully created or verified table: <strong>report_schedules</strong></li>";

    // 2. Create student_report_overrides table
    $sql2 = "
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
    ";
    $conn->exec($sql2);
    echo "<li style='color: green;'>Successfully created or verified table: <strong>student_report_overrides</strong></li>";

    echo "</ul>";
    echo "<h2><span style='color: blue;'>Update Complete!</span></h2>";
    echo "<p><strong>Important:</strong> Please delete this file (`run_database_updates.php`) from your server immediately for security reasons.</p>";

} catch (PDOException $e) {
    echo "</ul>";
    echo "<h2><span style='color: red;'>Error during update!</span></h2>";
    echo "<p>Error Details: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
