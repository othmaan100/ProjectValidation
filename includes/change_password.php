<?php
session_start();
include 'includes/auth.php';
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch the current user's password
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    if ($role === 'stu') {
        $stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    }
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Verify the current password
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Update the password
            if ($role === 'stu') {
                $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            }
            $stmt->execute([$hashed_password, $user_id]);

            echo "Password changed successfully!";
        } else {
            echo "New passwords do not match.";
        }
    } else {
        echo "Current password is incorrect.";
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
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Change Password</h1>
        <form method="POST">
            <input type="password" name="current_password" placeholder="Current Password" required>
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" name="change_password">Change Password</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>