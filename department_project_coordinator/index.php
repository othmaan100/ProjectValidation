<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPC Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php';  ?>
    <div class="container">
        <h1>Departmental Project Coordinator Dashboard</h1>
        <div class="dashboard">
            <div class="card">
                <h2>Manage Students</h2>
                <p>add, edit, or delete students in your department.</p>
                <a href="dpc_manage_students.php" class="button">Go to Manage Students</a>
            </div>
            <div class="card">
                <h2>Validate Topics</h2>
                <p>Validate student project topics for approval or rejection.</p>
                <a href="dpc_validate_topics.php" class="button">Go to Validate Topics</a>
            </div>
            <div class="card">
                <h2>Assign Supervisors</h2>
                <p>Assign supervisors to students with approved topics.</p>
                <a href="dpc_assign_supervisors.php" class="button">Go to Assign Supervisors</a>
            </div>
        </div>
    </div>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>