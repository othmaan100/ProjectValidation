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

    // Mobile Sidebar controls
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById("appSidebar");
        const toggleBtn = document.getElementById("sidebarToggleBtn");
        const closeBtn = document.getElementById("sidebarCloseBtn");
        const overlay = document.getElementById("sidebarOverlay");
        
        if (toggleBtn && sidebar && overlay) {
            toggleBtn.addEventListener("click", function() {
                sidebar.classList.add("open");
                overlay.classList.add("show");
            });
        }
        
        function closeSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.remove("open");
                overlay.classList.remove("show");
            }
        }
        
        if (closeBtn) {
            closeBtn.addEventListener("click", closeSidebar);
        }
        if (overlay) {
            overlay.addEventListener("click", closeSidebar);
        }
    });
</script>

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

<div class="app-layout">
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-halved" style="color: #3b82f6;"></i> PV-Systems</h2>
            <button class="sidebar-close" id="sidebarCloseBtn"><i class="fas fa-times"></i></button>
        </div>
        
        <?php if (isset($_SESSION['role'])): ?>
            <div class="sidebar-user">
                <i class="fas fa-user-circle"></i>
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
                    <span class="user-role"><?= strtoupper($_SESSION['role']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <nav class="sidebar-nav">
            <a href="<?= PROJECT_ROOT ?>index.php" class="<?= isActive('index.php', $current_page) ?>">
                <i class="fa-solid fa-house"></i> Home
            </a>

            <?php if (isset($_SESSION['role'])): ?>
                <?php if ($_SESSION['role'] === 'fpc'): ?>
                    <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_manage_departments.php" class="<?= isActive('fpc_manage_departments.php', $current_page) ?>">
                        <i class="fa-solid fa-building"></i> Departments
                    </a>
                    <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_manage_dpc.php" class="<?= isActive('fpc_manage_dpc.php', $current_page) ?>">
                        <i class="fa-solid fa-user-tie"></i> Coordinators (DPC)
                    </a>
                    <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_manage_hod.php" class="<?= isActive('fpc_manage_hod.php', $current_page) ?>">
                        <i class="fa-solid fa-user-gear"></i> Head of Dept (HOD)
                    </a>
                    <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_manage_topics.php" class="<?= isActive('fpc_manage_topics.php', $current_page) ?>">
                        <i class="fa-solid fa-lightbulb"></i> Topics
                    </a>
                    <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_view_past_projects.php" class="<?= isActive('fpc_view_past_projects.php', $current_page) ?>">
                        <i class="fa-solid fa-box-archive"></i> Archive
                    </a>
                    <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_reports.php" class="<?= isActive('fpc_reports.php', $current_page) ?>">
                        <i class="fa-solid fa-chart-line"></i> Analytics
                    </a>
                    <a href="<?= PROJECT_ROOT ?>faculty_project_coordinator/fpc_profile.php" class="<?= isActive('fpc_profile.php', $current_page) ?>">
                        <i class="fa-solid fa-lock"></i> Security
                    </a>
                <?php elseif ($_SESSION['role'] === 'dpc'): ?>
                    <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_manage_students.php" class="<?= isActive('dpc_manage_students.php', $current_page) ?>">
                        <i class="fa-solid fa-user-graduate"></i> Students
                    </a>
                    <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_manage_supervisors.php" class="<?= isActive('dpc_manage_supervisors.php', $current_page) ?>">
                        <i class="fa-solid fa-chalkboard-user"></i> Supervisors
                    </a>
                    <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_manage_external_examiners.php" class="<?= isActive('dpc_manage_external_examiners.php', $current_page) ?>">
                        <i class="fa-solid fa-user-check"></i> External Examiners
                    </a>
                    <div class="sidebar-dropdown">
                        <a href="#" class="sidebar-dropdown-toggle" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'flex' ? 'none' : 'flex'; return false;">
                            <i class="fa-solid fa-link"></i> Allocation & Validation <i class="fa-solid fa-chevron-down" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <div class="sidebar-dropdown-menu" style="display: none; flex-direction: column; padding-left: 20px; gap: 4px; margin-top: 4px;">
                            <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_assign_supervisors.php" class="<?= isActive('dpc_assign_supervisors.php', $current_page) ?>">
                                <i class="fa-solid fa-link"></i> Allocation
                            </a>
                            <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_topic_validation.php" class="<?= isActive('dpc_topic_validation.php', $current_page) ?>">
                                <i class="fa-solid fa-clipboard-check"></i> Validation
                            </a>
                        </div>
                    </div>
                    <div class="sidebar-dropdown">
                        <a href="#" class="sidebar-dropdown-toggle" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'flex' ? 'none' : 'flex'; return false;">
                            <i class="fa-solid fa-users-rectangle"></i> Panel <i class="fa-solid fa-chevron-down" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <div class="sidebar-dropdown-menu" style="display: none; flex-direction: column; padding-left: 20px; gap: 4px; margin-top: 4px;">
                            <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_manage_panels.php" class="<?= isActive('dpc_manage_panels.php', $current_page) ?>">
                                <i class="fa-solid fa-users-rectangle"></i> Manage Panels
                            </a>
                            <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_panel_pending_topics.php" class="<?= isActive('dpc_panel_pending_topics.php', $current_page) ?>">
                                <i class="fa-solid fa-clock-rotate-left"></i> Pending Topics
                            </a>
                            <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_panel_approved_topics.php" class="<?= isActive('dpc_panel_approved_topics.php', $current_page) ?>">
                                <i class="fa-solid fa-circle-check"></i> Approved Topics
                            </a>
                             <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_view_assessments.php" class="<?= isActive('dpc_view_assessments.php', $current_page) ?>">
                                <i class="fa-solid fa-square-poll-vertical"></i> Assessments
                            </a>
                            
                        </div>
                    </div>
                    <div class="sidebar-dropdown">
                        <a href="#" class="sidebar-dropdown-toggle" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'flex' ? 'none' : 'flex'; return false;">
                            <i class="fa-solid fa-calendar-days"></i> Schedule <i class="fa-solid fa-chevron-down" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <div class="sidebar-dropdown-menu" style="display: none; flex-direction: column; padding-left: 20px; gap: 4px; margin-top: 4px;">
                            <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_submission_schedule.php" class="<?= isActive('dpc_submission_schedule.php', $current_page) ?>">
                                <i class="fa-solid fa-calendar-days"></i> Topic Schedule
                            </a>
                            <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_report_schedule.php" class="<?= isActive('dpc_report_schedule.php', $current_page) ?>">
                                <i class="fa-solid fa-calendar-check"></i> Report Schedule
                            </a>
                        </div>
                    </div>
                    <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_manage_submissions.php" class="<?= isActive('dpc_manage_submissions.php', $current_page) ?>">
                        <i class="fa-solid fa-file-arrow-up"></i> Submissions
                    </a>

                    <div class="sidebar-dropdown">
                        <a href="#" class="sidebar-dropdown-toggle" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'flex' ? 'none' : 'flex'; return false;">
                            <i class="fa-solid fa-chart-pie"></i> Report <i class="fa-solid fa-chevron-down" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <div class="sidebar-dropdown-menu" style="display: none; flex-direction: column; padding-left: 20px; gap: 4px; margin-top: 4px;">
                            <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_reports.php" class="<?= isActive('dpc_reports.php', $current_page) ?>">
                                <i class="fa-solid fa-file-invoice"></i> Reports
                            </a>
                            <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_chapter_reports.php" class="<?= isActive('dpc_chapter_reports.php', $current_page) ?>">
                                <i class="fa-solid fa-book-open"></i> Clearance Reports
                            </a>
                           
                        </div>
                    </div>
                    <a href="<?= PROJECT_ROOT ?>app_messages.php" class="<?= isActive('app_messages.php', $current_page) ?>">
                        <i class="fa-solid fa-envelope"></i> Messages <?php if($unread_count > 0): ?><span style="background: #e74a3b; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px;"><?= $unread_count ?></span><?php endif; ?>
                    </a>

                    <a href="<?= PROJECT_ROOT ?>department_project_coordinator/dpc_change_password.php" class="<?= isActive('dpc_change_password.php', $current_page) ?>">
                        <i class="fa-solid fa-lock"></i> Security
                    </a>
                <?php elseif ($_SESSION['role'] === 'hod'): ?>
                    <a href="<?= PROJECT_ROOT ?>hod/hod_manage_students.php" class="<?= isActive('hod_manage_students.php', $current_page) ?>">
                        <i class="fa-solid fa-user-graduate"></i> Students
                    </a>
                    <a href="<?= PROJECT_ROOT ?>hod/hod_manage_supervisors.php" class="<?= isActive('hod_manage_supervisors.php', $current_page) ?>">
                        <i class="fa-solid fa-chalkboard-user"></i> Supervisors
                    </a>
                    <a href="<?= PROJECT_ROOT ?>hod/hod_manage_external_examiners.php" class="<?= isActive('hod_manage_external_examiners.php', $current_page) ?>">
                        <i class="fa-solid fa-user-check"></i> External Examiners
                    </a>
                    <div class="sidebar-dropdown">
                        <a href="#" class="sidebar-dropdown-toggle" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'flex' ? 'none' : 'flex'; return false;">
                            <i class="fa-solid fa-link"></i> Allocation & Validation <i class="fa-solid fa-chevron-down" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <div class="sidebar-dropdown-menu" style="display: none; flex-direction: column; padding-left: 20px; gap: 4px; margin-top: 4px;">
                            <a href="<?= PROJECT_ROOT ?>hod/hod_assign_supervisors.php" class="<?= isActive('hod_assign_supervisors.php', $current_page) ?>">
                                <i class="fa-solid fa-link"></i> Allocation
                            </a>
                            <a href="<?= PROJECT_ROOT ?>hod/hod_topic_validation.php" class="<?= isActive('hod_topic_validation.php', $current_page) ?>">
                                <i class="fa-solid fa-clipboard-check"></i> Validation
                            </a>
                        </div>
                    </div>
                    <div class="sidebar-dropdown">
                        <a href="#" class="sidebar-dropdown-toggle" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'flex' ? 'none' : 'flex'; return false;">
                            <i class="fa-solid fa-users-rectangle"></i> Panel <i class="fa-solid fa-chevron-down" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <div class="sidebar-dropdown-menu" style="display: none; flex-direction: column; padding-left: 20px; gap: 4px; margin-top: 4px;">
                            <a href="<?= PROJECT_ROOT ?>hod/hod_manage_panels.php" class="<?= isActive('hod_manage_panels.php', $current_page) ?>">
                                <i class="fa-solid fa-users-rectangle"></i> Manage Panels
                            </a>
                            <a href="<?= PROJECT_ROOT ?>hod/hod_panel_pending_topics.php" class="<?= isActive('hod_panel_pending_topics.php', $current_page) ?>">
                                <i class="fa-solid fa-clock-rotate-left"></i> Pending Topics
                            </a>
                            <a href="<?= PROJECT_ROOT ?>hod/hod_panel_approved_topics.php" class="<?= isActive('hod_panel_approved_topics.php', $current_page) ?>">
                                <i class="fa-solid fa-circle-check"></i> Approved Topics
                            </a>
                            <a href="<?= PROJECT_ROOT ?>hod/hod_view_assessments.php" class="<?= isActive('hod_view_assessments.php', $current_page) ?>">
                                <i class="fa-solid fa-square-poll-vertical"></i> Assessments
                            </a>
                        </div>
                    </div>
                    <div class="sidebar-dropdown">
                        <a href="#" class="sidebar-dropdown-toggle" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'flex' ? 'none' : 'flex'; return false;">
                            <i class="fa-solid fa-calendar-days"></i> Schedule <i class="fa-solid fa-chevron-down" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <div class="sidebar-dropdown-menu" style="display: none; flex-direction: column; padding-left: 20px; gap: 4px; margin-top: 4px;">
                            <a href="<?= PROJECT_ROOT ?>hod/hod_submission_schedule.php" class="<?= isActive('hod_submission_schedule.php', $current_page) ?>">
                                <i class="fa-solid fa-calendar-days"></i> Topic Schedule
                            </a>
                            <a href="<?= PROJECT_ROOT ?>hod/hod_report_schedule.php" class="<?= isActive('hod_report_schedule.php', $current_page) ?>">
                                <i class="fa-solid fa-calendar-check"></i> Report Schedule
                            </a>
                        </div>
                    </div>
                    <a href="<?= PROJECT_ROOT ?>hod/hod_manage_submissions.php" class="<?= isActive('hod_manage_submissions.php', $current_page) ?>">
                        <i class="fa-solid fa-file-arrow-up"></i> Submissions
                    </a>
                    
                    <div class="sidebar-dropdown">
                        <a href="#" class="sidebar-dropdown-toggle" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'flex' ? 'none' : 'flex'; return false;">
                            <i class="fa-solid fa-chart-pie"></i> Report <i class="fa-solid fa-chevron-down" style="margin-left:auto; font-size:12px;"></i>
                        </a>
                        <div class="sidebar-dropdown-menu" style="display: none; flex-direction: column; padding-left: 20px; gap: 4px; margin-top: 4px;">
                            <a href="<?= PROJECT_ROOT ?>hod/hod_reports.php" class="<?= isActive('hod_reports.php', $current_page) ?>">
                                <i class="fa-solid fa-file-invoice"></i> Reports
                            </a>
                            <a href="<?= PROJECT_ROOT ?>hod/hod_chapter_reports.php" class="<?= isActive('hod_chapter_reports.php', $current_page) ?>">
                                <i class="fa-solid fa-book-open"></i> Clearance Reports
                            </a>
                            
                        </div>
                    </div>
                    <a href="<?= PROJECT_ROOT ?>app_messages.php" class="<?= isActive('app_messages.php', $current_page) ?>">
                        <i class="fa-solid fa-envelope"></i> Messages <?php if($unread_count > 0): ?><span style="background: #e74a3b; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px;"><?= $unread_count ?></span><?php endif; ?>
                    </a>

                    <a href="<?= PROJECT_ROOT ?>hod/hod_change_password.php" class="<?= isActive('hod_change_password.php', $current_page) ?>">
                        <i class="fa-solid fa-lock"></i> Security
                    </a>
                <?php elseif ($_SESSION['role'] === 'sup'): ?>
                    <a href="<?= PROJECT_ROOT ?>supervisor/sup_view_students.php" class="<?= isActive('sup_view_students.php', $current_page) ?>">
                        <i class="fa-solid fa-user-graduate"></i> Students
                    </a>
                    <a href="<?= PROJECT_ROOT ?>supervisor/sup_topic_validation.php" class="<?= isActive('sup_topic_validation.php', $current_page) ?>">
                        <i class="fa-solid fa-clipboard-check"></i> Validation
                    </a>
                    <a href="<?= PROJECT_ROOT ?>supervisor/sup_manage_submissions.php" class="<?= isActive('sup_manage_submissions.php', $current_page) ?>">
                        <i class="fa-solid fa-file-arrow-up"></i> Submissions
                    </a>
                    <a href="<?= PROJECT_ROOT ?>supervisor/sup_chapter_approvals.php" class="<?= isActive('sup_chapter_approvals.php', $current_page) ?>">
                        <i class="fa-solid fa-book-open"></i> Clearances
                    </a>
                    <a href="<?= PROJECT_ROOT ?>app_messages.php" class="<?= isActive('app_messages.php', $current_page) ?>">
                        <i class="fa-solid fa-envelope"></i> Messages <?php if($unread_count > 0): ?><span style="background: #e74a3b; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px;"><?= $unread_count ?></span><?php endif; ?>
                    </a>
                    <a href="<?= PROJECT_ROOT ?>supervisor/sup_manage_panels.php" class="<?= isActive('sup_manage_panels.php', $current_page) ?>">
                        <i class="fa-solid fa-users-rectangle"></i> Panels
                    </a>
                    <a href="<?= PROJECT_ROOT ?>supervisor/sup_change_password.php" class="<?= isActive('sup_change_password.php', $current_page) ?>">
                        <i class="fa-solid fa-lock"></i> Security
                    </a>
                <?php elseif ($_SESSION['role'] === 'stu'): ?>
                    <a href="<?= PROJECT_ROOT ?>student/stu_submit_topic.php" class="<?= isActive('stu_submit_topic.php', $current_page) ?>">
                        <i class="fa-solid fa-file-signature"></i> Submit Topic
                    </a>
                    <a href="<?= PROJECT_ROOT ?>student/stu_view_status.php" class="<?= isActive('stu_view_status.php', $current_page) ?>">
                        <i class="fa-solid fa-circle-info"></i> Status
                    </a>
                    <a href="<?= PROJECT_ROOT ?>student/stu_upload_report.php" class="<?= isActive('stu_upload_report.php', $current_page) ?>">
                        <i class="fa-solid fa-upload"></i> Upload Report
                    </a>
                    <a href="<?= PROJECT_ROOT ?>app_messages.php" class="<?= isActive('app_messages.php', $current_page) ?>">
                        <i class="fa-solid fa-envelope"></i> Messages <?php if($unread_count > 0): ?><span style="background: #e74a3b; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px;"><?= $unread_count ?></span><?php endif; ?>
                    </a>
                    <a href="<?= PROJECT_ROOT ?>student/stu_change_password.php" class="<?= isActive('stu_change_password.php', $current_page) ?>">
                        <i class="fa-solid fa-lock"></i> Security
                    </a>
                <?php elseif ($_SESSION['role'] === 'ext'): ?>
                    <a href="<?= PROJECT_ROOT ?>external_examiner/index.php" class="<?= isActive('index.php', $current_page) ?>">
                        <i class="fa-solid fa-chart-pie"></i> Dashboard
                    </a>
                    <a href="<?= PROJECT_ROOT ?>app_messages.php" class="<?= isActive('app_messages.php', $current_page) ?>">
                        <i class="fa-solid fa-envelope"></i> Messages <?php if($unread_count > 0): ?><span style="background: #e74a3b; color: white; padding: 2px 6px; border-radius: 50%; font-size: 10px;"><?= $unread_count ?></span><?php endif; ?>
                    </a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?= PROJECT_ROOT ?>super_admin/sa_manage_faculties.php" class="<?= isActive('sa_manage_faculties.php', $current_page) ?>">
                        <i class="fa-solid fa-building"></i> Faculties
                    </a>
                    <a href="<?= PROJECT_ROOT ?>super_admin/sa_manage_fpc.php" class="<?= isActive('sa_manage_fpc.php', $current_page) ?>">
                        <i class="fa-solid fa-user-tie"></i> FPC Manager
                    </a>
                    <a href="<?= PROJECT_ROOT ?>super_admin/sa_reports.php" class="<?= isActive('sa_reports.php', $current_page) ?>">
                        <i class="fa-solid fa-shield-halved"></i> System Audit
                    </a>
                    <a href="<?= PROJECT_ROOT ?>super_admin/sa_settings.php" class="<?= isActive('sa_settings.php', $current_page) ?>">
                        <i class="fa-solid fa-gears"></i> Settings
                    </a>
                <?php elseif ($_SESSION['role'] === 'lib'): ?>
                    <a href="<?= PROJECT_ROOT ?>library/lib_manage_projects.php" class="<?= isActive('lib_manage_projects.php', $current_page) ?>">
                        <i class="fa-solid fa-box-archive"></i> Repository
                    </a>
                    <a href="<?= PROJECT_ROOT ?>library/lib_generate_reports.php" class="<?= isActive('lib_generate_reports.php', $current_page) ?>">
                        <i class="fa-solid fa-chart-line"></i> Stats
                    </a>
                <?php endif; ?>

                <a href="<?php echo PROJECT_ROOT; ?>index.php?logout=1" style="margin-top: auto; background: rgba(231, 74, 59, 0.1); color: #e74a3b;">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content wrapper -->
    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <button class="sidebar-toggle" id="sidebarToggleBtn"><i class="fas fa-bars"></i></button>
            <div class="top-navbar-right">
                <?php if (isset($_SESSION['role'])): ?>
                    <div class="user-badge">
                        <i class="fas fa-user-circle"></i>
                        <span>Role: <span style="text-transform: uppercase;"><?php echo $_SESSION['role']; ?></span></span>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Modal for Pop-Up Messages -->
        <div id="messageModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span id="messageText"></span>
                <button onclick="closeMessageModal()">OK</button>
            </div>
        </div>

        <div class="container">