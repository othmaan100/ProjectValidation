<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$supervisor_id = $_SESSION['user_id'];
$active_session = $current_session;

// Fetch all panels where current supervisor is a member
$panel_stmt = $conn->prepare("
    SELECT dp.id, dp.panel_name, dp.panel_type
    FROM defense_panels dp
    JOIN panel_members pm ON dp.id = pm.panel_id
    WHERE pm.supervisor_id = ?
    ORDER BY FIELD(dp.panel_type, 'proposal', 'internal', 'external'), dp.panel_name
");
$panel_stmt->execute([$supervisor_id]);
$panels = $panel_stmt->fetchAll(PDO::FETCH_ASSOC);

$panels_data = [];
foreach ($panels as $panel) {
    // Fetch all members of this panel
    $member_stmt = $conn->prepare("
        SELECT u.id, u.name, u.role, u.email,
               COALESCE(s.phone, e.phone) as phone
        FROM panel_members pm
        JOIN users u ON pm.supervisor_id = u.id
        LEFT JOIN supervisors s ON u.id = s.id
        LEFT JOIN external_examiners e ON u.id = e.id
        WHERE pm.panel_id = ?
    ");
    $member_stmt->execute([$panel['id']]);
    $members = $member_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all students assigned to this panel (for this session)
    $stu_stmt = $conn->prepare("
        SELECT s.id, s.name, s.reg_no, pt.topic
        FROM student_panel_assignments spa
        JOIN students s ON spa.student_id = s.id
        LEFT JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
        WHERE spa.panel_id = ? AND spa.panel_type = ? AND spa.academic_session = ?
        ORDER BY s.name
    ");
    $stu_stmt->execute([$panel['id'], $panel['panel_type'], $active_session]);
    $panel_students = $stu_stmt->fetchAll(PDO::FETCH_ASSOC);

    $panels_data[] = [
        'info' => $panel,
        'members' => $members,
        'students' => $panel_students
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My assigned Panels | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --success: #1cc88a; --info: #36b9cc; --bg: #f8f9fc; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; color: #2d3436; }
        .page-container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 20px; }
        .back-btn:hover { text-decoration: underline; }

        .header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; margin: 0; display: flex; align-items: center; gap: 15px; }

        .panel-card { 
            background: white; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            margin-bottom: 30px; overflow: hidden; border: 1px solid #e3e6f0;
        }
        .panel-header { 
            background: #f8f9fc; padding: 20px 30px; border-bottom: 1px solid #e3e6f0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .panel-header h2 { margin: 0; font-size: 20px; color: var(--primary); }

        .type-badge { 
            padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; 
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .type-proposal { background: #e0f2fe; color: #0369a1; }
        .type-internal { background: #fef3c7; color: #92400e; }
        .type-external { background: #dcfce7; color: #166534; }

        .panel-body { padding: 30px; }
        .members-title { 
            font-size: 13px; color: #858796; text-transform: uppercase; 
            letter-spacing: 1px; font-weight: 700; margin-bottom: 20px;
        }

        .members-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
        .member-item { 
            background: #fff; padding: 15px; border-radius: 12px; 
            display: flex; align-items: center; gap: 15px; border: 1px solid #eaecf4;
            transition: 0.2s;
        }
        .member-item:hover { border-color: var(--primary); background: #f8f9fc; }
        .member-icon { width: 40px; height: 40px; border-radius: 10px; background: #eaecf4; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #5a5c69; }
        .member-info h4 { margin: 0; font-size: 15px; color: #333; display: flex; align-items: center; gap: 8px; }
        .member-info p { margin: 2px 0 0; font-size: 12px; color: #858796; }
        
        .role-tag { font-size: 9px; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; }
        .role-sup { background: #dddfeb; color: #4e73df; }
        .role-ext { background: #c6f6d5; color: #22543d; }
        .me-tag { background: var(--primary); color: white; border-radius: 4px; padding: 2px 6px; font-size: 9px; text-transform: uppercase; }

        .empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .empty-state i { font-size: 60px; color: #eaecf4; margin-bottom: 20px; }
        
        .btn-action {
             display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; 
             background: var(--primary); color: white; text-decoration: none; border-radius: 10px;
             font-weight: 600; font-size: 14px; transition: 0.2s;
        }
        .btn-action:hover { background: #224abe; transform: translateY(-1px); }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    
    <div class="page-container">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="header">
            <h1><i class="fas fa-users-rectangle" style="color: var(--primary);"></i> My Defense Panels</h1>
            <a href="sup_manage_panels.php" class="btn-action"><i class="fas fa-vial"></i> Assess Students</a>
        </div>

        <?php if (!empty($panels_data)): ?>
            <?php foreach ($panels_data as $data): ?>
                <div class="panel-card">
                    <div class="panel-header">
                        <h2><?= htmlspecialchars($data['info']['panel_name']) ?></h2>
                        <span class="type-badge type-<?= $data['info']['panel_type'] ?>">
                            <?= htmlspecialchars($data['info']['panel_type']) ?> Stage
                        </span>
                    </div>
                    <div class="panel-body">
                        <div class="members-title">Panel Members & Examiners</div>
                        <div class="members-grid">
                            <?php foreach ($data['members'] as $member): ?>
                                <div class="member-item">
                                    <div class="member-icon">
                                        <i class="fas <?= $member['role'] === 'ext' ? 'fa-user-nurse' : 'fa-user-tie' ?>"></i>
                                    </div>
                                    <div class="member-info">
                                        <h4>
                                            <?= htmlspecialchars($member['name']) ?>
                                            <?php if ($member['id'] == $supervisor_id): ?>
                                                <span class="me-tag">You</span>
                                            <?php endif; ?>
                                            <span class="role-tag <?= $member['role'] === 'ext' ? 'role-ext' : 'role-sup' ?>">
                                                <?= $member['role'] === 'ext' ? 'External' : 'Internal' ?>
                                            </span>
                                        </h4>
                                        <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($member['email'] ?: 'No email') ?></p>
                                        <?php if (!empty($member['phone'])): ?>
                                            <p><i class="fas fa-phone-alt"></i> <?= htmlspecialchars($member['phone']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="members-title" style="margin-top: 30px;">Assigned Students for Defense</div>
                        <div class="members-grid">
                            <?php foreach ($data['students'] as $s): ?>
                                <div class="member-item">
                                    <div class="member-icon" style="background: #fdf2f2; color: #dc3545;">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="member-info">
                                        <h4><?= htmlspecialchars($s['name']) ?></h4>
                                        <p><?= htmlspecialchars($s['reg_no']) ?></p>
                                        <?php if ($s['topic']): ?>
                                            <p style="font-size: 11px; margin-top: 5px; color: var(--primary); font-style: italic;">
                                                "<?= htmlspecialchars($s['topic']) ?>"
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <p>You are not currently assigned to any defense panels for this session.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
