<?php
session_start();

// Include the database connection file
include __DIR__ . '/includes/db.php';

// Redirect to the login page if the student is not logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: stu_login.php");
    exit();
}

// Redirect to the dashboard if it's not the first login
if (!$_SESSION['first_login']) {
    header("Location: stu_dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in the database
        $stmt = $conn->prepare("UPDATE students SET password = :password, first_login = FALSE WHERE id = :student_id");
        $stmt->execute([
            ':password' => $hashed_password,
            ':student_id' => $_SESSION['student_id']
        ]);

        // Update the session variable to indicate that the first login is complete
        $_SESSION['first_login'] = FALSE;

        // Redirect to the student dashboard
        header("Location: stu_dashboard.php");
        exit();
    } else {
        $error_message = "Passwords do not match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Change Password</h1>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>
            <br>
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            <br>
            <button type="submit">Change Password</button>
        </form>
    </div>
</body>
</html>