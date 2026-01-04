<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /projectval/");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4338ca; }
        body { font-family: 'Outfit', sans-serif; background: #f1f5f9; padding: 20px; }
        .container { max-width: 800px; margin: 40px auto; background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .feature-coming { text-align: center; padding: 60px 0; }
        .feature-coming i { font-size: 64px; color: var(--primary); margin-bottom: 20px; opacity: 0.2; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    <div class="container">
        <div class="feature-coming">
            <i class="fas fa-tools"></i>
            <h1>Global Settings</h1>
            <p style="color: #64748b; margin-top: 10px;">This module is under development. Soon you will be able to manage academic sessions and system-wide configurations.</p>
            <a href="sa_dashboard.php" style="display: inline-block; margin-top: 30px; color: var(--primary); font-weight: 700; text-decoration: none;"><i class="fas fa-arrow-left"></i> Return to Dashboard</a>
        </div>
    </div>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
