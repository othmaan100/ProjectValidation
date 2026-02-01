<?php
session_start();

// Redirect if the user is not logged in or is not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'stu') {
    include_once __DIR__ . '/../includes/auth.php'; // Ensure PROJECT_ROOT is defined if accessed directly
    header("Location: " . PROJECT_ROOT);
    exit();
}

include_once __DIR__ . '/../includes/db.php';

$student_id = $_SESSION['user_id'];
$message = '';
$status = '';

// Fetch the approved topic for this student
$stmt = $conn->prepare("SELECT * FROM project_topics WHERE student_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$student_id]);
$approved_topic = $stmt->fetch();

if (!$approved_topic) {
    header("Location: stu_dashboard.php");
    exit();
}

// Handle report upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['report_file'])) {
    try {
        if ($_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error during file upload.");
        }

        $file_extension = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            throw new Exception("Only PDF documents are allowed.");
        }

        // Validate file size (e.g., 10MB limit)
        if ($_FILES['report_file']['size'] > 10 * 1024 * 1024) {
            throw new Exception("File size too large. Maximum limit is 10MB.");
        }

        $upload_dir = __DIR__ . '/../assets/uploads/student_reports/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_filename = 'report_' . $approved_topic['id'] . '_' . time() . '.pdf';
        $file_path = 'assets/uploads/student_reports/' . $new_filename;
        $target_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['report_file']['tmp_name'], $target_path)) {
            // Update the project_topics table
            $update_stmt = $conn->prepare("UPDATE project_topics SET pdf_path = ?, report_status = 'pending' WHERE id = ?");
            if ($update_stmt->execute([$file_path, $approved_topic['id']])) {
                $message = "Your project report has been successfully uploaded and is now awaiting supervisor approval.";
                $status = "success";
                // Refresh the approved topic data
                $approved_topic['pdf_path'] = $file_path;
                $approved_topic['report_status'] = 'pending';
            } else {
                throw new Exception("Failed to update record in database.");
            }
        } else {
            throw new Exception("Failed to save the uploaded file.");
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $status = "error";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Final Report | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; }
        .page-container { max-width: 700px; margin: 50px auto; padding: 20px; }
        .card { 
            background: var(--glass); padding: 40px; border-radius: 25px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); backdrop-filter: blur(15px);
        }
        .card h1 { color: #2d3436; font-size: 26px; margin-bottom: 10px; text-align: center; }
        .card p { color: #636e72; font-size: 14px; text-align: center; margin-bottom: 30px; }
        
        .project-info {
            background: #f8faff; border: 1px solid #e1e8f0; border-radius: 15px; padding: 20px; margin-bottom: 30px;
        }
        .project-info h3 { margin: 0 0 10px; font-size: 14px; text-transform: uppercase; color: #636e72; }
        .project-info p { margin: 0; font-size: 16px; font-weight: 600; color: #2d3436; text-align: left; }

        .upload-area {
            border: 2px dashed var(--primary); border-radius: 20px; padding: 40px; text-align: center;
            background: rgba(102, 126, 234, 0.05); cursor: pointer; transition: 0.3s;
        }
        .upload-area:hover { background: rgba(102, 126, 234, 0.1); border-color: var(--secondary); }
        .upload-area i { font-size: 50px; color: var(--primary); margin-bottom: 15px; }
        .upload-area h3 { margin: 0; font-size: 18px; color: #2d3436; }
        .upload-area p { margin: 10px 0 0; font-size: 13px; color: #636e72; }
        
        .file-input { display: none; }
        
        .btn-submit { 
            width: 100%; padding: 16px; border: none; border-radius: 15px; 
            background: var(--primary); color: white; font-weight: 700; cursor: pointer; 
            transition: 0.3s; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 10px;
            margin-top: 30px;
        }
        .btn-submit:hover { background: var(--secondary); transform: translateY(-2px); }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; transform: none; box-shadow: none; }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600; text-align: center; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        .current-file {
            margin-top: 20px; padding: 15px; background: #fff; border-radius: 12px; border: 1px solid #eee;
            display: flex; align-items: center; justify-content: space-between;
        }
        .file-link { color: var(--primary); text-decoration: none; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .file-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div>

    <div class="page-container">
        <div class="card">
            <h1><i class="fas fa-upload" style="color: var(--primary); margin-right: 10px;"></i>Report Submission</h1>
            <p>Upload your final approved project report in PDF format.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?= $status ?>"><?= $message ?></div>
            <?php endif; ?>

            <div class="project-info">
                <h3>Approved Project Title</h3>
                <p><?= htmlspecialchars($approved_topic['topic']) ?></p>
            </div>

            <?php if ($approved_topic['pdf_path']): ?>
                <?php 
                    $rs = $approved_topic['report_status'];
                    $status_class = 'alert-info';
                    $status_icon = 'fa-clock';
                    $status_text = 'Pending Approval';
                    $can_reupload = true;

                    if ($rs === 'approved') {
                        $status_class = 'alert-success';
                        $status_icon = 'fa-check-circle';
                        $status_text = 'Approved by Supervisor';
                        $can_reupload = false;
                    } elseif ($rs === 'rejected') {
                        $status_class = 'alert-error';
                        $status_icon = 'fa-times-circle';
                        $status_text = 'Rejected by Supervisor';
                        $can_reupload = true;
                    }
                ?>
                <div class="alert <?= $status_class ?>" style="margin-bottom: 20px;">
                    <i class="fas <?= $status_icon ?>"></i> Status: <strong><?= $status_text ?></strong>
                    <?php if ($rs === 'rejected' && !empty($approved_topic['report_feedback'])): ?>
                        <div style="margin-top: 10px; font-size: 13px; text-align: left; padding: 10px; background: rgba(0,0,0,0.05); border-radius: 8px;">
                            <strong>Feedback:</strong> <?= htmlspecialchars($approved_topic['report_feedback']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="current-file">
                    <span style="font-size: 13px; color: #636e72;">Last Upload:</span>
                    <a href="<?= PROJECT_ROOT . $approved_topic['pdf_path'] ?>" target="_blank" class="file-link">
                        <i class="fas fa-file-pdf"></i> View Submitted Report
                    </a>
                </div>

                <?php if (!$can_reupload): ?>
                    <p style="color: var(--success); font-weight: 600; margin-top: 20px;">
                        <i class="fas fa-lock"></i> Your report has been approved and cannot be changed.
                    </p>
                <?php else: ?>
                    <div style="margin: 30px 0; border-top: 1px solid #eee;"></div>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">You can upload a new version below to replace the current one.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!isset($can_reupload) || $can_reupload): ?>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="upload-area" onclick="document.getElementById('report_file').click()">
                        <i class="fas fa-file-pdf"></i>
                        <h3 id="fileName">Select PDF Report</h3>
                        <p>Click here to browse your files (Max 10MB)</p>
                        <input type="file" name="report_file" id="report_file" class="file-input" accept=".pdf" onchange="handleFileSelect(this)">
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        Upload Report <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            <?php endif; ?>

            <a href="stu_dashboard.php" style="display: block; text-align: center; margin-top: 25px; color: #636e72; text-decoration: none; font-weight: 600; font-size: 14px;">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
        </div>
    </div>

    <script>
        function handleFileSelect(input) {
            const fileNameDisplay = document.getElementById('fileName');
            const submitBtn = document.getElementById('submitBtn');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.type !== 'application/pdf') {
                    alert('Please select a PDF file.');
                    input.value = '';
                    fileNameDisplay.innerText = 'Select PDF Report';
                    submitBtn.disabled = true;
                    return;
                }
                
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size exceeds 10MB limit.');
                    input.value = '';
                    fileNameDisplay.innerText = 'Select PDF Report';
                    submitBtn.disabled = true;
                    return;
                }
                
                fileNameDisplay.innerText = file.name;
                submitBtn.disabled = false;
            } else {
                fileNameDisplay.innerText = 'Select PDF Report';
                submitBtn.disabled = true;
            }
        }
    </script>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
