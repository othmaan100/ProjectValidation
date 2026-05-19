<?php
include 'includes/db.php';

$tables_to_update = [
    'students' => "ALTER TABLE students ADD COLUMN session VARCHAR(50) DEFAULT '' AFTER department",
    'supervision' => "ALTER TABLE supervision ADD COLUMN session VARCHAR(50) DEFAULT '' AFTER student_id",
    'defense_panels' => "ALTER TABLE defense_panels ADD COLUMN session VARCHAR(50) DEFAULT '' AFTER defense_date",
    'report_schedules' => "ALTER TABLE report_schedules ADD COLUMN session VARCHAR(50) DEFAULT '' AFTER department_id",
    'submission_schedules' => "ALTER TABLE submission_schedules ADD COLUMN session VARCHAR(50) DEFAULT '' AFTER department_id"
];

// Fetch current session
$stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_session'");
$current_session = $stmt->fetchColumn() ?: '2025/2026';

foreach ($tables_to_update as $table => $query) {
    try {
        $conn->exec($query);
        echo "Added session column to $table\n";
        
        // Populate existing records with current session
        $conn->exec("UPDATE $table SET session = '$current_session' WHERE session = '' OR session IS NULL");
        echo "Updated existing records in $table to '$current_session'\n";
    } catch (PDOException $e) {
        echo "Error on $table: " . $e->getMessage() . "\n";
    }
}
?>
