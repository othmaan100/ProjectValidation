<?php
session_start();
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: index.php");
    exit();
}

// Check if the supervisor ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Supervisor ID is missing.";
    header("Location: dpc_assign_supervisors.php");
    exit();
}

$supervisor_id = $_GET['id'];

// Fetch the supervisor to ensure they exist
$stmt = $conn->prepare("SELECT id FROM supervisors WHERE id = ?");
$stmt->execute([$supervisor_id]);
$supervisor = $stmt->fetch();

if (!$supervisor) {
    $_SESSION['error_message'] = "Supervisor not found.";
    header("Location: dpc_assign_supervisors.php");
    exit();
}

// Delete the supervisor
try {
    $stmt = $conn->prepare("DELETE FROM supervisors WHERE id = ?");
    $stmt->execute([$supervisor_id]);

    // Set success message
    $_SESSION['success_message'] = "Supervisor deleted successfully!";
} catch (PDOException $e) {
    // Handle database errors
    $_SESSION['error_message'] = "An error occurred while deleting the supervisor: " . $e->getMessage();
}

// Redirect back to the supervisors list
header("Location: dpc_assign_supervisors.php");
exit();
?>