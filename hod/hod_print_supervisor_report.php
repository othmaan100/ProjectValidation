<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hod') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$hod_id = $_SESSION['user_id'];

// Fetch HOD's department info
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name FROM users u JOIN departments d ON u.department = d.id WHERE u.id = ?");
$stmt->execute([$hod_id]);
$hod_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $hod_info['dept_id'];
$dept_name = $hod_info['department_name'] ?? 'Department';

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

// Fetch every student in the department together with their assigned supervisor and
// the same score breakdown/formula used by the Final Result Template, grouped by
// supervisor (unassigned students are pushed into their own group at the end).
$query = "
    SELECT
        s.id as student_id,
        s.name as student_name,
        s.reg_no,
        pt.topic as project_title,
        sv.supervisor_id,
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
    ORDER BY (sv.supervisor_id IS NULL) ASC, sup.name ASC, s.reg_no ASC
";

$stmt = $conn->prepare($query);
$stmt->execute([$active_session, $dept_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group students under their supervisor, preserving the SQL-provided order.
$groups = [];
foreach ($rows as $row) {
    $sup_key = $row['supervisor_id'] ?? 'unassigned';
    $sup_name = $row['supervisor_name'] ?: 'No Supervisor Assigned';

    if (!isset($groups[$sup_key])) {
        $groups[$sup_key] = ['name' => $sup_name, 'students' => [], 'score_sum' => 0, 'graded_count' => 0];
    }

    $prop = $row['proposal_avg'] !== null ? floatval($row['proposal_avg']) : null;
    $int = $row['internal_avg'] !== null ? floatval($row['internal_avg']) : null;
    $ext = $row['external_avg'] !== null ? floatval($row['external_avg']) : null;
    $sup = $row['supervisor_score'] !== null ? floatval($row['supervisor_score']) : null;

    // Same formula as the Final Result Template: CA = External/100 scaled to 30,
    // Exam = Proposal (/10) + Internal (/20) + Supervisor (/40), Total = CA + Exam.
    $ca = ($ext !== null) ? (($ext / 100.0) * 30.0) : 0.0;
    $exam = ($prop ?? 0.0) + ($int ?? 0.0) + ($sup ?? 0.0);
    $total = $ca + $exam;
    $is_complete = $prop !== null && $int !== null && $ext !== null && $sup !== null;
    $grade_info = getGradeDetails($total);

    $groups[$sup_key]['students'][] = [
        'name' => $row['student_name'],
        'reg_no' => $row['reg_no'],
        'topic' => $row['project_title'] ?: 'Not assigned / Approved yet',
        'prop' => $prop,
        'int' => $int,
        'sup' => $sup,
        'ext' => $ext,
        'ca' => $ca,
        'exam' => $exam,
        'total' => $total,
        'grade' => $grade_info['grade'],
        'remark' => $grade_info['remark'],
        'is_complete' => $is_complete,
    ];
    $groups[$sup_key]['score_sum'] += $total;
    $groups[$sup_key]['graded_count']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Student Score Report - <?= htmlspecialchars($dept_name) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 12mm 16mm 12mm;
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
        .header h1 { font-size: 16pt; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 1px; }
        .header h2 { font-size: 12.5pt; margin: 0 0 4px 0; text-transform: uppercase; font-weight: normal; }
        .header h3 { font-size: 11.5pt; margin: 4px 0; text-transform: uppercase; text-decoration: underline; }
        .header-meta {
            display: flex;
            justify-content: space-between;
            font-size: 10pt;
            margin-top: 8px;
            font-weight: bold;
        }

        .supervisor-block {
            margin-bottom: 26px;
            page-break-inside: avoid;
        }
        .supervisor-title {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .supervisor-title h4 {
            font-size: 12.5pt;
            text-transform: uppercase;
        }
        .supervisor-title span {
            font-size: 9.5pt;
            font-weight: bold;
        }

        table.sup-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        table.sup-table th, table.sup-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            text-align: center;
        }
        table.sup-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 9pt;
        }
        table.sup-table td.text-left {
            text-align: left;
        }
        table.sup-table tr:nth-child(even) {
            background-color: #fbfbfb;
        }
        table.sup-table tfoot td {
            font-weight: bold;
            background-color: #f2f2f2;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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

        .empty-note {
            text-align: center;
            padding: 40px 0;
            font-style: italic;
            color: #555;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            table.sup-table th, table.sup-table tfoot td { background-color: #eee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="no-print no-print-bar">
        <div>
            <strong>Print View:</strong> Supervisor &amp; Assigned Student Scores Report
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-print" onclick="window.print()">Print Report</button>
            <button class="btn" onclick="window.close()">Close Window</button>
        </div>
    </div>

    <div class="header">
        <h1>PROJECT VALIDATION &amp; ASSESSMENT SYSTEM</h1>
        <h2>Department of <?= htmlspecialchars($dept_name) ?></h2>
        <h3>Supervisor &amp; Assigned Student Scores Report</h3>
        <div class="header-meta">
            <span>Academic Session: <?= htmlspecialchars($active_session) ?></span>
            <span>Total Supervisors: <?= count($groups) ?></span>
            <span>Date: <?= date('d/m/Y') ?></span>
        </div>
    </div>

    <?php if (empty($groups)): ?>
        <div class="empty-note">No students found in this department for session <?= htmlspecialchars($active_session) ?>.</div>
    <?php else: ?>
        <?php foreach ($groups as $group): ?>
            <?php $avg = count($group['students']) > 0 ? $group['score_sum'] / count($group['students']) : 0; ?>
            <div class="supervisor-block">
                <div class="supervisor-title">
                    <h4><?= htmlspecialchars($group['name']) ?></h4>
                    <span><?= count($group['students']) ?> Student(s) &mdash; Average: <?= number_format($avg, 1) ?>%</span>
                </div>
                <table class="sup-table">
                    <thead>
                        <tr>
                            <th style="width: 25px;">S/N</th>
                            <th style="width: 95px;">Reg. Number</th>
                            <th style="text-align: left; width: 150px;">Student Full Name</th>
                            <th style="text-align: left;">Project Topic</th>
                            <th style="width: 45px;">Proposal (/10)</th>
                            <th style="width: 45px;">Internal (/20)</th>
                            <th style="width: 45px;">Supervisor (/40)</th>
                            <th style="width: 50px;">CA (/30)</th>
                            <th style="width: 55px;">Total (100%)</th>
                            <th style="width: 40px;">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach ($group['students'] as $st): ?>
                            <tr>
                                <td><?= $sn++ ?></td>
                                <td><?= htmlspecialchars($st['reg_no']) ?></td>
                                <td class="text-left"><strong><?= htmlspecialchars($st['name']) ?></strong></td>
                                <td class="text-left" style="font-style: italic;"><?= htmlspecialchars($st['topic']) ?></td>
                                <td><?= $st['prop'] !== null ? number_format($st['prop'], 1) : '-' ?></td>
                                <td><?= $st['int'] !== null ? number_format($st['int'], 1) : '-' ?></td>
                                <td><?= $st['sup'] !== null ? number_format($st['sup'], 1) : '-' ?></td>
                                <td><?= number_format($st['ca'], 1) ?></td>
                                <td style="font-weight: bold; background-color: #fafafa;"><?= number_format($st['total'], 1) ?></td>
                                <td style="font-weight: bold;"><?= $st['grade'] ?><?= $st['is_complete'] ? '' : '*' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8" class="text-left">Supervisor Average</td>
                            <td colspan="2"><?= number_format($avg, 1) ?>%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endforeach; ?>
        <p class="no-print" style="font-size: 11px; color: #64748b;">* Grade marked with an asterisk has one or more missing score components (treated as 0 in the total shown).</p>
    <?php endif; ?>

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
    </div>

</body>
</html>
