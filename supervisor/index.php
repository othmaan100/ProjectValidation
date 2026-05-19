<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$supervisor_id = $_SESSION['user_id'];

// Get Supervisor name
$stmt = $conn->prepare("SELECT name FROM supervisors WHERE id = ?");
$stmt->execute([$supervisor_id]);
$sup_name = $stmt->fetchColumn() ?: "Supervisor";

// Use current session from global settings
$active_session = $current_session;

// Statistics
// 1. Total Allocated Students
$stmt = $conn->prepare("SELECT COUNT(*) FROM supervision WHERE supervisor_id = ? AND status = 'active'");
$stmt->execute([$supervisor_id]);
$total_students = $stmt->fetchColumn();

// 2. Pending Topic Reviews
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT s.id) 
    FROM students s
    JOIN supervision sp ON s.id = sp.student_id
    JOIN project_topics pt ON s.id = pt.student_id
    WHERE sp.supervisor_id = ? AND pt.status = 'pending' AND sp.status = 'active'
");
$stmt->execute([$supervisor_id]);
$pending_reviews = $stmt->fetchColumn();

// 3. Approved Projects
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM supervision sp
    JOIN project_topics pt ON sp.student_id = pt.student_id
    WHERE sp.supervisor_id = ? AND pt.status = 'approved' AND sp.status = 'active'
");
$stmt->execute([$supervisor_id]);
$approved_projects = $stmt->fetchColumn();

// 4. Panel Assessments Needed
$stmt = $conn->prepare("
    SELECT COUNT(spa.student_id)
    FROM student_panel_assignments spa
    JOIN panel_members pm ON spa.panel_id = pm.panel_id
    LEFT JOIN defense_scores ds ON spa.student_id = ds.student_id AND pm.supervisor_id = ds.supervisor_id AND spa.panel_id = ds.panel_id
    WHERE pm.supervisor_id = ? AND ds.score IS NULL
");
$stmt->execute([$supervisor_id]);
$pending_assessments = $stmt->fetchColumn();

// 5. Project Reports Pending Approval
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM supervision sp
    JOIN project_topics pt ON sp.student_id = pt.student_id
    WHERE sp.supervisor_id = ? AND pt.report_status = 'pending' AND sp.status = 'active'
");
$stmt->execute([$supervisor_id]);
$pending_reports = $stmt->fetchColumn();

// 6. Fetch student defense results summary for my students
$results_stmt = $conn->prepare("
    SELECT s.name, s.reg_no,
           AVG(CASE WHEN dp.panel_type = 'proposal' THEN ds.score END) as proposal_score,
           AVG(CASE WHEN dp.panel_type = 'internal' THEN ds.score END) as internal_score,
           AVG(CASE WHEN dp.panel_type = 'external' THEN ds.score END) as external_score,
           sa.score as sup_score
    FROM students s
    JOIN supervision sp ON s.id = sp.student_id
    LEFT JOIN defense_scores ds ON s.id = ds.student_id
    LEFT JOIN defense_panels dp ON ds.panel_id = dp.id
    LEFT JOIN supervisor_assessments sa ON s.id = sa.student_id AND sa.supervisor_id = ? AND sa.academic_session = ?
    WHERE sp.supervisor_id = ? AND sp.status = 'active'
    GROUP BY s.id
    ORDER BY s.name ASC
");
$results_stmt->execute([$supervisor_id, $active_session, $supervisor_id]);
$student_results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);

$current_session = $active_session;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --success: #1cc88a; --danger: #e74a3b; --warning: #f6c23e; --info: #36b9cc; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fc; margin: 0; color: #2d3436; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        
        .hero {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            padding: 40px; border-radius: 20px; color: white; margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .hero h1 { font-size: 32px; margin-bottom: 10px; }
        .hero p { font-size: 18px; opacity: 0.9; }

        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;
        }
        .stat-card {
            background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px;
        }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .icon-blue { background: var(--primary); }
        .icon-yellow { background: var(--warning); }
        .icon-green { background: var(--success); }
        .stat-info h3 { font-size: 28px; color: #333; margin: 0; }
        .stat-info p { font-size: 14px; color: #777; margin: 0; text-transform: uppercase; font-weight: 700; }

        .section-title { font-size: 22px; color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .performance-card { background: white; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 30px; }
        .performance-table { width: 100%; border-collapse: collapse; }
        .performance-table th { text-align: left; padding: 12px; border-bottom: 2px solid #f1f2f6; color: var(--primary); font-size: 13px; text-transform: uppercase; }
        .performance-table td { padding: 15px 12px; border-bottom: 1px solid #f1f2f6; }
        .score-pill { padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; color: white; display: inline-block; min-width: 45px; text-align: center; }
        .bg-proposal { background: #36b9cc; }
        .bg-internal { background: #f6c23e; }
        .bg-external { background: #1cc88a; }
        .bg-sup { background: #4e73df; }
        .bg-none { background: #eaecf4; color: #858796; }

        .tasks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .task-card {
            background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 15px; transition: 0.3s; border: 1px solid transparent;
        }
        .task-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); border-color: var(--primary); }
        .task-card i { font-size: 40px; color: var(--primary); }
        .task-card h2 { font-size: 22px; color: #2c3e50; margin: 0; }
        .task-card p { font-size: 15px; color: #666; line-height: 1.6; margin: 0; }
        .task-btn {
            margin-top: auto; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; background: var(--primary); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; transition: 0.3s;
        }
        .task-btn:hover { background: #224abe; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <div class="hero">
            <h1>Welcome back, <?php echo htmlspecialchars($sup_name); ?></h1>
            <p>Project Supervisor | Manage your students and validate their project progress.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-user-friends"></i></div>
                <div class="stat-info"><h3><?php echo $total_students; ?></h3><p>My Students</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-yellow"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h3><?php echo $pending_reviews; ?></h3><p>Pending Topics</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-check-double"></i></div>
                <div class="stat-info"><h3><?php echo $approved_projects; ?></h3><p>Approved Projects</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--info);"><i class="fas fa-vial"></i></div>
                <div class="stat-info"><h3><?php echo $pending_assessments; ?></h3><p>Assessments Needed</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--danger);"><i class="fas fa-file-upload"></i></div>
                <div class="stat-info"><h3><?php echo $pending_reports; ?></h3><p>Pending Reports</p></div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-chart-line"></i> Current Students Performance</h2>
        <div class="performance-card">
            <?php if (empty($student_results)): ?>
                <p style="text-align: center; color: #777;">No student performance data available.</p>
            <?php else: ?>
                <table class="performance-table">
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Proposal</th>
                            <th>Internal</th>
                            <th>External</th>
                            <th>Supervisor Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($student_results as $res): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($res['name']) ?></strong><br>
                                    <small style="color: #888;"><?= htmlspecialchars($res['reg_no']) ?></small>
                                </td>
                                <td>
                                    <span class="score-pill <?= $res['proposal_score'] !== null ? 'bg-proposal' : 'bg-none' ?>">
                                        <?= $res['proposal_score'] !== null ? number_format($res['proposal_score'], 1) . '%' : 'N/A' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="score-pill <?= $res['internal_score'] !== null ? 'bg-internal' : 'bg-none' ?>">
                                        <?= $res['internal_score'] !== null ? number_format($res['internal_score'], 1) . '%' : 'N/A' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="score-pill <?= $res['external_score'] !== null ? 'bg-external' : 'bg-none' ?>">
                                        <?= $res['external_score'] !== null ? number_format($res['external_score'], 1) . '%' : 'N/A' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="score-pill <?= $res['sup_score'] !== null ? 'bg-sup' : 'bg-none' ?>">
                                        <?= $res['sup_score'] !== null ? number_format($res['sup_score'], 1) . '%' : 'N/A' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <h2 class="section-title"><i class="fas fa-tasks"></i> Supervisor Tasks</h2>
        <div class="tasks-grid">
            <div class="task-card">
                <i class="fas fa-user-edit"></i>
                <h2>Assess My Students</h2>
                <p>Grade the overall performance and effort of students under your direct supervision for this session.</p>
                <a href="sup_assess_my_students.php" class="task-btn" style="background: var(--primary);">Assess Students <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="task-card">
                <i class="fas fa-clipboard-check"></i>
                <h2>Validate Topics</h2>
                <p>Review and approve project topics submitted by your allocated students. You can approve one topic per student or request revisions.</p>
                <a href="sup_topic_validation.php" class="task-btn" style="background: var(--info);">Launch Validation <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="task-card">
                <i class="fas fa-id-card"></i>
                <h2>Allocated Students</h2>
                <p>View the list of students assigned to you and their registration details. Monitor their overall progress.</p>
                <a href="sup_view_students.php" class="task-btn" style="background: var(--info);">View Students <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="task-card">
                <i class="fas fa-users-rectangle"></i>
                <h2>Project Panels</h2>
                <p>View the defense panels you are assigned to, see fellow panel members, and assess assigned students.</p>
                <a href="sup_view_panels.php" class="task-btn" style="background: var(--warning); color: #2d3436;">View My Panels <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="task-card">
                <i class="fas fa-file-invoice"></i>
                <h2>Project Submissions</h2>
                <p>Review final PDF project reports submitted by your students. You must verify the content before final approval.</p>
                <a href="sup_manage_submissions.php" class="task-btn" style="background: var(--success);">Manage Submissions <i class="fas fa-file-export"></i></a>
            </div>
            <div class="task-card">
                <i class="fas fa-list-ol" style="color: #e67e22;"></i>
                <h2>Chapter Approvals</h2>
                <p>Monitor and approve individual chapters (Chapter 1-5) as your students complete them. Track real-time project progress.</p>
                <a href="sup_chapter_approvals.php" class="task-btn" style="background: #e67e22;">Manage Chapters <i class="fas fa-check-circle"></i></a>
            </div>
            <div class="task-card">
                <i class="fas fa-envelope-open-text" style="color: var(--primary);"></i>
                <h2>In-App Messaging</h2>
                <p>Discuss project details with your students or communicate directly with the Departmental Coordinator (DPC).</p>
                <a href="../app_messages.php" class="task-btn" style="background: var(--primary);">Open Messages <i class="fas fa-comments"></i></a>
            </div>
            <div class="task-card">
                <i class="fas fa-user-shield"></i>
                <h2>Account Settings</h2>
                <p>Manage your account security by updating your login password and staff profile settings.</p>
                <div style="display: flex; gap: 10px; margin-top: auto;">
                    <a href="sup_profile.php" class="task-btn" style="background: var(--primary); flex: 1;">Profile <i class="fas fa-user-tie"></i></a>
                    <a href="sup_change_password.php" class="task-btn" style="background: var(--danger); flex: 1;">Password <i class="fas fa-key"></i></a>
                </div>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
