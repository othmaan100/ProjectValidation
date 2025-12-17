<?php
session_start();
include __DIR__ . '/includes/db.php';


// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'login') {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);
    
    if (empty($input_username) || empty($input_password)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // Query to check user credentials (without role)
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = '1'");
            $stmt->execute([$input_username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

           
            
            if ($user) {
                // Verify password (assuming passwords are hashed)
                if (password_verify($input_password, $user['password'])) {
                    // Login successful - Set up session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['department'] = $user['department'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // Update last login and session in database
                    $update_stmt = $conn->prepare("UPDATE users SET first_login = NOW(), session = ? WHERE id = ?");
                    $session_id = session_id();
                    $update_stmt->execute([$session_id, $user['id']]);
                    
                    // Redirect based on role fetched from database
                    switch($user['role']) {
                        case 'stu':
                            header("Location: student/stu_dashboard.php");
                            break;
                        case 'dpc':
                            header("Location: department_project_coordinator/dpc_dashboard.php");
                            break;
                        case 'fpc':
                            header("Location: faculty_project_coordinator/fpc_dashboard.php");
                            break;
                        case 'sup':
                            header("Location: supervisor_dashboard.php");
                            break;
                        default:
                            header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error_message = "Invalid username or password.";
                }
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch(PDOException $e) {
            $error_message = "Authentication error. Please try again later.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    // Clear session from database
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("UPDATE users SET session = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    session_destroy();
    $success_message = "You have been logged out successfully.";
}

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $current_user = $_SESSION;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Project Topics Validation System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 480px;
            position: relative;
        }
        
        .system-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .system-header h1 {
            color: #1e3c72;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .system-header .subtitle {
            color: #2a5298;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .system-header p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .features-overview {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f0ff 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 4px solid #2a5298;
        }
        
        .features-overview h3 {
            color: #1e3c72;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .features-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 13px;
            color: #555;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
        }
        
        .feature-item::before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 6px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fafbfc;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2a5298;
            background: white;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }
        

        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 60, 114, 0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            color: #c62828;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
            font-size: 14px;
        }
        
        .success-message {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
            font-size: 14px;
        }
        
        .dashboard-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 600px;
            text-align: center;
        }
        
        .user-welcome {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f0ff 100%);
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            border: 1px solid #e1e8f0;
        }
        
        .user-welcome h2 {
            color: #1e3c72;
            margin-bottom: 15px;
        }
        
        .user-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            text-align: left;
        }
        
        .info-item strong {
            color: #2a5298;
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
        }
        
        .info-item span {
            color: #333;
            font-size: 14px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .role-stu { background: #e3f2fd; color: #1976d2; }
        .role-dcp { background: #fff3e0; color: #f57c00; }
        .role-fcp { background: #e8f5e8; color: #388e3c; }
        .role-sup { background: #fce4ec; color: #c2185b; }
        
        .logout-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }
        
        .system-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e1e8f0;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #2a5298;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <?php if (isset($current_user)): ?>
        <!-- Dashboard for logged-in users -->
        <div class="dashboard-container">
            <div class="system-header">
                <h1>Project Topics Validation System</h1>
                <p class="subtitle">Welcome to Your Dashboard</p>
            </div>
            
            <div class="user-welcome">
                <h2>Hello, <?php echo htmlspecialchars($current_user['name'] ?: $current_user['username']); ?>!</h2>
                
                <div class="user-info-grid">
                    <div class="info-item">
                        <strong>Username</strong>
                        <span><?php echo htmlspecialchars($current_user['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <?php

                        echo $_SESSION['role']; 
                        ?>


                        <strong>Role</strong>
                        <span class="role-badge role-<?php echo $current_user['role']; ?>">
                            <?php 

                            


                            switch($current_user['role']) {
                                case 'stu': echo 'Student'; break;
                                case 'dpc': echo 'Dept. Coordinator'; break;
                                case 'fpc': echo 'Faculty Coordinator'; break;
                                case 'sup': echo 'Supervisor'; break;
                                default: echo ucfirst($current_user['role']);
                            }
                            ?>
                        </span>
                    </div>
                    <?php if ($current_user['department']): ?>
                    <div class="info-item">
                        <strong>Department</strong>
                        <span><?php echo htmlspecialchars($current_user['department']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($current_user['email']): ?>
                    <div class="info-item">
                        <strong>Email</strong>
                        <span><?php echo htmlspecialchars($current_user['email']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="system-stats">
                    <div class="stat-item">
                        <div class="stat-number">24</div>
                        <div class="stat-label">Active Projects</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">12</div>
                        <div class="stat-label">Pending Reviews</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Success Rate</div>
                    </div>
                </div>
            </div>
            
            <p>Access your personalized dashboard to manage project topics, validations, and academic progress.</p>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    <?php else: ?>
        <!-- Login form -->
        <div class="auth-container">
            <div class="system-header">
                <h1>Project Topics Validation System</h1>
                <p class="subtitle">Streamline Your Academic Projects</p>
                <p>Comprehensive platform for managing, validating, and tracking student project topics across departments.</p>
            </div>
            
            <div class="features-overview">
                <h3>Key Features</h3>
                <div class="features-list">
                    <div class="feature-item">Automated topic similarity checking</div>
                    <div class="feature-item">Real-time validation process</div>
                    <div class="feature-item">Department-specific coordination</div>
                    <div class="feature-item">Faculty-level oversight</div>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <input type="hidden" name="action" value="login">
                

                
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" required placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="login-btn">Access System</button>
            </form>
        </div>
    <?php endif; ?>
    
    <script>
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields to continue.');
                return false;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long.');
                return false;
            }
        });
        
        // Auto-hide messages after 6 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.error-message, .success-message');
            messages.forEach(function(msg) {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(function() {
                    if (msg.parentNode) {
                        msg.parentNode.removeChild(msg);
                    }
                }, 500);
            });
        }, 6000);
        
        // Add loading state to login button
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.querySelector('.login-btn');
            btn.innerHTML = 'Authenticating...';
            btn.disabled = true;
        });
    </script>
</body>
</html>