<?php
include_once __DIR__ . '/../includes/auth.php';

// Redirect to the main dashboard
if ($_SESSION['role'] === 'dpc') {
    header("Location: dpc_dashboard.php");
} else {
    header("Location: " . PROJECT_ROOT);
}
exit();
?>

