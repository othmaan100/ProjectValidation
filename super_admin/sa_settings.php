<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

// Check if the user is logged in as Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$message = '';
$message_type = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $conn->beginTransaction();
        
        $settings_to_update = [
            'current_session' => $_POST['current_session'] ?? '2023/2024',
            'max_proposals_per_student' => $_POST['max_proposals_per_student'] ?? '3',
            'allow_student_topic_edit' => isset($_POST['allow_student_topic_edit']) ? '1' : '0',
            'system_announcement' => $_POST['system_announcement'] ?? '',
            'similarity_threshold' => $_POST['similarity_threshold'] ?? '30'
        ];

        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($settings_to_update as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        $conn->commit();
        $message = "System settings updated successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $conn->rollBack();
        $message = "Error updating settings: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch current settings
$settings = [];
try {
    $stmt = $conn->query("SELECT * FROM system_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Settings - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --primary-light: #6366f1;
            --secondary: #db2777;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --bg-body: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-body); color: var(--text-main); }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }

        .settings-header {
            background: white;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .settings-header h1 { font-size: 24px; font-weight: 700; color: var(--primary); }
        
        .alert {
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
        }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #10b981; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #ef4444; }

        .settings-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .form-section { margin-bottom: 35px; }
        .form-section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .grid-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        
        label { font-size: 14px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        
        input[type="text"], 
        input[type="number"], 
        textarea, 
        select {
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 16px;
            transition: all 0.2s;
            outline: none;
        }
        
        input:focus, textarea:focus { border-color: var(--primary-light); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        
        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 4px; bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(24px); }

        .btn-save {
            background: var(--primary);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: center;
            margin-top: 20px;
        }
        .btn-save:hover { background: #3730a3; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(67, 56, 202, 0.3); }
        
        .help-text { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        @media (max-width: 768px) {
            .grid-form { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="settings-header">
            <div>
                <h1>Global System Settings</h1>
                <p style="color: var(--text-muted);">Configure academic sessions and project submission rules.</p>
            </div>
            <a href="index.php" style="text-decoration: none; color: var(--primary); font-weight: 700;"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="settings-card">
                <!-- Academic Settings -->
                <div class="form-section">
                    <h2 class="form-section-title"><i class="fas fa-calendar-alt"></i> Academic Session & Submissions</h2>
                    <div class="grid-form">
                        <div class="form-group">
                            <label for="current_session">Active Academic Session</label>
                            <input type="text" id="current_session" name="current_session" value="<?= htmlspecialchars($settings['current_session'] ?? '2023/2024') ?>" placeholder="e.g. 2023/2024">
                            <p class="help-text">This session will be applied to all new project topic submissions.</p>
                        </div>
                        <div class="form-group">
                            <label for="max_proposals_per_student">Max Topics per Student</label>
                            <input type="number" id="max_proposals_per_student" name="max_proposals_per_student" value="<?= htmlspecialchars($settings['max_proposals_per_student'] ?? '3') ?>" min="1" max="10">
                            <p class="help-text">Limit on how many topics a student can submit for validation.</p>
                        </div>
                    </div>
                </div>

                <!-- Validation Logic -->
                <div class="form-section">
                    <h2 class="form-section-title"><i class="fas fa-shield-halved"></i> Validation Rules</h2>
                    <div class="grid-form">
                        <div class="form-group">
                            <label for="similarity_threshold">Similarity Warning Threshold (%)</label>
                            <input type="number" id="similarity_threshold" name="similarity_threshold" value="<?= htmlspecialchars($settings['similarity_threshold'] ?? '30') ?>" min="0" max="100">
                            <p class="help-text">Auto-warn if topic similarity exceeds this percentage.</p>
                        </div>
                        <div class="form-group">
                            <label>Topic Modification</label>
                            <label class="toggle-switch">
                                <div class="switch">
                                    <input type="checkbox" name="allow_student_topic_edit" <?= ($settings['allow_student_topic_edit'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </div>
                                <span>Allow students to edit pending topics</span>
                            </label>
                            <p class="help-text">If disabled, topics are locked once submitted.</p>
                        </div>
                    </div>
                </div>

                <!-- Global Announcements -->
                <div class="form-section">
                    <h2 class="form-section-title"><i class="fas fa-bullhorn"></i> System Announcement</h2>
                    <div class="form-group full-width">
                        <label for="system_announcement">Dashboard Banner Text</label>
                        <textarea id="system_announcement" name="system_announcement" rows="3" placeholder="Enter announcement for all users..."><?= htmlspecialchars($settings['system_announcement'] ?? '') ?></textarea>
                        <p class="help-text">This message will appear on Student, Supervisor, and Coordinator dashboards.</p>
                    </div>
                </div>

                <button type="submit" name="update_settings" class="btn-save">
                    <i class="fas fa-save"></i> Save Global Configurations
                </button>
            </div>
        </form>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
