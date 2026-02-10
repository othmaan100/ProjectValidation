<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$sup_id = $_SESSION['user_id'];

// Use global current session from db.php
// $current_session is already defined there

// Fetch department info to get num_chapters
$stmt = $conn->prepare("SELECT d.num_chapters FROM supervisors s JOIN departments d ON s.department = d.id WHERE s.id = ?");
$stmt->execute([$sup_id]);
$num_chapters = (int)($stmt->fetchColumn() ?: 5);

// Handle chapater approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_chapter'])) {
    $student_id = intval($_POST['student_id']);
    $chapter_num = intval($_POST['chapter_num']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO chapter_approvals (student_id, supervisor_id, chapter_number, status, approval_date, academic_session) 
                                VALUES (?, ?, ?, 'approved', NOW(), ?) 
                                ON DUPLICATE KEY UPDATE status = 'approved', approval_date = NOW()");
        $stmt->execute([$student_id, $sup_id, $chapter_num, $current_session]);
        $_SESSION['success'] = "Chapter $chapter_num approved successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to approve chapter: " . $e->getMessage();
    }
    header("Location: sup_chapter_approvals.php");
    exit();
}

// Fetch students assigned to this supervisor
$stmt = $conn->prepare("
    SELECT s.id, s.name, s.reg_no, pt.topic
    FROM supervision sp
    JOIN students s ON sp.student_id = s.id
    LEFT JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
    WHERE sp.supervisor_id = ? AND sp.status = 'active'
");
$stmt->execute([$sup_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing approvals
$approvals = [];
$stmt = $conn->prepare("SELECT student_id, chapter_number, status FROM chapter_approvals WHERE supervisor_id = ?");
$stmt->execute([$sup_id]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $approvals[$row['student_id']][$row['chapter_number']] = $row['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter Approvals - Supervisor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= PROJECT_ROOT ?>assets/css/styles.css">
    <style>
        .approval-table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .approval-table th { background: #1a202c; color: white; padding: 15px; text-align: left; font-size: 13px; text-transform: uppercase; }
        .approval-table td { padding: 15px; border-bottom: 1px solid #edf2f7; }
        .chapter-box { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; font-weight: bold; margin-right: 5px; cursor: pointer; transition: 0.3s; border: 2px solid #e2e8f0; }
        .chapter-box.approved { background: #1cc88a; color: white; border-color: #1cc88a; }
        .chapter-box.pending { background: white; color: #a0aec0; }
        .chapter-box:hover { transform: scale(1.1); }
        .btn-approve { border: none; background: none; padding: 0; }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .pill-approved { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body style="background: #f7fafc;">
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="color: #2d3748; font-size: 28px;">Chapter Approvals</h1>
                <p style="color: #718096;">Manage progress for your allocated students (<?= $current_session ?> Session)</p>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="approval-table-container">
            <table class="approval-table">
                <thead>
                    <tr>
                        <th>Student Information</th>
                        <th>Project Topic</th>
                        <th>Progress (Click to Approve)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: #a0aec0;">No active students assigned to you.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <?php 
                                $approved_count = 0;
                                if (isset($approvals[$s['id']])) {
                                    $approved_count = count(array_filter($approvals[$s['id']], function($v) { return $v === 'approved'; }));
                                }
                                $percent = round(($approved_count / $num_chapters) * 100);
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #2d3748;"><?= htmlspecialchars($s['name']) ?></div>
                                    <div style="font-size: 12px; color: #718096;"><?= htmlspecialchars($s['reg_no']) ?></div>
                                </td>
                                <td style="max-width: 300px; font-size: 14px; color: #4a5568;">
                                    <?= htmlspecialchars($s['topic'] ?: 'No approved topic yet') ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <?php for ($i = 1; $i <= $num_chapters; $i++): ?>
                                            <?php $is_approved = isset($approvals[$s['id']][$i]) && $approvals[$s['id']][$i] === 'approved'; ?>
                                            <?php if ($is_approved): ?>
                                                <div class="chapter-box approved" title="Chapter <?= $i ?> Approved">
                                                    <?= $i ?>
                                                </div>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                                    <input type="hidden" name="chapter_num" value="<?= $i ?>">
                                                    <button type="submit" name="approve_chapter" class="btn-approve" onclick="return confirm('Approve Chapter <?= $i ?> for <?= htmlspecialchars($s['name']) ?>?')">
                                                        <div class="chapter-box pending" title="Click to Approve Chapter <?= $i ?>">
                                                            <?= $i ?>
                                                        </div>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="width: 100px; background: #edf2f7; height: 10px; border-radius: 10px; margin-bottom: 5px;">
                                        <div style="width: <?= $percent ?>%; background: #4e73df; height: 100%; border-radius: 10px;"></div>
                                    </div>
                                    <span style="font-size: 11px; font-weight: 700; color: #4e73df;"><?= $percent ?>% Completed</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
