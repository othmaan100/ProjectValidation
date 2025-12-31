<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'sup') {
    header("Location: sup_dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staff_no = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($staff_no) || empty($password)) {
        $error = "Please enter both Staff Number and Password.";
    } else {
        // Authentication strictly via 'users' table
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'sup' AND is_active = '1'");
        $stmt_user->execute([$staff_no]);
        $user_row = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($user_row && password_verify($password, $user_row['password'])) {
            // Success - fetch profile data from supervisors table
            $stmt_sup = $conn->prepare("SELECT * FROM supervisors WHERE id = ?");
            $stmt_sup->execute([$user_row['id']]);
            $supervisor = $stmt_sup->fetch(PDO::FETCH_ASSOC);

            if ($supervisor) {
                $_SESSION['user_id'] = $supervisor['id'];
                $_SESSION['staff_no'] = $supervisor['staff_no'];
                $_SESSION['name'] = $supervisor['name'];
                $_SESSION['role'] = 'sup';
                $_SESSION['dept'] = $supervisor['department'];
                
                header("Location: sup_dashboard.php");
                exit();
            } else {
                $error = "Supervisor profile not found. Contact Admin.";
            }
        } else {
            $error = "Invalid Staff Number or Password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Login | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --secondary: #224abe; --glass: rgba(255, 255, 255, 0.95); }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            height: 100vh; margin: 0; 
            display: flex; align-items: center; justify-content: center; 
        }
        .login-card { 
            background: var(--glass); padding: 40px; border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); width: 100%; max-width: 400px; 
            backdrop-filter: blur(10px); text-align: center;
        }
        .logo-circle {
            width: 70px; height: 70px; background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .logo-circle i { font-size: 30px; color: var(--primary); }
        h1 { color: #2d3436; font-size: 26px; margin-bottom: 8px; }
        p { color: #636e72; font-size: 14px; margin-bottom: 30px; }
        .form-group { text-align: left; margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2d3436; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 12px; font-family: inherit; transition: 0.3s; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary); outline: none; }
        .btn { 
            width: 100%; padding: 14px; border: none; border-radius: 12px; 
            background: var(--primary); color: white; font-weight: 700; cursor: pointer; 
            transition: 0.3s; font-size: 16px; margin-top: 10px;
        }
        .btn:hover { background: var(--secondary); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .alert { background: #ffebee; color: #c62828; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; display: <?= $error ? 'block' : 'none' ?>; }
        .footer-hint { margin-top: 25px; font-size: 13px; color: var(--primary); font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-circle">
            <i class="fas fa-user-tie"></i>
        </div>
        <h1>Supervisor Login</h1>
        <p>Access your student supervision portal</p>

        <div class="alert"><?= $error ?></div>

        <form method="POST">
            <div class="form-group">
                <label>Staff Number</label>
                <input type="text" name="username" class="form-control" placeholder="SP/R/..." required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Login to Portal</button>
        </form>
    </div>
</body>
</html>
