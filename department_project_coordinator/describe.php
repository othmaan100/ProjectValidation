<?php
include_once __DIR__ . '/../includes/db.php';
$stmt = $conn->query("SHOW CREATE TABLE submission_schedules");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $conn->query("SHOW CREATE TABLE student_submission_overrides");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
