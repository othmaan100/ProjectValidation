<?php

include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';
// Authentication check
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'dpc' && $_SESSION['role'] !== 'admin')) {
    header("Location: unauthorized.php");
    exit();
}

// Function to get distinct unassigned students with their latest project
function getUnassignedStudents($conn) {
    return $conn->query("
        SELECT s.id, s.reg_no, s.name, s.department, 
               p.id AS project_id, p.topic, d.department_name
        FROM students s
        JOIN project_topics p ON s.id = p.student_id
        JOIN departments d ON d.id = s.department
        WHERE s.id NOT IN (SELECT student_id FROM supervision)
        AND p.id = (
            SELECT MAX(p2.id) 
            FROM project_topics p2 
            WHERE p2.student_id = s.id
        )
        ORDER BY s.name
    ")->fetchAll();
}

// Handle automatic allocation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['auto_allocate'])) {
    try {
        $conn->beginTransaction();
        
        $unassignedStudents = getUnassignedStudents($conn);
        $supervisors = $conn->query("
            SELECT *, (max_students - current_load) AS available_slots
            FROM supervisors 
            WHERE current_load < max_students
            ORDER BY current_load ASC, RAND()
        ")->fetchAll();
        
        $allocationsMade = 0;
        $errors = [];
        $currentDate = date('Y-m-d H:i:s');
        
        foreach ($unassignedStudents as $student) {
            $compatibleSupervisors = array_filter($supervisors, function($sup) use ($student) {
                return $sup['department'] == $student['department'] && $sup['available_slots'] > 0;
            });
            
            if (empty($compatibleSupervisors)) {
                $errors[] = "No available {$student['department']} supervisors for {$student['name']}";
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
                    
                    // Create allocation in supervision table
                    $stmt = $conn->prepare("
                        INSERT INTO supervision 
                        (supervisor_id, student_id, project_id, allocation_date, status) 
                        VALUES (?, ?, ?, ?, 'active')
                    ");
                    $stmt->execute([
                        $supervisor['id'],
                        $student['id'],
                        $student['project_id'],
                        $currentDate
                    ]);
                    
                    // Update supervisor load
                    $stmt = $conn->prepare("
                        UPDATE supervisors 
                        SET current_load = current_load + 1 
                        WHERE id = ?
                    ");
                    $stmt->execute([$supervisor['id']]);
                    
                    // Update project status
                    $stmt = $conn->prepare("
                        UPDATE project_topics 
                        SET status = 'approved', 
                            supervisor_id = ?,
                            supervisor_name = (SELECT name FROM supervisors WHERE id = ?)
                        WHERE id = ?
                    ");
                    $stmt->execute([$supervisor['id'], $supervisor['id'], $student['project_id']]);
                    
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
                    
                } catch (PDOException $e) {
                    if ($e->getCode() != '23000') throw $e;
                }
            }
            
            if (!$allocated) {
                $errors[] = "Failed to allocate {$student['name']}";
            }
        }
        
        $conn->commit();
        
        if ($allocationsMade > 0) {
            $_SESSION['success'] = "Allocated $allocationsMade students automatically!";
        }
        
        if (!empty($errors)) {
            $_SESSION['error'] = "Some issues:<br>" . implode("<br>", array_unique($errors));
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Allocation failed: " . $e->getMessage();
    }
    
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
        
        // Validation checks
        $checkStmt = $conn->prepare("
            SELECT allocation_id FROM supervision WHERE student_id = ?
        ");
        $checkStmt->execute([$studentId]);
        
        if ($checkStmt->rowCount() > 0) {
            throw new Exception("Student already has a supervisor!");
        }
        
        $stmt = $conn->prepare("
            SELECT max_students, current_load 
            FROM supervisors WHERE id = ?
        ");
        $stmt->execute([$supervisorId]);
        $supervisor = $stmt->fetch();
        
        if ($supervisor['current_load'] >= $supervisor['max_students']) {
            throw new Exception("Supervisor has reached maximum capacity!");
        }
        
        // Create allocation in supervision table
        $stmt = $conn->prepare("
            INSERT INTO supervision 
            (supervisor_id, student_id, project_id, allocation_date, status) 
            VALUES (?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $supervisorId,
            $studentId,
            $projectId,
            $currentDate
        ]);
        
        // Update records
        $stmt = $conn->prepare("
            UPDATE supervisors 
            SET current_load = current_load + 1 
            WHERE id = ?
        ");
        $stmt->execute([$supervisorId]);
        
        $stmt = $conn->prepare("
            UPDATE project_topics 
            SET status = 'approved', 
                supervisor_id = ?,
                supervisor_name = (SELECT name FROM supervisors WHERE id = ?)
            WHERE id = ?
        ");
        $stmt->execute([$supervisorId, $supervisorId, $projectId]);
        
        $conn->commit();
        $_SESSION['success'] = "Manual allocation successful!";
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: dpc_assign_supervisors.php");
    exit();
}

// Get system statistics
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(DISTINCT s.id) FROM students s 
        WHERE s.id NOT IN (SELECT student_id FROM supervision WHERE status = 'active')) AS unassigned_count,
        (SELECT COUNT(*) FROM students) AS total_students,
        (SELECT COUNT(*) FROM supervisors WHERE current_load < max_students) AS available_sup,
        (SELECT COUNT(*) FROM supervisors) AS total_sup,
        (SELECT COUNT(DISTINCT student_id) FROM supervision WHERE status = 'active') AS allocated_count
")->fetch();

// Get data for display
$allocations = $conn->query("
    SELECT s.name AS student_name, s.reg_no, s.department AS student_dept,
           su.name AS supervisor_name, su.department AS supervisor_dept,
           p.topic, p.status, sp.allocation_date, sp.status AS allocation_status
    FROM supervision sp
    JOIN students s ON sp.student_id = s.id
    JOIN supervisors su ON sp.supervisor_id = su.id
    JOIN project_topics p ON sp.project_id = p.id
    WHERE sp.status = 'active'
    ORDER BY su.name, s.name
")->fetchAll();

$unassignedStudents = getUnassignedStudents($conn);
$availableSupervisors = $conn->query("
    SELECT * FROM supervisors 
    WHERE current_load < max_students
    ORDER BY name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocation System</title>
    <style>
        :root {
            --primary: #3498db;
            --success: #2ecc71;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-success {
            background-color: var(--success);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: var(--primary);
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .match { background-color: #e8f5e9; }
        .mismatch { background-color: #ffebee; }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        .tab.active {
            border-bottom-color: var(--primary);
            font-weight: bold;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .status-active {
            color: var(--success);
            font-weight: bold;
        }
        .status-inactive {
            color: var(--danger);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Student-Supervisor Allocation System</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Students</h3>
                <div class="stat-value"><?= $stats['total_students'] ?></div>
                <p><?= $stats['allocated_count'] ?> allocated</p>
                <p><?= $stats['unassigned_count'] ?> unassigned</p>
            </div>
            <div class="stat-card">
                <h3>Supervisors</h3>
                <div class="stat-value"><?= $stats['total_sup'] ?></div>
                <p><?= $stats['available_sup'] ?> available</p>
                <p><?= $stats['total_sup'] - $stats['available_sup'] ?> full</p>
            </div>
            <div class="stat-card">
                <h3>Actions</h3>
                <?php if ($stats['unassigned_count'] > 0 && $stats['available_sup'] > 0): ?>
                    <form method="POST">
                        <button type="submit" name="auto_allocate" class="btn btn-success">
                            Auto Allocate
                        </button>
                    </form>
                <?php elseif ($stats['unassigned_count'] == 0): ?>
                    <p>All students allocated</p>
                <?php else: ?>
                    <p>No available supervisors</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="switchTab('allocations')">Current Allocations</div>
            <div class="tab" onclick="switchTab('manual')">Manual Allocation</div>
            <div class="tab" onclick="switchTab('unassigned')">Unassigned Students</div>
        </div>
        
        <div id="allocations" class="tab-content active">
            <h2>Current Allocations</h2>
            <?php if (count($allocations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Reg No</th>
                            <th>Department</th>
                            <th>Supervisor</th>
                            <th>Department</th>
                            <th>Project</th>
                            <th>Allocation Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allocations as $alloc): ?>
                            <tr class="<?= $alloc['student_dept'] == $alloc['supervisor_dept'] ? 'match' : 'mismatch' ?>">
                                <td><?= htmlspecialchars($alloc['student_name']) ?></td>
                                <td><?= htmlspecialchars($alloc['reg_no']) ?></td>
                                <td><?= htmlspecialchars($alloc['student_dept']) ?></td>
                                <td><?= htmlspecialchars($alloc['supervisor_name']) ?></td>
                                <td><?= htmlspecialchars($alloc['supervisor_dept']) ?></td>
                                <td><?= htmlspecialchars($alloc['topic']) ?></td>
                                <td><?= date('M j, Y', strtotime($alloc['allocation_date'])) ?></td>
                                <td class="status-<?= $alloc['allocation_status'] === 'active' ? 'active' : 'inactive' ?>">
                                    <?= htmlspecialchars(ucfirst($alloc['allocation_status'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No active allocations found.</p>
            <?php endif; ?>
        </div>
        
        <div id="manual" class="tab-content">
            <h2>Manual Allocation</h2>
            <?php if (count($unassignedStudents) > 0 && count($availableSupervisors) > 0): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="student_id">Student:</label>
                        <select name="student_id" id="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($unassignedStudents as $student): ?>
                                <option value="<?= $student['id'] ?>" data-project="<?= $student['project_id'] ?>">
                                    <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['reg_no']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="supervisor_id">Supervisor:</label>
                        <select name="supervisor_id" id="supervisor_id" required>
                            <option value="">Select Supervisor</option>
                            <?php foreach ($availableSupervisors as $sup): ?>
                                <option value="<?= $sup['id'] ?>">
                                    <?= htmlspecialchars($sup['name']) ?> (<?= $sup['current_load'] ?>/<?= $sup['max_students'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="project_id" id="project_id">
                    <button type="submit" name="manual_allocate" class="btn">Allocate</button>
                </form>
                <script>
                    document.getElementById('student_id').addEventListener('change', function() {
                        var option = this.options[this.selectedIndex];
                        document.getElementById('project_id').value = option.getAttribute('data-project');
                    });
                </script>
            <?php else: ?>
                <p>No students or supervisors available for manual allocation.</p>
            <?php endif; ?>
        </div>
        
        <div id="unassigned" class="tab-content">
            <h2>Unassigned Students</h2>
            <?php if (count($unassignedStudents) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Reg No</th>
                            <th>Department</th>
                            <th>Project</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unassignedStudents as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['reg_no']) ?></td>
                                <td><?= htmlspecialchars($student['department_name']) ?></td>
                                <td><?= htmlspecialchars($student['topic']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>All students have been allocated.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected tab and content
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>