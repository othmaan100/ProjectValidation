<?php
session_start();

// Redirect if the user is not logged in or is not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'stu') {
    header("Location: index.php");
    exit();
}

// Display success message if set
if (isset($_SESSION['success_message'])) {
    echo "<div class='success-message'>" . $_SESSION['success_message'] . "</div>";
    unset($_SESSION['success_message']); // Clear the message after displaying it
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

// Fetch submitted topics
$stmt = $conn->prepare("SELECT * FROM project_topics WHERE student_reg_no = ?");
$stmt->execute([$student['reg_no']]);
$topics = $stmt->fetchAll();

// Fetch assigned supervisor (if any)
$stmt = $conn->prepare("SELECT supervisors.name AS supervisor_name
                        FROM supervisors
                        JOIN project_topics ON supervisors.id = project_topics.supervisor_id
                        WHERE project_topics.student_reg_no = ? AND project_topics.status = 'approved'");
$stmt->execute([$student['reg_no']]);
$supervisor = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($student['name']); ?></h1>
        <div class="dashboard">
            <!-- Submit Project Topic Card -->
            <div class="card">
                <h2>Submit Project Topics</h2>
                <p>Submit your project topics for validation.</p>
                <a href="stu_submit_topic.php" class="button">Go to Submit Topics</a>
            </div>

            <!-- View Topic Status Card -->
            <div class="card">
                <h2>View Topic Status</h2>
                <?php if (count($topics) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Topic</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topics as $topic): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($topic['topic']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($topic['status'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No topics submitted yet.</p>
                <?php endif; ?>
            </div>

            <!-- View Assigned Supervisor Card -->
            <div class="card">
                <h2>Assigned Supervisor</h2>
                <?php if ($supervisor): ?>
                    <p>Your assigned supervisor is: <strong><?php echo htmlspecialchars($supervisor['supervisor_name']); ?></strong></p>
                <?php else: ?>
                    <p>No supervisor has been assigned yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>