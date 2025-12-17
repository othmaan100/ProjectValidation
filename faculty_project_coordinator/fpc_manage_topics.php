<?php
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

// if ($_SESSION['role'] !== 'fpc') {
//     header("Location: index.php");
//     exit();
// }

var_dump($_SESSION['role']);

// Fetch all project topics
$stmt = $conn->prepare("SELECT * FROM project_topics join students on project_topics.student_id=students.id");
$stmt->execute();
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle topic deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_topic'])) {
    $topic_id = $_POST['topic_id'];

    // Delete the topic from the database
    $stmt = $conn->prepare("DELETE FROM project_topics WHERE id = ?");
    $stmt->execute([$topic_id]);

    echo "Topic deleted successfully!";
    header("Refresh:1");
}

// Handle form submission for adding topics, batch upload, and PDF upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mode'])) {
    $mode = $_POST['mode'];

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

    $stmt = $conn->prepare("INSERT INTO project_topics (topic, student_reg_no, student_name, session, supervisor_name) VALUES (:topic, :student_reg_no, :student_name, :session, :supervisor_name)");
    $stmt->execute([
        ':topic' => $topic,
        ':student_reg_no' => $student_reg_no,
        ':student_name' => $student_name,
        ':session' => $session,
        ':supervisor_name' => $supervisor_name
    ]);

    if ($stmt->rowCount() > 0) {
        echo "Project topic added successfully.";
    } else {
        echo "Error adding project topic.";
    }
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

    $successCount = 0;
    $errorCount = 0;

    while (($data = fgetcsv($handle)) !== FALSE) {
        $topic = $data[0];
        $student_reg_no = $data[1];
        $student_name = $data[2];
        $session = $data[3];
        $supervisor_name = $data[4];

        // Hash the registration number as the initial password
        $initial_password = password_hash($student_reg_no, PASSWORD_DEFAULT);

        // Insert the new student into the students table
        $stmt = $conn->prepare("INSERT INTO students (reg_no, name, password, first_login) VALUES (:reg_no, :name, :password, :first_login)");
        $stmt->execute([
            ':reg_no' => $student_reg_no,
            ':name' => $student_name,
            ':password' => $initial_password,
            ':first_login' => TRUE
        ]);

        // Insert into project_topics table
        $stmt = $conn->prepare("INSERT INTO project_topics (topic, student_reg_no, student_name, session, supervisor_name) VALUES (:topic, :student_reg_no, :student_name, :session, :supervisor_name)");
        $stmt->execute([
            ':topic' => $topic,
            ':student_reg_no' => $student_reg_no,
            ':student_name' => $student_name,
            ':session' => $session,
            ':supervisor_name' => $supervisor_name
        ]);

        if ($stmt->rowCount() > 0) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }

    fclose($handle);
    echo "Batch upload completed. Success: $successCount, Errors: $errorCount<br>";
}

// Mode 3: Upload and Map PDF Past Projects to Topics (PDF is optional)
function handleUploadPdf() {
    global $conn;

    $project_id = $_POST['project_id'] ?? '';

    if (empty($project_id)) {
        echo "Project ID is required.<br>";
        return;
    }

    // Check if the project topic exists
    $stmt = $conn->prepare("SELECT id FROM project_topics WHERE id = :project_id");
    $stmt->execute([':project_id' => $project_id]);

    if ($stmt->rowCount() === 0) {
        echo "Project topic does not exist.<br>";
        return;
    }

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
            $stmt = $conn->prepare("UPDATE project_topics SET pdf_path = :pdf_path WHERE id = :project_id");
            $stmt->execute([
                ':pdf_path' => $file_path,
                ':project_id' => $project_id
            ]);

            if ($stmt->rowCount() > 0) {
                echo "PDF uploaded and mapped successfully.<br>";
            } else {
                echo "Error updating database.<br>";
            }
        } else {
            echo "Error uploading PDF file.<br>";
        }
    } else {
        echo "No PDF file uploaded. Project ID updated successfully.<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Topics</title>
    <link rel="stylesheet" href="..\assets\css\styles.css">
</head>
<body>
    <?php require_once('../includes/header.php'); ?>
    <div class="container">
        <h1>Manage Project Topics</h1>

        <!-- Add Topic Form -->
        <h2>Add New Topic</h2>
        <form method="POST">
            <input type="hidden" name="mode" value="add_topic">
            <label for="topic">Topic:</label>
            <input type="text" name="topic" required>
            <label for="student_reg_no">Student Registration Number:</label>
            <input type="text" name="student_reg_no" required>
            <label for="student_name">Student Name:</label>
            <input type="text" name="student_name">
            <label for="session">Session:</label>
            <input type="text" name="session" required>
            <label for="supervisor_name">Supervisor Name:</label>
            <input type="text" name="supervisor_name">
            <button type="submit">Add Topic</button>
        </form>

        <!-- Batch Upload Form -->
        <h2>Batch Upload Topics</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="batch_upload">
            <label for="csv_file">Upload CSV File:</label>
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit">Upload CSV</button>
        </form>

        <!-- Upload PDF Form -->
        <h2>Upload PDF for Topic</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="mode" value="upload_pdf">
            <label for="project_id">Project ID:</label>
            <input type="number" name="project_id" required>
            <label for="pdf_file">Upload PDF:</label>
            <input type="file" name="pdf_file" accept=".pdf">
            <button type="submit">Upload PDF</button>
        </form>

        <!-- Display Existing Topics -->
        <h2>Existing Topics</h2>
        <table>
            <tr>
                <th>Topic</th>
                <th>Student Registration Number</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?php echo htmlspecialchars($topic['topic']); ?></td>
                    <td><?php echo htmlspecialchars($topic['reg_no']); ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($topic['status'])); ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                            <button type="submit" name="delete_topic" onclick="return confirm('Are you sure you want to delete this topic?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>