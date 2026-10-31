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
    if ($total >= 70) return 'A';
    if ($total >= 60) return 'B';
    if ($total >= 50) return 'C';
    if ($total >= 45) return 'D';
    if ($total >= 40) return 'E';
    return 'F';
}

// Fetch comprehensive assessment data - same formula as the Final Result Template
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
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bucket students under their letter grade, in display order A -> F
$grade_order = ['A', 'B', 'C', 'D', 'E', 'F'];
$grouped = array_fill_keys($grade_order, []);

foreach ($rows as $row) {
    $prop = $row['proposal_avg'] !== null ? floatval($row['proposal_avg']) : null;
    $int = $row['internal_avg'] !== null ? floatval($row['internal_avg']) : null;
    $ext = $row['external_avg'] !== null ? floatval($row['external_avg']) : null;
    $sup = $row['supervisor_score'] !== null ? floatval($row['supervisor_score']) : null;

    $ca = ($ext !== null) ? (($ext / 100.0) * 30.0) : 0.0;
    $exam = ($prop ?? 0.0) + ($int ?? 0.0) + ($sup ?? 0.0);
    $total = $ca + $exam;
    $grade = getGradeDetails($total);

    $grouped[$grade][] = [
        'reg_no' => $row['reg_no'],
        'name' => $row['student_name'],
        'topic' => $row['project_title'] ?: 'Not assigned / Approved yet',
        'supervisor' => $row['supervisor_name'] ?: 'Not Assigned',
    ];
}

$total_count = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Grouped by Grade - <?= htmlspecialchars($dept_name) ?></title>
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

        table.grade-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        table.grade-table th, table.grade-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: center;
        }
        table.grade-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 9.5pt;
        }
        table.grade-table td.text-left {
            text-align: left;
        }
        table.grade-table tr.separator-row td {
            background-color: #d9d9d9;
            padding: 3px;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
        }
        table.grade-table tr.grade-row td {
            background-color: #eef2ff;
            font-weight: bold;
            text-align: left;
            font-size: 9.5pt;
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
            table.grade-table th,
            table.grade-table tr.separator-row td,
            table.grade-table tr.grade-row td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <div class="no-print no-print-bar">
        <div>
            <strong>Print View:</strong> Students Grouped by Grade
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-print" onclick="window.print()">Print List</button>
            <button class="btn" onclick="window.close()">Close Window</button>
        </div>
    </div>

    <div class="header">
        <h1>PROJECT VALIDATION &amp; ASSESSMENT SYSTEM</h1>
        <h2>Department of <?= htmlspecialchars($dept_name) ?></h2>
        <h3>Students Grouped by Grade</h3>
        <div class="header-meta">
            <span>Academic Session: <?= htmlspecialchars($active_session) ?></span>
            <span>Total Students: <?= $total_count ?></span>
            <span>Date: <?= date('d/m/Y') ?></span>
        </div>
    </div>

    <?php if ($total_count === 0): ?>
        <div class="empty-note">No students found in this department for session <?= htmlspecialchars($active_session) ?>.</div>
    <?php else: ?>
        <table class="grade-table">
            <thead>
                <tr>
                    <th style="width: 30px;">S/N</th>
                    <th style="width: 95px;">Reg.No</th>
                    <th style="text-align: left; width: 150px;">Name</th>
                    <th style="text-align: left;">Topic</th>
                    <th style="text-align: left; width: 140px;">Supervisor</th>
                    <th style="width: 50px;">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php $sn = 1; $first_group = true; foreach ($grade_order as $grade): ?>
                    <?php if (empty($grouped[$grade])) continue; ?>
                    <?php if (!$first_group): ?>
                        <tr class="separator-row"><td colspan="6">&nbsp;</td></tr>
                    <?php endif; $first_group = false; ?>
                    <tr class="grade-row">
                        <td colspan="6">GRADE <?= $grade ?> &mdash; <?= count($grouped[$grade]) ?> Student(s)</td>
                    </tr>
                    <?php foreach ($grouped[$grade] as $st): ?>
                        <tr>
                            <td><?= $sn++ ?></td>
                            <td><?= htmlspecialchars($st['reg_no']) ?></td>
                            <td class="text-left"><?= htmlspecialchars($st['name']) ?></td>
                            <td class="text-left" style="font-style: italic;"><?= htmlspecialchars($st['topic']) ?></td>
                            <td class="text-left"><?= htmlspecialchars($st['supervisor']) ?></td>
                            <td><strong><?= $grade ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
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
