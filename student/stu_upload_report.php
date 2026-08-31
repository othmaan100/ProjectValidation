<?php
session_start();

// Redirect if the user is not logged in or is not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'stu') {
    include_once __DIR__ . '/../includes/auth.php';
    header("Location: " . PROJECT_ROOT);
    exit();
}

include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';

$student_id = $_SESSION['user_id'];
$message = '';
$status = '';

// Fetch the approved topic and supervisor info for this student
$stmt = $conn->prepare("
    SELECT pt.*, su.name AS supervisor_name, su.phone AS supervisor_phone, su.email AS supervisor_email, s.department
    FROM project_topics pt
    LEFT JOIN supervision sp ON pt.student_id = sp.student_id AND sp.status = 'active'
    LEFT JOIN supervisors su ON sp.supervisor_id = su.id
    JOIN students s ON pt.student_id = s.id
    WHERE pt.student_id = ? AND pt.status = 'approved' 
    LIMIT 1
");
$stmt->execute([$student_id]);
$approved_topic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$approved_topic) {
    header("Location: stu_dashboard.php");
    exit();
}

// Check report submission schedule
$dept_id = $approved_topic['department'];
$can_submit = false;
$deadline_info = "No report submission schedule set.";

// 1. Check for individual student override first
$stmt = $conn->prepare("SELECT * FROM student_report_overrides WHERE student_id = ? AND is_active = 1");
$stmt->execute([$student_id]);
$override = $stmt->fetch();

$now = time();

if ($override) {
    $start_time = strtotime($override['submission_start']);
    $end_time = strtotime($override['submission_end']);
    
    if ($now >= $start_time && $now <= $end_time) {
        $can_submit = true;
    }
    $deadline_info = "Individual Extension: " . date('M d, Y H:i', $start_time) . " to " . date('M d, Y H:i', $end_time);
} else {
    // 2. Fallback to departmental schedule
    $stmt = $conn->prepare("SELECT * FROM report_schedules WHERE department_id = ? AND is_active = 1");
    $stmt->execute([$dept_id]);
    $schedule = $stmt->fetch();

    if ($schedule) {
        $start_time = strtotime($schedule['submission_start']);
        $end_time = strtotime($schedule['submission_end']);
        
        if ($now >= $start_time && $now <= $end_time) {
            $can_submit = true;
        }
        $deadline_info = "Dept Window: " . date('M d, Y H:i', $start_time) . " to " . date('M d, Y H:i', $end_time);
    }
}

// Handle file uploads (Report PDF and/or Source Code ZIP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['report_file']) || isset($_FILES['source_code_file']))) {
    if (!$can_submit) {
        $message = "Report submission window is currently closed.";
        $status = "error";
    } elseif (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = "Session expired or invalid request.";
        $status = "error";
    } else {
        $uploaded_items = [];
        $errors = [];

        // 1. Process Report PDF
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
            $report_ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
            if ($report_ext !== 'pdf') {
                $errors[] = "Project Report must be a PDF document.";
            } elseif ($_FILES['report_file']['size'] > 25 * 1024 * 1024) {
                $errors[] = "Project Report size exceeds 25MB limit.";
            } else {
                $report_dir = __DIR__ . '/../assets/uploads/student_reports/';
                if (!is_dir($report_dir)) {
                    mkdir($report_dir, 0777, true);
                }

                $new_report_name = 'report_' . $approved_topic['id'] . '_' . time() . '.pdf';
                $report_target_path = $report_dir . $new_report_name;
                $report_db_path = 'assets/uploads/student_reports/' . $new_report_name;

                if (move_uploaded_file($_FILES['report_file']['tmp_name'], $report_target_path)) {
                    $update_stmt = $conn->prepare("UPDATE project_topics SET pdf_path = ?, report_status = 'pending', report_feedback = NULL WHERE id = ?");
                    $update_stmt->execute([$report_db_path, $approved_topic['id']]);
                    $approved_topic['pdf_path'] = $report_db_path;
                    $approved_topic['report_status'] = 'pending';
                    $uploaded_items[] = "Project Report (PDF)";
                } else {
                    $errors[] = "Failed to save the uploaded report file.";
                }
            }
        }

        // 2. Process Source Code ZIP
        if (isset($_FILES['source_code_file']) && $_FILES['source_code_file']['error'] === UPLOAD_ERR_OK) {
            $code_ext = strtolower(pathinfo($_FILES['source_code_file']['name'], PATHINFO_EXTENSION));
            if ($code_ext !== 'zip') {
                $errors[] = "Source Code file must be a ZIP archive (.zip).";
            } elseif ($_FILES['source_code_file']['size'] > 50 * 1024 * 1024) {
                $errors[] = "Source Code ZIP file size exceeds 50MB limit.";
            } else {
                $code_dir = __DIR__ . '/../assets/uploads/student_source_code/';
                if (!is_dir($code_dir)) {
                    mkdir($code_dir, 0777, true);
                }

                $new_code_name = 'source_code_' . $approved_topic['id'] . '_' . time() . '.zip';
                $code_target_path = $code_dir . $new_code_name;
                $code_db_path = 'assets/uploads/student_source_code/' . $new_code_name;

                if (move_uploaded_file($_FILES['source_code_file']['tmp_name'], $code_target_path)) {
                    $update_stmt = $conn->prepare("UPDATE project_topics SET source_code_path = ?, source_code_status = 'pending', source_code_feedback = NULL WHERE id = ?");
                    $update_stmt->execute([$code_db_path, $approved_topic['id']]);
                    $approved_topic['source_code_path'] = $code_db_path;
                    $approved_topic['source_code_status'] = 'pending';
                    $uploaded_items[] = "Source Code (ZIP)";
                } else {
                    $errors[] = "Failed to save the uploaded source code ZIP file.";
                }
            }
        }

        if (!empty($errors)) {
            $message = implode("<br>", $errors);
            $status = "error";
        } elseif (!empty($uploaded_items)) {
            $message = "Successfully uploaded: " . implode(" and ", $uploaded_items) . ". Your supervisor has been notified for validation.";
            $status = "success";
        } else {
            $message = "Please select at least one file to upload (PDF Report or Source Code ZIP).";
            $status = "error";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Final Report & Source Code | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
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
            --radius-md: 16px;
            --radius-lg: 24px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
        }

        .page-container {
            max-width: 850px;
            margin: 30px auto 60px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 36px;
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
        }

        .header-title {
            text-align: center;
            margin-bottom: 25px;
        }
        .header-title h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .header-title p {
            font-size: 14px;
            color: var(--text-muted);
        }

        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            line-height: 1.5;
        }
        .alert-success { background: var(--success-soft); color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: var(--danger-soft); color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: var(--info-soft); color: #075985; border: 1px solid #bae6fd; }

        .schedule-badge {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid transparent;
        }
        .schedule-open { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .schedule-closed { background: #fef2f2; color: #991b1b; border-color: #fecaca; }

        /* Project Info Card */
        .project-info-box {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 22px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 650px) {
            .project-info-box { grid-template-columns: 1fr; }
        }
        .info-col h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 700;
        }
        .info-col p {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
            line-height: 1.4;
        }

        /* Status & Submissions Section */
        .submissions-status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 30px;
        }
        @media (max-width: 650px) {
            .submissions-status-grid { grid-template-columns: 1fr; }
        }
        .submission-status-card {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 18px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .status-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .status-header h4 {
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge-pending { background: #fff7ed; color: #9a3412; }
        .badge-approved { background: #ecfdf5; color: #065f46; }
        .badge-rejected { background: #fef2f2; color: #991b1b; }
        .badge-none { background: #f1f5f9; color: #64748b; }

        .file-preview-link {
            font-size: 13px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .file-preview-link:hover {
            text-decoration: underline;
        }
        .feedback-box {
            background: #fff1f2;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: #9f1239;
            line-height: 1.4;
        }

        /* Upload Area Boxes */
        .upload-section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .upload-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 25px;
        }
        @media (max-width: 650px) {
            .upload-grid { grid-template-columns: 1fr; }
        }

        .upload-box {
            border: 2px dashed #cbd5e1;
            border-radius: var(--radius-md);
            padding: 28px 18px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
        }
        .upload-box:hover {
            border-color: var(--primary);
            background: var(--primary-soft);
        }
        .upload-box.active-file {
            border-color: var(--success);
            background: var(--success-soft);
        }
        .upload-box i.main-icon {
            font-size: 40px;
            margin-bottom: 12px;
            display: block;
        }
        .upload-box h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .upload-box p {
            font-size: 12px;
            color: var(--text-muted);
        }
        .file-selected-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--success);
            margin-top: 8px;
            word-break: break-all;
        }
        .file-input-hidden {
            display: none;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            background: var(--primary);
            color: white;
            font-family: inherit;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25);
        }
        .btn-submit:hover:not(:disabled) {
            background: #3730a3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35);
        }
        .btn-submit:disabled {
            background: #cbd5e1;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .return-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: color 0.2s;
        }
        .return-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="page-container">
        <div class="card">
            <div class="header-title">
                <h1><i class="fas fa-cloud-arrow-up" style="color: var(--primary);"></i> Project Final Submissions</h1>
                <p>Upload your Final Project Report (PDF) and Complete Source Code (ZIP) for supervisor review and validation.</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $status ?>">
                    <i class="fas fa-<?= $status === 'success' ? 'circle-check' : 'triangle-exclamation' ?>" style="font-size: 18px;"></i>
                    <div><?= $message ?></div>
                </div>
            <?php endif; ?>

            <!-- Submission Window Schedule Info -->
            <div class="schedule-badge <?= $can_submit ? 'schedule-open' : 'schedule-closed' ?>">
                <i class="fas <?= $can_submit ? 'fa-calendar-check' : 'fa-lock' ?>" style="font-size: 20px;"></i>
                <div>
                    <strong>Submission Window Status: <?= $can_submit ? 'OPEN' : 'CLOSED' ?></strong>
                    <div style="font-size: 12px; margin-top: 2px; opacity: 0.9;"><?= $deadline_info ?></div>
                </div>
            </div>

            <!-- Project Details -->
            <div class="project-info-box">
                <div class="info-col">
                    <h3><i class="fas fa-book-bookmark"></i> Approved Project Topic</h3>
                    <p><?= htmlspecialchars($approved_topic['topic']) ?></p>
                </div>
                <div class="info-col">
                    <h3><i class="fas fa-user-tie"></i> Assigned Supervisor</h3>
                    <p><?= htmlspecialchars($approved_topic['supervisor_name'] ?: 'Awaiting Allocation') ?></p>
                    <?php if (!empty($approved_topic['supervisor_email'])): ?>
                        <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($approved_topic['supervisor_email']) ?>
                        </small>
                    <?php endif; ?>
                    <?php if (!empty($approved_topic['supervisor_phone'])): ?>
                        <small style="color: var(--text-muted); display: block; margin-top: 2px;">
                            <i class="fas fa-phone"></i> <?= htmlspecialchars($approved_topic['supervisor_phone']) ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Current Submission Status Cards -->
            <div class="submissions-status-grid">
                <!-- 1. Report Status -->
                <div class="submission-status-card">
                    <div class="status-header">
                        <h4><i class="fas fa-file-pdf" style="color: #dc2626;"></i> Project Report</h4>
                        <?php 
                            $rs = $approved_topic['report_status'] ?? 'not_submitted';
                            if (empty($approved_topic['pdf_path'])) $rs = 'not_submitted';
                            $rs_class = 'badge-' . $rs;
                            $rs_icon = $rs === 'approved' ? 'fa-check-circle' : ($rs === 'rejected' ? 'fa-times-circle' : ($rs === 'pending' ? 'fa-clock' : 'fa-minus'));
                        ?>
                        <span class="status-badge <?= $rs_class ?>">
                            <i class="fas <?= $rs_icon ?>"></i> <?= str_replace('_', ' ', ucfirst($rs)) ?>
                        </span>
                    </div>
                    <?php if (!empty($approved_topic['pdf_path'])): ?>
                        <div>
                            <a href="<?= PROJECT_ROOT . $approved_topic['pdf_path'] ?>" target="_blank" class="file-preview-link">
                                <i class="fas fa-arrow-up-right-from-square"></i> View Submitted PDF
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="font-size: 13px; color: var(--text-muted); font-style: italic;">No PDF report uploaded yet.</div>
                    <?php endif; ?>

                    <?php if ($rs === 'rejected' && !empty($approved_topic['report_feedback'])): ?>
                        <div class="feedback-box">
                            <strong>Supervisor Feedback:</strong> <?= htmlspecialchars($approved_topic['report_feedback']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. Source Code Status -->
                <div class="submission-status-card">
                    <div class="status-header">
                        <h4><i class="fas fa-file-zipper" style="color: #7c3aed;"></i> Source Code (ZIP)</h4>
                        <?php 
                            $cs = $approved_topic['source_code_status'] ?? 'not_submitted';
                            if (empty($approved_topic['source_code_path'])) $cs = 'not_submitted';
                            $cs_class = 'badge-' . $cs;
                            $cs_icon = $cs === 'approved' ? 'fa-check-circle' : ($cs === 'rejected' ? 'fa-times-circle' : ($cs === 'pending' ? 'fa-clock' : 'fa-minus'));
                        ?>
                        <span class="status-badge <?= $cs_class ?>">
                            <i class="fas <?= $cs_icon ?>"></i> <?= str_replace('_', ' ', ucfirst($cs)) ?>
                        </span>
                    </div>
                    <?php if (!empty($approved_topic['source_code_path'])): ?>
                        <div>
                            <a href="<?= PROJECT_ROOT . $approved_topic['source_code_path'] ?>" download class="file-preview-link">
                                <i class="fas fa-download"></i> Download Submitted ZIP
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="font-size: 13px; color: var(--text-muted); font-style: italic;">No source code ZIP uploaded yet.</div>
                    <?php endif; ?>

                    <?php if ($cs === 'rejected' && !empty($approved_topic['source_code_feedback'])): ?>
                        <div class="feedback-box">
                            <strong>Supervisor Feedback:</strong> <?= htmlspecialchars($approved_topic['source_code_feedback']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upload Form -->
            <?php 
                $report_locked = ($approved_topic['report_status'] === 'approved');
                $code_locked = ($approved_topic['source_code_status'] === 'approved');
                $both_locked = ($report_locked && $code_locked);
            ?>

            <?php if ($both_locked): ?>
                <div class="alert alert-success" style="margin-top: 10px;">
                    <i class="fas fa-lock"></i>
                    <div>Both your project report and source code have been approved and validated by your supervisor. Submissions are finalized.</div>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" id="uploadForm" style="<?= !$can_submit ? 'opacity: 0.6;' : '' ?>">
                    <?= csrf_field(); ?>
                    
                    <div class="upload-section-title">
                        <i class="fas fa-arrow-up-from-bracket" style="color: var(--primary);"></i>
                        <span>Upload or Replace Files</span>
                    </div>

                    <div class="upload-grid">
                        <!-- PDF Upload Box -->
                        <div class="upload-box" id="pdfUploadBox" 
                             <?= ($can_submit && !$report_locked) ? 'onclick="document.getElementById(\'report_file\').click()"' : '' ?>
                             style="<?= (!$can_submit || $report_locked) ? 'cursor: not-allowed; opacity: 0.6;' : '' ?>">
                            <i class="fas fa-file-pdf main-icon" style="color: #dc2626;"></i>
                            <h4>Project Report (.PDF)</h4>
                            <p><?= $report_locked ? 'Report already approved (Locked)' : ($can_submit ? 'Click to select PDF file (Max 25MB)' : 'Submissions Closed') ?></p>
                            <div class="file-selected-name" id="pdfFileName"></div>
                            <input type="file" name="report_file" id="report_file" class="file-input-hidden" accept=".pdf" onchange="handlePdfSelect(this)" <?= (!$can_submit || $report_locked) ? 'disabled' : '' ?>>
                        </div>

                        <!-- Source Code ZIP Upload Box -->
                        <div class="upload-box" id="zipUploadBox" 
                             <?= ($can_submit && !$code_locked) ? 'onclick="document.getElementById(\'source_code_file\').click()"' : '' ?>
                             style="<?= (!$can_submit || $code_locked) ? 'cursor: not-allowed; opacity: 0.6;' : '' ?>">
                            <i class="fas fa-file-zipper main-icon" style="color: #7c3aed;"></i>
                            <h4>Source Code (.ZIP)</h4>
                            <p><?= $code_locked ? 'Code already approved (Locked)' : ($can_submit ? 'Click to select ZIP archive (Max 50MB)' : 'Submissions Closed') ?></p>
                            <div class="file-selected-name" id="zipFileName"></div>
                            <input type="file" name="source_code_file" id="source_code_file" class="file-input-hidden" accept=".zip,application/zip,application/x-zip-compressed" onchange="handleZipSelect(this)" <?= (!$can_submit || $code_locked) ? 'disabled' : '' ?>>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        <i class="fas <?= $can_submit ? 'fa-paper-plane' : 'fa-lock' ?>"></i>
                        <span><?= $can_submit ? 'Upload Selected File(s)' : 'Submissions Closed' ?></span>
                    </button>
                </form>
            <?php endif; ?>

            <a href="stu_dashboard.php" class="return-link">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
        </div>
    </div>

    <script>
        let hasPdf = false;
        let hasZip = false;

        function checkSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = !(hasPdf || hasZip);
            }
        }

        function handlePdfSelect(input) {
            const display = document.getElementById('pdfFileName');
            const box = document.getElementById('pdfUploadBox');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                const ext = file.name.split('.').pop().toLowerCase();
                
                if (ext !== 'pdf') {
                    alert('Invalid file format. Please select a PDF document.');
                    input.value = '';
                    display.innerText = '';
                    box.classList.remove('active-file');
                    hasPdf = false;
                } else if (file.size > 25 * 1024 * 1024) {
                    alert('File size exceeds 25MB limit.');
                    input.value = '';
                    display.innerText = '';
                    box.classList.remove('active-file');
                    hasPdf = false;
                } else {
                    display.innerText = 'Selected: ' + file.name + ' (' + (file.size / (1024*1024)).toFixed(2) + ' MB)';
                    box.classList.add('active-file');
                    hasPdf = true;
                }
            } else {
                display.innerText = '';
                box.classList.remove('active-file');
                hasPdf = false;
            }
            checkSubmitButton();
        }

        function handleZipSelect(input) {
            const display = document.getElementById('zipFileName');
            const box = document.getElementById('zipUploadBox');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                const ext = file.name.split('.').pop().toLowerCase();

                if (ext !== 'zip') {
                    alert('Invalid file format. Please select a ZIP archive (.zip).');
                    input.value = '';
                    display.innerText = '';
                    box.classList.remove('active-file');
                    hasZip = false;
                } else if (file.size > 50 * 1024 * 1024) {
                    alert('ZIP file size exceeds 50MB limit.');
                    input.value = '';
                    display.innerText = '';
                    box.classList.remove('active-file');
                    hasZip = false;
                } else {
                    display.innerText = 'Selected: ' + file.name + ' (' + (file.size / (1024*1024)).toFixed(2) + ' MB)';
                    box.classList.add('active-file');
                    hasZip = true;
                }
            } else {
                display.innerText = '';
                box.classList.remove('active-file');
                hasZip = false;
            }
            checkSubmitButton();
        }
    </script>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
