<?php
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';

if ($_SESSION['role'] !== 'fpc') {
    header("Location: index.php");
    exit();
}

// Database connection
$host = 'localhost'; // Database host
$user = 'root';      // Database username (default for XAMPP is 'root')
$password = '';      // Database password (default for XAMPP is empty)
$database = 'my_project_topics'; // Replace with your actual database name

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission based on mode
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mode = $_POST['mode'] ?? '';

    switch ($mode) {
        case 'add_topic':
            handleAddTopic();
            break;
        case 'batch_upload':
            handleBatchUpload();
            break;
        case 'upload_pdf':
            handleUploadPdf();
            break;
        default:
            echo "Invalid mode selected.";
            break;
    }
}

// Mode 1: Add Past Project Topic
function handleAddTopic() {
    global $conn;

    $topic = $_POST['topic'] ?? '';
    $student_reg_no = $_POST['student_reg_no'] ?? '';
    $student_name = $_POST['student_name'] ?? '';
    $session = $_POST['session'] ?? '';
    $supervisor_name = $_POST['supervisor_name'] ?? '';

    if (empty($topic) || empty($student_reg_no) || empty($session)) {
        echo "Topic, Student Registration Number, and Session are required fields.";
        return;
    }

    $stmt = $conn->prepare("INSERT INTO past_projects (topic, student_reg_no, student_name, session, supervisor_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $topic, $student_reg_no, $student_name, $session, $supervisor_name);

    if ($stmt->execute()) {
        echo "Project topic added successfully.";
    } else {
        echo "Error adding project topic: " . $stmt->error;
    }

    $stmt->close();
}

// Mode 2: Batch Upload Past Project Topics
function handleBatchUpload() {
    global $conn;

    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo "Error uploading file.";
        return;
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');

    // Skip header row
    fgetcsv($handle);

    while (($data = fgetcsv($handle)) !== FALSE) {
        $topic = $data[0];
        $student_reg_no = $data[1];
        $student_name = $data[2];
        $session = $data[3];
        $supervisor_name = $data[4];

        $stmt = $conn->prepare("INSERT INTO past_projects (topic, student_reg_no, student_name, session, supervisor_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $topic, $student_reg_no, $student_name, $session, $supervisor_name);
        $stmt->execute();
        $stmt->close();
    }

    fclose($handle);
    echo "Batch upload completed successfully.";
}

// Mode 3: Upload and Map PDF Past Projects to Topics (PDF is optional)
function handleUploadPdf() {
    global $conn;

    $project_id = $_POST['project_id'] ?? '';

    if (empty($project_id)) {
        echo "Project ID is required.<br>"; // Debugging
        return;
    }

    // Check if the project topic exists
    $stmt = $conn->prepare("SELECT id FROM past_projects WHERE id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo "Project topic does not exist.<br>"; // Debugging
        $stmt->close();
        return;
    }

    $stmt->close();

    // Handle PDF upload if a file is provided
    $pdf_file = $_FILES['pdf_file'] ?? null;
    if ($pdf_file && $pdf_file['error'] === UPLOAD_ERR_OK) {
        // Upload PDF file
        $upload_dir = 'assets/uploads/past_projects/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
        }

        $file_name = basename($pdf_file['name']);
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($pdf_file['tmp_name'], $file_path)) {
            // Update database with PDF file path
            $stmt = $conn->prepare("UPDATE past_projects SET pdf_path = ? WHERE id = ?");
            $stmt->bind_param("si", $file_path, $project_id);

            if ($stmt->execute()) {
                echo "PDF uploaded and mapped successfully.<br>"; // Debugging
            } else {
                echo "Error updating database: " . $stmt->error . "<br>"; // Debugging
            }

            $stmt->close();
        } else {
            echo "Error uploading PDF file.<br>"; // Debugging
        }
    } else {
        echo "No PDF file uploaded. Project ID updated successfully.<br>"; // Debugging
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Past Projects</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Upload Past Projects</h1>
        <form method="POST" enctype="multipart/form-data">
            <!-- Mode Selector -->
            <div class="form-group mode-selector">
                <label>Select Mode:</label>
                <label><input type="radio" name="mode" value="add_topic" checked> Add Past Project Topic</label>
                <label><input type="radio" name="mode" value="batch_upload"> Batch Upload Past Project Topics</label>
                <label><input type="radio" name="mode" value="upload_pdf"> Upload and Map PDF</label>
            </div>

            <!-- Add Past Project Topic Fields -->
            <div id="add_topic_fields" class="mode-fields">
                <input type="text" name="session" placeholder="Session" required>    
                <input type="text" name="topic" placeholder="Project Topic" required>
                <input type="text" name="student_reg_no" placeholder="Student Registration Number" required>
                <input type="text" name="student_name" placeholder="Student Name">
                <input type="text" name="supervisor_name" placeholder="Supervisor Name">
            </div>

            <!-- Batch Upload Past Project Topics Fields -->
            <div id="batch_upload_fields" class="mode-fields" style="display: none;">
                <input type="file" name="csv_file" accept=".csv" required>
            </div>

            <!-- Upload and Map PDF Fields -->
            <div id="upload_pdf_fields" class="mode-fields" style="display: none;">
                <input type="number" name="project_id" placeholder="Project ID" required>
                <input type="file" name="pdf_file" accept=".pdf">
            </div>

            <!-- Submit Button -->
            <button type="submit">Submit</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script>
        // Show/hide fields based on selected mode
        const modeSelector = document.querySelectorAll('input[name="mode"]');
        const modeFields = document.querySelectorAll('.mode-fields');

        modeSelector.forEach((radio) => {
            radio.addEventListener('change', () => {
                modeFields.forEach((field) => {
                    field.style.display = 'none';
                });
                document.getElementById(`${radio.value}_fields`).style.display = 'block';
            });
        });
    </script>
</body>
</html>