<?php
session_start();
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

if ($_SESSION['role'] !== 'hod') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$student_id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
$stmt->execute([$student_id]);

header("Location: hod_manage_students.php");
exit();
?>

