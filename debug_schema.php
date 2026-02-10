<?php
include 'includes/db.php';
$tables = ['departments', 'supervision', 'project_topics', 'students'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $stmt = $conn->query("DESCRIBE $table");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
}
?>
