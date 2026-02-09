<?php
session_start();

// Redirect if the user is not logged in or is not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'stu') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

include_once __DIR__ . '/../includes/db.php';

$student_id = $_SESSION['user_id'];

// Fetch submitted topics
$stmt = $conn->prepare("SELECT * FROM project_topics WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$student_id]);
$topics = $stmt->fetchAll();

// Fetch supervisor if assigned (if any topic is approved)
$supervisor = null;
$stmt = $conn->prepare("
    SELECT su.name, su.phone, su.email
    FROM supervision sp 
    JOIN supervisors su ON sp.supervisor_id = su.id 
    WHERE sp.student_id = ? AND sp.status = 'active'
");
$stmt->execute([$student_id]);
$supervisor = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Status | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --warning: #f6c23e; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fe; margin: 0; padding-bottom: 50px; }
        .page-container { max-width: 900px; margin: 40px auto; padding: 20px; }
        .main-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .main-card h1 { margin-top: 0; font-size: 24px; color: #2d3436; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px; border-bottom: 2px solid #f1f2f6; color: #636e72; font-size: 13px; text-transform: uppercase; }
        td { padding: 18px; border-bottom: 1px solid #f1f2f6; font-size: 15px; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: #fff8e1; color: #ff8f00; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }

        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 20px; }
        .back-btn:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div>

    <div class="page-container">
        <a href="stu_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="main-card">
            <?php if ($supervisor): ?>
                <div style="background: #f0f7ff; border-radius: 15px; padding: 20px; border: 1px solid #d0e1fd; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h4 style="margin: 0; font-size: 13px; color: #636e72; text-transform: uppercase;">Assigned Supervisor</h4>
                        <p style="margin: 5px 0 0; font-size: 18px; font-weight: 700; color: var(--primary);"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($supervisor['name']) ?></p>
                    </div>
                    <?php if (!empty($supervisor['phone'])): ?>
                    <div style="background: white; padding: 10px 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                        <h4 style="margin: 0; font-size: 11px; color: #b2bec3; text-transform: uppercase;">Contact info</h4>
                        <p style="margin: 2px 0 0; font-size: 15px; font-weight: 700; color: var(--success);"><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($supervisor['phone']) ?></p>
                        <p style="margin: 2px 0 0; font-size: 13px; font-weight: 500; color: #636e72;"><i class="fas fa-envelope"></i> <?= htmlspecialchars($supervisor['email']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <h1><i class="fas fa-tasks"></i> Tracking Proposals</h1>
            
            <?php if (count($topics) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Project Topic</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topics as $t): ?>
                            <tr>
                                <td style="font-weight: 500; font-size: 14px;"><?= htmlspecialchars($t['topic']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $t['status'] ?>">
                                        <?= $t['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 60px;">
                    <i class="fas fa-folder-open" style="font-size: 50px; color: #dfe6e9; margin-bottom: 20px;"></i>
                    <p style="color: #636e72;">No proposal history found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

