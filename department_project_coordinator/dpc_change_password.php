<?php
session_start();
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: index.php");
    exit();
}

// Fetch the DPC's details
$dpcId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT password_changed FROM users WHERE id = ?");
$stmt->execute([$dpcId]);
$dpc = $stmt->fetch();

if (!$dpc) {
    header("Location: index.php");
    exit();
}

// Redirect to dashboard if the password has already been changed
if ($dpc['password_changed']) {
    header("Location: dpc_dashboard.php");
    exit();
}

// Handle form submission to change password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update the DPC's password and mark it as changed
        $stmt = $conn->prepare("UPDATE users SET password = ?, password_changed = 1 WHERE id = ?");
        $stmt->execute([$hashedPassword, $dpcId]);

        echo "Password changed successfully!";
        header("Refresh:1; url=dpc_dashboard.php");
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

        <!-- Display error message if passwords do not match -->
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Change Password Form -->
        <form method="POST">
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit" name="change_password">Change Password</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>