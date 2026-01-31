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
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name FROM users u JOIN departments d ON u.department = d.id WHERE u.id = ?");
$stmt->execute([$dpc_id]);
$dpc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $dpc_info['dept_id'];
$dept_name = $dpc_info['department_name'];

// Get current session
$session_stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_session'");
$active_session = $session_stmt->fetchColumn() ?: date('Y') . '/' . (date('Y') + 1);

// Fetch all panels for this department
$panel_stmt = $conn->prepare("
    SELECT dp.*, 
    (SELECT GROUP_CONCAT(s.name SEPARATOR ', ') FROM panel_members pm JOIN supervisors s ON pm.supervisor_id = s.id WHERE pm.panel_id = dp.id) as members,
    (SELECT COUNT(*) FROM student_panel_assignments spa WHERE spa.panel_id = dp.id AND spa.academic_session = ?) as student_count
    FROM defense_panels dp
    WHERE dp.department_id = ?
    ORDER BY dp.panel_name
");
$panel_stmt->execute([$active_session, $dept_id]);
$panels = $panel_stmt->fetchAll(PDO::FETCH_ASSOC);

// For each panel, fetch its students
foreach ($panels as $key => $panel) {
    $stu_stmt = $conn->prepare("
        SELECT s.name, s.reg_no, pt.topic
        FROM student_panel_assignments spa
        JOIN students s ON spa.student_id = s.id
        LEFT JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
        WHERE spa.panel_id = ? AND spa.academic_session = ?
        ORDER BY s.name
    ");
    $stu_stmt->execute([$panel['id'], $active_session]);
    $panels[$key]['students'] = $stu_stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Details - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --primary-light: #6366f1;
            --success: #059669;
            --bg-body: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        .header { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; font-weight: 700; color: var(--primary); margin: 0; }

        .panel-section { margin-bottom: 40px; }
        .panel-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .panel-header { background: #f8fafc; padding: 20px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .panel-header h2 { margin: 0; font-size: 20px; color: var(--primary); }
        
        .panel-meta { padding: 15px 30px; background: white; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: var(--text-muted); }
        .panel-meta strong { color: var(--text-main); }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 30px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid #f1f5f9; background: #fafafa; }
        td { padding: 15px 30px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

        .btn { padding: 12px 20px; border: none; border-radius: 12px; font-family: inherit; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #3730a3; transform: translateY(-2px); }

        .empty-state { text-align: center; padding: 40px; color: var(--text-muted); }

        .project-topic { font-style: italic; color: #475569; max-width: 400px; font-size: 13px; }

        @media print {
            .no-print, header, nav { display: none !important; }
            .print-only { display: block !important; }
            .container { margin: 0; width: 100%; max-width: none; padding: 0; }
            .panel-card { box-shadow: none; border: 1px solid #000; break-inside: avoid; }
            body { background: white; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <!-- Print Only Header -->
        <div class="print-only" style="display: none; text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px;">
            <h1 style="margin: 0; color: #000; font-size: 24px; text-transform: uppercase;">Panel Allocation List</h1>
            <p style="margin: 5px 0;"><strong>Department:</strong> <?= htmlspecialchars($dept_name) ?> | <strong>Session:</strong> <?= htmlspecialchars($active_session) ?></p>
            <p style="font-size: 12px; margin: 0;">Generated on: <?= date('F d, Y') ?></p>
        </div>

        <div class="header no-print">
            <div>
                <h1>Panel Allocation Details</h1>
                <p style="color: var(--text-muted);">View all panels and their assigned students for <?= $active_session ?>.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn btn-primary" style="background: var(--success);"><i class="fas fa-print"></i> Print Details</button>
                <a href="dpc_manage_panels.php" class="btn btn-primary">Manage Panels</a>
            </div>
        </div>

        <?php if (count($panels) > 0): ?>
            <?php foreach ($panels as $panel): ?>
                <div class="panel-section">
                    <div class="panel-card">
                        <div class="panel-header">
                            <h2><i class="fas fa-users-rectangle"></i> <?= htmlspecialchars($panel['panel_name']) ?></h2>
                            <span style="font-size: 14px;" class="no-print">Capacity: <?= $panel['student_count'] ?> / <?= $panel['max_students'] ?> Students</span>
                        </div>
                        <div class="panel-meta">
                            <p><strong>Panel Members (Supervisors):</strong> <?= htmlspecialchars($panel['members'] ?: 'No members assigned') ?></p>
                        </div>
                        
                        <?php if (count($panel['students']) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">S/N</th>
                                        <th>Student Name</th>
                                        <th>Registration Number</th>
                                        <th>Project Topic</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1; foreach ($panel['students'] as $stu): ?>
                                        <tr>
                                            <td><?= $sn++ ?></td>
                                            <td><strong><?= htmlspecialchars($stu['name']) ?></strong></td>
                                            <td><?= htmlspecialchars($stu['reg_no']) ?></td>
                                            <td>
                                                <div class="project-topic">
                                                    <?= htmlspecialchars($stu['topic'] ?: 'No approved topic yet') ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                No students assigned to this panel yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 80px;">
                <i class="fas fa-layer-group" style="font-size: 60px; color: #e2e8f0; margin-bottom: 20px;"></i>
                <h2>No Panels Found</h2>
                <p>You haven't created any defense panels for this session yet.</p>
                <a href="dpc_manage_panels.php" class="btn btn-primary" style="margin-top: 20px;">Create First Panel</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
