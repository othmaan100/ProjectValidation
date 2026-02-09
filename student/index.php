<?php
session_start();

// Redirect if the user is not logged in or is not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'stu') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

include_once __DIR__ . '/../includes/db.php';

$student_id = $_SESSION['user_id'];

// Fetch student details
$stmt = $conn->prepare("SELECT s.*, d.department_name, d.project_guideline 
                        FROM students s 
                        JOIN departments d ON s.department = d.id 
                        WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header("Location: stu_login.php");
    exit();
}

// Fetch submitted topics
$stmt = $conn->prepare("SELECT * FROM project_topics WHERE student_id = ? ORDER BY id DESC");
$stmt->execute([$student_id]);
$topics = $stmt->fetchAll();

// Count statuses
$pending_count = 0;
$approved_topic = null;
foreach ($topics as $t) {
    if ($t['status'] === 'pending') $pending_count++;
    if ($t['status'] === 'approved') $approved_topic = $t;
}

// Check for first login
if ($student['first_login']) {
    echo "<script>
        alert('Welcome! This is your first login. For security reasons, please change your password.');
        window.location.href = 'stu_change_password.php';
    </script>";
    exit();
}

// Fetch Supervisor details if approved
$assigned_supervisor = null;
if ($approved_topic) {
    $stmt = $conn->prepare("
        SELECT su.name, su.phone, su.email
        FROM supervision sp 
        JOIN supervisors su ON sp.supervisor_id = su.id 
        WHERE sp.student_id = ? AND sp.status = 'active'
    ");
    $stmt->execute([$student_id]);
    $assigned_supervisor = $stmt->fetch();
}

// Fetch submission schedule
$dept_id = $student['department'];
$stmt = $conn->prepare("SELECT * FROM submission_schedules WHERE department_id = ? AND is_active = 1");
$stmt->execute([$dept_id]);
$schedule = $stmt->fetch();

$now = time();
$can_submit = false;
if ($schedule) {
    if ($now >= strtotime($schedule['submission_start']) && $now <= strtotime($schedule['submission_end'])) {
        $can_submit = true;
    }
}

$schedule_msg = "";
if (!$schedule) {
    $schedule_msg = "No submission schedule has been set for your department yet.";
} elseif ($now < strtotime($schedule['submission_start'])) {
    $schedule_msg = "Submissions will open on " . date('M d, Y | h:i A', strtotime($schedule['submission_start']));
} elseif ($now > strtotime($schedule['submission_end'])) {
    $schedule_msg = "The submission window closed on " . date('M d, Y | h:i A', strtotime($schedule['submission_end']));
}

$error_msg = "";
if (isset($_GET['error']) && $_GET['error'] === 'schedule_closed') {
    $error_msg = "Sorry, project topic submissions are currently closed.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --warning: #f6c23e; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fe; margin: 0; color: #2d3436; }
        .page-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .hero { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            padding: 40px; border-radius: 20px; color: white; margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.25);
            display: flex; justify-content: space-between; align-items: center;
        }
        .hero-text h1 { margin: 0; font-size: 28px; }
        .hero-text p { margin: 10px 0 0; opacity: 0.9; font-size: 16px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { 
            background: white; padding: 25px; border-radius: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex; align-items: center; gap: 20px;
        }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
        .stat-info h3 { margin: 0; font-size: 24px; }
        .stat-info p { margin: 2px 0 0; color: #636e72; font-size: 13px; font-weight: 600; text-transform: uppercase; }

        .main-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .content-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .content-card h2 { margin-top: 0; margin-bottom: 25px; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #f1f2f6; color: #636e72; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f1f2f6; font-size: 14px; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: #fff8e1; color: #ff8f00; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }

        .action-card { 
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); 
            padding: 30px; border-radius: 20px; color: white; text-align: center;
            display: flex; flex-direction: column; align-items: center; gap: 15px;
        }
        .btn-action { 
            background: white; color: #1cc88a; padding: 12px 25px; border-radius: 12px; 
            text-decoration: none; font-weight: 700; transition: 0.3s;
        }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .supervisor-card { background: #f8faff; border: 1px dashed #667eea; padding: 20px; border-radius: 15px; margin-top: 20px; }
        .supervisor-card i { color: var(--primary); margin-right: 8px; }

        @media (max-width: 992px) { .main-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    
    <div class="page-container">
        <!-- Hero Section -->
        <div class="hero">
            <div class="hero-text">
                <h1>Hello, <?= htmlspecialchars($student['name']) ?></h1>
                <p>Registration No: <strong><?= htmlspecialchars($student['reg_no']) ?></strong> | Department of <?= htmlspecialchars($student['department_name']) ?></p>
            </div>
            <i class="fas fa-graduation-cap" style="font-size: 60px; opacity: 0.3;"></i>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger" style="padding: 15px; border-radius: 12px; margin-bottom: 25px; text-align: center; background: #ffebee; color: #c62828;">
                <i class="fas fa-exclamation-triangle"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <?php if ($schedule_msg): ?>
            <div class="alert alert-info" style="padding: 15px; border-radius: 12px; margin-bottom: 25px; text-align: center; background: #e3f2fd; color: #1976d2;">
                <i class="fas fa-calendar-alt"></i> <?= $schedule_msg ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary);"><i class="fas fa-file-alt"></i></div>
                <div class="stat-info"><h3><?= count($topics) ?></h3><p>Total Submitted</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--warning);"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h3><?= $pending_count ?></h3><p>Pending Review</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--success);"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info"><h3><?= $approved_topic ? 1 : 0 ?></h3><p>Approved Project</p></div>
            </div>
        </div>

        <div class="main-grid">
            <div class="content-card">
                <h2><i class="fas fa-list-ul"></i> My Proposal Status</h2>
                <?php if (count($topics) > 0): ?>
                    <table>
                        <thead><tr><th>Proposed Topic</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($topics as $t): ?>
                                <tr><td><?= htmlspecialchars($t['topic']) ?></td><td><span class="status-badge status-<?= $t['status'] ?>"><?= $t['status'] ?></span></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #636e72; padding: 40px;">No topics submitted yet. Please submit your proposals.</p>
                <?php endif; ?>
            </div>

            <div class="side-actions">
                <?php if (!$approved_topic && count($topics) < 3): ?>
                    <div class="action-card" style="<?= !$can_submit ? 'filter: grayscale(1); opacity: 0.8;' : '' ?>">
                        <i class="fas fa-plus-circle" style="font-size: 40px;"></i>
                        <h2 style="margin: 0; font-size: 18px;">New Submission</h2>
                        <p style="font-size: 13px; opacity: 0.9;">You have submitted <?= count($topics) ?>/3 topics.</p>
                        <?php if ($can_submit): ?>
                            <a href="stu_submit_topic.php" class="btn-action">Submit New Topic</a>
                        <?php else: ?>
                            <button class="btn-action" style="cursor: not-allowed; opacity: 0.7; width: 100%; border: none; font-family: inherit;" disabled>Submit New Topic</button>
                        <?php endif; ?>
                    </div>
                <?php elseif ($approved_topic): ?>
                    <div class="content-card">
                        <h2 style="color: var(--success);"><i class="fas fa-trophy"></i> Project Approved!</h2>
                        <p style="font-size: 14px; font-weight: 600;"><?= htmlspecialchars($approved_topic['topic']) ?></p>
                        <div class="supervisor-card">
                            <p style="margin: 0; font-size: 13px; color: #636e72;">Assigned Supervisor:</p>
                            <p style="margin: 5px 0 0; font-weight: 700;"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($assigned_supervisor['name'] ?? 'Awaiting Allocation') ?></p>
                            <?php if (!empty($assigned_supervisor['phone'])): ?>
                                <p style="margin: 3px 0 0; font-size: 14px; color: var(--primary); font-weight: 600;">
                                    <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($assigned_supervisor['phone']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($assigned_supervisor['email'])): ?>
                                <p style="margin: 3px 0 0; font-size: 14px; color: #636e72;">
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($assigned_supervisor['email']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 25px;">
                            <a href="stu_upload_report.php" class="btn-action" style="display: block; background: var(--primary); color: white;"><i class="fas fa-upload"></i> Upload Final Report</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="content-card" style="text-align: center;"><i class="fas fa-hourglass-half" style="font-size: 40px; color: var(--warning); margin-bottom: 15px;"></i><p style="font-size: 14px; color: #636e72;">You have submitted all 3 proposals. Please await coordinator review.</p></div>
                <?php endif; ?>

                <?php if (!empty($student['project_guideline'])): ?>
                    <div class="content-card" style="margin-top: 25px;">
                        <h2><i class="fas fa-book" style="color: var(--primary);"></i> Project Guideline</h2>
                        <p style="font-size: 13px; color: #636e72; margin-bottom: 15px;">
                            Download the official project guidelines for the Department of <?= htmlspecialchars($student['department_name']) ?>.
                        </p>
                        <a href="<?= PROJECT_ROOT . $student['project_guideline'] ?>" target="_blank" class="btn-action" style="display: block; width: 100%; text-align: center; box-sizing: border-box; background: var(--secondary); color: white;">
                            <i class="fas fa-file-download"></i> Download PDF
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
