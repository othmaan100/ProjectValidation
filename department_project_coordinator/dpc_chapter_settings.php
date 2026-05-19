<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dpc_id = $_SESSION['user_id'];

// Fetch the DPC's department info
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name, d.num_chapters 
                        FROM users u 
                        JOIN departments d ON u.department = d.id 
                        WHERE u.id = ?");
$stmt->execute([$dpc_id]);
$dpc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $dpc_info['dept_id'];
$dept_name = $dpc_info['department_name'];
$current_chapters = $dpc_info['num_chapters'] ?? 5;

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_chapters'])) {
    $num_chapters = intval($_POST['num_chapters']);
    if ($num_chapters > 0 && $num_chapters <= 10) {
        $stmt = $conn->prepare("UPDATE departments SET num_chapters = ? WHERE id = ?");
        $stmt->execute([$num_chapters, $dept_id]);
        $_SESSION['success'] = "Number of project chapters updated to $num_chapters.";
    } else {
        $_SESSION['error'] = "Invalid number of chapters (1-10 allowed).";
    }
    header("Location: dpc_chapter_settings.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter Settings - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= PROJECT_ROOT ?>assets/css/styles.css">
    <style>
        .settings-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 40px auto;
        }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: 600; color: #2d3436; }
        .form-control { width: 100%; padding: 15px; border: 2px solid #eee; border-radius: 12px; font-size: 16px; }
        .btn { padding: 15px 30px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; background: #4e73df; color: white; width: 100%; font-size: 16px; }
        .btn:hover { background: #2e59d9; transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #d4edda; color: #155724; border-left: 5px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 5px solid #dc3545; }
    </style>
</head>
<body style="background: #f0f2f5;">
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div> <!-- Close header's container -->
    
    <div class="container">
        <div class="settings-card">
            <h1 style="color: #1a202c; margin-bottom: 10px;"><i class="fas fa-cog"></i> Project Chapter Settings</h1>
            <p style="color: #718096; margin-bottom: 30px;">Set the total number of chapters required for students in the <?= htmlspecialchars($dept_name) ?> department.</p>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="num_chapters">Total Number of Chapters</label>
                    <input type="number" name="num_chapters" id="num_chapters" class="form-control" value="<?= $current_chapters ?>" min="1" max="10" required>
                    <small style="color: #a0aec0; display: block; margin-top: 8px;">Standard projects usually have 5 chapters.</small>
                </div>
                <button type="submit" name="update_chapters" class="btn">Update Settings</button>
            </form>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
