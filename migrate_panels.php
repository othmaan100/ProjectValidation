<?php
include_once 'includes/db.php';

try {
    $conn->exec("ALTER TABLE defense_panels ADD COLUMN panel_type ENUM('proposal', 'internal', 'external') DEFAULT 'proposal' AFTER panel_name");
    echo "Column 'panel_type' added successfully to 'defense_panels'.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'panel_type' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
