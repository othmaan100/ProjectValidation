<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: /projectval/");
    exit();
}

$supervisor_id = $_SESSION['user_id'];

// Get Supervisor name
$stmt = $conn->prepare("SELECT name FROM supervisors WHERE id = ?");
$stmt->execute([$supervisor_id]);
$sup_name = $stmt->fetchColumn() ?: "Supervisor";

// Statistics
// 1. Total Allocated Students
$stmt = $conn->prepare("SELECT COUNT(*) FROM supervision WHERE supervisor_id = ? AND status = 'active'");
$stmt->execute([$supervisor_id]);
$total_students = $stmt->fetchColumn();

// 2. Pending Topic Reviews (Students with topics waiting for approval)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT s.id) 
    FROM students s
    JOIN supervision sp ON s.id = sp.student_id
    JOIN project_topics pt ON s.id = pt.student_id
    WHERE sp.supervisor_id = ? AND pt.status = 'pending' AND sp.status = 'active'
");
$stmt->execute([$supervisor_id]);
$pending_reviews = $stmt->fetchColumn();

// 3. Approved Projects - Updated for decoupled schema
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM supervision sp
    JOIN project_topics pt ON sp.student_id = pt.student_id
    WHERE sp.supervisor_id = ? AND pt.status = 'approved' AND sp.status = 'active'
");
$stmt->execute([$supervisor_id]);
$approved_projects = $stmt->fetchColumn();

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
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            padding: 40px;
            border-radius: 20px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .hero h1 { font-size: 32px; margin-bottom: 10px; }
        .hero p { font-size: 18px; opacity: 0.9; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .icon-blue { background: var(--primary); }
        .icon-yellow { background: var(--warning); }
        .icon-green { background: var(--success); }
        .stat-info h3 { font-size: 28px; color: #333; margin: 0; }
        .stat-info p { font-size: 14px; color: #777; margin: 0; text-transform: uppercase; font-weight: 700; }

        /* Tasks Section */
        .section-title { font-size: 22px; color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        .task-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 15px;
            transition: 0.3s;
            border: 1px solid transparent;
        }
        .task-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); border-color: var(--primary); }
        .task-card i { font-size: 40px; color: var(--primary); }
        .task-card h2 { font-size: 22px; color: #2c3e50; margin: 0; }
        .task-card p { font-size: 15px; color: #666; line-height: 1.6; margin: 0; }
        .task-btn {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }
        .task-btn:hover { background: #224abe; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div> <!-- Close container from header -->

    <div class="dashboard-container">
        <!-- Hero Section -->
        <div class="hero">
            <h1>Welcome back, <?php echo htmlspecialchars($sup_name); ?></h1>
            <p>Project Supervisor | Manage your students and validate their project progress.</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_students; ?></h3>
                    <p>My Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-yellow">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending_reviews; ?></h3>
                    <p>Pending Topics</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $approved_projects; ?></h3>
                    <p>Approved Projects</p>
                </div>
            </div>
        </div>

        <!-- Supervisor Tasks -->
        <h2 class="section-title"><i class="fas fa-tasks"></i> Supervisor Tasks</h2>
        <div class="tasks-grid">
            <div class="task-card">
                <i class="fas fa-clipboard-check"></i>
                <h2>Validate Topics</h2>
                <p>Review and approve project topics submitted by your allocated students. You can approve one topic per student or request revisions.</p>
                <a href="sup_topic_validation.php" class="task-btn">
                    Launch Validation <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="task-card">
                <i class="fas fa-id-card"></i>
                <h2>Allocated Students</h2>
                <p>View the list of students assigned to you and their registration details. Monitor their overall progress.</p>
                <a href="sup_view_students.php" class="task-btn" style="background: var(--info);">
                    View Students <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="task-card">
                <i class="fas fa-user-shield"></i>
                <h2>Account Settings</h2>
                <p>Manage your account security by updating your login password and staff profile settings.</p>
                <a href="sup_change_password.php" class="task-btn" style="background: var(--danger);">
                    Change Password <i class="fas fa-key"></i>
                </a>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
