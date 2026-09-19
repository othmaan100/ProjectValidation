<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dpc_id = $_SESSION['user_id'];

// Fetch DPC's department info
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name FROM users u JOIN departments d ON u.department = d.id WHERE u.id = ?");
$stmt->execute([$dpc_id]);
$dpc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $dpc_info['dept_id'];
$dept_name = $dpc_info['department_name'] ?? 'Department';

$active_session = $current_session;

function getGradeDetails($total) {
    if ($total >= 70) {
        return ['grade' => 'A', 'remark' => 'Excellent'];
    } elseif ($total >= 60) {
        return ['grade' => 'B', 'remark' => 'Very Good'];
    } elseif ($total >= 50) {
        return ['grade' => 'C', 'remark' => 'Good'];
    } elseif ($total >= 45) {
        return ['grade' => 'D', 'remark' => 'Fair'];
    } elseif ($total >= 40) {
        return ['grade' => 'E', 'remark' => 'Pass'];
    } else {
        return ['grade' => 'F', 'remark' => 'Fail'];
    }
}

// Fetch comprehensive assessment data
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
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grade_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0];
$total_score_sum = 0;
$highest = 0;
$lowest = 100;
$total_count = count($students);

$processed = [];
foreach ($students as $row) {
    $prop = $row['proposal_avg'] !== null ? floatval($row['proposal_avg']) : null;
    $int = $row['internal_avg'] !== null ? floatval($row['internal_avg']) : null;
    $ext = $row['external_avg'] !== null ? floatval($row['external_avg']) : null;
    $sup = $row['supervisor_score'] !== null ? floatval($row['supervisor_score']) : null;

    // CA = External (out of 100) scaled to its 30-point share
    $ca = ($ext !== null) ? (($ext / 100.0) * 30.0) : 0.0;

    // Exam = Proposal (max 10) + Internal (max 20) + Supervisor (max 40) - each is
    // already entered on its own final scale, so no further scaling is needed;
    // together they cap at 70.
    $exam = ($prop ?? 0.0) + ($int ?? 0.0) + ($sup ?? 0.0);

    // Total = CA + Exam (caps at 100)
    $total = $ca + $exam;
    
    $grade_info = getGradeDetails($total);
    $grade_distribution[$grade_info['grade']]++;
    
    $total_score_sum += $total;
    if ($total > $highest) $highest = $total;
    if ($total < $lowest && $total_count > 0) $lowest = $total;

    $processed[] = [
        'name' => $row['student_name'],
        'reg_no' => $row['reg_no'],
        'topic' => $row['project_title'],
        'supervisor' => $row['supervisor_name'],
        'prop' => $prop,
        'int' => $int,
        'sup' => $sup,
        'ext' => $ext,
        'ca' => $ca,
        'exam' => $exam,
        'total' => $total,
        'grade' => $grade_info['grade'],
        'remark' => $grade_info['remark']
    ];
}

$class_avg = $total_count > 0 ? ($total_score_sum / $total_count) : 0;
if ($total_count === 0) $lowest = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Final Result Broadsheet - <?= htmlspecialchars($dept_name) ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 10mm 15mm 10mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 15px;
            font-size: 11pt;
            line-height: 1.3;
        }

        .no-print-bar {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            border: 1px solid #94a3b8;
            background: #fff;
            color: #0f172a;
        }
        .btn-print { background: #4338ca; color: #fff; border-color: #4338ca; }

        .header {
            text-align: center;
            margin-bottom: 18px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 { font-size: 17pt; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 1px; }
        .header h2 { font-size: 13pt; margin: 0 0 4px 0; text-transform: uppercase; font-weight: normal; }
        .header h3 { font-size: 12pt; margin: 4px 0; text-transform: uppercase; text-decoration: underline; }
        .header-meta {
            display: flex;
            justify-content: space-between;
            font-size: 10pt;
            margin-top: 8px;
            font-weight: bold;
        }

        .formula-box {
            border: 1px dashed #444;
            padding: 6px 12px;
            font-size: 9pt;
            margin-bottom: 12px;
            background: #fafafa;
            display: flex;
            justify-content: space-around;
        }

        table.broadsheet {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        table.broadsheet th, table.broadsheet td {
            border: 1px solid #000;
            padding: 5px 6px;
            text-align: center;
        }
        table.broadsheet th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 9pt;
        }
        table.broadsheet td.text-left {
            text-align: left;
        }
        table.broadsheet tr:nth-child(even) {
            background-color: #fbfbfb;
        }

        .summary-section {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            page-break-inside: avoid;
        }
        .dist-table {
            border-collapse: collapse;
            font-size: 9pt;
        }
        .dist-table th, .dist-table td {
            border: 1px solid #000;
            padding: 4px 10px;
            text-align: center;
        }
        .dist-table th { background: #f0f0f0; }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .sig-block {
            text-align: center;
            font-size: 9.5pt;
        }
        .sig-line {
            border-top: 1px solid #000;
            margin-top: 45px;
            padding-top: 5px;
            font-weight: bold;
        }
        .sig-title {
            font-size: 8.5pt;
            color: #333;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .broadsheet th { background-color: #eee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="if(window.location.search.indexOf('noprint') === -1) { /* window.print(); */ }">

    <div class="no-print no-print-bar">
        <div>
            <strong>Official Print View:</strong> Final Result Template (30% CA / 70% Exam)
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-print" onclick="window.print()">Print Broadsheet</button>
            <button class="btn" onclick="window.close()">Close Window</button>
        </div>
    </div>

    <div class="header">
        <h1>PROJECT VALIDATION & ASSESSMENT SYSTEM</h1>
        <h2>Department of <?= htmlspecialchars($dept_name) ?></h2>
        <h3>Final Project Result Template & Broadsheet</h3>
        <div class="header-meta">
            <span>Academic Session: <?= $active_session ?></span>
            <span>Weighting: CA (30%) + EXAM (70%) = 100%</span>
            <span>Date: <?= date('d/m/Y') ?></span>
        </div>
    </div>

    <div class="formula-box">
        <span><strong>Continuous Assessment (CA - 30%):</strong> External Defense &divide; 100 &times; 30</span>
        <span><strong>EXAM (70%):</strong> Proposal (/10) + Internal (/20) + Supervisor (/40)</span>
        <span><strong>Overall Total (100%):</strong> CA Score + EXAM Score</span>
    </div>

    <table class="broadsheet">
        <thead>
            <tr>
                <th rowspan="2" style="width: 25px;">S/N</th>
                <th rowspan="2" style="width: 95px;">Reg. Number</th>
                <th rowspan="2" style="text-align: left; width: 170px;">Student Full Name</th>
                <th colspan="4">Raw Assessment Scores</th>
                <th colspan="2">Weighted Components</th>
                <th colspan="3">Final Result</th>
            </tr>
            <tr>
                <th style="width: 45px;">Proposal (/10)</th>
                <th style="width: 45px;">Internal (/20)</th>
                <th style="width: 45px;">Supervisor (/40)</th>
                <th style="width: 45px;">External (/100)</th>
                <th style="width: 55px;">CA (30%)</th>
                <th style="width: 60px;">EXAM (70%)</th>
                <th style="width: 65px;">Total (100%)</th>
                <th style="width: 40px;">Grade</th>
                <th style="width: 75px;">Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $sn = 1;
            foreach ($processed as $p): 
            ?>
                <tr>
                    <td><?= $sn++ ?></td>
                    <td><?= htmlspecialchars($p['reg_no']) ?></td>
                    <td class="text-left"><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                    <td><?= $p['prop'] !== null ? number_format($p['prop'], 1) : '-' ?></td>
                    <td><?= $p['int'] !== null ? number_format($p['int'], 1) : '-' ?></td>
                    <td><?= $p['sup'] !== null ? number_format($p['sup'], 1) : '-' ?></td>
                    <td><?= $p['ext'] !== null ? number_format($p['ext'], 1) : '-' ?></td>
                    <td><strong><?= number_format($p['ca'], 1) ?></strong></td>
                    <td><strong><?= number_format($p['exam'], 1) ?></strong></td>
                    <td style="font-weight: bold; background-color: #fafafa;"><?= number_format($p['total'], 1) ?></td>
                    <td style="font-weight: bold;"><?= $p['grade'] ?></td>
                    <td><?= $p['remark'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary-section">
        <div>
            <strong style="font-size: 10pt; display: block; margin-bottom: 5px;">Grade Distribution & Summary:</strong>
            <table class="dist-table">
                <thead>
                    <tr>
                        <th>Grade</th>
                        <th>A (70-100%)</th>
                        <th>B (60-69%)</th>
                        <th>C (50-59%)</th>
                        <th>D (45-49%)</th>
                        <th>E (40-44%)</th>
                        <th>F (<40%)</th>
                        <th>Total Students</th>
                        <th>Class Average</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Count</strong></td>
                        <td><?= $grade_distribution['A'] ?></td>
                        <td><?= $grade_distribution['B'] ?></td>
                        <td><?= $grade_distribution['C'] ?></td>
                        <td><?= $grade_distribution['D'] ?></td>
                        <td><?= $grade_distribution['E'] ?></td>
                        <td><?= $grade_distribution['F'] ?></td>
                        <td><strong><?= $total_count ?></strong></td>
                        <td><strong><?= number_format($class_avg, 1) ?>%</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Official Sign-offs -->
    <div class="signatures-grid">
        <div class="sig-block">
            <div class="sig-line">Project Coordinator</div>
            <div class="sig-title">Sign / Date</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">Head of Department</div>
            <div class="sig-title">Sign / Date</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">External Examiner</div>
            <div class="sig-title">Sign / Date</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">Dean of Faculty</div>
            <div class="sig-title">Sign / Date</div>
        </div>
    </div>

</body>
</html>
