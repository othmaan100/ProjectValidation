<?php
include 'includes/db.php';
$students = $conn->query("SELECT * FROM students LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$topics = $conn->query("SELECT * FROM project_topics LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "STUDENT SAMPLE:\n"; print_r($students);
echo "\nTOPIC SAMPLE:\n"; print_r($topics);
?>
