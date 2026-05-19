<?php
include 'includes/db.php';
$tables = ['defense_panels', 'panel_members', 'student_panel_assignments', 'defense_scores'];
foreach ($tables as $table) {
    echo "--- TABLE: $table ---\n";
    try {
        $stmt = $conn->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
