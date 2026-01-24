<?php
include_once __DIR__ .'/../includes/auth.php';
include_once __DIR__ .'/../includes/db.php';

// Redirect if user is not DPC
if ($_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dept_id = $_SESSION['department']; // Assuming session department matches the DPC department ID
$message = '';
$status = '';

// Fetch existing schedule
$stmt = $conn->prepare("SELECT * FROM submission_schedules WHERE department_id = ?");
$stmt->execute([$dept_id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        .page-container { max-width: 800px; margin: 50px auto; padding: 20px; }
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
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    
    <div class="page-container">
        <a href="dpc_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="card">
            <div class="header">
                <h1><i class="fas fa-calendar-alt" style="color: var(--primary);"></i> Submission Schedule</h1>
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
                            $now = time();
                            $start = strtotime($schedule['submission_start']);
                            $end = strtotime($schedule['submission_end']);
                            $active = $schedule['is_active'];

                            if (!$active) echo '<span style="color: #e74a3b;">Disabled by Admin</span>';
                            elseif ($now < $start) echo '<span style="color: #f6c23e;">Not Yet Open</span>';
                            elseif ($now > $end) echo '<span style="color: #e74a3b;">Closed</span>';
                            else echo '<span style="color: #1cc88a;">Active / Open</span>';
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">
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
                    Update Schedule <i class="fas fa-save"></i>
                </button>
            </form>
        </div>
    </div>

    <?php include_once __DIR__ .'/../includes/footer.php'; ?>
</body>
</html>
