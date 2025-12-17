<?php
session_start();

// Redirect if the user is not logged in or is not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'stu') {
    header("Location: index.php");
    exit();
}

// Include the database connection
include __DIR__ . '/includes/db.php';

// Fetch student details using $_SESSION['user_id']
$student_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Error: Student not found.");
}

// Handle form submission to submit project topics
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $topic1 = trim($_POST['topic1']);
    $topic2 = trim($_POST['topic2']);
    $topic3 = trim($_POST['topic3']);

    // Validate that at least one topic is provided
    if (empty($topic1)) {
        $error_message = "At least one topic is required.";
    } else {
        // Insert the topics into the project_topics table
        $topics = [$topic1, $topic2, $topic3]; // Store all topics in an array
        $success_count = 0; // Track the number of successfully submitted topics

        foreach ($topics as $topic) {
            if (!empty($topic)) {
                $stmt = $conn->prepare("INSERT INTO project_topics (topic, student_reg_no, status) VALUES (?, ?, 'pending')");
                $stmt->execute([$topic, $student['reg_no']]);
                $success_count++;
            }
        }

        if ($success_count > 0) {
            // Redirect to the dashboard with a success message
            $_SESSION['success_message'] = "Topic(s) submitted successfully!";
            header("Location: stu_dashboard.php");
            exit();
        } else {
            $error_message = "No topics were submitted.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Project Topics</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Submit Project Topics</h1>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="topic1">Topic 1 (Required):</label>
            <textarea id="topic1" name="topic1" rows="2" cols="50" required></textarea>
            <br>
            <label for="topic2">Topic 2 (Optional):</label>
            <textarea id="topic2" name="topic2" rows="2" cols="50"></textarea>
            <br>
            <label for="topic3">Topic 3 (Optional):</label>
            <textarea id="topic3" name="topic3" rows="2" cols="50"></textarea>
            <br>
            <button type="submit">Submit Topics</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>