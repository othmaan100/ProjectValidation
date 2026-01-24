<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Check if the user is logged in as Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Fetch global stats
$stats = [
    'total_students' => 0,
    'approved' => 0,
    'pending' => 0,
];

try {
    $stats['total_students'] = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $stats['approved'] = $conn->query("SELECT COUNT(*) FROM project_topics WHERE status = 'approved'")->fetchColumn();
    $stats['pending'] = $conn->query("SELECT COUNT(*) FROM project_topics WHERE status = 'pending'")->fetchColumn();
} catch (Exception $e) {}

// Faculty Breakdown
$faculty_stats = $conn->query("
    SELECT 
        f.id, f.faculty as faculty_name,
        (SELECT COUNT(*) FROM students s WHERE s.faculty_id = f.id) as students_count,
        (SELECT COUNT(*) FROM project_topics pt JOIN students s2 ON pt.student_id = s2.id WHERE s2.faculty_id = f.id AND pt.status = 'approved') as approved_count,
        (SELECT COUNT(*) FROM project_topics pt JOIN students s2 ON pt.student_id = s2.id WHERE s2.faculty_id = f.id AND pt.status = 'pending') as pending_count
    FROM faculty f
    ORDER BY f.faculty ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Recent Approved Projects
$recent_approved = $conn->query("
    SELECT pt.topic, pt.student_name, pt.session, f.faculty as faculty_name 
    FROM project_topics pt 
    JOIN students s ON pt.student_id = s.id 
    JOIN faculty f ON s.faculty_id = f.id 
    WHERE pt.status = 'approved' 
    ORDER BY pt.id DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Reports - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4338ca; --secondary: #db2777; --success: #059669; --warning: #d97706; }
        body { font-family: 'Outfit', sans-serif; background: #f1f5f9; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; }
        .stat-card h3 { font-size: 32px; color: var(--primary); margin-bottom: 5px; }
        .stat-card p { color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 13px; }

        .card { background: white; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden; }
        .card-header { padding: 25px 35px; border-bottom: 1px solid #f1f5f9; background: #fafbff; }
        .card-header h2 { font-size: 20px; color: #1e293b; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 20px 35px; background: #f8fafc; font-size: 12px; color: #64748b; text-transform: uppercase; }
        td { padding: 20px 35px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }
        
        .progress-bar { height: 8px; background: #f1f5f9; border-radius: 10px; overflow: hidden; margin-top: 10px; }
        .progress-fill { height: 100%; background: var(--success); }
        .btn-print { padding: 12px 24px; background: #1e293b; color: white; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1>University Project Analytics</h1>
                <p>Consolidated data overview across all faculties</p>
            </div>
            <a href="javascript:window.print()" class="btn-print"><i class="fas fa-print"></i> Print Insight</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><h3><?= number_format($stats['total_students']) ?></h3><p>Total Registered Students</p></div>
            <div class="stat-card" style="border-bottom: 4px solid var(--success);"><h3><?= number_format($stats['approved']) ?></h3><p>Approved Final Topics</p></div>
            <div class="stat-card" style="border-bottom: 4px solid var(--warning);"><h3><?= number_format($stats['pending']) ?></h3><p>Proposals Awaiting Review</p></div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Faculty Performance Breakdown</h2></div>
            <table>
                <thead>
                    <tr>
                        <th>Faculty</th>
                        <th>Students</th>
                        <th>Approved</th>
                        <th>Pending</th>
                        <th>Completion status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faculty_stats as $fac): 
                        $pct = $fac['students_count'] > 0 ? ($fac['approved_count'] / $fac['students_count']) * 100 : 0;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($fac['faculty_name']) ?></strong></td>
                            <td><?= $fac['students_count'] ?></td>
                            <td><span style="color: var(--success); font-weight: 700;"><?= $fac['approved_count'] ?></span></td>
                            <td><span style="color: var(--warning); font-weight: 700;"><?= $fac['pending_count'] ?></span></td>
                            <td width="250">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px;">
                                    <span><?= round($pct, 1) ?>%</span>
                                </div>
                                <div class="progress-bar"><div class="progress-fill" style="width: <?= $pct ?>%"></div></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header"><h2>Latest Approved Projects</h2></div>
            <table>
                <thead>
                    <tr>
                        <th>Topic</th>
                        <th>Student</th>
                        <th>Faculty</th>
                        <th>Session</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_approved as $proj): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($proj['topic']) ?></strong></td>
                            <td><?= htmlspecialchars($proj['student_name']) ?></td>
                            <td><?= htmlspecialchars($proj['faculty_name']) ?></td>
                            <td style="color: #64748b; font-size: 13px;"><?= htmlspecialchars($proj['session']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

