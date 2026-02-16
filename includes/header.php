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
<!-- Core CSS & Icons -->
<link rel="stylesheet" href="<?= PROJECT_ROOT ?>assets/css/styles.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="<?= PROJECT_ROOT ?>assets/js/scripts.js" defer></script>

<script>
    // Global function to open the message modal
    function showMessageModal(message) {
        const modal = document.getElementById('messageModal');
        const text = document.getElementById('messageText');
        if (modal && text) {
            text.innerText = message;
            modal.style.display = 'block';
        }
    }

    // Global function to close the message modal
    function closeMessageModal() {
        const modal = document.getElementById('messageModal');
        if (modal) modal.style.display = 'none';
    }
</script>

<header>
    <h1><i class="fas fa-shield-halved" style="color: #4e73df;"></i> PV-Systems</h1>
    <?php if (isset($_SESSION['role'])): ?>
        <div class="user-badge">
            <i class="fas fa-user-circle"></i>
            <span>Role: <span style="text-transform: uppercase;"><?php echo $_SESSION['role']; ?></span></span>
        </div>
    <?php endif; ?>
</header>
    <nav>
        <?php 
            $current_page = basename($_SERVER['PHP_SELF']); 
            function isActive($page, $current) { return strpos($current, $page) !== false ? 'active' : ''; }
            
            // Get unread message count
            $unread_count = 0;
            if (isset($_SESSION['user_id'])) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
                $stmt->execute([$_SESSION['user_id']]);
                $unread_count = $stmt->fetchColumn();
            }
        ?>
        <a href="<?= PROJECT_ROOT ?>index.php" class="<?= isActive('index.php', $current_page) ?>">Home</a>

        <?php if (isset($_SESSION['role'])): ?>
            <?php if ($_SESSION['role'] === 'fpc'): ?>
                <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_manage_departments.php" class="<?= isActive('fpc_manage_departments.php', $current_page) ?>">Departments</a>
                <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_manage_dpc.php" class="<?= isActive('fpc_manage_dpc.php', $current_page) ?>">Coordinators (DPC)</a>
                <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_manage_topics.php" class="<?= isActive('fpc_manage_topics.php', $current_page) ?>">Topics</a>
                <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_view_past_projects.php" class="<?= isActive('fpc_view_past_projects.php', $current_page) ?>">Archive</a>
                <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_reports.php" class="<?= isActive('fpc_reports.php', $current_page) ?>">Analytics</a>
            <?php elseif ($_SESSION['role'] === 'dpc'): ?>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_manage_students.php" class="<?= isActive('dpc_manage_students.php', $current_page) ?>">Students</a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_manage_supervisors.php" class="<?= isActive('dpc_manage_supervisors.php', $current_page) ?>">Supervisors</a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_assign_supervisors.php" class="<?= isActive('dpc_assign_supervisors.php', $current_page) ?>">Allocation</a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_topic_validation.php" class="<?= isActive('dpc_topic_validation.php', $current_page) ?>">Validation</a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_submission_schedule.php" class="<?= isActive('dpc_submission_schedule.php', $current_page) ?>">Schedules</a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_manage_submissions.php" class="<?= isActive('dpc_manage_submissions.php', $current_page) ?>">Submissions</a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_manage_panels.php" class="<?= isActive('dpc_manage_panels.php', $current_page) ?>">Panels</a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_view_assessments.php" class="<?= isActive('dpc_view_assessments.php', $current_page) ?>">Assessments</a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_chapter_reports.php" class="<?= isActive('dpc_chapter_reports.php', $current_page) ?>">Chapter Progress</a>
                <a href="<?= PROJECT_ROOT ?>app_messages.php" class="<?= isActive('app_messages.php', $current_page) ?>">
                    Messages <?php if($unread_count > 0): ?><span style="background: #e74a3b; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px;"><?= $unread_count ?></span><?php endif; ?>
                </a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_reports.php" class="<?= isActive('dpc_reports.php', $current_page) ?>">Reports</a>
                <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_change_password.php" class="<?= isActive('dpc_change_password.php', $current_page) ?>">Security</a>
            <?php elseif ($_SESSION['role'] === 'sup'): ?>
                <a href="<?= PROJECT_ROOT ?>supervisor/sup_view_students.php" class="<?= isActive('sup_view_students.php', $current_page) ?>">Students</a>
                <a href="<?= PROJECT_ROOT ?>supervisor/sup_topic_validation.php" class="<?= isActive('sup_topic_validation.php', $current_page) ?>">Validation</a>
                <a href="<?= PROJECT_ROOT ?>supervisor/sup_manage_submissions.php" class="<?= isActive('sup_manage_submissions.php', $current_page) ?>">Submissions</a>
                <a href="<?= PROJECT_ROOT ?>supervisor/sup_chapter_approvals.php" class="<?= isActive('sup_chapter_approvals.php', $current_page) ?>">Chapters</a>
                <a href="<?= PROJECT_ROOT ?>app_messages.php" class="<?= isActive('app_messages.php', $current_page) ?>">
                    Messages <?php if($unread_count > 0): ?><span style="background: #e74a3b; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px;"><?= $unread_count ?></span><?php endif; ?>
                </a>
                <a href="<?= PROJECT_ROOT ?>supervisor/sup_manage_panels.php" class="<?= isActive('sup_manage_panels.php', $current_page) ?>">Panels</a>
                <a href="<?= PROJECT_ROOT ?>supervisor/sup_change_password.php" class="<?= isActive('sup_change_password.php', $current_page) ?>">Security</a>
            <?php elseif ($_SESSION['role'] === 'stu'): ?>
                <a href="<?= PROJECT_ROOT ?>student/stu_submit_topic.php" class="<?= isActive('stu_submit_topic.php', $current_page) ?>">Submit Topic</a>
                <a href="<?= PROJECT_ROOT ?>student/stu_view_status.php" class="<?= isActive('stu_view_status.php', $current_page) ?>">Status</a>
                <a href="<?= PROJECT_ROOT ?>student/stu_upload_report.php" class="<?= isActive('stu_upload_report.php', $current_page) ?>">Upload Report</a>
                <a href="<?= PROJECT_ROOT ?>app_messages.php" class="<?= isActive('app_messages.php', $current_page) ?>">
                    Messages <?php if($unread_count > 0): ?><span style="background: #e74a3b; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px;"><?= $unread_count ?></span><?php endif; ?>
                </a>
                <a href="<?= PROJECT_ROOT ?>student/stu_change_password.php" class="<?= isActive('stu_change_password.php', $current_page) ?>">Security</a>
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <a href="<?= PROJECT_ROOT ?>super_admin/sa_manage_faculties.php" class="<?= isActive('sa_manage_faculties.php', $current_page) ?>">Faculties</a>
                <a href="<?= PROJECT_ROOT ?>super_admin/sa_manage_fpc.php" class="<?= isActive('sa_manage_fpc.php', $current_page) ?>">FPC Manager</a>
                <a href="<?= PROJECT_ROOT ?>super_admin/sa_reports.php" class="<?= isActive('sa_reports.php', $current_page) ?>">System Audit</a>
                <a href="<?= PROJECT_ROOT ?>super_admin/sa_settings.php" class="<?= isActive('sa_settings.php', $current_page) ?>">Settings</a>
            <?php elseif ($_SESSION['role'] === 'lib'): ?>
                <a href="<?= PROJECT_ROOT ?>library/lib_manage_projects.php" class="<?= isActive('lib_manage_projects.php', $current_page) ?>">Repository</a>
                <a href="<?= PROJECT_ROOT ?>library/lib_generate_reports.php" class="<?= isActive('lib_generate_reports.php', $current_page) ?>">Stats</a>
            <?php endif; ?>

            <a href="<?php echo PROJECT_ROOT; ?>index.php?logout=1" style="background: rgba(231, 74, 59, 0.1); color: #e74a3b;">Logout</a>
        <?php endif; ?>
    </nav>

    <!-- Modal for Pop-Up Messages -->
    <div id="messageModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span id="messageText"></span>
            <button onclick="closeMessageModal()">OK</button>
        </div>
    </div>

    <div class="container">