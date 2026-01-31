<?php
include 'includes/db.php';
try {
    echo "--- students (first 5) ---\n";
    $students = $conn->query("SELECT id, reg_no, name FROM students LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($students);

    echo "\n--- project_topics (first 5) ---\n";
    $topics = $conn->query("SELECT id, topic, student_id, student_name, status FROM project_topics LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($topics);
} catch (Exception $e) {
    echo $e->getMessage();
}
