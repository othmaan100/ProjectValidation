<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;

    // Validate input
    if (empty($username) || empty($password)) {
        die("Staff number and password are required.");
    }

    // Fetch the supervisor from the supervisors table
    $stmt = $conn->prepare("SELECT * FROM supervisors WHERE staff_no = ?");
    $stmt->execute([$username]); // Use the staff number (username) to fetch the supervisor
    $supervisor = $stmt->fetch();

    if ($supervisor) {
        // Verify the password (assuming it's stored as an MD5 hash)
        if (md5($password) === $supervisor['password']) {
            // Login successful
            $_SESSION['supervisor_id'] = $supervisor['id'];
            $_SESSION['staff_no'] = $supervisor['staff_no'];
            $_SESSION['name'] = $supervisor['name'];
            $_SESSION['email'] = $supervisor['email'];
            $_SESSION['department'] = $supervisor['department']; // Ensure this matches your column name

            header("Location: sup_dashboard.php");
            exit();
        } else {
            // Invalid password
            echo "Invalid credentials!";
        }
    } else {
        // Supervisor not found
        echo "Invalid credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Supervisor Login</h1>
        <form method="POST">
            <input type="text" name="username" placeholder="Staff Number" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>