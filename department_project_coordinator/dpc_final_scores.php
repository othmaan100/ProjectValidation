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

// Use current session from global settings
$active_session = $current_session;

// Fetch total scores for each student
// This includes: Proposal Avg, Internal Avg, External Avg, and Supervisor Assessment
$query = "
    SELECT 
        s.id as student_id, 
        s.name as student_name, 
        s.reg_no,
        AVG(CASE WHEN dp.panel_type = 'proposal' THEN ds.score END) as proposal_avg,
        AVG(CASE WHEN dp.panel_type = 'internal' THEN ds.score END) as internal_avg,
        AVG(CASE WHEN dp.panel_type = 'external' THEN ds.score END) as external_avg,
        sa.score as supervisor_score,
        (
            COALESCE(AVG(CASE WHEN dp.panel_type = 'proposal' THEN ds.score END), 0) + 
            COALESCE(AVG(CASE WHEN dp.panel_type = 'internal' THEN ds.score END), 0) + 
            COALESCE(AVG(CASE WHEN dp.panel_type = 'external' THEN ds.score END), 0) + 
            COALESCE(sa.score, 0)
        ) as total_grand_score
    FROM students s
    LEFT JOIN defense_scores ds ON s.id = ds.student_id
    LEFT JOIN defense_panels dp ON ds.panel_id = dp.id
    LEFT JOIN supervisor_assessments sa ON s.id = sa.student_id AND sa.academic_session = ?
    WHERE s.department = ?
    GROUP BY s.id
    ORDER BY s.name ASC
";

$stmt = $conn->prepare($query);
$stmt->execute([$active_session, $dept_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "total_scores_" . str_replace(' ', '_', $dept_name) . "_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    // Header row
    fputcsv($output, ['S/N', 'Reg Number', 'Student Name', 'Proposal Avg', 'Internal Avg', 'External Avg', 'Supervisor Score', 'Grand Total']);
    
    $sn = 1;
    foreach ($results as $r) {
        fputcsv($output, [
            $sn++,
            $r['reg_no'],
            $r['student_name'],
            $r['proposal_avg'] !== null ? number_format($r['proposal_avg'], 1) : '0',
            $r['internal_avg'] !== null ? number_format($r['internal_avg'], 1) : '0',
            $r['external_avg'] !== null ? number_format($r['external_avg'], 1) : '0',
            $r['supervisor_score'] !== null ? number_format($r['supervisor_score'], 1) : '0',
            number_format($r['total_grand_score'], 1)
        ]);
    }
    
    fclose($output);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Project Scores - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --success: #059669;
            --info: #36b9cc;
            --bg: #f1f5f9;
        }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); margin: 0; color: #1e293b; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        .header { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; color: var(--primary); margin: 0; }
        
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        
        .btn { padding: 12px 20px; border: none; border-radius: 10px; font-family: inherit; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-success { background: var(--success); color: white; }
        .btn-primary { background: var(--primary); color: white; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; font-size: 13px; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }
        
        .score-box { padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 14px; display: inline-block; min-width: 50px; text-align: center; }
        .bg-prop { background: #e0f2fe; color: #0369a1; }
        .bg-int { background: #fef3c7; color: #92400e; }
        .bg-ext { background: #dcfce7; color: #166534; }
        .bg-sup { background: #f3e8ff; color: #6b21a8; }
        .bg-total { background: var(--primary); color: white; }
        .bg-none { background: #f1f5f9; color: #94a3b8; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1>Student Final Scores Summary</h1>
                <p style="color: #64748b;">Academic Session: <?= $active_session ?> | Dept: <?= htmlspecialchars($dept_name) ?></p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="?export=csv" class="btn btn-success"><i class="fas fa-file-csv"></i> Export CSV</a>
                <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>

        <div class="card">
            <?php if (count($results) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student Information</th>
                            <th>Proposal Avg</th>
                            <th>Internal Avg</th>
                            <th>External Avg</th>
                            <th>Supervisor</th>
                            <th style="text-align: center;">Grand Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($r['student_name']) ?></strong><br>
                                    <small style="color: #64748b;"><?= htmlspecialchars($r['reg_no']) ?></small>
                                </td>
                                <td>
                                    <span class="score-box <?= $r['proposal_avg'] !== null ? 'bg-prop' : 'bg-none' ?>">
                                        <?= $r['proposal_avg'] !== null ? number_format($r['proposal_avg'], 1) : '--' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="score-box <?= $r['internal_avg'] !== null ? 'bg-int' : 'bg-none' ?>">
                                        <?= $r['internal_avg'] !== null ? number_format($r['internal_avg'], 1) : '--' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="score-box <?= $r['external_avg'] !== null ? 'bg-ext' : 'bg-none' ?>">
                                        <?= $r['external_avg'] !== null ? number_format($r['external_avg'], 1) : '--' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="score-box <?= $r['supervisor_score'] !== null ? 'bg-sup' : 'bg-none' ?>">
                                        <?= $r['supervisor_score'] !== null ? number_format($r['supervisor_score'], 1) : '--' ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="score-box bg-total" style="font-size: 16px;">
                                        <?= number_format($r['total_grand_score'], 1) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #64748b;">
                    <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <p>No student records found for this department.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
