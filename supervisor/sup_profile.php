<?php
session_start();
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$supervisor_id = $_SESSION['user_id'];
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
        $phone = trim($_POST['phone']);

        if (empty($name) || empty($email)) {
            $message = "Name and Email are required.";
            $status = "danger";
        } else {
            try {
                $conn->beginTransaction();
                
                // Update supervisors table
                $stmt = $conn->prepare("UPDATE supervisors SET name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $supervisor_id]);

                // Update users table
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $supervisor_id]);

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
$stmt = $conn->prepare("SELECT * FROM supervisors WHERE id = ?");
$stmt->execute([$supervisor_id]);
$supervisor = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Supervisor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --success: #1cc88a; --danger: #e74a3b; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fc; margin: 0; }
        .page-container { max-width: 600px; margin: 50px auto; padding: 20px; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .card h1 { margin-top: 0; color: #2c3e50; font-size: 24px; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #4e73df; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #d1d3e2; border-radius: 10px; box-sizing: border-box; }
        .btn { width: 100%; padding: 14px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #224abe; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        .alert-danger { background: #ffebee; color: #c62828; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    <div class="page-container">
        <div class="card">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $status ?>"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group">
                    <label>Staff Number (Read-only)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($supervisor['staff_no']) ?>" disabled style="background: #eaecf4;">
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($supervisor['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($supervisor['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($supervisor['phone']) ?>" placeholder="e.g. 08012345678">
                    <small style="color: #858796;">This phone number will be visible to your allocated students.</small>
                </div>

                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>

            <a href="index.php" style="display: block; text-align: center; margin-top: 20px; color: #858796; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
