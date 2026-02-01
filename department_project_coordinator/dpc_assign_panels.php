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

$message = '';
$message_type = '';

// Fetch active session from global settings
$session_stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_session'");
$active_session = $session_stmt->fetchColumn() ?: date('Y') . '/' . (date('Y') + 1);

// Handle Student Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_students'])) {
    $panel_id = $_POST['panel_id'];
    $student_ids = $_POST['student_ids'] ?? [];

    if (!empty($panel_id) && !empty($student_ids)) {
        try {
            $conn->beginTransaction();
            
            // Fetch panel type and capacity
            $stmt = $conn->prepare("SELECT panel_type, max_students, (SELECT COUNT(*) FROM student_panel_assignments WHERE panel_id = ? AND academic_session = ?) as current_count FROM defense_panels WHERE id = ?");
            $stmt->execute([$panel_id, $active_session, $panel_id]);
            $panel_info = $stmt->fetch(PDO::FETCH_ASSOC);
            $panel_type = $panel_info['panel_type'];

            if (($panel_info['current_count'] + count($student_ids)) > $panel_info['max_students']) {
                throw new Exception("Panel capacity exceeded! Max: {$panel_info['max_students']}, Current: {$panel_info['current_count']}. Cannot add " . count($student_ids) . " more.");
            }
            
            $stmt = $conn->prepare("INSERT INTO student_panel_assignments (student_id, panel_id, panel_type, academic_session) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE panel_id = VALUES(panel_id)");
            foreach ($student_ids as $stu_id) {
                $stmt->execute([$stu_id, $panel_id, $panel_type, $active_session]);
            }

            $conn->commit();
            $message = count($student_ids) . " students assigned to panel successfully!";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "Please select a panel and at least one student.";
        $message_type = "error";
    }
}

// Handle Auto Allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_allocate'])) {
    $stage = $_POST['stage'];
    try {
        $conn->beginTransaction();

        // 1. Fetch all panels for this stage with capacity info
        $stmt = $conn->prepare("
            SELECT dp.id, dp.panel_type, dp.max_students, 
            (SELECT COUNT(*) FROM student_panel_assignments spa WHERE spa.panel_id = dp.id AND spa.academic_session = ?) as current_count
            FROM defense_panels dp
            WHERE dp.department_id = ? AND dp.panel_type = ?
            ORDER BY dp.id
        ");
        $stmt->execute([$active_session, $dept_id, $stage]);
        $panels_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch all unassigned students for THIS stage
        $stu_stmt = $conn->prepare("
            SELECT s.id 
            FROM students s 
            WHERE s.department = ? 
            AND s.id NOT IN (SELECT student_id FROM student_panel_assignments WHERE academic_session = ? AND panel_type = ?)
            ORDER BY s.id
        ");
        $stu_stmt->execute([$dept_id, $active_session, $stage]);
        $unassigned_students = $stu_stmt->fetchAll(PDO::FETCH_COLUMN);

        $assigned_count = 0;
        $student_idx = 0;
        $total_unassigned = count($unassigned_students);

        // 3. Allocate
        foreach ($panels_list as $panel) {
            $available_slots = $panel['max_students'] - $panel['current_count'];
            
            if ($available_slots <= 0) continue;

            $to_assign = array_slice($unassigned_students, $student_idx, $available_slots);
            
            if (empty($to_assign)) break; // No more students to assign

            $insert_stmt = $conn->prepare("INSERT INTO student_panel_assignments (student_id, panel_id, panel_type, academic_session) VALUES (?, ?, ?, ?)");
            foreach ($to_assign as $stu_id) {
                $insert_stmt->execute([$stu_id, $panel['id'], $panel['panel_type'], $active_session]);
                $assigned_count++;
            }

            $student_idx += count($to_assign);
        }

        $conn->commit();
        
        $remaining = $total_unassigned - $assigned_count;
        $message = "Auto-allocation for " . ucfirst($stage) . " complete! Assigned $assigned_count students. Remaining: $remaining.";
        $message_type = $remaining > 0 ? "warning" : "success";

    } catch (Exception $e) {
        $conn->rollBack();
        $message = "Auto-allocation failed: " . $e->getMessage();
        $message_type = "error";
    }
}

// Handle Single Removal
if (isset($_GET['remove_assignment'])) {
    $assignment_id = $_GET['remove_assignment'];
    try {
        $stmt = $conn->prepare("DELETE FROM student_panel_assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $message = "Assignment removed successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch all panels in this department
$panel_stmt = $conn->prepare("SELECT id, panel_name, panel_type FROM defense_panels WHERE department_id = ? ORDER BY panel_type, panel_name");
$panel_stmt->execute([$dept_id]);
$panels = $panel_stmt->fetchAll(PDO::FETCH_ASSOC);

// For the UI, we'll need unassigned students per type or just all students
$all_stu_stmt = $conn->prepare("SELECT id, name, reg_no FROM students WHERE department = ? ORDER BY name");
$all_stu_stmt->execute([$dept_id]);
$all_students = $all_stu_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current assignments for display
$assign_stmt = $conn->prepare("
    SELECT spa.id as assignment_id, s.name as student_name, s.reg_no, dp.panel_name, dp.panel_type, spa.academic_session
    FROM student_panel_assignments spa
    JOIN students s ON spa.student_id = s.id
    JOIN defense_panels dp ON spa.panel_id = dp.id
    WHERE dp.department_id = ?
    ORDER BY FIELD(dp.panel_type, 'proposal', 'internal', 'external'), dp.panel_name, s.name
");
$assign_stmt->execute([$dept_id]);
$assignments = $assign_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Panels - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary: #4338ca;
            --primary-light: #6366f1;
            --success: #059669;
            --danger: #dc2626;
            --bg-body: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        .header { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; font-weight: 700; color: var(--primary); }

        .alert { padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; font-weight: 600; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #10b981; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #ef4444; }

        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }

        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .card h2 { font-size: 20px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }

        .form-group { margin-bottom: 20px; }
        label { font-size: 14px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }

        .btn { padding: 14px 24px; border: none; border-radius: 12px; font-family: inherit; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; width: 100%; justify-content: center; }
        .btn-primary:hover { background: #3730a3; transform: translateY(-2px); }
        .btn-danger { background: #fee2e2; color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: white; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }

        .session-badge { background: #e0e7ff; color: var(--primary); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        
        select.form-control { width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 16px; outline: none; appearance: none; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 15px center; }

        /* Select2 Customization */
        .select2-container--default .select2-selection--multiple { border: 2px solid #e2e8f0; border-radius: 12px; padding: 8px; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: var(--primary); border: none; color: white; border-radius: 6px; padding: 2px 10px; }

        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1>Assign Students to Panels</h1>
                <p style="color: var(--text-muted);">Current Session: <span class="session-badge"><?= $active_session ?></span></p>
            </div>
            <a href="dpc_manage_panels.php" class="btn btn-primary" style="width: auto;"><i class="fas fa-list"></i> Manage Panels</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <!-- Assignment Form Card -->
            <div class="card">
                <h2><i class="fas fa-link"></i> New Assignment</h2>
                
                <!-- Auto Allocation Section -->
                <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px dashed #cbd5e1;">
                    <h3 style="margin-top: 0; font-size: 16px; color: var(--text-main);">Auto-Allocation</h3>
                    <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 15px;">Automatically assign students to available panels for a specific stage.</p>
                    <form method="POST" onsubmit="return confirm('This will automatically assign students to available panels for the selected stage. Continue?');">
                        <div class="form-group">
                            <label for="stage">Select Defense Stage</label>
                            <select name="stage" id="stage" class="form-control" style="margin-bottom: 15px;" required>
                                <option value="proposal">Project Proposal</option>
                                <option value="internal">Internal Defense</option>
                                <option value="external">External Defense</option>
                            </select>
                        </div>
                        <button type="submit" name="auto_allocate" class="btn" style="background: var(--primary-light); color: white;">
                            <i class="fas fa-magic"></i> Run Auto Allocation
                        </button>
                    </form>
                </div>

                <h3 style="font-size: 16px; margin-bottom: 15px;">Manual Assignment</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Select Defense Panel</label>
                        <select name="panel_id" class="form-control" required>
                            <option value="">-- Choose Panel --</option>
                            <?php foreach ($panels as $panel): ?>
                                <option value="<?= $panel['id'] ?>"><?= htmlspecialchars($panel['panel_name']) ?> (<?= ucfirst($panel['panel_type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Students</label>
                        <select name="student_ids[]" class="select2" multiple required>
                            <?php foreach ($all_students as $stu): ?>
                                <option value="<?= $stu['id'] ?>"><?= htmlspecialchars($stu['name']) ?> (<?= htmlspecialchars($stu['reg_no']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--text-muted); display: block; margin-top: 5px;">Note: Students can be assigned to one panel for each stage (Proposal, Internal, External).</small>
                    </div>
                    <button type="submit" name="assign_students" class="btn btn-primary">
                        <i class="fas fa-save"></i> Assign to Panel
                    </button>
                </form>
            </div>

            <!-- Assignments List Card -->
            <div class="card">
                <h2><i class="fas fa-clipboard-list"></i> Assignment Records</h2>
                <?php if (count($assignments) > 0): 
                    $grouped_assignments = [
                        'proposal' => [],
                        'internal' => [],
                        'external' => []
                    ];
                    foreach ($assignments as $asgn) {
                        $grouped_assignments[$asgn['panel_type']][] = $asgn;
                    }

                    $stages = [
                        'proposal' => 'Project Proposal Defense',
                        'internal' => 'Internal Defense',
                        'external' => 'External Defense'
                    ];

                    foreach ($stages as $type => $label):
                        if (!empty($grouped_assignments[$type])):
                ?>
                    <h3 style="font-size: 16px; margin-top: 25px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; color: var(--primary);">
                        <?= $label ?> assignments
                    </h3>
                    <table style="margin-bottom: 30px;">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Panel Name</th>
                                <th>Session</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grouped_assignments[$type] as $asgn): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($asgn['student_name']) ?></strong><br>
                                        <small style="color: var(--text-muted);"><?= htmlspecialchars($asgn['reg_no']) ?></small>
                                    </td>
                                    <td>
                                        <span class="panel-badge"><?= htmlspecialchars($asgn['panel_name']) ?></span>
                                    </td>
                                    <td><span class="session-badge"><?= htmlspecialchars($asgn['academic_session']) ?></span></td>
                                    <td style="text-align: right;">
                                        <a href="?remove_assignment=<?= $asgn['assignment_id'] ?>" class="btn btn-danger" style="padding: 8px 12px;" onclick="return confirm('Remove student from this panel?')">
                                            <i class="fas fa-user-minus"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php 
                        endif;
                    endforeach;
                else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <i class="fas fa-user-tag" style="font-size: 40px; margin-bottom: 15px; opacity: 0.2;"></i>
                        <p>No student assignments yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Select students...",
                width: '100%'
            });
        });
    </script>
</body>
</html>
