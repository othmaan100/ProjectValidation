<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: /projectval/");
    exit();
}

$supervisor_id = $_SESSION['user_id'];

// Fetch Allocated Students - Refined for decoupled schema
$stmt = $conn->prepare("
    SELECT s.id, s.name, s.reg_no, s.email, d.department_name, pt.topic as approved_topic
    FROM students s
    JOIN supervision sp ON s.id = sp.student_id
    LEFT JOIN departments d ON s.department = d.id
    LEFT JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
    WHERE sp.supervisor_id = ? AND sp.status = 'active'
    ORDER BY s.name ASC
");
$stmt->execute([$supervisor_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Allocated Students | Supervisor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --success: #1cc88a; --info: #36b9cc; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fc; margin: 0; color: #2d3436; }
        .page-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        
        .header-section { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-section h1 { font-size: 28px; color: #2c3e50; }
        
        .main-card { background: white; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px 20px; text-align: left; color: #4e73df; font-size: 13px; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid #eee; }
        td { padding: 20px; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
        
        .student-name { font-weight: 700; color: #2d3436; display: block; }
        .student-reg { color: #6e707e; font-size: 13px; }
        
        .topic-pill { background: #f0f3ff; color: #4e73df; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-block; border: 1px solid #d1d9ff; }
        .topic-none { color: #bdc3c7; font-style: italic; font-size: 13px; }
        
        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
        .btn-back { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        .empty-state { text-align: center; padding: 60px; color: #636e72; background: white; border-radius: 15px; }
        .empty-state i { font-size: 50px; color: #dfe6e9; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div> <!-- Close container from header -->

    <div class="page-container">
        <div class="header-section">
            <div>
                <h1><i class="fas fa-users"></i> My Allocated Students</h1>
                <p>Overview of all students currently assigned to your supervision.</p>
            </div>
            <a href="sup_dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if (empty($students)): ?>
            <div class="empty-state">
                <i class="fas fa-user-friends"></i>
                <h2>No Students Assigned</h2>
                <p>You haven't been assigned any students for supervision yet.</p>
            </div>
        <?php else: ?>
            <div class="main-card">
                <table>
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Department</th>
                            <th>Approved Project</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td>
                                    <span class="student-name"><?= htmlspecialchars($s['name']) ?></span>
                                    <span class="student-reg"><?= htmlspecialchars($s['reg_no']) ?></span>
                                    <br>
                                    <small style="color: #9ea0a5;"><i class="fas fa-envelope"></i> <?= htmlspecialchars($s['email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($s['department_name']) ?></td>
                                <td style="max-width: 400px;">
                                    <?php if ($s['approved_topic']): ?>
                                        <div class="topic-pill"><?= htmlspecialchars($s['approved_topic']) ?></div>
                                    <?php else: ?>
                                        <span class="topic-none">Not yet approved</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($s['approved_topic']): ?>
                                        <span style="color: var(--success); font-weight: bold;"><i class="fas fa-check-circle"></i> Project Active</span>
                                    <?php else: ?>
                                        <span style="color: #f6c23e; font-weight: bold;"><i class="fas fa-hourglass-half"></i> Topic Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
