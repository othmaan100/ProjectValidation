<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Topics Validation System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="../assets/js/scripts.js" defer></script>
    <style>
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 5px;
            width: 300px;
            text-align: center;
        }

        .modal-content button {
            background-color: #333;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-content button:hover {
            background-color: #555;
        }
    </style>
    <script>
        // Function to open the message modal
        function showMessageModal(message) {
            document.getElementById('messageText').innerText = message;
            document.getElementById('messageModal').style.display = 'block';
        }

        // Function to close the message modal
        function closeMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
        }
    </script>
</head>
<body>
    <header>
        <h1>Project Topics Validation System</h1>
    </header>
    <nav>
        <!-- Add the "Home" link here -->
        <a href="index.php">Home</a>

        <?php if (isset($_SESSION['role'])): ?>
            <?php if ($_SESSION['role'] === 'fpc'): ?>
                <a href="fpc_dashboard.php">Dashboard</a>
                <a href="fpc_manage_dpc.php">Manage DPC</a>
                <a href="fpc_manage_topics.php">Manage Topics</a>
            <?php elseif ($_SESSION['role'] === 'dpc'): ?>
                <a href="dpc_dashboard.php">Dashboard</a>
                <a href="dpc_manage_students.php">Manage Students</a>
                <a href="dpc_topic_validation.php">Validate Topics</a>
                <a href="dpc_change_password.php">Change Password</a>
            <?php elseif ($_SESSION['role'] === 'sup'): ?>
                <a href="sup_dashboard.php">Dashboard</a>
                <a href="sup_change_password.php">Change Password</a>
            <?php elseif ($_SESSION['role'] === 'stu'): ?>
                <a href="stu_dashboard.php">Dashboard</a>
                <a href="stu_submit_topic.php">Submit Topic</a>
                <a href="stu_view_status.php">View Status</a>
                <a href="stu_change_password.php">Change Password</a>
            <?php endif; ?>
            <a href="../logout.php">Logout</a>
        <?php endif; ?>
    </nav>

    <!-- Modal for Pop-Up Messages -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span id="messageText"></span>
            <button onclick="closeMessageModal()">OK</button>
        </div>
    </div>

    <div class="container">