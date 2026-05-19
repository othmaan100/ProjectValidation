<?php
include 'includes/db.php';
$tables = ['students', 'project_topics', 'supervision', 'supervisors'];
foreach($tables as $table) {
    echo "TABLE: $table\n";
    $stmt = $conn->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($columns as $col) {
        if ($col['Field'] == 'session' || $col['Field'] == 'academic_session' || $col['Field'] == 'project_session') {
            echo "-> has session column: " . $col['Field'] . "\n";
        }
    }
}
?>
