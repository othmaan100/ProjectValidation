<?php
include 'includes/db.php';
$target_tables = ['system_settings', 'project_topics'];
foreach ($target_tables as $table) {
    echo "Table: $table\n";
    try {
        $stmt = $conn->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  {$row['Field']} - {$row['Type']}\n";
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
