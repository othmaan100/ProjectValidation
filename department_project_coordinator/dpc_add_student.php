<?php
session_start();
include __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $reg_no = $_POST['reg_no'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $state = $_POST['state'];

    $stmt = $conn->prepare("INSERT INTO students (reg_no, name, department, state) VALUES (?, ?, ?, ?)");
    $stmt->execute([$reg_no, $name, $department, $state]);

    echo "Student added successfully!";
    header("Refresh:1");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Add Student</h1>
        <form method="POST">
            <input type="text" name="reg_no" placeholder="Registration Number" required>
            <input type="text" name="name" placeholder="Name" required>
            <select name="department" required>
                <option value="Computer Science">Computer Science</option>
                <option value="Cyber Security">Cyber Security</option>
                <option value="Information Technology">Information Technology</option>
                <option value="Software Engineering">Software Engineering</option>
            </select>
            <input type="text" name="state" placeholder="State" required>
            <button type="submit" name="add_student">Add Student</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

