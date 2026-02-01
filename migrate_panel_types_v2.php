<?php
include_once __DIR__ . '/includes/db.php';

try {
    // Check if panel_type already exists in defense_panels
    $check = $conn->query("SHOW COLUMNS FROM defense_panels LIKE 'panel_type'");
    if ($check->rowCount() == 0) {
        $conn->exec("ALTER TABLE defense_panels ADD panel_type ENUM('proposal', 'internal', 'external') NOT NULL DEFAULT 'proposal' AFTER panel_name");
        echo "Successfully added panel_type to defense_panels table.\n";
    } else {
        echo "panel_type already exists in defense_panels.\n";
    }

    // Add panel_type to student_panel_assignments
    $check = $conn->query("SHOW COLUMNS FROM student_panel_assignments LIKE 'panel_type'");
    if ($check->rowCount() == 0) {
        $conn->exec("ALTER TABLE student_panel_assignments ADD panel_type ENUM('proposal', 'internal', 'external') NOT NULL DEFAULT 'proposal' AFTER panel_id");
        echo "Successfully added panel_type to student_panel_assignments table.\n";
    } else {
        echo "panel_type already exists in student_panel_assignments.\n";
    }

    // Add unique index to student_panel_assignments to ensure 1 student 1 panel per stage per session
    try {
        $conn->exec("ALTER TABLE student_panel_assignments ADD UNIQUE KEY `unique_student_panel_type` (student_id, academic_session, panel_type)");
        echo "Added unique index to student_panel_assignments.\n";
    } catch (Exception $e) {
        echo "Index might already exist or error: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
