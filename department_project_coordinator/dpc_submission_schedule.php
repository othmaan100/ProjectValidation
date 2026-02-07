<?php
include_once __DIR__ .'/../includes/auth.php';
include_once __DIR__ .'/../includes/db.php';

// Redirect if user is not DPC
if ($_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dpc_id = $_SESSION['user_id'];

// Fetch the DPC's department info
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name FROM users u JOIN departments d ON u.department = d.id WHERE u.id = ?");
$stmt->execute([$dpc_id]);
$dpc_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dpc_info) {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dept_id = $dpc_info['dept_id'];
$dept_name = $dpc_info['department_name'];
$message = '';
$status = '';

// Fetch existing general schedule
$stmt = $conn->prepare("SELECT * FROM submission_schedules WHERE department_id = ?");
$stmt->execute([$dept_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all students for the dropdown/search
$students_stmt = $conn->prepare("SELECT id, name, reg_no FROM students WHERE department = ? ORDER BY name");
$students_stmt->execute([$dept_id]);
$all_students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Student Override Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_override'])) {
    $student_id = $_POST['student_id'];
    $ov_start = $_POST['override_start'];
    $ov_end = $_POST['override_end'];

    if (empty($student_id) || empty($ov_start) || empty($ov_end)) {
        $message = "Please select a student and provide both dates for the override.";
        $status = "error";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO student_submission_overrides (student_id, submission_start, submission_end, is_active) 
                                   VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE submission_start = ?, submission_end = ?, is_active = 1");
            $stmt->execute([$student_id, $ov_start, $ov_end, $ov_start, $ov_end]);
            $message = "Individual student override set successfully.";
            $status = "success";
        } catch (PDOException $e) {
            $message = "Error setting override: " . $e->getMessage();
            $status = "error";
        }
    }
}

// Handle Override Deletion
if (isset($_GET['delete_override'])) {
    $ov_id = $_GET['delete_override'];
    $stmt = $conn->prepare("DELETE FROM student_submission_overrides WHERE id = ?");
    $stmt->execute([$ov_id]);
    $message = "Override removed.";
    $status = "success";
}

// Fetch all active overrides for this department
$overrides_stmt = $conn->prepare("
    SELECT o.*, s.name, s.reg_no 
    FROM student_submission_overrides o 
    JOIN students s ON o.student_id = s.id 
    WHERE s.department = ?
");
$overrides_stmt->execute([$dept_id]);
$overrides = $overrides_stmt->fetchAll(PDO::FETCH_ASSOC);

// General Schedule Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_general'])) {
    $start = $_POST['submission_start'];
    $end = $_POST['submission_end'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($start) || empty($end)) {
        $message = "Please provide both start and end dates.";
        $status = "error";
    } elseif (strtotime($start) >= strtotime($end)) {
        $message = "Start date must be before the end date.";
        $status = "error";
    } else {
        try {
            if ($schedule) {
                $stmt = $conn->prepare("UPDATE submission_schedules SET submission_start = ?, submission_end = ?, is_active = ? WHERE department_id = ?");
                $stmt->execute([$start, $end, $is_active, $dept_id]);
            } else {
                $stmt = $conn->prepare("INSERT INTO submission_schedules (department_id, submission_start, submission_end, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([$dept_id, $start, $end, $is_active]);
            }
            $message = "Submission schedule updated successfully.";
            $status = "success";
            
            // Refresh schedule data
            $stmt = $conn->prepare("SELECT * FROM submission_schedules WHERE department_id = ?");
            $stmt->execute([$dept_id]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $status = "error";
        }
    }
}

// Format dates for input fields
$start_val = $schedule ? date('Y-m-d\TH:i', strtotime($schedule['submission_start'])) : '';
$end_val = $schedule ? date('Y-m-d\TH:i', strtotime($schedule['submission_end'])) : '';
$active_val = $schedule ? ($schedule['is_active'] ? 'checked' : '') : 'checked';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Submission Schedule | DPC Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --secondary: #2e59d9; --bg: #f8f9fc; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; }
        .page-container { max-width: 800px; margin: 10px auto 50px auto; padding: 20px; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2c3e50; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #777; font-size: 16px; }

        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: 600; color: #333; }
        .input-styled { 
            width: 100%; padding: 12px 15px; border: 2px solid #e3e6f0; border-radius: 10px; 
            font-size: 15px; transition: 0.3s; box-sizing: border-box;
        }
        .input-styled:focus { border-color: var(--primary); outline: none; }

        .switch-group { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { 
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; 
            background-color: #ccc; transition: .4s; border-radius: 34px; 
        }
        .slider:before { 
            position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; 
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(26px); }

        .btn-save { 
            width: 100%; padding: 15px; border: none; border-radius: 12px; 
            background: var(--primary); color: white; font-weight: 700; cursor: pointer; 
            transition: 0.3s; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-save:hover { background: var(--secondary); transform: translateY(-2px); }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 20px; }
        .back-btn:hover { text-decoration: underline; }

        .current-status { 
            background: #f8faff; border-radius: 15px; padding: 20px; border: 1px solid #eef2f8; margin-bottom: 30px;
            display: flex; justify-content: space-around; text-align: center;
        }
        .status-box h4 { margin: 0 0 5px; font-size: 12px; text-transform: uppercase; color: #777; }
        .status-box p { margin: 0; font-weight: 700; color: #333; }

        @media (max-width: 600px) {
            .page-container { margin: 20px auto; padding: 0 15px; }
            .card { padding: 25px; }
            .header h1 { font-size: 22px; }
            .current-status { flex-direction: column; gap: 20px; text-align: left; }
            .status-box { padding: 0 !important; border: none !important; }
            .form-group div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
            .switch-group { align-items: flex-start; }
            table thead { display: none; }
            table, table tbody, table tr, table td { display: block; width: 100%; }
            table tr { margin-bottom: 20px; border: 1px solid #e3e6f0; border-radius: 12px; padding: 10px; }
            table td { border-bottom: none; padding: 8px 10px; position: relative; }
            table td:before { content: attr(data-label); font-weight: 700; color: #777; display: block; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    
    <div class="page-container">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="card">
            <div class="header">
                <h1><i class="fas fa-calendar-alt" style="color: var(--primary);"></i> General Submission Schedule</h1>
                <p>Control the window for project topic submissions for your department.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $status ?>"><?= $message ?></div>
            <?php endif; ?>

            <?php if ($schedule): ?>
                <div class="current-status">
                    <div class="status-box">
                        <h4>Submissions Start</h4>
                        <p><?= date('M d, Y | h:i A', strtotime($schedule['submission_start'])) ?></p>
                    </div>
                    <div class="status-box">
                        <h4>Submissions Close</h4>
                        <p><?= date('M d, Y | h:i A', strtotime($schedule['submission_end'])) ?></p>
                    </div>
                    <div class="status-box" style="border-left: 2px solid #eee; padding-left: 20px;">
                        <h4>Current Status</h4>
                        <?php 
                            $now_check = time();
                            $start_check = strtotime($schedule['submission_start']);
                            $end_check = strtotime($schedule['submission_end']);
                            $active_check = $schedule['is_active'];

                            if (!$active_check) echo '<span style="color: #e74a3b;">Disabled</span>';
                            elseif ($now_check < $start_check) echo '<span style="color: #f6c23e;">Not Yet Open</span>';
                            elseif ($now_check > $end_check) echo '<span style="color: #e74a3b;">Closed</span>';
                            else echo '<span style="color: #1cc88a;">Active / Open</span>';
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="update_general" value="1">
                <div class="form-group">
                    <label>Start Date & Time</label>
                    <input type="datetime-local" name="submission_start" class="input-styled" value="<?= $start_val ?>" required>
                </div>

                <div class="form-group">
                    <label>End Date & Time</label>
                    <input type="datetime-local" name="submission_end" class="input-styled" value="<?= $end_val ?>" required>
                </div>

                <div class="switch-group">
                    <label class="switch">
                        <input type="checkbox" name="is_active" <?= $active_val ?>>
                        <span class="slider"></span>
                    </label>
                    <label style="margin: 0; cursor: pointer; font-weight: 600;">Enable Submissions for this Department</label>
                </div>

                <button type="submit" class="btn-save">
                    Update General Schedule <i class="fas fa-save"></i>
                </button>
            </form>
        </div>

        <div class="card" style="margin-top: 30px;">
            <div class="header">
                <h1><i class="fas fa-user-clock" style="color: var(--success);"></i> Individual Student Overrides</h1>
                <p>Grant specific students access even when the general window is closed.</p>
            </div>

            <form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 30px;">
                <input type="hidden" name="add_override" value="1">
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" class="input-styled select2" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($all_students as $stu): ?>
                            <option value="<?= $stu['id'] ?>"><?= htmlspecialchars($stu['name']) ?> (<?= htmlspecialchars($stu['reg_no']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Override Start</label>
                        <input type="datetime-local" name="override_start" class="input-styled" required>
                    </div>
                    <div class="form-group">
                        <label>Override End</label>
                        <input type="datetime-local" name="override_end" class="input-styled" required>
                    </div>
                </div>
                <button type="submit" class="btn-save" style="background: var(--success);">
                    Grant Individual Access <i class="fas fa-plus"></i>
                </button>
            </form>

            <h3>Existing Overrides</h3>
            <?php if (empty($overrides)): ?>
                <p style="text-align: center; color: #888; padding: 20px;">No individual student overrides found.</p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #eee;">
                            <th style="padding: 10px;">Student</th>
                            <th style="padding: 10px;">Window</th>
                            <th style="padding: 10px;">Status</th>
                            <th style="padding: 10px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overrides as $ov): 
                            $ov_start = strtotime($ov['submission_start']);
                            $ov_end = strtotime($ov['submission_end']);
                            $now = time();
                            $is_current = ($now >= $ov_start && $now <= $ov_end);
                        ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td data-label="Student" style="padding: 10px;">
                                    <strong><?= htmlspecialchars($ov['name']) ?></strong><br>
                                    <small><?= htmlspecialchars($ov['reg_no']) ?></small>
                                </td>
                                <td data-label="Window" style="padding: 10px;">
                                    <small><?= date('M d, H:i', $ov_start) ?> - <?= date('M d, H:i', $ov_end) ?></small>
                                </td>
                                <td data-label="Status" style="padding: 10px;">
                                    <span style="color: <?= $is_current ? 'var(--success)' : 'var(--danger)' ?>; font-weight: bold;">
                                        <?= $is_current ? 'Active' : 'Expired/Future' ?>
                                    </span>
                                </td>
                                <td data-label="Action" style="padding: 10px;">
                                    <a href="?delete_override=<?= $ov['id'] ?>" style="color: var(--danger);" onclick="return confirm('Remove student override?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once __DIR__ .'/../includes/footer.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Search and select student...",
                width: '100%'
            });
        });
    </script>
</body>
</html>
