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

// Get current session
$session_stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_session'");
$active_session = $session_stmt->fetchColumn() ?: date('Y') . '/' . (date('Y') + 1);

// Fetch assessments
$query = "
    SELECT 
        s.id as student_id, 
        s.name as student_name, 
        s.reg_no,
        dp.panel_name,
        dp.panel_type,
        GROUP_CONCAT(CONCAT(sup.name, ': ', ds.score) SEPARATOR ' | ') as individual_scores,
        AVG(ds.score) as average_score
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Student Assessments - <?= htmlspecialchars($dept_name) ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 0; padding: 20px; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 5px 0; font-size: 20px; text-transform: uppercase; }
        .header h2 { margin: 5px 0; font-size: 16px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #f0f0f0; }

        .footer { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature { width: 200px; border-top: 1px solid #000; text-align: center; padding-top: 5px; margin-top: 40px; font-weight: bold; }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="background: #fff3cd; padding: 10px; text-align: center; margin-bottom: 20px; border: 1px solid #ffeeba; border-radius: 5px;">
        <button onclick="window.print()">Print Again</button>
        <button onclick="window.close()">Close Window</button>
    </div>

    <div class="header">
        <h1>Federal University of Technology</h1>
        <h2>Department of <?= htmlspecialchars($dept_name) ?></h2>
        <h3>Project Defense Assessment Score Sheet</h3>
        <p>Academic Session: <?= $active_session ?></p>
    </div>

    <?php 
    $grouped_assessments = [
        'proposal' => [],
        'internal' => [],
        'external' => []
    ];
    foreach ($assessments as $as) {
        $grouped_assessments[$as['panel_type']][] = $as;
    }

    $stages = [
        'proposal' => 'PROJECT PROPOSAL DEFENSE',
        'internal' => 'INTERNAL DEFENSE',
        'external' => 'EXTERNAL DEFENSE'
    ];

    foreach ($stages as $type => $label):
        if (!empty($grouped_assessments[$type])):
    ?>
    <h4 style="margin-top: 30px; margin-bottom: 10px; border-bottom: 1px solid #000; display: inline-block;"><?= $label ?></h4>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">S/N</th>
                <th style="width: 25%;">Student Name</th>
                <th style="width: 15%;">Reg No</th>
                <th style="width: 15%;">Panel Name</th>
                <th>Individual Scores (Lecturers)</th>
                <th style="width: 10%; text-align: center;">Avg (%)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $count = 1;
            foreach ($grouped_assessments[$type] as $as): ?>
                <tr>
                    <td><?= $count++ ?></td>
                    <td><?= htmlspecialchars($as['student_name']) ?></td>
                    <td><?= htmlspecialchars($as['reg_no']) ?></td>
                    <td><?= htmlspecialchars($as['panel_name']) ?></td>
                    <td><?= $as['individual_scores'] ?: '--' ?></td>
                    <td style="text-align: center;"><strong><?= $as['average_score'] !== null ? number_format($as['average_score'], 1) : '--' ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php 
        endif;
    endforeach;

    if (empty($assessments)): ?>
        <p style="text-align: center; margin-top: 50px;">No assessment records found.</p>
    <?php endif; ?>

    <div class="footer">
        <div class="signature">
            Lecturer-in-Charge
        </div>
        <div class="signature">
            Head of Department
        </div>
    </div>

    <div style="margin-top: 20px; font-size: 10px; color: #666; text-align: right;">
        Generated on: <?= date('Y-m-d H:i:s') ?>
    </div>
</body>
</html>
