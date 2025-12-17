<?php
session_start();

// Redirect to the dashboard if the student is already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'stu') {
    header("Location: stu_dashboard.php");
    exit();
}

// Include the database connection
include __DIR__ . '/includes/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Check if the form is submitted
    // echo "<pre>";
    // print_r($_POST);
    // echo "</pre>";
    // exit(); // Temporarily stop execution to debug

    // Check if the form fields are submitted
    if (!isset($_POST['reg_no']) || !isset($_POST['password'])) {
        die("Form fields are missing.");
    }

    $reg_no = trim($_POST['reg_no']);
    $password = trim($_POST['password']);

    // Fetch the student's record from the students table
    $stmt = $conn->prepare("SELECT * FROM students WHERE reg_no = :reg_no");
    $stmt->execute([':reg_no' => $reg_no]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        // Debug: Check values being compared
        // echo "Registration number from form: $reg_no<br>";
        // echo "Registration number from database: " . $student['reg_no'] . "<br>";
        // echo "Password from form: $password<br>";
        // echo "Password from database: " . $student['password'] . "<br>";
        // exit(); // Temporarily stop execution to debug

        // Check if it's the first login
        if ($student['first_login'] == 1) {
            // For first login, compare the password with the registration number
            if ($password === $student['reg_no']) {
                // Set session variables
                $_SESSION['user_id'] = $student['id'];
                $_SESSION['reg_no'] = $student['reg_no'];
                $_SESSION['first_login'] = TRUE; // Indicate first login
                $_SESSION['role'] = 'stu'; // Set the role to 'stu'

                header("Location: stu_change_password.php");
                exit();
            } else {
                $error_message = "Invalid password. Use your registration number as the password for first login.";
            }
        } else {
            // For subsequent logins, verify the hashed password
            if (password_verify($password, $student['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $student['id'];
                $_SESSION['reg_no'] = $student['reg_no'];
                $_SESSION['first_login'] = FALSE; // Not first login
                $_SESSION['role'] = 'stu'; // Set the role to 'stu'

                header("Location: stu_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        }
    } else {
        $error_message = "Student not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Student Login</h1>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="reg_no">Registration Number:</label>
            <input type="text" name="reg_no" id="reg_no" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <br>
            <button type="submit">Login</button>
        </form>
        <p>First-time login? Use your registration number as the password.</p>
    </div>
</body>
</html>