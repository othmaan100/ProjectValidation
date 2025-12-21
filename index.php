<?php
session_start();
include __DIR__ . '/includes/db.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
    
    if (empty($input_username) || empty($input_password)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = '1'");
            $stmt->execute([$input_username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($input_password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                $update_stmt = $conn->prepare("UPDATE users SET first_login = NOW(), session = ? WHERE id = ?");
                $session_id = session_id();
                $update_stmt->execute([$session_id, $user['id']]);
                
                // Redirect based on role
                $redirects = [
                    'stu' => "student/stu_dashboard.php",
                    'dpc' => "department_project_coordinator/dpc_dashboard.php",
                    'fpc' => "faculty_project_coordinator/fpc_dashboard.php",
                    'sup' => "supervisor/sup_dashboard.php"
                ];
                
                $location = isset($redirects[$user['role']]) ? $redirects[$user['role']] : "index.php";
                header("Location: $location");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch(PDOException $e) {
            $error_message = "Authentication error. Please try again later.";
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("UPDATE users SET session = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    session_destroy();
    header("Location: index.php?logged_out=1");
    exit();
}

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Validation System | Welcome</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #ec4899;
            --glass: rgba(255, 255, 255, 0.9);
            --text-dark: #1e293b;
            --text-light: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, #f8fafc, #e2e8f0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        /* Animated Background Shapes */
        .shape {
            position: fixed;
            z-index: -1;
            filter: blur(80px);
            border-radius: 50%;
            opacity: 0.4;
            animation: move 20s infinite alternate;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            background: var(--primary);
            top: -10%;
            right: -5%;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            background: var(--secondary);
            bottom: -5%;
            left: -5%;
            animation-delay: -5s;
        }

        @keyframes move {
            from { transform: translate(0, 0); }
            to { transform: translate(50px, 100px); }
        }

        .container {
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            background: var(--glass);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.5);
            margin: 20px;
        }

        /* Left Side: Info */
        .info-panel {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #2563eb 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .info-panel h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .info-panel p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .feature-list {
            list-style: none;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .feature-item i {
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 14px;
        }

        /* Right Side: Form */
        .form-panel {
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 28px;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 18px 20px 18px 55px;
            border: 2px solid #eef2f6;
            border-radius: 15px;
            font-size: 16px;
            font-family: inherit;
            color: var(--text-dark);
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .form-group input:focus + i {
            color: var(--primary);
        }

        .btn-login {
            width: 100%;
            padding: 18px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border-left: 4px solid #22c55e;
        }

        /* Logged In View */
        .logged-view {
            text-align: center;
            padding: 60px;
        }

        .logged-view h2 { font-size: 32px; margin-bottom: 20px; color: var(--text-dark); }
        .user-card {
            background: #f8fafc;
            padding: 30px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }

        .role-badge {
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .dash-links {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-secondary {
            text-decoration: none;
            padding: 15px 30px;
            background: white;
            color: var(--text-dark);
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        @media (max-width: 850px) {
            .container { grid-template-columns: 1fr; }
            .info-panel { display: none; }
            .form-panel { padding: 40px; }
        }
    </style>
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="container">
        <?php if ($is_logged_in): ?>
            <div class="form-panel" style="grid-column: span 2;">
                <div class="logged-view">
                    <h2>Welcome Back, <?php echo htmlspecialchars($_SESSION['name'] ?: $_SESSION['username']); ?>!</h2>
                    <div class="user-card">
                        <p style="color: var(--text-light); margin-bottom: 10px;">Logged in as:</p>
                        <span class="role-badge"><?php echo strtoupper($_SESSION['role']); ?></span>
                        <p style="margin-top: 15px; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                    </div>
                    <div class="dash-links">
                        <a href="<?php 
                            $redirects = ['stu'=>'student/stu_dashboard.php', 'dpc'=>'department_project_coordinator/dpc_dashboard.php', 'fpc'=>'faculty_project_coordinator/fpc_dashboard.php', 'sup'=>'supervisor/sup_dashboard.php'];
                            echo $redirects[$_SESSION['role']] ?? '#';
                        ?>" class="btn-login" style="text-decoration: none; width: auto; padding: 15px 40px;">Enter Dashboard</a>
                        <a href="?logout=1" class="btn-secondary">Logout</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="info-panel">
                <h1>Smart Project Topic Validation</h1>
                <p>Ensuring academic originality through advanced similarity detection and streamlined coordination.</p>
                <ul class="feature-list">
                    <li class="feature-item"><i class="fas fa-microscope"></i> AI-Powered Origin Check</li>
                    <li class="feature-item"><i class="fas fa-layer-group"></i> Multi-tier Review System</li>
                    <li class="feature-item"><i class="fas fa-bolt"></i> Real-time Coordination</li>
                    <li class="feature-item"><i class="fas fa-shield-halved"></i> Role-based Access</li>
                </ul>
            </div>
            <div class="form-panel">
                <div class="form-header">
                    <h2>Secure Access</h2>
                    <p>Enter your credentials to continue</p>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['logged_out'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Successfully logged out.
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Username" required autofocus>
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                        <i class="fas fa-lock"></i>
                    </div>
                    <button type="submit" class="btn-login">Login to System</button>
                    
                    <p style="text-align: center; margin-top: 30px; font-size: 14px; color: var(--text-light);">
                        Need assistance? Contact the <a href="#" style="color: var(--primary); text-decoration: none; font-weight: 600;">System Administrator</a>
                    </p>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>