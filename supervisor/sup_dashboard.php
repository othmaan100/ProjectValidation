<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: /projectval/");
    exit();
}

$supervisor_id = $_SESSION['user_id'];
$message = '';

// Handle Topic Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $student_id = intval($_POST['student_id']);
    
    if ($_POST['action'] === 'approve') {
        $topic_id = intval($_POST['topic_id']);
        try {
            $conn->beginTransaction();
            
            // 1. Reset all topics for this student to rejected
            $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected' WHERE student_id = ? AND supervisor_id = ?");
            $stmt->execute([$student_id, $supervisor_id]);
            
            // 2. Approve the selected one
            $stmt = $conn->prepare("UPDATE project_topics SET status = 'approved' WHERE id = ? AND student_id = ?");
            $stmt->execute([$topic_id, $student_id]);
            
            // 3. Update supervision table to link this specific project
            $stmt = $conn->prepare("UPDATE supervision SET project_id = ? WHERE student_id = ? AND supervisor_id = ?");
            $stmt->execute([$topic_id, $student_id, $supervisor_id]);
            
            $conn->commit();
            $message = "Topic approved successfully!";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Error: " . $e->getMessage();
        }
    } 
    elseif ($_POST['action'] === 'reject_all') {
        $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected' WHERE student_id = ? AND supervisor_id = ?");
        $stmt->execute([$student_id, $supervisor_id]);
        $message = "All topics rejected for this student.";
    }
}

// Fetch Students and their Topics
// Grouped by student
$stmt = $conn->prepare("
    SELECT s.id as stu_id, s.name as stu_name, s.reg_no, 
           p.id as topic_id, p.topic, p.status
    FROM students s
    JOIN supervision sp ON s.id = sp.student_id
    JOIN project_topics p ON s.id = p.student_id
    WHERE sp.supervisor_id = ? AND sp.status = 'active'
    ORDER BY s.name, p.id
");
$stmt->execute([$supervisor_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process into grouped array
$students = [];
foreach ($data as $row) {
    if (!isset($students[$row['stu_id']])) {
        $students[$row['stu_id']] = [
            'name' => $row['stu_name'],
            'reg_no' => $row['reg_no'],
            'topics' => []
        ];
    }
    $students[$row['stu_id']]['topics'][] = [
        'id' => $row['topic_id'],
        'title' => $row['topic'],
        'status' => $row['status']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --success: #1cc88a; --danger: #e74a3b; --warning: #f6c23e; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fc; margin: 0; color: #2d3436; }
        .page-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        
        .header-section { margin-bottom: 30px; }
        .header-section h1 { font-size: 28px; color: #2c3e50; }
        
        .student-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; }
        .student-header { background: #f8f9fa; padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .student-info h3 { margin: 0; font-size: 18px; color: var(--primary); }
        .student-info p { margin: 5px 0 0; font-size: 13px; color: #636e72; font-weight: 600; }
        
        .topics-list { padding: 20px; }
        .topic-row { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 15px; border: 1px solid #f1f2f6; border-radius: 10px; margin-bottom: 10px;
            transition: 0.3s;
        }
        .topic-row:hover { background: #fcfdfe; border-color: var(--primary); }
        .topic-content { flex: 1; margin-right: 20px; }
        .topic-title { font-size: 15px; font-weight: 500; }
        .topic-status { margin-top: 5px; }

        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: #fff8e1; color: #ff8f00; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }

        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .btn-approve { background: var(--success); color: white; }
        .btn-reject { background: var(--danger); color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .alert { padding: 15px; border-radius: 10px; background: #e8f5e9; color: #2e7d32; margin-bottom: 25px; border: 1px solid #c8e6c9; }
        
        .empty-state { text-align: center; padding: 60px; color: #636e72; }
        .empty-state i { font-size: 50px; color: #dfe6e9; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div> <!-- Close container from header -->

    <div class="page-container">
        <div class="header-section">
            <h1><i class="fas fa-chalkboard-teacher"></i> Supervisor Dashboard</h1>
            <p>Review and manage project topics for your assigned students.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert"><i class="fas fa-check-circle"></i> <?= $message ?></div>
        <?php endif; ?>

        <?php if (empty($students)): ?>
            <div class="empty-state">
                <i class="fas fa-user-friends"></i>
                <h2>No Students Allocated</h2>
                <p>You haven't been assigned any students for supervision yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($students as $sid => $s): ?>
                <div class="student-card">
                    <div class="student-header">
                        <div class="student-info">
                            <h3><?= htmlspecialchars($s['name']) ?></h3>
                            <p><i class="fas fa-id-badge"></i> <?= htmlspecialchars($s['reg_no']) ?></p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to reject ALL topics for this student?')">
                            <input type="hidden" name="student_id" value="<?= $sid ?>">
                            <input type="hidden" name="action" value="reject_all">
                            <button type="submit" class="btn btn-reject"><i class="fas fa-times-circle"></i> Reject All</button>
                        </form>
                    </div>
                    <div class="topics-list">
                        <?php foreach ($s['topics'] as $t): ?>
                            <div class="topic-row">
                                <div class="topic-content">
                                    <div class="topic-title"><?= htmlspecialchars($t['title']) ?></div>
                                    <div class="topic-status">
                                        <span class="status-pill status-<?= $t['status'] ?>"><?= $t['status'] ?></span>
                                    </div>
                                </div>
                                <div class="topic-actions">
                                    <?php if ($t['status'] !== 'approved'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="student_id" value="<?= $sid ?>">
                                            <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-approve"><i class="fas fa-check"></i> Approve This</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--success); font-weight: bold;"><i class="fas fa-check-double"></i> Selected Project</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
