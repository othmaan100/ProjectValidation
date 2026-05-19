<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';

// Check if the user is logged in as DPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dpc_id = $_SESSION['user_id'];

// Fetch the DPC's department info
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name, d.project_guideline 
                        FROM users u 
                        JOIN departments d ON u.department = d.id 
                        WHERE u.id = ?");

// Try to execute. If it fails, it might be because 'project_guideline' column doesn't exist.
try {
    $stmt->execute([$dpc_id]);
} catch (PDOException $e) {
    // Attempt to add the column if it's missing
    if (strpos($e->getMessage(), "Unknown column 'd.project_guideline'") !== false || strpos($e->getMessage(), "Unknown column") !== false) {
        $conn->exec("ALTER TABLE departments ADD COLUMN project_guideline VARCHAR(255) DEFAULT NULL");
        // Retry execution
        $stmt->execute([$dpc_id]);
    } else {
        throw $e; // Re-throw other errors
    }
}

$dpc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $dpc_info['dept_id'];
$dept_name = $dpc_info['department_name'];
$current_guideline = $dpc_info['project_guideline'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['guideline_file'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Session expired or invalid request. Please refresh and try again.";
    } else {
        $file = $_FILES['guideline_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['application/pdf'];
            $file_type = mime_content_type($file['tmp_name']);
            
            if (in_array($file_type, $allowed_types) && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'pdf') {
                if ($file['size'] <= 5 * 1024 * 1024) { // 5MB limit
                    $upload_dir = __DIR__ . '/../assets/uploads/guidelines/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename to avoid conflicts/caching issues
                    $filename = 'guideline_' . $dept_id . '_' . time() . '.pdf';
                    $target_path = $upload_dir . $filename;
                    
                    // Remove old file if exists
                    if ($current_guideline && file_exists(__DIR__ . '/../' . $current_guideline)) {
                        unlink(__DIR__ . '/../' . $current_guideline);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Update database
                        $db_path = 'assets/uploads/guidelines/' . $filename;
                        $update_stmt = $conn->prepare("UPDATE departments SET project_guideline = ? WHERE id = ?");
                        $update_stmt->execute([$db_path, $dept_id]);
                        
                        $message = "Project guideline uploaded successfully!";
                        $current_guideline = $db_path;
                    } else {
                        $error = "Failed to save the file.";
                    }
                } else {
                    $error = "File size exceeds 5MB limit.";
                }
            } else {
                $error = "Only PDF files are allowed.";
            }
        } else {
            $error = "Error uploading file. Error code: " . $file['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Guideline - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; padding: 20px; }
        .page-container { max-width: 800px; margin: 40px auto; }
        .card { 
            background: var(--glass); padding: 40px; border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); backdrop-filter: blur(10px);
        }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2d3436; margin-bottom: 5px; }
        .header p { color: #636e72; margin-top: 5px; }
        
        .upload-area {
            border: 2px dashed var(--primary); border-radius: 15px; padding: 40px; text-align: center;
            background: rgba(102, 126, 234, 0.05); cursor: pointer; transition: 0.3s;
            margin-bottom: 25px;
        }
        .upload-area:hover { background: rgba(102, 126, 234, 0.1); }
        .upload-area i { font-size: 48px; color: var(--primary); margin-bottom: 15px; }
        
        .btn { 
            padding: 12px 25px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; 
            transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; 
            font-size: 16px; width: 100%; justify-content: center;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--secondary); transform: translateY(-2px); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        .alert-danger { background: #ffebee; color: #c62828; }
        
        .current-file {
            background: white; padding: 20px; border-radius: 15px; margin-bottom: 25px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="card">
            <div class="header">
                <h1><i class="fas fa-file-pdf" style="color: var(--danger);"></i> Project Guideline</h1>
                <p>Upload the official project guideline PDF for <strong><?= htmlspecialchars($dept_name) ?></strong> students.</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($current_guideline): ?>
                <div class="current-file">
                    <div>
                        <small style="color: #636e72; font-weight: 600; text-transform: uppercase;">Current File</small>
                        <div style="font-weight: 600; margin-top: 5px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: var(--success);"></i>
                            Guideline Available
                        </div>
                    </div>
                    <a href="<?= PROJECT_ROOT . $current_guideline ?>" target="_blank" class="btn" style="width: auto; background: #e2e8f0; color: #2d3436; font-size: 14px;">
                        View <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                
                <div class="upload-area" onclick="document.getElementById('file_input').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Click to Select PDF</h3>
                    <p>Max file size: 5MB</p>
                    <input type="file" name="guideline_file" id="file_input" accept=".pdf" style="display: none;" onchange="updateFilename(this)">
                    <p id="filename_display" style="font-weight: 600; color: var(--primary); margin-top: 10px;"></p>
                </div>
                
                <button type="submit" class="btn btn-primary" id="submit_btn" disabled>
                    <i class="fas fa-upload"></i> Upload Guideline
                </button>
            </form>
            
            <a href="index.php" style="display: block; text-align: center; margin-top: 25px; color: #636e72; text-decoration: none; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script>
        function updateFilename(input) {
            if (input.files && input.files.length > 0) {
                const name = input.files[0].name;
                document.getElementById('filename_display').textContent = name;
                document.getElementById('submit_btn').disabled = false;
                
                if (!name.toLowerCase().endsWith('.pdf')) {
                    alert('Please select a PDF file.');
                    input.value = '';
                    document.getElementById('filename_display').textContent = '';
                    document.getElementById('submit_btn').disabled = true;
                }
            }
        }
    </script>
</body>
</html>
