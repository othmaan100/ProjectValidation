<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Check if the user is logged in as DPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dpc_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pw'])) {
    $current_pw = $_POST['current_password'];
    $new_pw = $_POST['new_password'];
    $confirm_pw = $_POST['confirm_password'];

    // Basic validation
    if (empty($current_pw) || empty($new_pw) || empty($confirm_pw)) {
        $error = "All fields are required.";
    } elseif ($new_pw !== $confirm_pw) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pw) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$dpc_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_pw, $user['password'])) {
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $dpc_id])) {
                $message = "Password updated successfully!";
            } else {
                $error = "Failed to update password.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - DPC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; }
        .page-container { max-width: 500px; margin: 60px auto; padding: 20px; }
        .card { background: var(--glass); padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); backdrop-filter: blur(10px); }
        .card h1 { color: #2d3436; font-size: 24px; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #636e72; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 12px; font-family: inherit; transition: 0.3s; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary); outline: none; }
        .btn { width: 100%; padding: 14px; border: none; border-radius: 12px; background: var(--primary); color: white; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn:hover { background: var(--secondary); transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 600; text-align: center; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-danger { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: white; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="card">
            <h1><i class="fas fa-key" style="color: var(--primary); margin-right: 10px;"></i>Change Password</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="change_pw" class="btn">Update Password</button>
            </form>
        </div>
        <a href="dpc_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>

