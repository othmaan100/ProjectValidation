<?php
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';   // Include database connection

if ($_SESSION['role'] !== 'sup') {
    header("Location: index.php");
    exit();
}

// Fetch assigned students and their topics
$supervisor_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT students.name, students.reg_no, project_topics.topic
                        FROM project_topics
                        JOIN students ON project_topics.student_reg_no = students.reg_no
                        WHERE project_topics.supervisor_id = ?");
$stmt->execute([$supervisor_id]);
$assigned_students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Supervisor Dashboard</h1>
        <div class="dashboard">
            <div class="card">
                <h2>Assigned Students</h2>
                <table>
                    <tr>
                        <th>Student Name</th>
                        <th>Registration Number</th>
                        <th>Project Topic</th>
                    </tr>
                    <?php foreach ($assigned_students as $student): ?>
                        <tr>
                            <td><?php echo $student['name']; ?></td>
                            <td><?php echo $student['reg_no']; ?></td>
                            <td><?php echo $student['topic']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>