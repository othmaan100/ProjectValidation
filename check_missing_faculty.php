<?php
include 'includes/db.php';
try {
    $count = $conn->query("SELECT COUNT(*) FROM students WHERE faculty_id IS NULL OR faculty_id = 0")->fetchColumn();
    echo "Students with missing/zero faculty_id: $count\n";

    if ($count > 0) {
        $sample = $conn->query("SELECT id, reg_no, name, faculty_id, department FROM students WHERE faculty_id IS NULL OR faculty_id = 0 LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        print_r($sample);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
