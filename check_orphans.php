<?php
include 'includes/db.php';
try {
    $orphans = $conn->query("SELECT COUNT(*) FROM students s LEFT JOIN departments d ON s.department = d.id WHERE d.id IS NULL")->fetchColumn();
    echo "Students with orphaned department IDs: $orphans\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
