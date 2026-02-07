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

// Fetch assessments for students in this department for the active session
$query = "
    SELECT 
        s.id as student_id, 
        s.name as student_name, 
        s.reg_no,
        dp.panel_name,
        dp.panel_type,
        GROUP_CONCAT(CONCAT(sup.name, ': ', ds.score) SEPARATOR ' | ') as individual_scores,
        AVG(ds.score) as average_score,
        COUNT(ds.score) as score_count
    FROM students s
    JOIN student_panel_assignments spa ON s.id = spa.student_id
    JOIN defense_panels dp ON spa.panel_id = dp.id
    LEFT JOIN defense_scores ds ON s.id = ds.student_id AND ds.panel_id = dp.id
    LEFT JOIN supervisors sup ON ds.supervisor_id = sup.id
    WHERE s.department = ? AND spa.academic_session = ?
    GROUP BY s.id, dp.id, dp.panel_type
    ORDER BY FIELD(dp.panel_type, 'proposal', 'internal', 'external'), dp.panel_name, s.name
";

$stmt = $conn->prepare($query);
$stmt->execute([$dept_id, $active_session]);
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "assessments_" . str_replace(' ', '_', $dept_name) . "_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    // Header row
    fputcsv($output, ['S/N', 'Registration Number', 'Student Name', 'Defense Stage', 'Panel Name', 'Individual Scores', 'Average Score']);
    
    $sn = 1;
    foreach ($assessments as $as) {
        fputcsv($output, [
            $sn++,
            $as['reg_no'],
            $as['student_name'],
            ucfirst($as['panel_type']),
            $as['panel_name'],
            $as['individual_scores'] ?: 'N/A',
            $as['average_score'] !== null ? number_format($as['average_score'], 2) : '0'
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
    <title>Student Assessments - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
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
        .header h1 { font-size: 24px; font-weight: 700; color: var(--primary); margin: 0; }

        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        
        .btn { padding: 12px 20px; border: none; border-radius: 12px; font-family: inherit; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #3730a3; transform: translateY(-2px); }
        .btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: var(--primary); color: white; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; vertical-align: middle; }

        .panel-badge { background: #f1f5f9; padding: 4px 10px; border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--primary); }
        .score-badge { display: inline-block; padding: 6px 12px; border-radius: 10px; font-weight: 700; }
        .score-good { background: #ecfdf5; color: #065f46; }
        .score-none { background: #fef2f2; color: #991b1b; }

        .individual-scores { font-size: 12px; color: var(--text-muted); font-style: italic; }

        @media print {
            .header .btn, .footer { display: none; }
            .container { margin: 0; padding: 0; width: 100%; max-width: 100%; }
            .card { box-shadow: none; padding: 0; }
            body { background: white; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1>Project Defense Assessments</h1>
                <p style="color: var(--text-muted);">Session: <?= $active_session ?> | Dept: <?= htmlspecialchars($dept_name) ?></p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="?export=csv" class="btn btn-outline" style="border-color: var(--success); color: var(--success);"><i class="fas fa-file-csv"></i> Export CSV</a>
                <a href="dpc_print_assessments.php" target="_blank" class="btn btn-outline"><i class="fas fa-print"></i> Print Scores</a>
                <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>

        <div class="card">
            <?php if (count($assessments) > 0): 
                $grouped_assessments = [
                    'proposal' => [],
                    'internal' => [],
                    'external' => []
                ];
                foreach ($assessments as $as) {
                    $grouped_assessments[$as['panel_type']][] = $as;
                }

                $stages = [
                    'proposal' => 'Project Proposal Defense',
                    'internal' => 'Internal Defense',
                    'external' => 'External Defense'
                ];

                foreach ($stages as $type => $label):
                    if (!empty($grouped_assessments[$type])):
            ?>
                <h3 style="font-size: 18px; margin-top: 30px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-graduation-cap"></i> <?= $label ?> Results
                </h3>
                <table style="margin-bottom: 40px;">
                    <thead>
                        <tr>
                            <th>Student Information</th>
                            <th>Panel</th>
                            <th>Individual Scores (Lecturers)</th>
                            <th style="text-align: center;">Average Score</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grouped_assessments[$type] as $as): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($as['student_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars($as['reg_no']) ?></small>
                                </td>
                                <td><span class="panel-badge"><?= htmlspecialchars($as['panel_name']) ?></span></td>
                                <td>
                                    <div class="individual-scores">
                                        <?= $as['individual_scores'] ?: 'No scores yet' ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <strong><?= $as['average_score'] !== null ? number_format($as['average_score'], 2) . '%' : '--' ?></strong>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($as['score_count'] > 0): ?>
                                        <span class="score-badge score-good">
                                            <i class="fas fa-check-circle"></i> Assessed (<?= $as['score_count'] ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="score-badge score-none">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php 
                    endif;
                endforeach;
            else: ?>
                <div style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <p>No assessment data found for this session.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
