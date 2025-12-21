<?php
session_start();
include 'includes/auth.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: /projectval/");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_supervisor'])) {
    $name = $_POST['name'];
    $department = $_POST['department'];

    $stmt = $conn->prepare("INSERT INTO supervisors (name, department) VALUES (?, ?)");
    $stmt->execute([$name, $department]);

    echo "Supervisor added successfully!";
    header("Refresh:1");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Supervisor</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Add Supervisor</h1>
        <form method="POST">
            <input type="text" name="name" placeholder="Name" required>
            <select name="department" required>
                <option value="Computer Science">Computer Science</option>
                <option value="Cyber Security">Cyber Security</option>
                <option value="Information Technology">Information Technology</option>
                <option value="Software Engineering">Software Engineering</option>
            </select>
            <button type="submit" name="add_supervisor">Add Supervisor</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
