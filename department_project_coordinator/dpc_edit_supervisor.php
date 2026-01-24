<?php
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$supervisor_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM supervisors WHERE id = ?");
$stmt->execute([$supervisor_id]);
$supervisor = $stmt->fetch();

if (!$supervisor) {
    die("Supervisor not found.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_supervisor'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $staff_no = trim($_POST['staff_no']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);

    // Validate required fields
    if (empty($name) || empty($email) || empty($staff_no) || empty($phone) || empty($department)) {
        $error_message = "All fields are required.";
    } else {
        // Update the supervisor's details
        $stmt = $conn->prepare("UPDATE supervisors SET name = ?, email = ?, staff_no = ?, phone = ?, department = ? WHERE id = ?");
        $stmt->execute([$name, $email, $staff_no, $phone, $department, $supervisor_id]);

        $success_message = "Supervisor updated successfully!";
        header("Refresh:2; url=dpc_assign_supervisors.php"); // Redirect after 2 seconds
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supervisor</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        button[type="submit"] {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #45a049;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .message.error {
            background-color: #ffebee;
            color: #c62828;
        }

        .message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Edit Supervisor</h1>

        <!-- Display error or success messages -->
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Edit Supervisor Form -->
        <form method="POST">
            <div>
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($supervisor['name']); ?>" required>
            </div>

            <div>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($supervisor['email']); ?>" required>
            </div>

            <div>
                <label for="staff_no">Staff Number:</label>
                <input type="text" name="staff_no" id="staff_no" value="<?php echo htmlspecialchars($supervisor['staff_no']); ?>" required>
            </div>

            <div>
                <label for="phone">Phone:</label>
                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($supervisor['phone']); ?>" required>
            </div>

            <div>
                <label for="department">Department:</label>
                <select name="department" id="department" required>
                    <option value="Computer Science" <?php echo ($supervisor['department'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="Cyber Security" <?php echo ($supervisor['department'] == 'Cyber Security') ? 'selected' : ''; ?>>Cyber Security</option>
                    <option value="Information Technology" <?php echo ($supervisor['department'] == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                    <option value="Software Engineering" <?php echo ($supervisor['department'] == 'Software Engineering') ? 'selected' : ''; ?>>Software Engineering</option>
                </select>
            </div>

            <button type="submit" name="update_supervisor">Update Supervisor</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>

