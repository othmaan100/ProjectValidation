<?php
if (!defined('PROJECT_ROOT')) {
    $script_directory = str_replace('\\', '/', dirname(__DIR__));
    $document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $base_path = str_replace($document_root, '', $script_directory);
    $base_path = '/' . ltrim($base_path, '/') . '/';
    $base_path = str_replace('//', '/', $base_path);
    define('PROJECT_ROOT', $base_path);
}
?>
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
                <a href="index.php">Dashboard</a>
                <a href="fpc_manage_departments.php">Manage Departments</a>
                <a href="fpc_manage_dpc.php">Manage DPC</a>
                <a href="fpc_manage_topics.php">Manage Topics</a>
                <a href="fpc_view_past_projects.php">Past Projects</a>
                <a href="fpc_reports.php">Reports</a>
            <?php elseif ($_SESSION['role'] === 'dpc'): ?>
                <a href="index.php">Dashboard</a>
                <a href="dpc_manage_students.php">Manage Students</a>
                <a href="dpc_manage_supervisors.php">Manage Supervisors</a>
                <a href="dpc_assign_supervisors.php">Assign Supervisors</a>
                <a href="dpc_topic_validation.php">Validate Topics</a>
                <a href="dpc_submission_schedule.php">Submission Schedule</a>
                <a href="dpc_manage_panels.php">Manage Panels</a>
                <a href="dpc_view_assessments.php">View Assessments</a>
                <a href="dpc_reports.php">Reports</a>
                <a href="dpc_change_password.php">Change Password</a>
            <?php elseif ($_SESSION['role'] === 'sup'): ?>
                <a href="index.php">Dashboard</a>
                <a href="sup_view_students.php">My Students</a>
                <a href="sup_topic_validation.php">Validate Topics</a>
                <a href="sup_manage_panels.php">Defense Panels</a>
                <a href="sup_change_password.php">Change Password</a>
            <?php elseif ($_SESSION['role'] === 'stu'): ?>
                <a href="index.php">Dashboard</a>
                <a href="stu_submit_topic.php">Submit Topic</a>
                <a href="stu_view_status.php">View Status</a>
                <a href="stu_upload_report.php">Upload Report</a>
                <a href="stu_change_password.php">Change Password</a>
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <a href="index.php">Dashboard</a>
                <a href="sa_manage_faculties.php">Manage Faculty</a>
                <a href="sa_manage_fpc.php">Manage FPC</a>
                <a href="sa_reports.php">Reports</a>
            <?php elseif ($_SESSION['role'] === 'lib'): ?>
                <a href="index.php">Dashboard</a>
                <a href="lib_manage_projects.php">Project Repository</a>
                <a href="lib_generate_reports.php">Statistics</a>
            <?php endif; ?>
            <a href="<?php echo PROJECT_ROOT; ?>index.php?logout=1">Logout</a>
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