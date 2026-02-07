<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dpc_id = $_SESSION['user_id'];
$panel_id = $_GET['id'] ?? null;

// Use current session from global settings
$active_session = $current_session;

if (!$panel_id) {
    header("Location: dpc_manage_panels.php");
    exit();
}

// Fetch the DPC's department info
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name FROM users u JOIN departments d ON u.department = d.id WHERE u.id = ?");
$stmt->execute([$dpc_id]);
$dpc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $dpc_info['dept_id'];
$dept_name = $dpc_info['department_name'];

// Verify panel belongs to this department
$stmt = $conn->prepare("SELECT * FROM defense_panels WHERE id = ? AND department_id = ?");
$stmt->execute([$panel_id, $dept_id]);
$panel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$panel) {
    header("Location: dpc_manage_panels.php");
    exit();
}

$message = '';
$message_type = '';

// Handle Panel Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_panel'])) {
    $panel_name = trim($_POST['panel_name']);
    $max_students = (int)$_POST['max_students'];
    $supervisor_ids = $_POST['supervisor_ids'] ?? [];

    if (!empty($panel_name) && $max_students > 0) {
        try {
            $conn->beginTransaction();
            
            // Update panel details
            $stmt = $conn->prepare("UPDATE defense_panels SET panel_name = ?, max_students = ? WHERE id = ?");
            $stmt->execute([$panel_name, $max_students, $panel_id]);

            // Update panelists (remove old, insert new)
            $stmt = $conn->prepare("DELETE FROM panel_members WHERE panel_id = ?");
            $stmt->execute([$panel_id]);

            $stmt = $conn->prepare("INSERT INTO panel_members (panel_id, supervisor_id) VALUES (?, ?)");
            foreach ($supervisor_ids as $sup_id) {
                $stmt->execute([$panel_id, $sup_id]);
            }

            $conn->commit();
            $message = "Panel updated successfully!";
            $message_type = "success";
            
            // Refresh panel data
            $stmt = $conn->prepare("SELECT * FROM defense_panels WHERE id = ?");
            $stmt->execute([$panel_id]);
            $panel = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "Please provide all required fields.";
        $message_type = "error";
    }
}

// Handle Student Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_students'])) {
    $student_ids = $_POST['student_ids'] ?? [];
    if (!empty($student_ids)) {
        try {
            $conn->beginTransaction();
            
            // Check capacity
            $cap_stmt = $conn->prepare("SELECT max_students, (SELECT COUNT(*) FROM student_panel_assignments WHERE panel_id = ? AND academic_session = ?) as current_count FROM defense_panels WHERE id = ?");
            $cap_stmt->execute([$panel_id, $active_session, $panel_id]);
            $cap_info = $cap_stmt->fetch(PDO::FETCH_ASSOC);

            if (($cap_info['current_count'] + count($student_ids)) > $cap_info['max_students']) {
                throw new Exception("Adding these students would exceed the panel capacity ({$cap_info['max_students']}).");
            }

            $stmt = $conn->prepare("INSERT INTO student_panel_assignments (student_id, panel_id, academic_session) VALUES (?, ?, ?)");
            foreach ($student_ids as $stu_id) {
                // Remove existing assignment first
                $del = $conn->prepare("DELETE FROM student_panel_assignments WHERE student_id = ? AND academic_session = ?");
                $del->execute([$stu_id, $active_session]);
                $stmt->execute([$stu_id, $panel_id, $active_session]);
            }
            $conn->commit();
            $message = count($student_ids) . " students added to panel successfully!";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Handle Student Removal
if (isset($_GET['remove_student'])) {
    $assignment_id = $_GET['remove_student'];
    try {
        $stmt = $conn->prepare("DELETE FROM student_panel_assignments WHERE id = ? AND panel_id = ?");
        $stmt->execute([$assignment_id, $panel_id]);
        $message = "Student removed from panel successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch all supervisors in this department
$sup_stmt = $conn->prepare("SELECT id, name FROM supervisors WHERE department = ? ORDER BY name");
$sup_stmt->execute([$dept_id]);
$all_supervisors = $sup_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current panelists
$member_stmt = $conn->prepare("SELECT supervisor_id FROM panel_members WHERE panel_id = ?");
$member_stmt->execute([$panel_id]);
$current_members = $member_stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch assigned students
$student_stmt = $conn->prepare("
    SELECT spa.id as assignment_id, s.name, s.reg_no, spa.academic_session
    FROM student_panel_assignments spa
    JOIN students s ON spa.student_id = s.id
    WHERE spa.panel_id = ?
    ORDER BY s.name
");
$student_stmt->execute([$panel_id]);
$assigned_students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unassigned students for adding
$unassigned_stmt = $conn->prepare("
    SELECT s.id, s.name, s.reg_no 
    FROM students s 
    WHERE s.department = ? 
    AND s.id NOT IN (SELECT student_id FROM student_panel_assignments WHERE academic_session = ?)
    ORDER BY s.name
");
$unassigned_stmt->execute([$dept_id, $active_session]);
$unassigned_students = $unassigned_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Panel - <?= htmlspecialchars($panel['panel_name']) ?></title>
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
        .header h1 { font-size: 24px; font-weight: 700; color: var(--primary); margin: 0; }

        .alert { padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; font-weight: 600; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #10b981; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #ef4444; }

        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .card h2 { font-size: 20px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }

        .form-group { margin-bottom: 20px; }
        label { font-size: 14px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 16px; outline: none; transition: 0.2s; box-sizing: border-box; }
        input:focus { border-color: var(--primary-light); }

        .btn { padding: 12px 20px; border: none; border-radius: 12px; font-family: inherit; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #3730a3; transform: translateY(-2px); }
        .btn-danger { background: #fee2e2; color: var(--danger); font-size: 13px; }
        .btn-danger:hover { background: var(--danger); color: white; }
        .btn-secondary { background: #f1f5f9; color: var(--text-main); }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }

        .panel-badge { background: #f1f5f9; padding: 4px 10px; border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--primary); }

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
                <h1>Edit Panel: <?= htmlspecialchars($panel['panel_name']) ?></h1>
                <p style="color: var(--text-muted);">Modify panel details and manage assignments.</p>
            </div>
            <a href="dpc_manage_panels.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Panels</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <!-- Edit Details Card -->
            <div class="card">
                <h2><i class="fas fa-edit"></i> Panel Details</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="panel_name">Panel Name</label>
                        <input type="text" id="panel_name" name="panel_name" value="<?= htmlspecialchars($panel['panel_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="max_students">Max Students</label>
                        <input type="number" id="max_students" name="max_students" min="1" value="<?= htmlspecialchars($panel['max_students']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Panel Members (Supervisors)</label>
                        <select name="supervisor_ids[]" class="select2" multiple required>
                            <?php foreach ($all_supervisors as $sup): ?>
                                <option value="<?= $sup['id'] ?>" <?= in_array($sup['id'], $current_members) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sup['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="update_panel" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>

                <hr style="margin: 30px 0; border: none; border-top: 2px dashed #f1f5f9;">

                <h2><i class="fas fa-user-plus"></i> Add Students</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Select Students (Unassigned)</label>
                        <?php if (count($unassigned_students) > 0): ?>
                            <select name="student_ids[]" class="select2-students" multiple required>
                                <?php foreach ($unassigned_students as $stu): ?>
                                    <option value="<?= $stu['id'] ?>"><?= htmlspecialchars($stu['name']) ?> (<?= htmlspecialchars($stu['reg_no']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <p style="font-size: 13px; color: var(--text-muted); margin-top: 8px;">
                                Total unassigned: <strong><?= count($unassigned_students) ?></strong>
                            </p>
                        <?php else: ?>
                            <div style="background: #ecfdf5; color: #065f46; padding: 15px; border-radius: 12px; font-size: 14px;">
                                <i class="fas fa-check-circle"></i> All students in your department are already assigned to panels.
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($unassigned_students) > 0): ?>
                    <button type="submit" name="assign_students" class="btn btn-primary" style="width: 100%; justify-content: center; background: var(--success);">
                        <i class="fas fa-plus"></i> Add Selected Students
                    </button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Assigned Students Card -->
            <div class="card">
                <h2><i class="fas fa-user-graduate"></i> Assigned Students (<?= count($assigned_students) ?>)</h2>
                <?php if (count($assigned_students) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Session</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned_students as $asgn): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($asgn['name']) ?></strong><br>
                                            <small style="color: var(--text-muted);"><?= htmlspecialchars($asgn['reg_no']) ?></small>
                                        </td>
                                        <td><span class="panel-badge"><?= htmlspecialchars($asgn['academic_session']) ?></span></td>
                                        <td style="text-align: right;">
                                            <a href="?id=<?= $panel_id ?>&remove_student=<?= $asgn['assignment_id'] ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Remove student from this panel?')">
                                                <i class="fas fa-user-minus"></i> Remove
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <i class="fas fa-user-slash" style="font-size: 40px; margin-bottom: 15px; opacity: 0.2;"></i>
                        <p>No students assigned to this panel.</p>
                        <a href="dpc_assign_panels.php" class="btn btn-secondary">Assign Students</a>
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
                placeholder: "Select supervisors...",
                width: '100%'
            });
            $('.select2-students').select2({
                placeholder: "Select unassigned students...",
                width: '100%'
            });
        });
    </script>
</body>
</html>
