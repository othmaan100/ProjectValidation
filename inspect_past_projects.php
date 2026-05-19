<?php
include 'includes/db.php';
$stmt = $conn->query("DESCRIBE past_projects");
echo "--- Table: past_projects ---\n";
while($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
