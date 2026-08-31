<?php
include_once __DIR__ . '/includes/db.php';

try {
    // Check if columns exist
    $stmt = $conn->query("SHOW COLUMNS FROM project_topics LIKE 'source_code_path'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE project_topics ADD COLUMN source_code_path VARCHAR(255) DEFAULT NULL");
        echo "Column 'source_code_path' added.\n";
    } else {
        echo "Column 'source_code_path' already exists.\n";
    }

    $stmt = $conn->query("SHOW COLUMNS FROM project_topics LIKE 'source_code_status'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE project_topics ADD COLUMN source_code_status ENUM('not_submitted', 'pending', 'approved', 'rejected') DEFAULT 'not_submitted'");
        echo "Column 'source_code_status' added.\n";
    } else {
        echo "Column 'source_code_status' already exists.\n";
    }

    $stmt = $conn->query("SHOW COLUMNS FROM project_topics LIKE 'source_code_feedback'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE project_topics ADD COLUMN source_code_feedback TEXT DEFAULT NULL");
        echo "Column 'source_code_feedback' added.\n";
    } else {
        echo "Column 'source_code_feedback' already exists.\n";
    }

    echo "Schema update completed successfully.\n";
} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
