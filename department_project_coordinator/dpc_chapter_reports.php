<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dpc_id = $_SESSION['user_id'];

// Use global current session from db.php

// Fetch the DPC's department info
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name, d.num_chapters FROM users u JOIN departments d ON u.department = d.id WHERE u.id = ?");
$stmt->execute([$dpc_id]);
$dpc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $dpc_info['dept_id'];
$dept_name = $dpc_info['department_name'];
$num_chapters = (int)($dpc_info['num_chapters'] ?? 5);

// Fetch all students in the department and their supervisors and chapter progress
$stmt = $conn->prepare("
    SELECT s.id, s.name, s.reg_no, su.name AS supervisor_name,
           (SELECT status FROM chapter_approvals WHERE student_id = s.id AND clearance_level = 'proposal') as proposal_status,
           (SELECT status FROM chapter_approvals WHERE student_id = s.id AND clearance_level = 'internal') as internal_status,
           (SELECT status FROM chapter_approvals WHERE student_id = s.id AND clearance_level = 'external') as external_status
    FROM students s
    LEFT JOIN supervision sp ON s.id = sp.student_id AND sp.status = 'active'
    LEFT JOIN supervisors su ON sp.supervisor_id = su.id
    WHERE s.department = ?
    ORDER BY s.name ASC
");
$stmt->execute([$dept_id]);
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapter Progress Report - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= PROJECT_ROOT ?>assets/css/styles.css">
    <style>
        .report-header { margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .report-table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        .report-table th { background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; text-align: left; font-size: 12px; text-transform: uppercase; color: #64748b; }
        .report-table td { padding: 12px; border: 1px solid #e2e8f0; font-size: 14px; }
        .progress-bar-container { width: 100%; background: #f1f5f9; height: 8px; border-radius: 4px; overflow: hidden; }
        .progress-bar { height: 100%; background: #10b981; }
        .print-btn { background: #1a202c; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        
        @media print {
            .no-print, header, nav, footer, .print-btn { display: none !important; }
            html, body { 
                background: white !important; 
                display: block !important;
                height: auto !important;
                min-height: 0 !important;
            }
            .container { 
                width: 100% !important; 
                max-width: 100% !important; 
                padding: 0 !important; 
                margin: 0 !important;
                display: block !important;
                box-shadow: none !important;
            }
            .report-table { font-size: 12px; border: 1px solid #000 !important; }
            .report-table th, .report-table td { border: 1px solid #000 !important; color: #000 !important; }
            .print-only { display: block !important; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body style="background: #f8fafc;">
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div> <!-- Close header's container -->

    <div class="container" style="padding: 40px 20px;">
        <div class="report-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 style="font-size: 24px; color: #1e293b; margin-bottom: 5px;">Defense Clearance Report</h1>
                    <p style="color: #64748b;">Department: <?= htmlspecialchars($dept_name) ?> | Session: <?= $current_session ?></p>
                </div>
                <button onclick="window.print()" class="print-btn no-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 50px;">S/N</th>
                    <th>Student Name</th>
                    <th>Reg Number</th>
                    <th>Supervisor</th>
                    <th style="text-align: center;">Proposal Defense</th>
                    <th style="text-align: center;">Internal Defense</th>
                    <th style="text-align: center;">External Defense</th>
                    <th style="text-align: center;">Completion</th>
                </tr>
            </thead>
            <tbody>
                <?php $sn = 1; foreach ($report_data as $row): ?>
                    <?php 
                        $approved_count = 0;
                        if ($row['proposal_status'] == 'approved') $approved_count++;
                        if ($row['internal_status'] == 'approved') $approved_count++;
                        if ($row['external_status'] == 'approved') $approved_count++;
                        $percent = round(($approved_count / 3) * 100);
                        
                        // We use a simple helper function in the loop body or conditionally output it
                    ?>
                    <tr>
                        <td style="text-align: center;"><?= $sn++ ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                        <td><code><?= htmlspecialchars($row['reg_no']) ?></code></td>
                        <td><?= htmlspecialchars($row['supervisor_name'] ?: 'N/A') ?></td>
                        <td style="text-align: center;">
                            <?= $row['proposal_status'] == 'approved' ? '<span style="color: #10b981; font-size: 18px;"><i class="fas fa-check-circle"></i></span>' : '<span style="color: #cbd5e1; font-size: 18px;"><i class="fas fa-times-circle"></i></span>' ?>
                        </td>
                        <td style="text-align: center;">
                            <?= $row['internal_status'] == 'approved' ? '<span style="color: #10b981; font-size: 18px;"><i class="fas fa-check-circle"></i></span>' : '<span style="color: #cbd5e1; font-size: 18px;"><i class="fas fa-times-circle"></i></span>' ?>
                        </td>
                        <td style="text-align: center;">
                            <?= $row['external_status'] == 'approved' ? '<span style="color: #10b981; font-size: 18px;"><i class="fas fa-check-circle"></i></span>' : '<span style="color: #cbd5e1; font-size: 18px;"><i class="fas fa-times-circle"></i></span>' ?>
                        </td>
                        <td>
                            <div style="margin-bottom: 4px; font-weight: bold; font-size: 12px; color: #10b981;">
                                <?= $percent ?>%
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?= $percent ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 50px; display: none;" class="print-only">
            <div style="display: flex; justify-content: space-between;">
                <div style="text-align: center;">
                    <div style="border-bottom: 1px solid black; width: 200px; margin-bottom: 5px;"></div>
                    <p>Coordinator's Signature</p>
                </div>
                <div style="text-align: center;">
                    <div style="border-bottom: 1px solid black; width: 200px; margin-bottom: 5px;"></div>
                    <p>Date</p>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>
</html>
