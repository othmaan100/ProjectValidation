<?php
session_start();
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'fpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$status = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = "Invalid CSRF token.";
        $status = "danger";
    } else {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $current_pw = trim($_POST['current_password']);
        $new_pw = trim($_POST['new_password']);
        $confirm_pw = trim($_POST['confirm_password']);

        if (empty($name) || empty($email)) {
            $message = "Name and Email are required.";
            $status = "danger";
        } else {
            try {
                $conn->beginTransaction();
                
                // Fetch current user data
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                // Update basic info
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $user_id]);

                // Update password if provided
                if (!empty($new_pw)) {
                    if (empty($current_pw)) {
                        throw new Exception("Current password is required to change password.");
                    }
                    if (!password_verify($current_pw, $user['password'])) {
                        throw new Exception("Incorrect current password.");
                    }
                    if ($new_pw !== $confirm_pw) {
                        throw new Exception("New passwords do not match.");
                    }
                    if (strlen($new_pw) < 6) {
                        throw new Exception("New password must be at least 6 characters.");
                    }
                    
                    $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed, $user_id]);
                }

                $conn->commit();
                $message = "Profile updated successfully!";
                $status = "success";
            } catch (Exception $e) {
                $conn->rollBack();
                $message = "Error: " . $e->getMessage();
                $status = "danger";
            }
        }
    }
}

// Fetch current details
$stmt = $conn->prepare("SELECT u.*, f.faculty as faculty_name FROM users u LEFT JOIN faculty f ON u.faculty_id = f.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$fpc = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | FPC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4338ca; --success: #059669; --danger: #dc2626; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Outfit', sans-serif; background: #f1f5f9; padding: 20px; }
        .page-container { max-width: 650px; margin: 40px auto; }
        .card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .card h1 { margin-top: 0; color: var(--primary); font-size: 28px; text-align: center; margin-bottom: 30px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; box-sizing: border-box; font-size: 16px; transition: 0.3s; background: #f8fafc; }
        .form-control:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1); }
        
        .divider { height: 1px; background: #e2e8f0; margin: 30px 0; position: relative; }
        .divider span { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 0 15px; color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        
        .btn { width: 100%; padding: 14px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 16px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(67, 56, 202, 0.3); }
        
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #d1fae5; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
        
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #eef2ff; color: #4338ca; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    
    <div class="page-container">
        <div class="card">
            <h1><i class="fas fa-user-shield"></i> FPC Profile</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $status ?>">
                    <i class="fas fa-<?= $status === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username (Read-only)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($fpc['username']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Faculty</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($fpc['faculty_name']) ?>" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($fpc['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($fpc['email']) ?>" required>
                </div>

                <div class="divider"><span>Change Password</span></div>
                <p style="color: #64748b; font-size: 13px; margin-bottom: 20px; text-align: center;">Leave password fields blank if you don't want to change it.</p>

                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Required for password change">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Min. 6 chars">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>

            <a href="index.php" style="display: block; text-align: center; margin-top: 25px; color: #64748b; text-decoration: none; font-weight: 500; font-size: 14px;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
