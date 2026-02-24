<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'stu') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$student_id = $_SESSION['user_id'];
$active_session = $current_session;

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: stu_login.php");
    exit();
}

// Fetch all panels assigned to this student
$stmt = $conn->prepare("
    SELECT spa.panel_id, spa.panel_type, dp.panel_name, spa.academic_session
    FROM student_panel_assignments spa
    JOIN defense_panels dp ON spa.panel_id = dp.id
    WHERE spa.student_id = ?
    ORDER BY FIELD(spa.panel_type, 'proposal', 'internal', 'external')
");
$stmt->execute([$student_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$panels_data = [];
foreach ($assignments as $asgn) {
    // Fetch members for this panel (Internal Supervisors and External Examiners)
    $member_stmt = $conn->prepare("
        SELECT u.id, u.name, u.role, u.email, 
               COALESCE(s.phone, e.phone) as phone
        FROM panel_members pm
        JOIN users u ON pm.supervisor_id = u.id
        LEFT JOIN supervisors s ON u.id = s.id
        LEFT JOIN external_examiners e ON u.id = e.id
        WHERE pm.panel_id = ?
    ");
    $member_stmt->execute([$asgn['panel_id']]);
    $members = $member_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all students assigned to this panel (for this stage and session)
    $stu_stmt = $conn->prepare("
        SELECT s.name, s.reg_no
        FROM student_panel_assignments spa
        JOIN students s ON spa.student_id = s.id
        WHERE spa.panel_id = ? AND spa.panel_type = ? AND spa.academic_session = ?
        ORDER BY s.name
    ");
    $stu_stmt->execute([$asgn['panel_id'], $asgn['panel_type'], $asgn['academic_session']]);
    $panel_students = $stu_stmt->fetchAll(PDO::FETCH_ASSOC);

    $panels_data[] = [
        'info' => $asgn,
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
    <title>My Defense Panels | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --info: #36b9cc; --bg: #f4f7fe; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; color: #2d3436; }
        .page-container { max-width: 900px; margin: 40px auto; padding: 20px; }
        
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 20px; }
        .back-btn:hover { text-decoration: underline; }

        .header { margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin: 0; display: flex; align-items: center; gap: 15px; }

        .panel-card { 
            background: white; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            margin-bottom: 30px; overflow: hidden; border: 1px solid #eef2f7;
        }
        .panel-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 25px 30px; display: flex; justify-content: space-between; align-items: center;
        }
        .panel-header-info h2 { margin: 0; font-size: 22px; }
        .panel-header-info p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }

        .type-badge { 
            background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; 
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.3);
        }

        .panel-body { padding: 30px; }
        .members-title { 
            font-size: 14px; color: #b2bec3; text-transform: uppercase; 
            letter-spacing: 1px; font-weight: 700; margin-bottom: 20px; 
            display: flex; align-items: center; gap: 10px;
        }
        .members-title::after { content: ''; flex: 1; height: 1px; background: #f1f2f6; }

        .members-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .member-item { 
            background: #f8faff; padding: 15px 20px; border-radius: 15px; 
            display: flex; align-items: center; gap: 15px; border: 1px solid #edf2f7;
        }
        .member-icon { width: 45px; height: 45px; border-radius: 12px; background: white; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--primary); box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .member-info h4 { margin: 0; font-size: 15px; color: #2d3436; }
        .member-info p { margin: 2px 0 0; font-size: 12px; color: #636e72; }
        .role-tag { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; margin-left: 5px; }
        .role-sup { background: #e0f2fe; color: #0369a1; }
        .role-ext { background: #dcfce7; color: #166534; }

        .empty-state { text-align: center; padding: 60px; background: white; border-radius: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .empty-state i { font-size: 60px; color: #dfe6e9; margin-bottom: 20px; }
        .empty-state p { color: #636e72; font-size: 16px; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    
    <div class="page-container">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="header">
            <h1><i class="fas fa-users-rectangle" style="color: var(--primary);"></i> My Defense Panels</h1>
        </div>

        <?php if (!empty($panels_data)): ?>
            <?php foreach ($panels_data as $data): ?>
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="panel-header-info">
                            <h2><?= htmlspecialchars($data['info']['panel_name']) ?></h2>
                            <p><i class="fas fa-calendar-alt"></i> Session: <?= htmlspecialchars($data['info']['academic_session']) ?></p>
                        </div>
                        <div class="type-badge">
                            <?= htmlspecialchars($data['info']['panel_type']) ?>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="members-title">Panel Members (Examiners)</div>
                        <div class="members-grid">
                            <?php foreach ($data['members'] as $member): ?>
                                <div class="member-item">
                                    <div class="member-icon">
                                        <i class="fas <?= $member['role'] === 'ext' ? 'fa-user-nurse' : 'fa-user-tie' ?>"></i>
                                    </div>
                                    <div class="member-info">
                                        <h4>
                                            <?= htmlspecialchars($member['name']) ?>
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

                        <div class="members-title" style="margin-top: 30px;">Panel Students (Candidates)</div>
                        <div class="members-grid">
                            <?php foreach ($data['students'] as $s): ?>
                                <div class="member-item" style="background: #fff; border-style: solid;">
                                    <div class="member-icon" style="background: #eef2f7; color: #764ba2;">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="member-info">
                                        <h4 style="<?= $s['reg_no'] === $student['reg_no'] ? 'color: var(--primary); font-weight: 700;' : '' ?>">
                                            <?= htmlspecialchars($s['name']) ?>
                                            <?php if ($s['reg_no'] === $student['reg_no']): ?>
                                                <span class="role-tag" style="background: var(--primary); color: white;">You</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p><?= htmlspecialchars($s['reg_no']) ?></p>
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
                <p>You have not been assigned to any defense panels for the current session yet.</p>
                <p style="font-size: 14px; opacity: 0.7;">Panels are typically assigned after project topics are approved.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
