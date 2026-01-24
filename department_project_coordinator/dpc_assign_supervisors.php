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

// Function to get distinct unassigned students with all their proposed topics
function getUnassignedStudents($conn, $dept_id) {
    $stmt = $conn->prepare("
        SELECT s.id, s.reg_no, s.name, s.department, 
               GROUP_CONCAT(p.topic SEPARATOR '||') AS topics, 
               d.department_name
        FROM students s
        JOIN departments d ON d.id = s.department
        LEFT JOIN project_topics p ON p.student_id = s.id
        WHERE s.id NOT IN (SELECT student_id FROM supervision)
        AND s.department = ?
        GROUP BY s.id
        ORDER BY s.name
    ");
    $stmt->execute([$dept_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle automatic allocation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['auto_allocate'])) {
    try {
        $conn->beginTransaction();
        
        $unassignedStudents = getUnassignedStudents($conn, $dept_id);
        
        $stmt = $conn->prepare("
            SELECT *, (max_students - current_load) AS available_slots
            FROM supervisors 
            WHERE department = ? AND current_load < max_students
            ORDER BY current_load ASC, RAND()
        ");
        $stmt->execute([$dept_id]);
        $supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $allocationsMade = 0;
        $errors = [];
        $currentDate = date('Y-m-d H:i:s');
        
        foreach ($unassignedStudents as $student) {
            $compatibleSupervisors = array_filter($supervisors, function($sup) use ($student) {
                return $sup['available_slots'] > 0;
            });
            
            if (empty($compatibleSupervisors)) {
                $errors[] = "No available supervisors for {$student['name']} ({$student['reg_no']})";
                continue;
            }
            
            $allocated = false;
            shuffle($compatibleSupervisors);
            
            foreach ($compatibleSupervisors as $supervisor) {
                try {
                    // Check for existing allocation
                    $checkStmt = $conn->prepare("SELECT allocation_id FROM supervision WHERE student_id = ?");
                    $checkStmt->execute([$student['id']]);
                    
                    if ($checkStmt->rowCount() > 0) continue;
                    
                    // Create allocation - Removed project_id
                    $stmt = $conn->prepare("INSERT INTO supervision (supervisor_id, student_id, allocation_date, status) VALUES (?, ?, ?, 'active')");
                    $stmt->execute([$supervisor['id'], $student['id'], $currentDate]);
                    
                    // Update supervisor load
                    $stmt = $conn->prepare("UPDATE supervisors SET current_load = current_load + 1 WHERE id = ?");
                    $stmt->execute([$supervisor['id']]);
                    
                    // Supervisor link is now purely through the supervision table
                    /* 
                    if ($student['project_id']) {
                        $stmt = $conn->prepare("UPDATE project_topics SET status = 'approved' WHERE id = ?");
                        $stmt->execute([$student['project_id']]);
                    }
                    */
                    
                    // Update local data
                    foreach ($supervisors as &$sup) {
                        if ($sup['id'] == $supervisor['id']) {
                            $sup['available_slots']--;
                            $sup['current_load']++;
                            break;
                        }
                    }
                    
                    $allocationsMade++;
                    $allocated = true;
                    break;
                } catch (PDOException $e) { if ($e->getCode() != '23000') throw $e; }
            }
            if (!$allocated) $errors[] = "Failed to allocate {$student['name']} ({$student['reg_no']})";
        }
        
        $conn->commit();
        if ($allocationsMade > 0) $_SESSION['success'] = "Allocated $allocationsMade students automatically!";
        if (!empty($errors)) $_SESSION['error'] = "Some issues:<br>" . implode("<br>", array_unique($errors));
    } catch (Exception $e) { $conn->rollBack(); $_SESSION['error'] = "Allocation failed: " . $e->getMessage(); }
    header("Location: dpc_assign_supervisors.php");
    exit();
}

// Handle manual allocation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manual_allocate'])) {
    $studentId = $_POST['student_id'];
    $supervisorId = $_POST['supervisor_id'];
    $projectId = $_POST['project_id'];
    $currentDate = date('Y-m-d H:i:s');
    
    try {
        $conn->beginTransaction();
        
        // Verify student and supervisor belong to this department
        $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND department = ?");
        $stmt->execute([$studentId, $dept_id]);
        if (!$stmt->fetch()) throw new Exception("Student not found in your department.");

        $stmt = $conn->prepare("SELECT id, current_load, max_students FROM supervisors WHERE id = ? AND department = ?");
        $stmt->execute([$supervisorId, $dept_id]);
        $supervisor = $stmt->fetch();
        if (!$supervisor) throw new Exception("Supervisor not found in your department.");
        
        if ($supervisor['current_load'] >= $supervisor['max_students']) throw new Exception("Supervisor has reached maximum capacity!");

        // Check for existing allocation
        $checkStmt = $conn->prepare("SELECT allocation_id FROM supervision WHERE student_id = ?");
        $checkStmt->execute([$studentId]);
        if ($checkStmt->rowCount() > 0) throw new Exception("Student already has an allocation!");
        
        // Create allocation - Removed project_id
        $stmt = $conn->prepare("INSERT INTO supervision (supervisor_id, student_id, allocation_date, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute([$supervisorId, $studentId, $currentDate]);
        
        // Update records
        $stmt = $conn->prepare("UPDATE supervisors SET current_load = current_load + 1 WHERE id = ?");
        $stmt->execute([$supervisorId]);
        
        /*
        if ($projectId) {
            $stmt = $conn->prepare("UPDATE project_topics SET status = 'approved' WHERE id = ?");
            $stmt->execute([$projectId]);
        }
        */
        
        $conn->commit();
        $_SESSION['success'] = "Manual allocation successful!";
    } catch (Exception $e) { $conn->rollBack(); $_SESSION['error'] = $e->getMessage(); }
    header("Location: dpc_assign_supervisors.php");
    exit();
}

// Get system statistics for department
$statsStmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(DISTINCT s.id) FROM students s 
         WHERE s.department = :dept AND s.id NOT IN (SELECT student_id FROM supervision)) AS unassigned_count,
        (SELECT COUNT(*) FROM students WHERE department = :dept) AS total_students,
        (SELECT COUNT(*) FROM supervisors WHERE department = :dept AND current_load < max_students) AS available_sup,
        (SELECT COUNT(*) FROM supervisors WHERE department = :dept) AS total_sup,
        (SELECT COUNT(DISTINCT sp.student_id) FROM supervision sp 
         JOIN students s ON sp.student_id = s.id WHERE s.department = :dept) AS allocated_count
");
$statsStmt->execute([':dept' => $dept_id]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get data for display
$allocStmt = $conn->prepare("
    SELECT s.name AS student_name, s.reg_no, s.department AS student_dept,
           su.name AS supervisor_name, su.department AS supervisor_dept,
           GROUP_CONCAT(p.topic SEPARATOR '||') AS topics, 
           sp.allocation_date, sp.status AS allocation_status
    FROM supervision sp
    JOIN students s ON sp.student_id = s.id
    JOIN supervisors su ON sp.supervisor_id = su.id
    LEFT JOIN project_topics p ON s.id = p.student_id
    WHERE s.department = :dept
    GROUP BY sp.allocation_id
    ORDER BY su.name, s.name
");
$allocStmt->execute([':dept' => $dept_id]);
$allocations = $allocStmt->fetchAll(PDO::FETCH_ASSOC);

$unassignedStudents = getUnassignedStudents($conn, $dept_id);

$supStmt = $conn->prepare("SELECT * FROM supervisors WHERE department = ? AND current_load < max_students ORDER BY name");
$supStmt->execute([$dept_id]);
$availableSupervisors = $supStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Supervisors - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --warning: #feca57; --glass: rgba(255, 255, 255, 0.95); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-bottom: 50px; }
        .page-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-card h1 { color: var(--primary); font-size: 28px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--glass); padding: 25px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .stat-info h3 { font-size: 28px; color: #2d3436; }
        .stat-info p { color: #636e72; font-size: 14px; font-weight: 600; text-transform: uppercase; }
        .main-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid rgba(0,0,0,0.05); padding-bottom: 15px; }
        .tab { padding: 12px 25px; border-radius: 12px; cursor: pointer; font-weight: 600; color: #636e72; transition: 0.3s; }
        .tab.active { background: var(--primary); color: white; }
        .tab:hover:not(.active) { background: rgba(102, 126, 234, 0.1); color: var(--primary); }
        .tab-content { display: none; animation: fadeIn 0.4s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; margin-top: 15px; }
        th { background: #f8faff; padding: 18px; text-align: left; color: #747d8c; font-size: 13px; text-transform: uppercase; }
        td { padding: 16px; border-bottom: 1px solid #eee; font-size: 14px; }
        .btn { padding: 12px 24px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #fff; font-size: 14px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-primary { background: var(--primary); }
        .btn-success { background: var(--success); }
        .btn-warning { background: var(--warning); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2d3436; }
        .form-control { width: 100%; padding: 14px; border: 2px solid #eee; border-radius: 12px; outline: none; transition: 0.3s; font-family: inherit; }
        .form-control:focus { border-color: var(--primary); }
        .alert { padding: 15px 25px; border-radius: 12px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 5px solid var(--success); }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 5px solid var(--danger); }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff8e1; color: #ff8f00; }
        .status-no-project { background: #eee; color: #777; }

        /* Select2 Customization */
        .select2-container--default .select2-selection--single {
            height: 50px;
            border: 2px solid #eee;
            border-radius: 12px;
            padding: 10px;
            background: white;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 48px;
        }
        .select2-dropdown {
            border-radius: 12px;
            border: 2px solid #667eea;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div> <!-- Close header's container -->

    <div class="page-container">
        <div class="header-card">
            <div>
                <h1><i class="fas fa-sitemap"></i> Supervisor Allocation</h1>
                <p style="color: #636e72; margin-top: 5px;">Department of <?= htmlspecialchars($dept_name) ?></p>
            </div>
            <div class="header-actions">
                <?php if ($stats['unassigned_count'] > 0 && $stats['available_sup'] > 0): ?>
                    <form method="POST">
                        <button type="submit" name="auto_allocate" class="btn btn-success"><i class="fas fa-magic"></i> Auto Allocate</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?><?php unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?><?php unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary);"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['total_students'] ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--success);"><i class="fas fa-check-double"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['allocated_count'] ?></h3>
                    <p>Allocated</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--warning);"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['unassigned_count'] ?></h3>
                    <p>Unassigned</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--secondary);"><i class="fas fa-user-tie"></i></div>
                <div class="stat-info">
                    <h3><?= $stats['available_sup'] ?>/<?= $stats['total_sup'] ?></h3>
                    <p>Available Staff</p>
                </div>
            </div>
        </div>

        <div class="main-card">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('allocations')"><i class="fas fa-list"></i> Current Allocations</div>
                <div class="tab" onclick="switchTab('manual')"><i class="fas fa-hand-pointer"></i> Manual Allocation</div>
                <div class="tab" onclick="switchTab('unassigned')"><i class="fas fa-user-slash"></i> Unassigned Students</div>
            </div>

            <div id="allocations" class="tab-content active">
                <?php if (count($allocations) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Reg No</th>
                                <th>Supervisor</th>
                                <th>Project Topic</th>
                                <th>Allocation Date</th>
                                <th style="text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allocations as $alloc): ?>
                                <tr>
                                    <td><span style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($alloc['student_name']) ?></span></td>
                                    <td><code><?= htmlspecialchars($alloc['reg_no']) ?></code></td>
                                    <td><?= htmlspecialchars($alloc['supervisor_name']) ?></td>
                                    <td style="max-width:300px;">
                                        <?php 
                                        if ($alloc['topics']) {
                                            $tps = explode('||', $alloc['topics']);
                                            foreach ($tps as $index => $t) {
                                                echo '<div style="font-size: 12px; margin-bottom: 4px; line-height: 1.4;">' . ($index + 1) . '. ' . htmlspecialchars($t) . '</div>';
                                            }
                                        } else {
                                            echo '<span style="color:#999; font-style:italic;">[No Topics Submitted]</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($alloc['allocation_date'])) ?></td>
                                    <td style="text-align: center;">
                                        <span class="status-badge status-active">Active</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align:center; padding: 60px; color: #636e72;">
                        <i class="fas fa-folder-open" style="font-size: 40px; margin-bottom: 20px; opacity: 0.3;"></i>
                        <p>No active allocations found in your department.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="manual" class="tab-content">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h2 style="margin-bottom: 25px; color: var(--primary); text-align: center;">Manual Allocation Form</h2>
                    <?php if (count($unassignedStudents) > 0 && count($availableSupervisors) > 0): ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Select Student</label>
                                <select name="student_id" id="sel_student" class="form-control select2" required>
                                    <option value="">-- Choose Student --</option>
                                    <?php foreach ($unassignedStudents as $student): ?>
                                        <option value="<?= $student['id'] ?>">
                                            <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['reg_no']) ?>)
                                            <?= $student['topics'] ? '' : '- [NO TOPIC]' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select Supervisor</label>
                                <select name="supervisor_id" class="form-control select2" required>
                                    <option value="">-- Choose Supervisor --</option>
                                    <?php foreach ($availableSupervisors as $sup): ?>
                                        <option value="<?= $sup['id'] ?>">
                                            <?= htmlspecialchars($sup['name']) ?> (Slots: <?= $sup['max_students'] - $sup['current_load'] ?> available)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Project ID is handled via topics relationship, no longer needed in supervision table -->
                            <button type="submit" name="manual_allocate" class="btn btn-primary" style="width:100%; justify-content: center; padding: 18px; font-size: 16px;">
                                <i class="fas fa-save"></i> Complete Allocation
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="text-align:center; padding: 40px; background: #f8faff; border-radius: 20px;">
                            <i class="fas fa-info-circle" style="font-size: 30px; color: var(--primary); margin-bottom: 15px;"></i>
                            <p>No unassigned students or available supervisors found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="unassigned" class="tab-content">
                <?php if (count($unassignedStudents) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Reg No</th>
                                <th>Proposed Project</th>
                                <th style="text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unassignedStudents as $s): ?>
                                <tr>
                                    <td><span style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($s['name']) ?></span></td>
                                    <td><code><?= htmlspecialchars($s['reg_no']) ?></code></td>
                                    <td>
                                        <?php 
                                        if ($s['topics']) {
                                            $tps = explode('||', $s['topics']);
                                            foreach ($tps as $index => $t) {
                                                echo '<div style="font-size: 12px; margin-bottom: 4px; line-height: 1.4;">' . ($index + 1) . '. ' . htmlspecialchars($t) . '</div>';
                                            }
                                        } else {
                                            echo '<span style="color:#999; font-style:italic;">No project submitted yet</span>';
                                        }
                                        ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="status-badge <?= $s['topics'] ? 'status-pending' : 'status-no-project' ?>">
                                            <?= $s['topics'] ? 'Unassigned' : 'Missing Project' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align:center; padding: 60px; color: #636e72;">
                        <i class="fas fa-check-circle" style="font-size: 40px; margin-bottom: 20px; color: var(--success);"></i>
                        <p>All students in your department have been allocated to supervisors!</p>
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
                placeholder: "-- Select --",
                allowClear: true,
                width: '100%'
            });
        });

        function switchTab(id) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById(id).classList.add('active');
        }

        /* Project ID linkage is handled automatically via student_id now */
    </script>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

