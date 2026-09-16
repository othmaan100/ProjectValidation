<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT evaluation_remind_count, evaluation_submitted FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || $row['evaluation_submitted'] == 1) {
    echo json_encode(['success' => true, 'dismissed' => true]);
    exit();
}

$max_reminders = 2;
if ((int)$row['evaluation_remind_count'] < $max_reminders) {
    $stmt = $conn->prepare("UPDATE users SET evaluation_remind_count = evaluation_remind_count + 1 WHERE id = ?");
    $stmt->execute([$user_id]);
}

// Suppress the gate for the rest of this login session ("next login" = until re-authentication)
$_SESSION['evaluation_reminded_this_login'] = true;

echo json_encode(['success' => true]);
