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

// Handle Panel Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_panel'])) {
    $panel_name = trim($_POST['panel_name']);
    $max_students = (int)$_POST['max_students'];
    $supervisor_ids = $_POST['supervisor_ids'] ?? [];

    if (!empty($panel_name) && !empty($supervisor_ids) && $max_students > 0) {
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("INSERT INTO defense_panels (panel_name, department_id, max_students) VALUES (?, ?, ?)");
            $stmt->execute([$panel_name, $dept_id, $max_students]);
            $panel_id = $conn->lastInsertId();

            $stmt = $conn->prepare("INSERT INTO panel_members (panel_id, supervisor_id) VALUES (?, ?)");
            foreach ($supervisor_ids as $sup_id) {
                $stmt->execute([$panel_id, $sup_id]);
            }

            $conn->commit();
            $message = "Panel '$panel_name' created successfully!";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "Please provide a panel name, valid max students, and select at least one supervisor.";
        $message_type = "error";
    }
}

// Handle Panel Deletion
if (isset($_GET['delete_panel'])) {
    $panel_id = $_GET['delete_panel'];
    try {
        // Verify panel belongs to this department
        $stmt = $conn->prepare("SELECT id FROM defense_panels WHERE id = ? AND department_id = ?");
        $stmt->execute([$panel_id, $dept_id]);
        if ($stmt->fetch()) {
            $stmt = $conn->prepare("DELETE FROM defense_panels WHERE id = ?");
            $stmt->execute([$panel_id]);
            $message = "Panel deleted successfully!";
            $message_type = "success";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch all supervisors in this department
$sup_stmt = $conn->prepare("SELECT id, name FROM supervisors WHERE department = ? ORDER BY name");
$sup_stmt->execute([$dept_id]);
$supervisors = $sup_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all panels for this department
$panel_stmt = $conn->prepare("
    SELECT dp.*, GROUP_CONCAT(s.name SEPARATOR ', ') as members
    FROM defense_panels dp
    LEFT JOIN panel_members pm ON dp.id = pm.panel_id
    LEFT JOIN supervisors s ON pm.supervisor_id = s.id
    WHERE dp.department_id = ?
    GROUP BY dp.id
    ORDER BY dp.panel_name
");
$panel_stmt->execute([$dept_id]);
$panels = $panel_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Panels - <?= htmlspecialchars($dept_name) ?></title>
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
        input[type="text"] { width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 16px; outline: none; transition: 0.2s; }
        input:focus { border-color: var(--primary-light); }

        .btn { padding: 14px 24px; border: none; border-radius: 12px; font-family: inherit; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; width: 100%; justify-content: center; }
        .btn-primary:hover { background: #3730a3; transform: translateY(-2px); }
        .btn-danger { background: #fee2e2; color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: white; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }

        .panel-badge { background: #f1f5f9; padding: 4px 10px; border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--primary); }
        
        /* Select2 Customization */
        .select2-container--default .select2-selection--multiple { border: 2px solid #e2e8f0; border-radius: 12px; padding: 8px; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: var(--primary); border: none; color: white; border-radius: 6px; padding: 2px 10px; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: white; margin-right: 8px; border: none; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover { background: none; color: #f8fafc; }

        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1>Manage Defense Panels</h1>
                <p style="color: var(--text-muted);">Create and manage panels for project defenses.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="dpc_panel_details.php" class="btn btn-primary" style="background: var(--success);"><i class="fas fa-eye"></i> View Detailed Information</a>
                <a href="dpc_assign_panels.php" class="btn btn-primary" style="width: auto;"><i class="fas fa-user-plus"></i> Assign Students to Panels</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <!-- Create Panel Card -->
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> New Panel</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="panel_name">Panel Name</label>
                        <input type="text" id="panel_name" name="panel_name" placeholder="e.g. Panel A, Software Dev Panel" required>
                    </div>
                    <div class="form-group">
                        <label for="max_students">Max Students</label>
                        <input type="number" id="max_students" name="max_students" min="1" value="10" required>
                    </div>
                    <div class="form-group">
                        <label>Select Panel Members (Supervisors)</label>
                        <select name="supervisor_ids[]" class="select2" multiple required>
                            <?php foreach ($supervisors as $sup): ?>
                                <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="create_panel" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Panel
                    </button>
                </form>
            </div>

            <!-- Panels List Card -->
            <div class="card">
                <h2><i class="fas fa-users-rectangle"></i> Existing Panels</h2>
                <?php if (count($panels) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Panel Name</th>
                                <th>Max Students</th>
                                <th>Members</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($panels as $panel): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($panel['panel_name']) ?></strong></td>
                                    <td><span class="panel-badge"><?= htmlspecialchars($panel['max_students']) ?></span></td>
                                    <td>
                                        <div style="color: var(--text-muted); font-size: 14px;">
                                            <?= htmlspecialchars($panel['members'] ?: 'No members assigned') ?>
                                        </div>
                                    </td>
                                    <td style="text-align: right; display: flex; gap: 8px; justify-content: flex-end;">
                                        <a href="dpc_edit_panel.php?id=<?= $panel['id'] ?>" class="btn" style="background: #e0e7ff; color: var(--primary); padding: 8px 12px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete_panel=<?= $panel['id'] ?>" class="btn btn-danger" style="padding: 8px 12px;" onclick="return confirm('Are you sure you want to delete this panel?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <i class="fas fa-users-slash" style="font-size: 40px; margin-bottom: 15px; opacity: 0.2;"></i>
                        <p>No panels created yet.</p>
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
        });
    </script>
</body>
</html>
