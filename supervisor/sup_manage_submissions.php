<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$supervisor_id = $_SESSION['user_id'];
$message = '';
$status = '';

// Handle Approval / Validation / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $topic_id = intval($_POST['topic_id'] ?? 0);
    $action = $_POST['action']; // 'approve_report', 'reject_report', 'approve_code', 'reject_code', 'approve_both'
    $feedback = trim($_POST['feedback'] ?? '');

    try {
        if ($action === 'approve_report') {
            $stmt = $conn->prepare("UPDATE project_topics SET report_status = 'approved', report_feedback = NULL WHERE id = ?");
            $stmt->execute([$topic_id]);
            $message = "Project report approved successfully.";
            $status = "success";
        } elseif ($action === 'reject_report') {
            if (empty($feedback)) {
                throw new Exception("Please provide feedback for report rejection.");
            }
            $stmt = $conn->prepare("UPDATE project_topics SET report_status = 'rejected', report_feedback = ? WHERE id = ?");
            $stmt->execute([$feedback, $topic_id]);
            $message = "Project report rejected. Feedback sent to student.";
            $status = "success";
        } elseif ($action === 'approve_code') {
            $stmt = $conn->prepare("UPDATE project_topics SET source_code_status = 'approved', source_code_feedback = NULL WHERE id = ?");
            $stmt->execute([$topic_id]);
            $message = "Student source code validated and approved successfully.";
            $status = "success";
        } elseif ($action === 'reject_code') {
            if (empty($feedback)) {
                throw new Exception("Please provide feedback explaining why the source code was rejected.");
            }
            $stmt = $conn->prepare("UPDATE project_topics SET source_code_status = 'rejected', source_code_feedback = ? WHERE id = ?");
            $stmt->execute([$feedback, $topic_id]);
            $message = "Source code submission rejected. Student will be prompted to re-upload.";
            $status = "success";
        } elseif ($action === 'approve_both') {
            $stmt = $conn->prepare("UPDATE project_topics SET report_status = 'approved', report_feedback = NULL, source_code_status = 'approved', source_code_feedback = NULL WHERE id = ?");
            $stmt->execute([$topic_id]);
            $message = "Both project report and source code validated and approved successfully.";
            $status = "success";
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $status = "error";
    }
}

// Fetch students assigned to this supervisor and their approved topics (including PDF and Source Code info)
$query = "
    SELECT 
        s.id as student_id,
        s.name as student_name, 
        s.reg_no, 
        pt.id as topic_id,
        pt.topic, 
        pt.pdf_path, 
        pt.report_status, 
        pt.report_feedback,
        pt.source_code_path,
        pt.source_code_status,
        pt.source_code_feedback
    FROM students s
    JOIN supervision sv ON s.id = sv.student_id
    JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
    WHERE sv.supervisor_id = ? AND sv.status = 'active'
    ORDER BY 
        (pt.report_status = 'pending' OR pt.source_code_status = 'pending') DESC,
        s.name ASC
";
$stmt = $conn->prepare($query);
$stmt->execute([$supervisor_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$stats = [
    'total' => count($submissions),
    'pending_reports' => 0,
    'pending_code' => 0,
    'fully_approved' => 0
];

foreach ($submissions as $sub) {
    if (!empty($sub['pdf_path']) && $sub['report_status'] === 'pending') {
        $stats['pending_reports']++;
    }
    if (!empty($sub['source_code_path']) && $sub['source_code_status'] === 'pending') {
        $stats['pending_code']++;
    }
    if ($sub['report_status'] === 'approved' && $sub['source_code_status'] === 'approved') {
        $stats['fully_approved']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Project Submissions & Source Code | Supervisor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --primary-dark: #3730a3;
            --primary-light: #6366f1;
            --primary-soft: #eef2ff;
            --secondary: #7c3aed;
            --success: #059669;
            --success-soft: #ecfdf5;
            --danger: #dc2626;
            --danger-soft: #fef2f2;
            --warning: #d97706;
            --warning-soft: #fffbeb;
            --info: #0284c7;
            --info-soft: #f0f9ff;
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius-md: 14px;
            --radius-lg: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; padding: 0; min-height: 100vh; }
        .container { max-width: 1350px; margin: 30px auto 60px auto; padding: 0 24px; }

        .header {
            background: white;
            padding: 30px;
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 12px -2px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header p {
            color: var(--text-muted);
            margin-top: 4px;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px 24px;
            border-radius: var(--radius-md);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .icon-indigo { background: var(--primary-soft); color: var(--primary); }
        .icon-amber { background: var(--warning-soft); color: var(--warning); }
        .icon-purple { background: #f3e8ff; color: var(--secondary); }
        .icon-green { background: var(--success-soft); color: var(--success); }

        .stat-meta h3 { font-size: 22px; font-weight: 800; color: var(--text-main); line-height: 1; }
        .stat-meta p { font-size: 13px; color: var(--text-muted); margin-top: 4px; font-weight: 500; }

        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 14px;
        }
        .alert-success { background: var(--success-soft); color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: var(--danger-soft); color: #991b1b; border: 1px solid #fecaca; }

        .card {
            background: white;
            padding: 28px;
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 12px -2px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-top: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            white-space: nowrap;
        }
        th {
            text-align: left;
            padding: 14px 16px;
            font-size: 12px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f1f5f9;
            border-bottom: 2px solid var(--border-color);
        }
        td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        tbody tr:hover {
            background: #f8fafc;
        }

        .student-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .student-name { font-weight: 700; color: var(--text-main); font-size: 15px; }
        .student-reg { font-size: 12px; color: var(--primary); font-weight: 600; }

        .topic-cell {
            max-width: 260px;
            font-size: 13px;
            line-height: 1.4;
            color: #334155;
            white-space: normal;
        }

        /* Submission Box in Table */
        .sub-block {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 160px;
        }
        .file-link {
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            width: fit-content;
            transition: all 0.2s;
        }
        .file-link-pdf { color: #dc2626; }
        .file-link-pdf:hover { background: #fee2e2; }
        .file-link-zip { color: #7c3aed; }
        .file-link-zip:hover { background: #f3e8ff; }

        .status-badge {
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            width: fit-content;
        }
        .status-pending { background: #fff7ed; color: #9a3412; }
        .status-approved { background: #ecfdf5; color: #065f46; }
        .status-rejected { background: #fef2f2; color: #991b1b; }
        .status-not_submitted { background: #f1f5f9; color: #64748b; }

        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #047857; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); }
        .btn-outline:hover { background: #f8fafc; border-color: var(--primary); color: var(--primary); }
        .btn-purple { background: var(--secondary); color: white; }
        .btn-purple:hover { background: #6d28d9; }

        .action-cell {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        .action-row {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .modal-header h2 {
            font-size: 18px;
            margin: 0;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 13px;
            color: var(--text-main);
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-family: inherit;
            resize: vertical;
            min-height: 110px;
            box-sizing: border-box;
            font-size: 14px;
            outline: none;
        }
        textarea:focus {
            border-color: var(--primary);
        }

        .search-bar {
            position: relative;
            max-width: 380px;
            margin-bottom: 15px;
        }
        .search-bar i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        .search-bar input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            font-family: inherit;
            font-size: 14px;
            background: #f8fafc;
            outline: none;
        }
        .search-bar input:focus {
            border-color: var(--primary);
            background: #fff;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-file-circle-check"></i> Manage Project Submissions</h1>
                <p>Review and validate both final PDF project reports and uploaded source code ZIP archives for your assigned students.</p>
            </div>
            <a href="index.php" class="btn btn-outline"><i class="fas fa-house"></i> Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $status ?>">
                <i class="fas fa-<?= $status === 'success' ? 'circle-check' : 'triangle-exclamation' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Summary KPIs -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-indigo"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-meta">
                    <h3><?= $stats['total'] ?></h3>
                    <p>Assigned Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-amber"><i class="fas fa-file-pdf"></i></div>
                <div class="stat-meta">
                    <h3><?= $stats['pending_reports'] ?></h3>
                    <p>Pending Reports</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-file-zipper"></i></div>
                <div class="stat-meta">
                    <h3><?= $stats['pending_code'] ?></h3>
                    <p>Pending Source Code</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-check-double"></i></div>
                <div class="stat-meta">
                    <h3><?= $stats['fully_approved'] ?></h3>
                    <p>Fully Validated</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="filterInput" placeholder="Filter by student name, reg no, or topic...">
            </div>

            <?php if (count($submissions) > 0): ?>
                <div class="table-responsive">
                    <table id="subTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Project Topic</th>
                                <th>Report (PDF)</th>
                                <th>Source Code (ZIP)</th>
                                <th style="text-align: right;">Validation Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): 
                                $report_status = !empty($sub['pdf_path']) ? $sub['report_status'] : 'not_submitted';
                                $code_status = !empty($sub['source_code_path']) ? $sub['source_code_status'] : 'not_submitted';
                            ?>
                                <tr class="sub-row" data-search="<?= strtolower(htmlspecialchars($sub['student_name'] . ' ' . $sub['reg_no'] . ' ' . $sub['topic'])) ?>">
                                    <td>
                                        <div class="student-cell">
                                            <span class="student-name"><?= htmlspecialchars($sub['student_name']) ?></span>
                                            <span class="student-reg"><?= htmlspecialchars($sub['reg_no']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="topic-cell"><?= htmlspecialchars($sub['topic']) ?></div>
                                    </td>

                                    <!-- 1. Report Status & Link -->
                                    <td>
                                        <div class="sub-block">
                                            <?php if (!empty($sub['pdf_path'])): ?>
                                                <a href="<?= PROJECT_ROOT . $sub['pdf_path'] ?>" target="_blank" class="file-link file-link-pdf">
                                                    <i class="fas fa-file-pdf"></i> View PDF
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 13px; font-style: italic;">Not uploaded</span>
                                            <?php endif; ?>
                                            
                                            <span class="status-badge status-<?= $report_status ?>">
                                                <i class="fas <?= $report_status === 'approved' ? 'fa-check' : ($report_status === 'rejected' ? 'fa-times' : ($report_status === 'pending' ? 'fa-clock' : 'fa-minus')) ?>"></i>
                                                Report: <?= str_replace('_', ' ', ucfirst($report_status)) ?>
                                            </span>

                                            <?php if ($report_status === 'rejected' && !empty($sub['report_feedback'])): ?>
                                                <small style="color: #991b1b; font-size: 11px;" title="<?= htmlspecialchars($sub['report_feedback']) ?>">
                                                    <i class="fas fa-comment"></i> <?= htmlspecialchars(mb_strimwidth($sub['report_feedback'], 0, 35, '...')) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- 2. Source Code Status & Link -->
                                    <td>
                                        <div class="sub-block">
                                            <?php if (!empty($sub['source_code_path'])): ?>
                                                <a href="<?= PROJECT_ROOT . $sub['source_code_path'] ?>" download class="file-link file-link-zip">
                                                    <i class="fas fa-file-zipper"></i> Download ZIP
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 13px; font-style: italic;">Not uploaded</span>
                                            <?php endif; ?>

                                            <span class="status-badge status-<?= $code_status ?>">
                                                <i class="fas <?= $code_status === 'approved' ? 'fa-check' : ($code_status === 'rejected' ? 'fa-times' : ($code_status === 'pending' ? 'fa-clock' : 'fa-minus')) ?>"></i>
                                                Code: <?= str_replace('_', ' ', ucfirst($code_status)) ?>
                                            </span>

                                            <?php if ($code_status === 'rejected' && !empty($sub['source_code_feedback'])): ?>
                                                <small style="color: #991b1b; font-size: 11px;" title="<?= htmlspecialchars($sub['source_code_feedback']) ?>">
                                                    <i class="fas fa-comment"></i> <?= htmlspecialchars(mb_strimwidth($sub['source_code_feedback'], 0, 35, '...')) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Validation Action Controls -->
                                    <td style="text-align: right;">
                                        <div class="action-cell">
                                            <!-- Report Action Controls -->
                                            <?php if (!empty($sub['pdf_path']) && $sub['report_status'] === 'pending'): ?>
                                                <div class="action-row">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="topic_id" value="<?= $sub['topic_id'] ?>">
                                                        <input type="hidden" name="action" value="approve_report">
                                                        <button type="submit" class="btn btn-success" title="Approve PDF Report">
                                                            <i class="fas fa-check"></i> Approve Report
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-danger" onclick="openRejectModal(<?= $sub['topic_id'] ?>, 'report', '<?= htmlspecialchars(addslashes($sub['student_name'])) ?>')" title="Reject Report with Feedback">
                                                        <i class="fas fa-times"></i> Reject Report
                                                    </button>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Source Code Action Controls -->
                                            <?php if (!empty($sub['source_code_path']) && $sub['source_code_status'] === 'pending'): ?>
                                                <div class="action-row">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="topic_id" value="<?= $sub['topic_id'] ?>">
                                                        <input type="hidden" name="action" value="approve_code">
                                                        <button type="submit" class="btn btn-purple" title="Validate and Approve Source Code">
                                                            <i class="fas fa-shield-halved"></i> Validate Code
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-danger" onclick="openRejectModal(<?= $sub['topic_id'] ?>, 'code', '<?= htmlspecialchars(addslashes($sub['student_name'])) ?>')" title="Reject Source Code with Feedback">
                                                        <i class="fas fa-times"></i> Reject Code
                                                    </button>
                                                </div>
                                            <?php endif; ?>

                                            <!-- If Both Pending: Quick Approve Both Button -->
                                            <?php if (!empty($sub['pdf_path']) && $sub['report_status'] === 'pending' && !empty($sub['source_code_path']) && $sub['source_code_status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline; margin-top: 2px;">
                                                    <input type="hidden" name="topic_id" value="<?= $sub['topic_id'] ?>">
                                                    <input type="hidden" name="action" value="approve_both">
                                                    <button type="submit" class="btn btn-success" style="background: #1e3a8a; border: 1px solid #1e3a8a;" onclick="return confirm('Validate and approve BOTH the Report and Source Code?')">
                                                        <i class="fas fa-check-double"></i> Validate & Approve Both
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- If Both Approved -->
                                            <?php if ($sub['report_status'] === 'approved' && $sub['source_code_status'] === 'approved'): ?>
                                                <span style="color: var(--success); font-weight: 700; font-size: 13px;">
                                                    <i class="fas fa-circle-check"></i> Submissions Fully Validated
                                                </span>
                                            <?php elseif ($sub['report_status'] === 'approved' && empty($sub['source_code_path'])): ?>
                                                <span style="color: var(--info); font-weight: 600; font-size: 12px;">
                                                    Report Approved (Awaiting Code)
                                                </span>
                                            <?php elseif (empty($sub['pdf_path']) && empty($sub['source_code_path'])): ?>
                                                <span style="color: var(--text-muted); font-size: 12px; font-style: italic;">
                                                    Awaiting Student Uploads
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-users-slash" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No active students assigned to you found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reject Feedback Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-triangle-exclamation"></i> Reject Submission</h2>
                <i class="fas fa-times" style="cursor: pointer; color: var(--text-muted);" onclick="closeRejectModal()"></i>
            </div>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
                Rejecting <strong id="submissionTypeLabel"></strong> for: <strong id="studentNameLabel" style="color: var(--text-main);"></strong>
            </p>
            <form method="POST">
                <input type="hidden" name="topic_id" id="modalTopicId">
                <input type="hidden" name="action" id="modalAction">
                <div class="form-group">
                    <label>Reason for Rejection / Feedback for Student</label>
                    <textarea name="feedback" id="feedbackText" placeholder="Detail what corrections or fixes the student must make before re-uploading..." required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(topicId, type, studentName) {
            document.getElementById('modalTopicId').value = topicId;
            document.getElementById('studentNameLabel').innerText = studentName;
            
            if (type === 'report') {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-pdf"></i> Reject Project Report';
                document.getElementById('submissionTypeLabel').innerText = 'Project Report (PDF)';
                document.getElementById('modalAction').value = 'reject_report';
                document.getElementById('feedbackText').placeholder = 'Explain why the PDF report is being rejected and what the student needs to revise...';
            } else {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-zipper"></i> Reject Source Code';
                document.getElementById('submissionTypeLabel').innerText = 'Source Code (ZIP)';
                document.getElementById('modalAction').value = 'reject_code';
                document.getElementById('feedbackText').placeholder = 'Explain why the source code ZIP is incomplete, defective, or rejected...';
            }

            document.getElementById('rejectModal').style.display = 'flex';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('rejectModal')) {
                closeRejectModal();
            }
        }

        // Live table search filter
        const filterInput = document.getElementById('filterInput');
        if (filterInput) {
            filterInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('.sub-row');
                rows.forEach(row => {
                    const searchData = row.getAttribute('data-search') || '';
                    if (query === '' || searchData.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    </script>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
