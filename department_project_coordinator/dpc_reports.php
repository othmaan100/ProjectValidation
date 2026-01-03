<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Check if the user is logged in as DPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: /projectval/");
    exit();
}

// Fetch DPC's Department
$stmt = $conn->prepare("SELECT department FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$dept_id = $stmt->fetchColumn();

// Handle Export if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Get department name
    $stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
    $stmt->execute([$dept_id]);
    $dept_name = $stmt->fetchColumn();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$dept_name.'_Report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['S/N', 'Reg No', 'Student Name', 'Approved Topic', 'Supervisor', 'Status']);
    
    $stmt = $conn->prepare("
        SELECT 
            s.reg_no, 
            s.name as student_name, 
            pt.topic as approved_topic,
            sup.name as supervisor_name,
            pt.status
        FROM students s
        LEFT JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
        LEFT JOIN supervision sv ON s.id = sv.student_id AND sv.status = 'active'
        LEFT JOIN supervisors sup ON sv.supervisor_id = sup.id
        WHERE s.department = ?
        ORDER BY s.reg_no ASC
    ");
    $stmt->execute([$dept_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $i = 1;
    foreach ($rows as $row) {
        fputcsv($output, [
            $i++,
            $row['reg_no'],
            $row['student_name'],
            $row['approved_topic'] ?? 'N/A',
            $row['supervisor_name'] ?? 'Not Assigned',
            $row['status'] ?? 'No Approved Topic'
        ]);
    }
    fclose($output);
    exit();
}

// Statistics
$stats = [];
// Total Students
$stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE department = ?");
$stmt->execute([$dept_id]);
$stats['total_students'] = $stmt->fetchColumn();

// Approved Topics
$stmt = $conn->prepare("SELECT COUNT(*) FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE s.department = ? AND pt.status = 'approved'");
$stmt->execute([$dept_id]);
$stats['approved_topics'] = $stmt->fetchColumn();

// Pending Topics
$stmt = $conn->prepare("SELECT COUNT(*) FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE s.department = ? AND pt.status = 'pending'");
$stmt->execute([$dept_id]);
$stats['pending_topics'] = $stmt->fetchColumn();

// Supervisors Count
$stmt = $conn->prepare("SELECT COUNT(*) FROM supervisors WHERE department = ?");
$stmt->execute([$dept_id]);
$stats['total_supervisors'] = $stmt->fetchColumn();

// Student-Topic List
$stmt = $conn->prepare("
    SELECT 
        s.reg_no, 
        s.name as student_name, 
        pt.topic as approved_topic,
        sup.name as supervisor_name
    FROM students s
    LEFT JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
    LEFT JOIN supervision sv ON s.id = sv.student_id AND sv.status = 'active'
    LEFT JOIN supervisors sup ON sv.supervisor_id = sup.id
    WHERE s.department = ?
    ORDER BY s.reg_no ASC
");
$stmt->execute([$dept_id]);
$students_report = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Supervisor Load (Department specific)
$stmt = $conn->prepare("
    SELECT 
        sup.name, 
        sup.max_students, 
        (SELECT COUNT(*) FROM supervision sv WHERE sv.supervisor_id = sup.id AND sv.status = 'active') as assigned_count
    FROM supervisors sup
    WHERE sup.department = ?
    ORDER BY sup.name ASC
");
$stmt->execute([$dept_id]);
$supervisor_loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departmental Reports - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --blue: #2e59d9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fc;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            transition: 0.3s;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-outline { border: 1px solid #ddd; background: white; color: #333; }
        .btn:hover { opacity: 0.85; }

        /* Stats Blocks */
        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-box {
            flex: 1;
            min-width: 200px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-left: 0.25rem solid var(--primary);
        }

        .stat-box.green { border-left-color: var(--success); }
        .stat-box.yellow { border-left-color: var(--warning); }
        .stat-box.info { border-left-color: var(--info); }

        .stat-box div:first-child {
            text-transform: uppercase;
            font-size: 0.7rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-box.green div:first-child { color: var(--success); }
        .stat-box.yellow div:first-child { color: var(--warning); }
        .stat-box.info div:first-child { color: var(--info); }

        .stat-box div:last-child {
            font-size: 1.5rem;
            font-weight: bold;
            color: #5a5c69;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background: #f8f9fc;
            padding: 15px 20px;
            border-bottom: 1px solid #e3e6f0;
            color: var(--blue);
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #e3e6f0;
            font-size: 14px;
        }

        th {
            background: #f8f9fc;
            color: #4e73df;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
        }

        tr:hover { background: #fcfdff; }

        .progress {
            height: 10px;
            background: #eaecf4;
            border-radius: 5px;
            overflow: hidden;
            width: 100px;
        }

        .progress-bar {
            height: 100%;
            background: var(--primary);
        }

        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: white;
        }

        .badge-none { background: #858796; }
        .badge-active { background: var(--success); }

        @media print {
            .btn, nav, .header-actions h1 p { display: none; }
            header { display: none; }
            .container { margin: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header-actions">
            <div>
                <h1>Departmental Report</h1>
                <p>Detailed overview of students, topics, and supervisors</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn btn-outline">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="?export=csv" class="btn btn-success">
                    <i class="fas fa-download"></i> Export CSV
                </a>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-box info">
                <div>Total Students</div>
                <div><?php echo $stats['total_students']; ?></div>
            </div>
            <div class="stat-box green">
                <div>Approved Topics</div>
                <div><?php echo $stats['approved_topics']; ?></div>
            </div>
            <div class="stat-box yellow">
                <div>Pending Topics</div>
                <div><?php echo $stats['pending_topics']; ?></div>
            </div>
            <div class="stat-box">
                <div>Supervisors</div>
                <div><?php echo $stats['total_supervisors']; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Student & Topic Status List</div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Student Name</th>
                            <th>Approved Topic</th>
                            <th>Supervisor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students_report as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td>
                                <?php if ($row['approved_topic']): ?>
                                    <?php echo htmlspecialchars($row['approved_topic']); ?>
                                <?php else: ?>
                                    <span class="badge badge-none">No approved topic</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['supervisor_name']): ?>
                                    <?php echo htmlspecialchars($row['supervisor_name']); ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">Not assigned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="max-width: 600px;">
            <div class="card-header">Supervisor Load Status</div>
            <table>
                <thead>
                    <tr>
                        <th>Supervisor</th>
                        <th>Assigned</th>
                        <th>Capacity</th>
                        <th>Load</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($supervisor_loads as $sup): 
                        $pct = $sup['max_students'] > 0 ? ($sup['assigned_count'] / $sup['max_students']) * 100 : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sup['name']); ?></td>
                        <td><?php echo $sup['assigned_count']; ?></td>
                        <td><?php echo $sup['max_students']; ?></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" style="width: <?php echo $pct; ?>%; background: <?php echo $pct > 90 ? 'var(--danger)' : ($pct > 70 ? 'var(--warning)' : 'var(--success)'); ?>"></div>
                            </div>
                            <span style="font-size: 10px;"><?php echo round($pct); ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
