<?php
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: /projectval/");
    exit();
}

// Fetch the DPC's department
$dpcId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT department FROM users WHERE id = ?");
$stmt->execute([$dpcId]);
$dpcDepartment = $stmt->fetchColumn();

// Fetch approved topics in the DPC's department
$stmt = $conn->prepare("SELECT pt.id AS topic_id, pt.topic, s.reg_no, s.name AS student_name 
                        FROM project_topics pt 
                        JOIN students s ON pt.student_reg_no = s.reg_no 
                        WHERE pt.status = 'approved' AND s.department = ?");
$stmt->execute([$dpcDepartment]);
$topics = $stmt->fetchAll();

// Fetch supervisors in the DPC's department from the supervisors table
$stmt = $conn->prepare("SELECT id, name, email, staff_no, phone FROM supervisors WHERE department = ?");
$stmt->execute([$dpcDepartment]);
$supervisors = $stmt->fetchAll();

// Handle form submission to create a new supervisor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_supervisor'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $staffNo = trim($_POST['staff_no']);
    $phone = trim($_POST['phone']);

    if (empty($name) || empty($email) || empty($staffNo) || empty($phone)) {
        $error_message = "Name, email, staff number, and phone are required.";
    } else {
        // Check if staff number already exists
        $stmt = $conn->prepare("SELECT id FROM supervisors WHERE staff_no = ?");
        $stmt->execute([$staffNo]);
        if ($stmt->fetchColumn()) {
            $error_message = "Staff number already exists.";
        } else {
            // Generate a unique password
            $password = bin2hex(random_bytes(2)); // Generates a random 8-character password

            // Insert the new supervisor into the supervisors table
            $stmt = $conn->prepare("INSERT INTO supervisors (name, email, department, staff_no, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $dpcDepartment, $staffNo, $phone, password_hash($password, PASSWORD_BCRYPT)]);

            // Store credentials in session to display in the modal
            $_SESSION['supervisor_credentials'] = [
                'staff_no' => $staffNo,
                'password' => $password
            ];

            header("Location: dpc_assign_supervisors.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Supervisors</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .action-buttons a {
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
        }

        .action-buttons a.edit {
            background-color: #4CAF50;
        }

        .action-buttons a.delete {
            background-color: #f44336;
        }

        .action-buttons a:hover {
            opacity: 0.8;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            text-align: center;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Manage Supervisors</h1>

        <!-- Create Supervisor Form -->
        <h2>Create New Supervisor</h2>
        <form method="POST">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required>
            <br>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
            <br>
            <label for="staff_no">Staff Number:</label>
            <input type="text" name="staff_no" id="staff_no" required>
            <br>
            <label for="phone">Phone:</label>
            <input type="text" name="phone" id="phone" required>
            <br>
            <button type="submit" name="create_supervisor">Create Supervisor</button>
        </form>

        <!-- List of Supervisors -->
        <h2>Supervisors in Your Department</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Staff Number</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($supervisors as $supervisor): ?>
                <tr>
                    <td><?php echo htmlspecialchars($supervisor['name']); ?></td>
                    <td><?php echo htmlspecialchars($supervisor['email']); ?></td>
                    <td><?php echo htmlspecialchars($supervisor['staff_no']); ?></td>
                    <td><?php echo htmlspecialchars($supervisor['phone']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <!-- Edit Link -->
                            <a href="dpc_edit_supervisor.php?id=<?php echo $supervisor['id']; ?>" class="edit">Edit</a>
                            <!-- Delete Link -->
                            <a href="dpc_delete_supervisor.php?id=<?php echo $supervisor['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this supervisor?')">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Assign Supervisors to Topics -->
        <h2>Assign Supervisors to Approved Topics</h2>
        <table>
            <tr>
                <th>Student Name</th>
                <th>Topic</th>
                <th>Assign Supervisor</th>
            </tr>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?php echo htmlspecialchars($topic['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($topic['topic']); ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="topic_id" value="<?php echo $topic['topic_id']; ?>">
                            <select name="supervisor_id" required>
                                <option value="">Select Supervisor</option>
                                <?php foreach ($supervisors as $supervisor): ?>
                                    <option value="<?php echo $supervisor['id']; ?>"><?php echo htmlspecialchars($supervisor['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_supervisor">Assign</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Modal for displaying supervisor credentials -->
    <?php if (isset($_SESSION['supervisor_credentials'])): ?>
        <div id="credentialsModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Supervisor Credentials</h2>
                <p><strong>Staff Number (Username):</strong> <?php echo $_SESSION['supervisor_credentials']['staff_no']; ?></p>
                <p><strong>Password:</strong> <?php echo $_SESSION['supervisor_credentials']['password']; ?></p>
                <button onclick="closeModal()">OK</button>
            </div>
        </div>
        <?php unset($_SESSION['supervisor_credentials']); ?>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Get the modal
        var modal = document.getElementById("credentialsModal");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the page loads, open the modal 
        window.onload = function() {
            if (modal) {
                modal.style.display = "block";
            }
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks on OK button, close the modal
        function closeModal() {
            modal.style.display = "none";
        }
    </script>
</body>
</html>
