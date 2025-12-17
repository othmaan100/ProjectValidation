<?php
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';

if ($_SESSION['role'] !== 'stu') {
    header("Location: index.php");
    exit();
}

$student_reg_no = $_SESSION['reg_no'];
$stmt = $conn->prepare("SELECT * FROM project_topics WHERE reg_no = ?");
$stmt->execute([$student_reg_no]);
$topics = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Topic Status</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>View Topic Status</h1>
        <table>
            <tr>
                <th>Topic</th>
                <th>Status</th>
            </tr>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?php echo $topic['topic']; ?></td>
                    <td><?php echo ucfirst($topic['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>