<?php
include 'includes/db.php';
$stmt = $conn->query('SELECT * FROM system_settings');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
