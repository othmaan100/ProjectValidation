<?php
session_start();
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$student_id = $_GET['id'];
$stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
$stmt->execute([$student_id]);

header("Location: dpc_manage_students.php");
exit();
?>

