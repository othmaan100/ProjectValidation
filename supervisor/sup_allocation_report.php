<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$supervisor_id = $_SESSION['user_id'];
$department_id = $_SESSION['department'];
$supervisor_name = $_SESSION['name'];

// Global current session is available via $current_session from db.php (if set) or we fetch it
$session_val = isset($current_session) ? $current_session : (isset($_SESSION['session']) ? $_SESSION['session'] : date('Y'));

// Handle POST acceptance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_allocation'])) {
    $stmtAccept = $conn->prepare("INSERT IGNORE INTO supervisor_allocation_status (supervisor_id, session, accepted_at) VALUES (?, ?, NOW())");
    $stmtAccept->execute([$supervisor_id, $session_val]);
}

// Check if already accepted
$stmtCheck = $conn->prepare("SELECT accepted_at FROM supervisor_allocation_status WHERE supervisor_id = ? AND session = ?");
$stmtCheck->execute([$supervisor_id, $session_val]);
$acceptance = $stmtCheck->fetch(PDO::FETCH_ASSOC);

// Fetch Allocated Students
$stmt = $conn->prepare("
    SELECT s.name, s.reg_no
    FROM students s
    JOIN supervision sp ON s.id = sp.student_id
    WHERE sp.supervisor_id = ? AND sp.status = 'active'
    ORDER BY s.name ASC
");
$stmt->execute([$supervisor_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch DPC name
$stmtDpc = $conn->prepare("SELECT name FROM users WHERE department = ? AND role = 'dpc' LIMIT 1");
$stmtDpc->execute([$department_id]);
$dpc = $stmtDpc->fetch(PDO::FETCH_ASSOC);
$dpc_name = $dpc ? $dpc['name'] : 'Department Project Coordinator';

// Fetch Department name
$stmtDept = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
$stmtDept->execute([$department_id]);
$dept = $stmtDept->fetch(PDO::FETCH_ASSOC);
$dept_name = $dept ? $dept['department_name'] : '';

$date = date("d F Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocation Report - <?= htmlspecialchars($supervisor_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; line-height: 1.6; margin: 0; padding: 0; background: #f0f0f0; }
        .page { background: #fff; width: 21cm; min-height: 29.7cm; padding: 2.54cm; margin: 2cm auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); box-sizing: border-box; position: relative; }
        
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #000; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 16px; font-weight: bold; text-transform: uppercase; }
        
        .date { text-align: right; margin-bottom: 40px; font-weight: bold; }
        
        .recipient { margin-bottom: 40px; }
        .recipient strong { display: block; font-size: 18px; }
        
        .content { margin-bottom: 40px; text-align: justify; font-size: 16px; }
        
        .student-list { margin: 20px 0 20px 40px; }
        .student-list li { margin-bottom: 10px; font-size: 16px; font-weight: bold; }
        
        .acceptance { margin-top: 50px; font-size: 16px; display: flex; align-items: center; }
        .checkbox { display: inline-block; width: 20px; height: 20px; border: 2px solid #000; margin-right: 15px; }
        
        .sign-off { margin-top: 80px; }
        .sign-off p { margin: 0; }
        .signature-line { margin-top: 50px; border-bottom: 1px solid #000; width: 250px; }
        
        .action-bar { text-align: center; padding: 20px; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .btn { padding: 10px 20px; font-size: 16px; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; margin: 0 10px; display: inline-block; font-family: Arial, sans-serif; }
        .btn-print { background: #4e73df; }
        .btn-back { background: #6c757d; }
        
        .print-only { display: none; }
        
        @media print {
            body { background: #fff; }
            .page { margin: 0; padding: 2cm; box-shadow: none; width: 100%; min-height: auto; }
            .action-bar, .no-print { display: none !important; }
            .print-only { display: flex !important; }
        }
    </style>
</head>
<body>
    <div class="action-bar">
        <a href="sup_view_students.php" class="btn btn-back">Back to Students</a>
        <button class="btn btn-print" onclick="window.print()">Print Report</button>
    </div>
    
    <div class="page">
        <div class="header">
            <?php if ($dept_name): ?>
            <h1>Department of <?= htmlspecialchars($dept_name) ?></h1>
            <?php else: ?>
            <h1>Final Year Project Supervision</h1>
            <?php endif; ?>
            <p>Allocation of Students for Supervision</p>
        </div>
        
        <div class="date">
            Date: <?= $date ?>
        </div>
        
        <div class="recipient">
            To:
            <strong><?= htmlspecialchars($supervisor_name) ?></strong>
        </div>
        
        <div class="content">
            <p>Dear <?= htmlspecialchars($supervisor_name) ?>,</p>
            <p>On behalf of the HOD, the following students are assigned/allocated to you for final year project supervision. They are as follows:</p>
            
            <ol class="student-list">
                <?php if (empty($students)): ?>
                    <li><em>No students have been allocated yet.</em></li>
                <?php else: ?>
                    <?php foreach($students as $student): ?>
                        <li><?= htmlspecialchars($student['name']) ?> &mdash; <?= htmlspecialchars($student['reg_no']) ?></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ol>
            
            <p>Kindly check the box below to indicate your acceptance.</p>
            
            <?php if ($acceptance): ?>
                <div class="acceptance" style="color: #1cc88a; font-weight: bold; font-family: Arial, sans-serif;">
                    <i class="fas fa-check-circle" style="font-size: 24px; margin-right: 10px;"></i>
                    Allocation Accepted on <?= date('d M Y, h:i A', strtotime($acceptance['accepted_at'])) ?>
                </div>
                <div class="acceptance print-only">
                    <div class="checkbox" style="background: #000;"></div> I accept the supervision of the above-listed students.
                </div>
            <?php else: ?>
                <form method="POST" class="acceptance no-print">
                    <label style="display: flex; align-items: center; cursor: pointer; font-family: Arial, sans-serif;">
                        <input type="checkbox" name="confirm" required style="width: 20px; height: 20px; margin-right: 10px;">
                        I accept the supervision of the above-listed students.
                    </label>
                    <button type="submit" name="accept_allocation" class="btn btn-print" style="margin-left: 20px;">Submit Acceptance</button>
                </form>
                <div class="acceptance print-only">
                    <div class="checkbox"></div> I accept the supervision of the above-listed students.
                </div>
            <?php endif; ?>
            
            <p style="margin-top: 40px;">Thank you.</p>
        </div>
        
        <div class="sign-off">
            <p>Yours sincerely,</p>
            <div class="signature-line"></div>
            <p style="margin-top: 5px;"><strong><?= htmlspecialchars($dpc_name) ?></strong></p>
            <p>Dept Project Coordinator</p>
        </div>
    </div>
</body>
</html>
