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
           (SELECT COUNT(*) FROM chapter_approvals WHERE student_id = s.id AND status = 'approved') as approved_chapters
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
            .no-print { display: none !important; }
            body { background: white !important; }
            .container { width: 100% !important; max-width: 100% !important; padding: 0 !important; }
            .report-table { font-size: 12px; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body style="background: #f8fafc;">
    <div class="no-print">
        <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div>

    <div class="container" style="padding: 40px 20px;">
        <div class="report-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1 style="font-size: 24px; color: #1e293b; margin-bottom: 5px;">Project Chapter Progress Report</h1>
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
                    <th>Chapters Approved</th>
                    <th>Completion Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $sn = 1; foreach ($report_data as $row): ?>
                    <?php 
                        $percent = $num_chapters > 0 ? round(($row['approved_chapters'] / $num_chapters) * 100) : 0;
                    ?>
                    <tr>
                        <td style="text-align: center;"><?= $sn++ ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></td>
                        <td><code><?= htmlspecialchars($row['reg_no']) ?></code></td>
                        <td><?= htmlspecialchars($row['supervisor_name'] ?: 'N/A') ?></td>
                        <td style="text-align: center;">
                            <strong><?= $row['approved_chapters'] ?></strong> / <?= $num_chapters ?>
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

    <style>
        @media print {
            .print-only { display: block !important; }
        }
    </style>
</body>
</html>
