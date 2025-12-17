<?php
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: index.php");
    exit();
}

// Fetch the DPC's department
$dpcId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT department FROM users WHERE id = ?");
$stmt->execute([$dpcId]);
$dpcDepartment = $stmt->fetchColumn();

// Fetch all students in the DPC's department
$stmt = $conn->prepare("SELECT * FROM students WHERE department = ?");
$stmt->execute([$dpcDepartment]);
$students = $stmt->fetchAll();

// Handle form submission to add a new student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $regNo = $_POST['reg_no'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // Check if the registration number already exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE reg_no = ?");
    $stmt->execute([$regNo]);

    if ($stmt->rowCount() > 0) {
        echo "Error: Student with registration number '$regNo' already exists.";
    } else {
        // Use the registration number as the initial password
        $initial_password = $regNo; // Store the reg_no directly as the password

        // Insert the new student
        $stmt = $conn->prepare("INSERT INTO students (reg_no, name, phone, email, department, password, first_login) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$regNo, $name, $phone, $email, $dpcDepartment, $initial_password, 1]); // Set first_login to 1

        echo "Student added successfully!";
        header("Refresh:1");
    }
}

// Handle form submission to upload students in batches
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_students'])) {
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo "Error uploading file.";
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');

        // Skip the header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            $regNo = $data[0];
            $name = $data[1];
            $phone = $data[2];
            $email = $data[3];

            // Check if the registration number already exists
            $stmt = $conn->prepare("SELECT id FROM students WHERE reg_no = ?");
            $stmt->execute([$regNo]);

            if ($stmt->rowCount() === 0) {
                // Use the registration number as the initial password
                $initial_password = $regNo; // Store the reg_no directly as the password

                // Insert the new student
                $stmt = $conn->prepare("INSERT INTO students (reg_no, name, phone, email, department, password, first_login) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$regNo, $name, $phone, $email, $dpcDepartment, $initial_password, 1]); // Set first_login to 1
            }
        }

        fclose($handle);
        echo "Students uploaded successfully!";
        header("Refresh:1");
    }
}

// Handle form submission to delete a student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_student'])) {
    $studentId = $_POST['student_id'];

    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$studentId]);

    echo "Student deleted successfully!";
    header("Refresh:1");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Style for the Edit and Delete buttons */
        .action-buttons {
            display: flex;
            gap: 5px; /* Space between buttons */
        }

        .action-buttons a, .action-buttons button {
            padding: 5px 10px; /* Smaller padding */
            font-size: 14px; /* Smaller font size */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .action-buttons a {
            background-color: #4CAF50; /* Green for Edit */
            color: white;
        }

        .action-buttons a:hover {
            background-color: #45a049;
        }

        .action-buttons button {
            background-color: #f44336; /* Red for Delete */
            color: white;
        }

        .action-buttons button:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Manage Students</h1>

        <!-- Add New Student Form -->
        <h2>Add New Student</h2>
        <form method="POST">
            <input type="text" name="reg_no" placeholder="Registration Number" required>
            <input type="text" name="name" placeholder="Name" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit" name="add_student">Add Student</button>
        </form>

        <!-- Upload Students in Batches -->
        <h2>Upload Students (CSV)</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit" name="upload_students">Upload CSV</button>
        </form>

        <!-- Existing Students Table -->
        <h2>Existing Students (Department: <?php echo $dpcDepartment; ?>)</h2>
        <table>
            <tr>
                <th>Registration Number</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo $student['reg_no']; ?></td>
                    <td><?php echo $student['name']; ?></td>
                    <td><?php echo $student['phone']; ?></td>
                    <td><?php echo $student['email']; ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="dpc_edit_student.php?id=<?php echo $student['id']; ?>">Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                <button type="submit" name="delete_student" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>