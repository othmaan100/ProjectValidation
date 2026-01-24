<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dynamically determine the project root URL path
$script_directory = str_replace('\\', '/', dirname(__DIR__));
$document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$base_path = str_replace($document_root, '', $script_directory);
$base_path = '/' . ltrim($base_path, '/') . '/';
$base_path = str_replace('//', '/', $base_path);
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', $base_path);
}

// Set session timeout to 30 minutes
$inactive = 1800; // 30 minutes in seconds
if (isset($_SESSION['timeout'])) {
    $session_life = time() - $_SESSION['timeout'];
    if ($session_life > $inactive) {
        session_unset();
        session_destroy();
        header("Location: " . PROJECT_ROOT);
        exit();
    }
}
$_SESSION['timeout'] = time();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Fetch role from session
$role = $_SESSION['role'];

// Redirect based on role
if ($role === 'fpc' && !strpos($_SERVER['PHP_SELF'], 'faculty_project_coordinator')) {
    header("Location: " . PROJECT_ROOT . "faculty_project_coordinator/fpc_dashboard.php");
    exit();
} elseif ($role === 'dpc' && !strpos($_SERVER['PHP_SELF'], 'department_project_coordinator')) {
    header("Location: " . PROJECT_ROOT . "department_project_coordinator/dpc_dashboard.php");
    exit();
} elseif ($role === 'sup' && !strpos($_SERVER['PHP_SELF'], 'supervisor')) {
    header("Location: " . PROJECT_ROOT . "supervisor/sup_dashboard.php");
    exit();
} elseif ($role === 'stu' && !strpos($_SERVER['PHP_SELF'], 'student')) {
    header("Location: " . PROJECT_ROOT . "student/stu_dashboard.php");
    exit();
} elseif ($role === 'admin' && !strpos($_SERVER['PHP_SELF'], 'super_admin')) {
    header("Location: " . PROJECT_ROOT . "super_admin/sa_dashboard.php");
    exit();
} elseif ($role === 'lib' && !strpos($_SERVER['PHP_SELF'], 'library')) {
    header("Location: " . PROJECT_ROOT . "library/lib_dashboard.php");
    exit();
}

?>
