<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Check if the user is logged in as FPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'fpc') {
    header("Location: /projectval/");
    exit();
}

// Handle Export if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="faculty_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['S/N', 'Department', 'Total Students', 'Approved Topics', 'Pending Topics', 'Staff Count']);
    
    $stmt = $conn->prepare("
        SELECT 
            d.department_name,
            (SELECT COUNT(*) FROM students s WHERE s.department = d.id) as total_students,
            (SELECT COUNT(*) FROM project_topics pt JOIN students s2 ON pt.student_id = s2.id WHERE s2.department = d.id AND pt.status = 'approved') as approved_topics,
            (SELECT COUNT(*) FROM project_topics pt JOIN students s2 ON pt.student_id = s2.id WHERE s2.department = d.id AND pt.status = 'pending') as pending_topics,
            (SELECT COUNT(*) FROM supervisors sup WHERE sup.department = d.id) as staff_count
        FROM departments d
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $i = 1;
    foreach ($rows as $row) {
        fputcsv($output, [
            $i++,
            $row['department_name'],
            $row['total_students'],
            $row['approved_topics'],
            $row['pending_topics'],
            $row['staff_count']
        ]);
    }
    fclose($output);
    exit();
}

// Fetch Global Statistics
$stats = [];

// Total Students
$stats['total_students'] = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Topic Status Breakdown
$stats['approved_topics'] = $conn->query("SELECT COUNT(*) FROM project_topics WHERE status = 'approved'")->fetchColumn();
$stats['pending_topics'] = $conn->query("SELECT COUNT(*) FROM project_topics WHERE status = 'pending'")->fetchColumn();
$stats['rejected_topics'] = $conn->query("SELECT COUNT(*) FROM project_topics WHERE status = 'rejected'")->fetchColumn();

// Supervisors
$stats['total_supervisors'] = $conn->query("SELECT COUNT(*) FROM supervisors")->fetchColumn();

// Department-wise breakdown for table
$stmt = $conn->prepare("
    SELECT 
        d.id,
        d.department_name,
        (SELECT COUNT(*) FROM students s WHERE s.department = d.id) as total_students,
        (SELECT COUNT(*) FROM project_topics pt JOIN students s2 ON pt.student_id = s2.id WHERE s2.department = d.id AND pt.status = 'approved') as approved_topics,
        (SELECT COUNT(*) FROM project_topics pt JOIN students s2 ON pt.student_id = s2.id WHERE s2.department = d.id AND pt.status = 'pending') as pending_topics,
        (SELECT COUNT(*) FROM supervisors sup WHERE sup.department = d.id) as staff_count
    FROM departments d
");
$stmt->execute();
$dept_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Supervisor Load Report
$stmt = $conn->prepare("
    SELECT 
        s.name, 
        d.department_name, 
        s.max_students, 
        (SELECT COUNT(*) FROM supervision sv WHERE sv.supervisor_id = s.id AND sv.status = 'active') as assigned_count
    FROM supervisors s
    JOIN departments d ON s.department = d.id
    ORDER BY d.department_name, s.name
");
$stmt->execute();
$supervisor_loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Reports - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --dark: #5a5c69;
            --light: #f8f9fc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fe;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .report-header h1 {
            font-size: 28px;
            color: var(--secondary);
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-outline { border: 2px solid var(--primary); color: var(--primary); background: transparent; }

        .btn:hover { opacity: 0.9; transform: translateY(-2px); }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }

        .stat-card.approved { border-left-color: var(--success); }
        .stat-card.pending { border-left-color: var(--warning); }
        .stat-card.students { border-left-color: var(--info); }

        .stat-card h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .stat-card p {
            font-size: 14px;
            color: #777;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Tables */
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 40px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            background: #fafbff;
            border-bottom: 1px solid #edf2f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 18px;
            color: var(--secondary);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 25px;
            text-align: left;
            border-bottom: 1px solid #edf2f9;
        }

        th {
            background: #f8f9fc;
            font-weight: 600;
            color: #6e707e;
            text-transform: uppercase;
            font-size: 12px;
        }

        tr:hover { background: #fcfdff; }

        .progress-bar {
            height: 8px;
            background: #eaecf4;
            border-radius: 4px;
            overflow: hidden;
            width: 100%;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-success { background: #e3f9ef; color: #1cc88a; }
        .badge-warning { background: #fff9e6; color: #f6c23e; }

        @media print {
            .btn, nav, header { display: none; }
            .container { margin: 0; width: 100%; max-width: 100%; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="report-header">
            <div>
                <h1>Faculty Progress Reports</h1>
                <p>Summary of project validation activities across all departments</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn btn-outline">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="?export=csv" class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card students">
                <p>Total Students</p>
                <h3><?php echo number_format($stats['total_students']); ?></h3>
            </div>
            <div class="stat-card approved">
                <p>Approved Topics</p>
                <h3><?php echo number_format($stats['approved_topics']); ?></h3>
            </div>
            <div class="stat-card pending">
                <p>Pending Topics</p>
                <h3><?php echo number_format($stats['pending_topics']); ?></h3>
            </div>
            <div class="stat-card">
                <p>Total Supervisors</p>
                <h3><?php echo number_format($stats['total_supervisors']); ?></h3>
            </div>
        </div>

        <!-- Departmental Summary -->
        <div class="card">
            <div class="card-header">
                <h2>Departmental Breakdown</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Total Students</th>
                            <th>Approved</th>
                            <th>Pending</th>
                            <th>Staff Count</th>
                            <th>Completion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dept_reports as $dept): 
                            $completion = $dept['total_students'] > 0 ? ($dept['approved_topics'] / $dept['total_students']) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                            <td><?php echo $dept['total_students']; ?></td>
                            <td><span class="badge badge-success"><?php echo $dept['approved_topics']; ?></span></td>
                            <td><span class="badge badge-warning"><?php echo $dept['pending_topics']; ?></span></td>
                            <td><?php echo $dept['staff_count']; ?></td>
                            <td width="200">
                                <div style="font-size: 11px; margin-bottom: 2px;"><?php echo round($completion, 1); ?>%</div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $completion; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Supervisor Load Summary -->
        <div class="card">
            <div class="card-header">
                <h2>Supervisor Load Monitoring</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Supervisor Name</th>
                            <th>Department</th>
                            <th>Assigned</th>
                            <th>Capacity</th>
                            <th>Load Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($supervisor_loads as $sup): 
                            $load_percent = $sup['max_students'] > 0 ? ($sup['assigned_count'] / $sup['max_students']) * 100 : 0;
                            $status_color = $load_percent >= 90 ? 'var(--danger)' : ($load_percent >= 70 ? 'var(--warning)' : 'var(--success)');
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sup['name']); ?></td>
                            <td><?php echo htmlspecialchars($sup['department_name']); ?></td>
                            <td><?php echo $sup['assigned_count']; ?></td>
                            <td><?php echo $sup['max_students']; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $load_percent; ?>%; background: <?php echo $status_color; ?>;"></div>
                                </div>
                                <span style="font-size: 10px; color: <?php echo $status_color; ?>"><?php echo round($load_percent); ?>% Full</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
