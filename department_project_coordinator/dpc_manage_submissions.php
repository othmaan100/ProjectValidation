<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dpc_id = $_SESSION['user_id'];
$message = '';
$status = '';

// Fetch the DPC's department info
$stmt = $conn->prepare("SELECT department FROM users WHERE id = ?");
$stmt->execute([$dpc_id]);
$dept_id = $stmt->fetchColumn();

// Handle Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $topic_id = $_POST['topic_id'];
    $action = $_POST['action'];
    $feedback = trim($_POST['feedback'] ?? '');

    try {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE project_topics SET report_status = 'approved', report_feedback = NULL WHERE id = ?");
            $stmt->execute([$topic_id]);
            $message = "Project report approved successfully.";
            $status = "success";
        } elseif ($action === 'reject') {
            if (empty($feedback)) {
                throw new Exception("Please provide feedback for rejection.");
            }
            $stmt = $conn->prepare("UPDATE project_topics SET report_status = 'rejected', report_feedback = ? WHERE id = ?");
            $stmt->execute([$feedback, $topic_id]);
            $message = "Project report rejected. Student will be notified to re-upload.";
            $status = "success";
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $status = "error";
    }
}

// Fetch all students in this department and their approved topics (including PDF submission info)
$query = "
    SELECT 
        s.name as student_name, 
        s.reg_no, 
        pt.id as topic_id,
        pt.topic, 
        pt.pdf_path, 
        pt.report_status, 
        pt.report_feedback,
        sup.name as supervisor_name
    FROM students s
    JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
    LEFT JOIN supervision sv ON s.id = sv.student_id AND sv.status = 'active'
    LEFT JOIN supervisors sup ON sv.supervisor_id = sup.id
    WHERE s.department = ?
    ORDER BY FIELD(pt.report_status, 'pending', 'rejected', 'approved'), s.name ASC
";
$stmt = $conn->prepare($query);
$stmt->execute([$dept_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Project Submissions | DPC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --primary-light: #6366f1;
            --success: #059669;
            --danger: #dc2626;
            --warning: #f59e0b;
            --bg-body: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }

        .header { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; font-weight: 700; color: var(--primary); margin: 0; }

        .alert { padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; font-weight: 600; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #10b981; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #ef4444; }

        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }

        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; }
        .status-pending { background: #fff7ed; color: #9a3412; }
        .status-approved { background: #ecfdf5; color: #065f46; }
        .status-rejected { background: #fef2f2; color: #991b1b; }
        .status-not_submitted { background: #f3f4f6; color: #4b5563; }

        .btn { padding: 8px 16px; border: none; border-radius: 10px; font-family: inherit; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; justify-content: center; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: var(--primary); color: white; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; }
        .modal-content { background: white; padding: 30px; border-radius: 20px; width: 100%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 20px; margin: 0; color: var(--primary); }
        
        textarea { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; resize: vertical; min-height: 100px; box-sizing: border-box; margin-top: 8px; }
        textarea:focus { border-color: var(--primary-light); outline: none; }

        .file-link { color: var(--primary); text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
        .file-link:hover { text-decoration: underline; }

        @media (max-width: 900px) {
            .header { flex-direction: column; text-align: center; gap: 20px; }
            .header div:last-child { width: 100%; }
            .btn { width: 100%; }
        }

        @media (max-width: 600px) {
            .container { margin: 10px auto; padding: 0 15px; }
            .card { padding: 15px; }
            table thead { display: none; }
            table, table tbody, table tr, table td { display: block; width: 100%; }
            table tr { margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 15px; padding: 15px; }
            table td { border: none; padding: 8px 0; text-align: left !important; position: relative; }
            table td:before { content: attr(data-label); font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; }
            .btn-group { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1>Project Submissions</h1>
                <p style="color: var(--text-muted);">Departmental review of student final project reports.</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $status ?>">
                <i class="fas fa-<?= $status === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <?php if (count($submissions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Topic & Supervisor</th>
                            <th>Submission</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td data-label="Student">
                                    <strong><?= htmlspecialchars($sub['student_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars($sub['reg_no']) ?></small>
                                </td>
                                <td data-label="Topic & Supervisor">
                                    <div style="font-size: 13px; line-height: 1.4;"><?= htmlspecialchars($sub['topic']) ?></div>
                                    <div style="margin-top: 5px; font-size: 12px; color: var(--primary);">
                                        <i class="fas fa-user-tie"></i> <?= htmlspecialchars($sub['supervisor_name'] ?: 'No Supervisor Assigned') ?>
                                    </div>
                                </td>
                                <td data-label="Submission">
                                    <?php if ($sub['pdf_path']): ?>
                                        <a href="<?= PROJECT_ROOT . $sub['pdf_path'] ?>" target="_blank" class="file-link">
                                            <i class="fas fa-file-pdf"></i> View PDF
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-style: italic;">Not uploaded</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <?php 
                                        $rs = $sub['report_status'];
                                        if (!$sub['pdf_path']) $rs = 'not_submitted';
                                        $status_text = str_replace('_', ' ', $rs);
                                    ?>
                                    <span class="status-badge status-<?= $rs ?>">
                                        <i class="fas fa-<?= $rs === 'approved' ? 'check' : ($rs === 'rejected' ? 'times' : ($rs === 'pending' ? 'clock' : 'minus')) ?>"></i>
                                        <?= $status_text ?>
                                    </span>
                                </td>
                                <td data-label="Actions" style="text-align: right;">
                                    <?php if ($sub['pdf_path']): ?>
                                        <div class="btn-group" style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="topic_id" value="<?= $sub['topic_id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success" <?= $sub['report_status'] === 'approved' ? 'disabled style="opacity:0.5; cursor:default;"' : '' ?> onclick="return confirm('Approve this project report?')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <button class="btn btn-danger" <?= $sub['report_status'] === 'rejected' ? 'disabled style="opacity:0.5; cursor:default;"' : '' ?> onclick="openRejectModal(<?= $sub['topic_id'] ?>, '<?= htmlspecialchars($sub['student_name']) ?>')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                        <?php if ($sub['report_status'] === 'rejected'): ?>
                                            <div style="margin-top: 5px; font-size: 11px; color: var(--danger);">
                                                <i class="fas fa-info-circle"></i> Rejected: <?= htmlspecialchars($sub['report_feedback']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-file-invoice" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <p>No project submissions found for your department yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reject Submission</h2>
                <i class="fas fa-times" style="cursor: pointer;" onclick="closeRejectModal()"></i>
            </div>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
                Rejecting report for: <strong id="studentName"></strong>
            </p>
            <form method="POST">
                <input type="hidden" name="topic_id" id="modalTopicId">
                <input type="hidden" name="action" value="reject">
                <div>
                    <label style="font-size: 13px; font-weight: 600; color: var(--text-muted);">Reason for Rejection / Feedback</label>
                    <textarea name="feedback" placeholder="Explain why the report is being rejected..." required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" style="border-color:#e2e8f0; color:#64748b;" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(topicId, studentName) {
            document.getElementById('modalTopicId').value = topicId;
            document.getElementById('studentName').innerText = studentName;
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
    </script>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
