<?php
include 'includes/db.php';
try {
    $count = $conn->query("SELECT COUNT(*) FROM students WHERE department IS NULL OR department = 0")->fetchColumn();
    echo "Students with missing/zero department: $count\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
