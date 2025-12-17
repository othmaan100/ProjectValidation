<?php
session_start();
// if (!isset($_SESSION['user_id'])) {
//     // Redirect to the appropriate dashboard based on the user's role
//      header("location:stu_login.php");
//     exit();
// }
?>
    
<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Topics Validation System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?> <!-- Include the header file -->

    <div class="container">
        <h2>Choose Your Role to Login</h2>
        <div class="dashboard">
            <div class="card">
                <h3>Faculty Project Coordinator</h3>
                <p>Manage the entire system, create DPCs, and upload past projects.</p>
                <a href="fpc_login.php" class="button">Login as FPC</a>
            </div>
            <div class="card">
                <h3>Departmental Project Coordinator</h3>
                <p>Validate topics, manage students, and assign supervisors.</p>
                <a href="dpc_login.php" class="button">Login as DPC</a>
            </div>
            <div class="card">
                <h3>Supervisor</h3>
                <p>View assigned students and their topics.</p>
                <a href="sup_login.php" class="button">Login as Supervisor</a>
            </div>
            <div class="card">
                <h3>Student</h3>
                <p>Submit project topics and view feedback.</p>
                <a href="stu_login.php" class="button">Login as Student</a>
            </div>
        </div>
    </div>
</body>
</html>