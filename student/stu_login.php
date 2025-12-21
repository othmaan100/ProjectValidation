<?php
session_start();

// Redirect to the dashboard if already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'stu') {
    header("Location: stu_dashboard.php");
    exit();
}

include_once __DIR__ . '/../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_no = trim($_POST['reg_no']);
    $password = trim($_POST['password']);

    if (empty($reg_no) || empty($password)) {
        $error = "Please enter both Registration Number and Password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM students WHERE reg_no = :reg_no");
        $stmt->execute([':reg_no' => $reg_no]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // First login logic: check if password matches reg_no
            if ($student['first_login'] == 1) {
                if ($password === $student['reg_no']) {
                    $_SESSION['user_id'] = $student['id'];
                    $_SESSION['reg_no'] = $student['reg_no'];
                    $_SESSION['role'] = 'stu';
                    header("Location: stu_change_password.php");
                    exit();
                } else {
                    $error = "First login? Use your Registration Number as the password.";
                }
            } else {
                // Regular login with hashed password
                if (password_verify($password, $student['password'])) {
                    $_SESSION['user_id'] = $student['id'];
                    $_SESSION['reg_no'] = $student['reg_no'];
                    $_SESSION['role'] = 'stu';
                    header("Location: stu_dashboard.php");
                    exit();
                } else {
                    $error = "Invalid password. Please try again.";
                }
            }
        } else {
            $error = "Registration number not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | Project Pro</title>
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
        .login-card { 
            background: var(--glass); 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            width: 100%; 
            max-width: 400px; 
            backdrop-filter: blur(10px); 
            text-align: center;
        }
        .login-card h1 { color: #2d3436; font-size: 28px; margin-bottom: 10px; }
        .login-card p { color: #636e72; font-size: 14px; margin-bottom: 30px; }
        .form-group { text-align: left; margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2d3436; font-size: 14px; }
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
        }
        .btn:hover { background: var(--secondary); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .alert { 
            background: #ffebee; 
            color: #c62828; 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            font-size: 14px; 
            font-weight: 600; 
            display: <?= $error ? 'block' : 'none' ?>; 
        }
        .first-login-hint { margin-top: 25px; font-size: 13px; color: var(--secondary); font-weight: 600; }
        .logo-circle {
            width: 70px; height: 70px; background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .logo-circle i { font-size: 30px; color: var(--primary); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-circle">
            <i class="fas fa-user-graduate"></i>
        </div>
        <h1>Student Portal</h1>
        <p>Login to manage your project topics</p>

        <div class="alert"><?= $error ?></div>

        <form method="POST">
            <div class="form-group">
                <label>Registration Number</label>
                <input type="text" name="reg_no" class="form-control" placeholder="e.g. FCP/CSC/19/..." required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Login to Dashboard</button>
        </form>

        <div class="first-login-hint">
            <i class="fas fa-info-circle"></i> First login? Use Reg No as password.
        </div>
    </div>
</body>
</html>
