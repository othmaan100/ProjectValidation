<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Check if the user is logged in as FPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'fpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Handle CSV Template Download
if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=past_projects_template.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Topic', 'Reg No', 'Student Name', 'Session', 'Supervisor']);
    fputcsv($output, ['Sample Project Topic Title', '2023/CS/001', 'John Doe', '2023/2024', 'Dr. Smith']);
    fclose($output);
    exit();
}

$message = '';
$status = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $mode = $_POST['mode'] ?? '';

        switch ($mode) {
            case 'add_topic':
                $message = handleAddTopic($conn);
                $status = 'success';
                break;
            case 'batch_upload':
                $message = handleBatchUpload($conn);
                $status = 'success';
                break;
            case 'upload_pdf':
                $message = handleUploadPdf($conn);
                $status = 'success';
                break;
            default:
                throw new Exception("Invalid mode selected.");
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $status = 'error';
    }
}

// Fetch some recently uploaded projects for display in this faculty
try {
    $faculty_id = $_SESSION['faculty_id'];
    $stmt = $conn->prepare("SELECT * FROM past_projects WHERE faculty_id = ? ORDER BY id DESC LIMIT 10");
    $stmt->execute([$faculty_id]);
    $recentProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentProjects = [];
}

/** FUNCTIONS **/

function handleAddTopic($conn) {
    $topic = trim($_POST['topic'] ?? '');
    $reg_no = trim($_POST['reg_no'] ?? '');
    $student_name = trim($_POST['student_name'] ?? '');
    $session = trim($_POST['session'] ?? '');
    $supervisor_name = trim($_POST['supervisor_name'] ?? '');

    if (empty($topic) || empty($reg_no) || empty($session)) {
        throw new Exception("Topic, Registration Number, and Session are required.");
    }

    $faculty_id = $_SESSION['faculty_id'];
    $stmt = $conn->prepare("INSERT INTO past_projects (topic, reg_no, student_name, session, supervisor_name, faculty_id) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$topic, $reg_no, $student_name, $session, $supervisor_name, $faculty_id])) {
        return "Project topic added successfully.";
    } else {
        throw new Exception("Error adding project topic.");
    }
}

function handleBatchUpload($conn) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error uploading file.");
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    
    // Skip header row
    fgetcsv($handle);

    $successCount = 0;
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) < 3) continue; // Basic validation
        
        $topic = $data[0] ?? '';
        $reg_no = $data[1] ?? '';
        $student_name = $data[2] ?? '';
        $session = $data[3] ?? '';
        $supervisor_name = $data[4] ?? '';

        $faculty_id = $_SESSION['faculty_id'];
        $stmt = $conn->prepare("INSERT INTO past_projects (topic, reg_no, student_name, session, supervisor_name, faculty_id) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$topic, $reg_no, $student_name, $session, $supervisor_name, $faculty_id])) {
            $successCount++;
        }
    }

    fclose($handle);
    return "Batch upload completed successfully. $successCount records imported.";
}

function handleUploadPdf($conn) {
    $project_id = $_POST['project_id'] ?? '';

    if (empty($project_id)) {
        throw new Exception("Project ID is required.");
    }

    // Check existence in this faculty
    $faculty_id = $_SESSION['faculty_id'];
    $stmt = $conn->prepare("SELECT id FROM past_projects WHERE id = ? AND faculty_id = ?");
    $stmt->execute([$project_id, $faculty_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Project topic with ID #$project_id does not exist.");
    }

    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No PDF file uploaded or upload error.");
    }

    $upload_dir = __DIR__ . '/../assets/uploads/past_projects/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'pdf') {
        throw new Exception("Only PDF files are allowed.");
    }

    $new_filename = 'project_' . $project_id . '_' . time() . '.pdf';
    $file_path = 'assets/uploads/past_projects/' . $new_filename;
    $target_path = $upload_dir . $new_filename;

    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_path)) {
        $stmt = $conn->prepare("UPDATE past_projects SET pdf_path = ? WHERE id = ?");
        $stmt->execute([$file_path, $project_id]);
        return "PDF uploaded and mapped to Project #$project_id successfully.";
    } else {
        throw new Exception("Failed to save the uploaded file.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Past Projects - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        /* Header Section */
        .page-header {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #667eea);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .page-title {
            color: #667eea;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 16px;
        }

        /* Message Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        /* Main Card */
        .action-card {
            background: white;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        /* Tab Switcher */
        .mode-tabs {
            display: flex;
            background: #f0f2f5;
            padding: 8px;
            border-radius: 15px;
            margin-bottom: 30px;
            gap: 5px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .tab-btn.active {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .tab-btn i {
            font-size: 18px;
        }

        /* Form Styling */
        .mode-section {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        
        .mode-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #fafbfc;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .file-upload-wrapper {
            border: 2px dashed #667eea;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            background: #f8f9ff;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-upload-wrapper:hover {
            background: #eff2ff;
            border-color: #764ba2;
        }
        
        .file-upload-wrapper i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .file-info {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        /* Recent Activity Table */
        .table-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .table-card h3 {
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 15px;
            background: #f8f9ff;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            border-bottom: 2px solid #e1e8f0;
        }
        
        td {
            padding: 15px;
            font-size: 14px;
            border-bottom: 1px solid #e1e8f0;
            color: #444;
        }
        
        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            background: #eef2ff;
            color: #667eea;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
            opacity: 0.9;
        }
        
        .btn-back:hover {
            opacity: 1;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="fpc_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="page-header">
            <h1 class="page-title">Past Projects Repository</h1>
            <p class="page-subtitle">Populate and manage the historical database of validated project topics</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $status; ?>">
                <i class="fas <?php echo $status === 'success' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <div class="action-card">
            <div class="mode-tabs">
                <button type="button" class="tab-btn active" onclick="switchMode('add_topic')">
                    <i class="fas fa-keyboard"></i> Single Entry
                </button>
                <button type="button" class="tab-btn" onclick="switchMode('batch_upload')">
                    <i class="fas fa-file-csv"></i> CSV Upload
                </button>
                <button type="button" class="tab-btn" onclick="switchMode('upload_pdf')">
                    <i class="fas fa-file-pdf"></i> Map PDF
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" id="mainForm">
                <input type="hidden" name="mode" id="modeInput" value="add_topic">

                <!-- Single Entry Mode -->
                <div id="section_add_topic" class="mode-section active">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label><i class="fas fa-heading"></i> Project Topic</label>
                            <input type="text" name="topic" placeholder="Enter the full project title..." required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Student Reg No.</label>
                            <input type="text" name="reg_no" placeholder="e.g. 2023/CS/001" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Academic Session</label>
                            <input type="text" name="session" placeholder="e.g. 2023/2024" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user-graduate"></i> Student Name</label>
                            <input type="text" name="student_name" placeholder="Full name of the student">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user-tie"></i> Supervisor Name</label>
                            <input type="text" name="supervisor_name" placeholder="Full name of the supervisor">
                        </div>
                    </div>
                </div>

                <!-- Batch Upload Mode -->
                <div id="section_batch_upload" class="mode-section">
                    <div style="margin-bottom: 20px; text-align: right;">
                        <a href="?download_template=1" class="tab-btn" style="display: inline-flex; width: auto; background: #eef2ff; color: #667eea; border: 1px solid #667eea;">
                            <i class="fas fa-download"></i> Download CSV Template
                        </a>
                    </div>
                    <div class="file-upload-wrapper" onclick="document.getElementById('csv_file').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3>Click to upload CSV file</h3>
                        <p>File format: Topic, Reg No, Student Name, Session, Supervisor</p>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" style="display: none;" onchange="updateFileInfo(this, 'csv-info')">
                        <div class="file-info" id="csv-info">No file selected</div>
                    </div>
                </div>

                <!-- PDF Mapping Mode -->
                <div id="section_upload_pdf" class="mode-section">
                    <div class="form-group">
                        <label><i class="fas fa-fingerprint"></i> Existing Project ID</label>
                        <input type="number" name="project_id" placeholder="Enter the ID of the existing record">
                    </div>
                    <div class="file-upload-wrapper" onclick="document.getElementById('pdf_file').click()">
                        <i class="fas fa-file-pdf"></i>
                        <h3>Select PDF Document</h3>
                        <p>Upload the full project documentation</p>
                        <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" style="display: none;" onchange="updateFileInfo(this, 'pdf-info')">
                        <div class="file-info" id="pdf-info">No file selected</div>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Process Entry
                </button>
            </form>
        </div>

        <?php if (!empty($recentProjects)): ?>
        <div class="table-card">
            <h3><i class="fas fa-history"></i> Recently Added Projects</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Topic</th>
                            <th>Student</th>
                            <th>Session</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentProjects as $project): ?>
                        <tr>
                            <td>#<?php echo $project['id']; ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($project['topic']); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($project['student_name'] ?: 'N/A'); ?></div>
                                <div style="font-size: 11px; color: #888;"><?php echo htmlspecialchars($project['reg_no']); ?></div>
                            </td>
                            <td><span class="badge"><?php echo htmlspecialchars($project['session']); ?></span></td>
                            <td>
                                <?php if ($project['pdf_path']): ?>
                                    <span style="color: #28a745;"><i class="fas fa-file-pdf"></i> PDF Attached</span>
                                <?php else: ?>
                                    <span style="color: #666;"><i class="fas fa-minus-circle"></i> Info Only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function switchMode(mode) {
            // Update input
            document.getElementById('modeInput').value = mode;
            
            // Update tabs
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            if (mode === 'add_topic') buttons[0].classList.add('active');
            if (mode === 'batch_upload') buttons[1].classList.add('active');
            if (mode === 'upload_pdf') buttons[2].classList.add('active');

            // Update sections
            document.querySelectorAll('.mode-section').forEach(sec => {
                sec.classList.remove('active');
            });
            document.getElementById('section_' + mode).classList.add('active');
            
            // Update button text
            const btn = document.getElementById('submitBtn');
            if (mode === 'add_topic') btn.innerHTML = '<i class="fas fa-paper-plane"></i> Save Single Entry';
            if (mode === 'batch_upload') btn.innerHTML = '<i class="fas fa-file-import"></i> Start Batch Import';
            if (mode === 'upload_pdf') btn.innerHTML = '<i class="fas fa-link"></i> Map PDF to Record';

            // Reset required flags to avoid validation errors on hidden fields
            resetRequired(mode);
        }

        function resetRequired(mode) {
            // Remove required from all
            document.querySelectorAll('input').forEach(input => {
                if (input.name !== 'mode') input.required = false;
            });

            // Set required for active mode
            if (mode === 'add_topic') {
                document.querySelector('input[name="topic"]').required = true;
                document.querySelector('input[name="reg_no"]').required = true;
                document.querySelector('input[name="session"]').required = true;
            } else if (mode === 'batch_upload') {
                document.querySelector('input[name="csv_file"]').required = true;
            } else if (mode === 'upload_pdf') {
                document.querySelector('input[name="project_id"]').required = true;
                document.querySelector('input[name="pdf_file"]').required = true;
            }
        }

        function updateFileInfo(input, infoId) {
            const fileName = input.files[0] ? input.files[0].name : "No file selected";
            document.getElementById(infoId).innerHTML = "<strong>Selected:</strong> " + fileName;
        }

        // Initialize
        switchMode('add_topic');
    </script>
    <div style="margin-top: 50px; text-align: center; color: rgba(255,255,255,0.8);">
        <?php include_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>


