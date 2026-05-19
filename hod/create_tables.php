<?php
include_once __DIR__ . '/../includes/db.php';
try {
    $conn->exec('CREATE TABLE IF NOT EXISTS report_schedules (id INT AUTO_INCREMENT PRIMARY KEY, department_id INT, submission_start DATETIME, submission_end DATETIME, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY(department_id))');
    $conn->exec('CREATE TABLE IF NOT EXISTS student_report_overrides (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, submission_start DATETIME, submission_end DATETIME, is_active TINYINT(1) DEFAULT 1, UNIQUE KEY(student_id))');
    echo "Tables created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
