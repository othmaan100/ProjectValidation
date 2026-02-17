<?php
include_once __DIR__ . '/includes/db.php';
$stmt = $conn->query("DESCRIBE users role");
$row = $stmt->fetch();
echo "Users Role: " . $row['Type'] . "\n";

$stmt = $conn->query("SHOW TABLES LIKE 'external_examiners'");
if ($stmt->fetch()) {
    echo "external_examiners table exists.\n";
} else {
    echo "external_examiners table DOES NOT exist.\n";
}
?>
