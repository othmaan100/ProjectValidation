<?php
include_once __DIR__ . '/includes/db.php';

try {
    $conn->exec("ALTER TABLE defense_panels ADD COLUMN max_students INT DEFAULT 10 AFTER department_id");
    echo "Column max_students added successfully.";
} catch (Exception $e) {
    echo "Error or column already exists: " . $e->getMessage();
}
?>
