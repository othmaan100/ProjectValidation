<?php
session_start();

// Set session timeout to 30 minutes
$inactive = 1800; // 30 minutes in seconds
if (isset($_SESSION['timeout'])) {
    $session_life = time() - $_SESSION['timeout'];
    if ($session_life > $inactive) {
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit();
    }
}
$_SESSION['timeout'] = time();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch role from session
$role = $_SESSION['role'];

// Redirect based on role
if ($role === 'fpc' && !strpos($_SERVER['PHP_SELF'], 'faculty_project_coordinator')) {
    header("Location: faculty_project_coordinator/fpc_dashboard.php");
    exit();
} elseif ($role === 'dpc' && !strpos($_SERVER['PHP_SELF'], 'department_project_coordinator')) {
    header("Location: department_project_coordinator/dpc_dashboard.php");
    exit();
} elseif ($role === 'sup' && !strpos($_SERVER['PHP_SELF'], 'supervisor')) {
    header("Location: supervisor/sup_dashboard.php");
    exit();
} elseif ($role === 'stu' && !strpos($_SERVER['PHP_SELF'], 'student')) {
    header("Location: student/stu_dashboard.php");
    exit();
}

?>
