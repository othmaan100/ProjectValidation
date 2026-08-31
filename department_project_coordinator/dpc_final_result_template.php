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
$dept_name = $dpc_info['department_name'] ?? 'Department';

// Use current session from global settings
$active_session = $current_session;

// Helper function to calculate letter grade and remark
function getGradeDetails($total) {
    if ($total >= 70) {
        return ['grade' => 'A', 'remark' => 'Excellent (Distinction)', 'color' => '#059669', 'bg' => '#ecfdf5'];
    } elseif ($total >= 60) {
        return ['grade' => 'B', 'remark' => 'Very Good', 'color' => '#2563eb', 'bg' => '#eff6ff'];
    } elseif ($total >= 50) {
        return ['grade' => 'C', 'remark' => 'Good', 'color' => '#d97706', 'bg' => '#fffbeb'];
    } elseif ($total >= 45) {
        return ['grade' => 'D', 'remark' => 'Fair (Pass)', 'color' => '#b45309', 'bg' => '#fef3c7'];
    } elseif ($total >= 40) {
        return ['grade' => 'E', 'remark' => 'Pass', 'color' => '#ea580c', 'bg' => '#fff7ed'];
    } else {
        return ['grade' => 'F', 'remark' => 'Fail', 'color' => '#dc2626', 'bg' => '#fef2f2'];
    }
}

// Fetch comprehensive assessment data for each student in the department
$query = "
    SELECT 
        s.id as student_id, 
        s.name as student_name, 
        s.reg_no,
        pt.topic as project_title,
        sup.name as supervisor_name,
        (
            SELECT AVG(ds.score)
            FROM defense_scores ds
            JOIN defense_panels dp ON ds.panel_id = dp.id
            WHERE ds.student_id = s.id AND dp.panel_type = 'proposal'
        ) as proposal_avg,
        (
            SELECT AVG(ds.score)
            FROM defense_scores ds
            JOIN defense_panels dp ON ds.panel_id = dp.id
            WHERE ds.student_id = s.id AND dp.panel_type = 'internal'
        ) as internal_avg,
        (
            SELECT AVG(ds.score)
            FROM defense_scores ds
            JOIN defense_panels dp ON ds.panel_id = dp.id
            WHERE ds.student_id = s.id AND dp.panel_type = 'external'
        ) as external_avg,
        (
            SELECT sa.score
            FROM supervisor_assessments sa
            WHERE sa.student_id = s.id AND sa.academic_session = ?
            LIMIT 1
        ) as supervisor_score
    FROM students s
    LEFT JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
    LEFT JOIN supervision sv ON s.id = sv.student_id AND sv.status = 'active'
    LEFT JOIN supervisors sup ON sv.supervisor_id = sup.id
    WHERE s.department = ?
    ORDER BY s.reg_no ASC, s.name ASC
";

$stmt = $conn->prepare($query);
$stmt->execute([$active_session, $dept_id]);
$raw_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process and calculate scores for each student
$processed_results = [];
$stats = [
    'total' => count($raw_students),
    'complete' => 0,
    'incomplete' => 0,
    'total_scores_sum' => 0,
    'highest_score' => 0,
    'lowest_score' => 100,
    'passed_count' => 0
];

foreach ($raw_students as $row) {
    $prop = $row['proposal_avg'] !== null ? floatval($row['proposal_avg']) : null;
    $int = $row['internal_avg'] !== null ? floatval($row['internal_avg']) : null;
    $ext = $row['external_avg'] !== null ? floatval($row['external_avg']) : null;
    $sup = $row['supervisor_score'] !== null ? floatval($row['supervisor_score']) : null;

    $missing_components = [];
    if ($prop === null) $missing_components[] = 'Proposal';
    if ($int === null) $missing_components[] = 'Internal';
    if ($sup === null) $missing_components[] = 'Supervisor';
    if ($ext === null) $missing_components[] = 'External';

    $is_complete = empty($missing_components);
    if ($is_complete) {
        $stats['complete']++;
    } else {
        $stats['incomplete']++;
    }

    // 1. External Defense Score converted to 30% (CA Score)
    // Formula: (External Score / 100) * 30
    $ca_score = ($ext !== null) ? (($ext / 100.0) * 30.0) : 0.0;

    // 2. Proposal, Internal, and Supervisor Scores combined and scaled to 70% (EXAM Score)
    // Each is out of 100 (Max 300 total) -> Scaled to 70%
    // Formula: ((Proposal + Internal + Supervisor) / 300) * 70
    $exam_raw_sum = ($prop ?? 0.0) + ($int ?? 0.0) + ($sup ?? 0.0);
    $exam_score = ($exam_raw_sum / 300.0) * 70.0;

    // 3. Final Total Score (100% Max)
    // Formula: CA Score (30%) + EXAM Score (70%)
    $final_total = $ca_score + $exam_score;

    $grade_info = getGradeDetails($final_total);

    if ($final_total >= 40) {
        $stats['passed_count']++;
    }

    if ($final_total > $stats['highest_score']) {
        $stats['highest_score'] = $final_total;
    }
    if ($stats['total'] > 0 && $final_total < $stats['lowest_score']) {
        $stats['lowest_score'] = $final_total;
    }
    $stats['total_scores_sum'] += $final_total;

    $processed_results[] = [
        'student_id' => $row['student_id'],
        'student_name' => $row['student_name'],
        'reg_no' => $row['reg_no'],
        'project_title' => $row['project_title'] ?: 'Not assigned / Approved yet',
        'supervisor_name' => $row['supervisor_name'] ?: 'Not Assigned',
        'proposal_raw' => $prop,
        'internal_raw' => $int,
        'external_raw' => $ext,
        'supervisor_raw' => $sup,
        'ca_score' => $ca_score,
        'exam_raw_sum' => $exam_raw_sum,
        'exam_score' => $exam_score,
        'final_total' => $final_total,
        'grade' => $grade_info['grade'],
        'remark' => $grade_info['remark'],
        'grade_color' => $grade_info['color'],
        'grade_bg' => $grade_info['bg'],
        'is_complete' => $is_complete,
        'missing_components' => $missing_components
    ];
}

$class_average = $stats['total'] > 0 ? ($stats['total_scores_sum'] / $stats['total']) : 0.0;
$pass_rate = $stats['total'] > 0 ? (($stats['passed_count'] / $stats['total']) * 100.0) : 0.0;
if ($stats['total'] === 0) $stats['lowest_score'] = 0;

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "Final_Result_Template_" . str_replace(' ', '_', $dept_name) . "_" . str_replace('/', '-', $active_session) . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    // Title comments
    fputcsv($output, ["FINAL RESULT TEMPLATE - " . strtoupper($dept_name)]);
    fputcsv($output, ["Academic Session: " . $active_session, "Scoring Formula: CA = External (30%) | EXAM = (Proposal + Internal + Supervisor)/300 * 70% | Total = CA + EXAM (100%)"]);
    fputcsv($output, []); // blank line

    // Header row
    fputcsv($output, [
        'S/N', 
        'Registration Number', 
        'Student Full Name', 
        'Project Topic',
        'Assigned Supervisor',
        'Proposal Score (/100)', 
        'Internal Score (/100)', 
        'Supervisor Score (/100)', 
        'External Score (/100)', 
        'CA Score (30%)', 
        'EXAM Score (70%)', 
        'Final Total Score (100%)', 
        'Letter Grade', 
        'Remark', 
        'Grading Status'
    ]);
    
    $sn = 1;
    foreach ($processed_results as $r) {
        fputcsv($output, [
            $sn++,
            $r['reg_no'],
            $r['student_name'],
            $r['project_title'],
            $r['supervisor_name'],
            $r['proposal_raw'] !== null ? number_format($r['proposal_raw'], 1) : 'N/A',
            $r['internal_raw'] !== null ? number_format($r['internal_raw'], 1) : 'N/A',
            $r['supervisor_raw'] !== null ? number_format($r['supervisor_raw'], 1) : 'N/A',
            $r['external_raw'] !== null ? number_format($r['external_raw'], 1) : 'N/A',
            number_format($r['ca_score'], 2),
            number_format($r['exam_score'], 2),
            number_format($r['final_total'], 2),
            $r['grade'],
            $r['remark'],
            $r['is_complete'] ? 'Complete' : 'Pending (' . implode(', ', $r['missing_components']) . ')'
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
    <title>Final Result Template (30% CA / 70% Exam) - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --primary-dark: #3730a3;
            --primary-light: #6366f1;
            --primary-soft: #eef2ff;
            --success: #059669;
            --success-soft: #ecfdf5;
            --warning: #d97706;
            --warning-soft: #fffbeb;
            --danger: #dc2626;
            --danger-soft: #fef2f2;
            --info: #0284c7;
            --info-soft: #f0f9ff;
            --purple: #7c3aed;
            --purple-soft: #f5f3ff;
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px -2px rgba(0,0,0,0.07);
            --shadow-lg: 0 10px 25px -3px rgba(0,0,0,0.08);
            --radius-md: 14px;
            --radius-lg: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
        }

        .container {
            max-width: 1440px;
            margin: 20px auto 50px auto;
            padding: 0 24px;
        }

        /* Hero Banner */
        .page-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            border-radius: var(--radius-lg);
            padding: 36px 40px;
            color: white;
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            overflow: hidden;
        }
        .page-header::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -40px;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-content h1 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-content p {
            font-size: 15px;
            color: #c7d2fe;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .header-pill {
            background: rgba(255,255,255,0.15);
            padding: 4px 12px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 13px;
            backdrop-filter: blur(4px);
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 11px 20px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border: none;
        }
        .btn-primary { background: #4f46e5; color: white; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); }
        .btn-primary:hover { background: #4338ca; transform: translateY(-2px); }
        .btn-white { background: white; color: var(--primary); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-white:hover { background: #f8fafc; transform: translateY(-2px); }
        .btn-success { background: #059669; color: white; }
        .btn-success:hover { background: #047857; transform: translateY(-2px); }
        .btn-outline { background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3); }
        .btn-outline:hover { background: rgba(255,255,255,0.2); }

        /* KPI Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 22px 24px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .icon-indigo { background: var(--primary-soft); color: var(--primary); }
        .icon-green { background: var(--success-soft); color: var(--success); }
        .icon-blue { background: var(--info-soft); color: var(--info); }
        .icon-purple { background: var(--purple-soft); color: var(--purple); }
        .icon-amber { background: var(--warning-soft); color: var(--warning); }

        .stat-meta h3 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.1;
        }
        .stat-meta p {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 4px;
        }

        /* Formula Explainer Banner */
        .formula-card {
            background: white;
            border-radius: var(--radius-md);
            padding: 20px 24px;
            margin-bottom: 25px;
            border: 1px solid #e0e7ff;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .formula-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .formula-title i {
            font-size: 24px;
            color: var(--primary);
        }
        .formula-title h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
        }
        .formula-title p {
            font-size: 13px;
            color: var(--text-muted);
        }
        .formula-badges {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .calc-pill {
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .calc-ca { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .calc-exam { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .calc-total { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }

        /* Main Table Card */
        .card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 28px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        /* Search & Controls */
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 22px;
        }
        .search-box {
            position: relative;
            min-width: 320px;
            flex: 1;
            max-width: 450px;
        }
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
        }
        .search-box input {
            width: 100%;
            padding: 11px 16px 11px 42px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f8fafc;
        }
        .search-box input:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.12);
        }

        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-select {
            padding: 10px 16px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: #f8fafc;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            outline: none;
            cursor: pointer;
        }
        .filter-select:focus {
            border-color: var(--primary);
        }

        /* Score Table */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 14px;
            border: 1px solid var(--border-color);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
            white-space: nowrap;
        }
        thead th {
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 2px solid var(--border-color);
        }
        thead th.section-hdr {
            text-align: center;
            border-left: 1px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
        }
        tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.15s ease;
        }
        tbody tr:hover {
            background-color: #f8fafc;
        }
        tbody td {
            padding: 14px 16px;
            vertical-align: middle;
        }

        /* Student info cell */
        .student-cell {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .student-name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 15px;
        }
        .student-reg {
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
        }
        .student-topic {
            font-size: 12px;
            color: var(--text-muted);
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-style: italic;
        }

        /* Badges */
        .badge-raw {
            padding: 4px 9px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            background: #f1f5f9;
            color: #334155;
            display: inline-block;
            min-width: 44px;
            text-align: center;
        }
        .badge-raw-none {
            background: #fef2f2;
            color: #ef4444;
            font-style: italic;
            font-size: 11px;
            padding: 3px 6px;
        }

        .badge-ca {
            padding: 6px 12px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 14px;
            background: #dbeafe;
            color: #1e40af;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }

        .badge-exam {
            padding: 6px 12px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 14px;
            background: #dcfce7;
            color: #15803d;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }

        .badge-total {
            padding: 7px 14px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 15px;
            background: var(--primary);
            color: white;
            display: inline-block;
            min-width: 65px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(67, 56, 202, 0.25);
        }

        .grade-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
        }

        .status-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-complete { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-main);
        }

        @media (max-width: 960px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .header-actions { width: 100%; justify-content: flex-start; }
            .search-box { min-width: 100%; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <!-- Header Banner -->
        <div class="page-header">
            <div class="header-content">
                <h1><i class="fas fa-file-signature"></i> Final Result Template</h1>
                <p>
                    <span><i class="fas fa-building-columns"></i> <?= htmlspecialchars($dept_name) ?></span>
                    <span class="header-pill"><i class="fas fa-calendar-alt"></i> Session: <?= $active_session ?></span>
                    <span class="header-pill"><i class="fas fa-sliders"></i> Weighted Scheme (30% CA / 70% Exam)</span>
                </p>
            </div>
            <div class="header-actions">
                <a href="?export=csv" class="btn btn-white">
                    <i class="fas fa-file-csv" style="color: var(--success);"></i> Export CSV
                </a>
                <a href="dpc_print_final_result.php" target="_blank" class="btn btn-outline">
                    <i class="fas fa-print"></i> Print Official Sheet
                </a>
                <a href="dpc_view_assessments.php" class="btn btn-outline">
                    <i class="fas fa-list-check"></i> Defense Scores
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-house"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- KPI Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-indigo"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-meta">
                    <h3><?= $stats['total'] ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-circle-check"></i></div>
                <div class="stat-meta">
                    <h3><?= $stats['complete'] ?> <small style="font-size: 13px; color: var(--text-muted); font-weight: 500;">/ <?= $stats['total'] ?></small></h3>
                    <p>Fully Graded</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-chart-line"></i></div>
                <div class="stat-meta">
                    <h3><?= number_format($class_average, 1) ?>%</h3>
                    <p>Class Average</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-trophy"></i></div>
                <div class="stat-meta">
                    <h3><?= number_format($stats['highest_score'], 1) ?>%</h3>
                    <p>Highest Score</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-amber"><i class="fas fa-percent"></i></div>
                <div class="stat-meta">
                    <h3><?= number_format($pass_rate, 1) ?>%</h3>
                    <p>Pass Rate (&ge;40%)</p>
                </div>
            </div>
        </div>

        <!-- Formula Rule Card -->
        <div class="formula-card">
            <div class="formula-title">
                <i class="fas fa-calculator"></i>
                <div>
                    <h4>Result Calculation Scheme</h4>
                    <p>Institutional weighting applied across continuous assessment and final project defenses</p>
                </div>
            </div>
            <div class="formula-badges">
                <div class="calc-pill calc-ca">
                    <i class="fas fa-user-shield"></i> <strong>CA Score (30%)</strong>: External Defense &times; 0.30
                </div>
                <div class="calc-pill calc-exam">
                    <i class="fas fa-scale-balanced"></i> <strong>EXAM Score (70%)</strong>: (Prop + Internal + Sup) / 300 &times; 70%
                </div>
                <div class="calc-pill calc-total">
                    <i class="fas fa-award"></i> <strong>Final Total</strong>: CA (30%) + EXAM (70%) = 100%
                </div>
            </div>
        </div>

        <!-- Main Score Table Card -->
        <div class="card">
            <div class="table-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="studentSearch" placeholder="Search by student name, reg number, topic...">
                </div>
                <div class="filter-group">
                    <select id="statusFilter" class="filter-select">
                        <option value="all">All Grading Statuses</option>
                        <option value="complete">Fully Graded Only</option>
                        <option value="pending">Pending Components</option>
                    </select>
                    <select id="gradeFilter" class="filter-select">
                        <option value="all">All Grades</option>
                        <option value="A">Grade A (70-100%)</option>
                        <option value="B">Grade B (60-69%)</option>
                        <option value="C">Grade C (50-59%)</option>
                        <option value="D">Grade D (45-49%)</option>
                        <option value="E">Grade E (40-44%)</option>
                        <option value="F">Grade F (&lt;40%)</option>
                    </select>
                </div>
            </div>

            <?php if (count($processed_results) > 0): ?>
                <div class="table-responsive">
                    <table id="scoresTable">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 40px; text-align: center;">S/N</th>
                                <th rowspan="2">Student Information</th>
                                <th colspan="4" class="section-hdr" style="background: #f8fafc; color: #334155;">Raw Breakdown Scores (/100)</th>
                                <th colspan="2" class="section-hdr" style="background: #f1f5f9; color: var(--primary);">Weighted Scores</th>
                                <th colspan="3" class="section-hdr" style="background: #eef2ff; color: #312e81;">Final Computed Result</th>
                                <th rowspan="2" style="text-align: center;">Status</th>
                            </tr>
                            <tr>
                                <!-- Raw Breakdown Sub-headers -->
                                <th style="text-align: center;">Proposal</th>
                                <th style="text-align: center;">Internal</th>
                                <th style="text-align: center;">Supervisor</th>
                                <th style="text-align: center;">External</th>
                                <!-- Weighted Sub-headers -->
                                <th style="text-align: center; color: #1e40af;">CA (30%)<br><small style="font-size: 10px; font-weight: normal;">Ext &times; 30%</small></th>
                                <th style="text-align: center; color: #166534;">EXAM (70%)<br><small style="font-size: 10px; font-weight: normal;">(P+I+S)/300&times;70</small></th>
                                <!-- Final Result Sub-headers -->
                                <th style="text-align: center; font-weight: 800;">Total (100%)</th>
                                <th style="text-align: center;">Grade</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sn = 1;
                            foreach ($processed_results as $r): 
                            ?>
                                <tr class="result-row" 
                                    data-search="<?= strtolower(htmlspecialchars($r['student_name'] . ' ' . $r['reg_no'] . ' ' . $r['project_title'] . ' ' . $r['supervisor_name'])) ?>"
                                    data-status="<?= $r['is_complete'] ? 'complete' : 'pending' ?>"
                                    data-grade="<?= $r['grade'] ?>"
                                >
                                    <td style="text-align: center; font-weight: 700; color: var(--text-muted);"><?= $sn++ ?></td>
                                    <td>
                                        <div class="student-cell">
                                            <span class="student-name"><?= htmlspecialchars($r['student_name']) ?></span>
                                            <span class="student-reg"><?= htmlspecialchars($r['reg_no']) ?></span>
                                            <span class="student-topic" title="<?= htmlspecialchars($r['project_title']) ?>">
                                                <i class="fas fa-book" style="font-size: 10px;"></i> <?= htmlspecialchars($r['project_title']) ?>
                                            </span>
                                            <small style="font-size: 11px; color: #64748b;">
                                                <i class="fas fa-chalkboard-user"></i> Sup: <?= htmlspecialchars($r['supervisor_name']) ?>
                                            </small>
                                        </div>
                                    </td>

                                    <!-- Raw Scores -->
                                    <td style="text-align: center;">
                                        <?php if ($r['proposal_raw'] !== null): ?>
                                            <span class="badge-raw"><?= number_format($r['proposal_raw'], 1) ?></span>
                                        <?php else: ?>
                                            <span class="badge-raw badge-raw-none">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($r['internal_raw'] !== null): ?>
                                            <span class="badge-raw"><?= number_format($r['internal_raw'], 1) ?></span>
                                        <?php else: ?>
                                            <span class="badge-raw badge-raw-none">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($r['supervisor_raw'] !== null): ?>
                                            <span class="badge-raw"><?= number_format($r['supervisor_raw'], 1) ?></span>
                                        <?php else: ?>
                                            <span class="badge-raw badge-raw-none">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($r['external_raw'] !== null): ?>
                                            <span class="badge-raw" style="background: #e0f2fe; color: #0369a1;"><?= number_format($r['external_raw'], 1) ?></span>
                                        <?php else: ?>
                                            <span class="badge-raw badge-raw-none">Pending</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Weighted Scores -->
                                    <td style="text-align: center;">
                                        <span class="badge-ca"><?= number_format($r['ca_score'], 1) ?></span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-exam"><?= number_format($r['exam_score'], 1) ?></span>
                                    </td>

                                    <!-- Final Computed Result -->
                                    <td style="text-align: center;">
                                        <span class="badge-total"><?= number_format($r['final_total'], 1) ?>%</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="grade-badge" style="color: <?= $r['grade_color'] ?>; background: <?= $r['grade_bg'] ?>; border: 1px solid <?= $r['grade_color'] ?>;">
                                            <?= $r['grade'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: <?= $r['grade_color'] ?>; font-size: 13px;"><?= $r['remark'] ?></strong>
                                    </td>

                                    <!-- Status -->
                                    <td style="text-align: center;">
                                        <?php if ($r['is_complete']): ?>
                                            <span class="status-pill status-complete">
                                                <i class="fas fa-check"></i> Complete
                                            </span>
                                        <?php else: ?>
                                            <span class="status-pill status-pending" title="Missing: <?= implode(', ', $r['missing_components']) ?>">
                                                <i class="fas fa-clock"></i> Partial (<?= count($r['missing_components']) ?> Left)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Student Records Found</h3>
                    <p>There are no registered students or assessment records found in your department for session <?= $active_session ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Live client-side search & filtering
        const searchInput = document.getElementById('studentSearch');
        const statusFilter = document.getElementById('statusFilter');
        const gradeFilter = document.getElementById('gradeFilter');
        const rows = document.querySelectorAll('.result-row');

        function filterTable() {
            const query = searchInput.value.toLowerCase().trim();
            const selectedStatus = statusFilter.value;
            const selectedGrade = gradeFilter.value;

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                const rowStatus = row.getAttribute('data-status') || '';
                const rowGrade = row.getAttribute('data-grade') || '';

                const matchesQuery = query === '' || searchData.includes(query);
                const matchesStatus = selectedStatus === 'all' || rowStatus === selectedStatus;
                const matchesGrade = selectedGrade === 'all' || rowGrade === selectedGrade;

                if (matchesQuery && matchesStatus && matchesGrade) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (statusFilter) statusFilter.addEventListener('change', filterTable);
        if (gradeFilter) gradeFilter.addEventListener('change', filterTable);
    </script>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
