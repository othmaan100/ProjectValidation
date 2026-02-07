<?php
session_start();

// Include the database connection file
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';

// Redirect to the login page if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'stu') {
    header("Location: stu_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_pw'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Session expired. Please refresh and try again.";
    } else {
        $new_password = $_POST['new_password'];
    }
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password)) {
        $error = "Please enter a new password.";
    } elseif ($new_password === $confirm_password) {
        if (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $conn->beginTransaction();
            try {
                // Update first_login status in students
                $stmt1 = $conn->prepare("UPDATE students SET first_login = 0 WHERE id = :id");
                $stmt1->execute([':id' => $user_id]);

                // Update users table (primary authentication source)
                $stmt2 = $conn->prepare("UPDATE users SET password = :pw WHERE id = :id");
                $stmt2->execute([':pw' => $hashed_password, ':id' => $user_id]);

                $conn->commit();
                header("Location: stu_dashboard.php?pwd_updated=1");
                exit();
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Update failed: " . $e->getMessage();
                }

        }
    } else {
        $error = "Passwords do not match.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --glass: rgba(255, 255, 255, 0.95); }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            height: 100vh; 
            margin: 0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .card { 
            background: var(--glass); 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            width: 100%; 
            max-width: 400px; 
            backdrop-filter: blur(10px); 
            text-align: center;
        }
        .card h1 { color: #2d3436; font-size: 24px; margin-bottom: 30px; }
        .form-group { text-align: left; margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #636e72; font-size: 14px; }
        .form-control { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #eee; 
            border-radius: 12px; 
            font-family: inherit; 
            transition: 0.3s; 
            box-sizing: border-box;
        }
        .form-control:focus { border-color: var(--primary); outline: none; }
        .btn { 
            width: 100%; 
            padding: 14px; 
            border: none; 
            border-radius: 12px; 
            background: var(--primary); 
            color: white; 
            font-weight: 700; 
            cursor: pointer; 
            transition: 0.3s; 
            font-size: 16px;
            margin-top: 10px;
        }
        .btn:hover { background: var(--secondary); transform: translateY(-2px); }
        .alert { background: #ffebee; color: #c62828; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h1><i class="fas fa-lock" style="color: var(--primary); margin-right: 15px;"></i>Change Password</h1>
        
        <?php if ($error): ?>
            <div class="alert"><?= $error ?></div>
        <?php endif; ?>

        <p style="color: #636e72; font-size: 14px; margin-bottom: 25px;">Please set a new password for your account.</p>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" name="change_pw" class="btn">Update and Continue</button>
        </form>
    </div>
</body>
</html>
