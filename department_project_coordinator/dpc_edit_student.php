<?php
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: index.php");
    exit();
}

// Fetch the student ID from the URL
if (!isset($_GET['id'])) {
    header("Location: dpc_manage_students.php");
    exit();
}

$studentId = $_GET['id'];

// Fetch the student's details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: dpc_manage_students.php");
    exit();
}

// Handle form submission to update the student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
    $regNo = $_POST['reg_no'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // Update the student's details
    $stmt = $conn->prepare("UPDATE students SET reg_no = ?, name = ?, phone = ?, email = ? WHERE id = ?");
    $stmt->execute([$regNo, $name, $phone, $email, $studentId]);

    echo "Student updated successfully!";
    header("Refresh:1");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Edit Student</h1>

        <!-- Edit Student Form -->
        <form method="POST">
            <input type="text" name="reg_no" placeholder="Registration Number" value="<?php echo $student['reg_no']; ?>" required>
            <input type="text" name="name" placeholder="Name" value="<?php echo $student['name']; ?>" required>
            <input type="text" name="phone" placeholder="Phone" value="<?php echo $student['phone']; ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo $student['email']; ?>" required>
            <button type="submit" name="update_student">Update Student</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

