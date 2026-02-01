<?php
include_once __DIR__ . '/includes/db.php';

try {
    $conn->exec("ALTER TABLE defense_panels ADD panel_type ENUM('proposal', 'internal', 'external') NOT NULL DEFAULT 'proposal' AFTER panel_name");
    echo "Successfully added panel_type to defense_panels table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
