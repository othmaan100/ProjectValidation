<?php
include_once __DIR__ .'/../includes/auth.php';
include_once __DIR__ .'/../includes/db.php';

// Redirect if user is not DPC
if ($_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Fetch DPC Info and Department
$stmt = $conn->prepare("SELECT u.*, d.department_name 
                        FROM users u 
                        LEFT JOIN departments d ON u.department = d.id 
                        WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$dept_id = $user['department'];
$dept_name = $user['department_name'] ?? "Unknown Department";
$userName = $user['name'] ?? $user['username'];

// Statistics
// 1. Total Students in Department
$stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE department = ?");
$stmt->execute([$dept_id]);
$total_students = $stmt->fetchColumn();

// 2. Count topics within this department (using join)
$stmt = $conn->prepare("SELECT COUNT(*) FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE s.department = ?");
$stmt->execute([$dept_id]);
$total_topics = $stmt->fetchColumn();

// 3. Pending topics in department
$stmt = $conn->prepare("SELECT COUNT(*) FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE s.department = ? AND pt.status = 'pending'");
$stmt->execute([$dept_id]);
$pending_topics = $stmt->fetchColumn();

// 4. Approved topics in department
$stmt = $conn->prepare("SELECT COUNT(*) FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE s.department = ? AND pt.status = 'approved'");
$stmt->execute([$dept_id]);
$approved_topics = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPC Dashboard - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 40px;
            border-radius: 20px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .hero h1 { font-size: 32px; margin-bottom: 10px; }
        .hero p { font-size: 18px; opacity: 0.9; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
            transition: transform 0.3s;
        }

        .stat-card:hover { transform: translateY(-5px); }

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

        .icon-blue { background: #4e73df; }
        .icon-yellow { background: #f6c23e; }
        .icon-green { background: #1cc88a; }
        .icon-purple { background: #6f42c1; }

        .stat-info h3 { font-size: 28px; color: #333; margin-bottom: 2px; }
        .stat-info p { font-size: 14px; color: #777; font-weight: 600; text-transform: uppercase; }

        /* Action Grid */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .action-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 15px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s;
        }

        .action-card:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .action-card i {
            font-size: 40px;
            color: #4e73df;
            margin-bottom: 10px;
        }

        .action-card h2 { font-size: 22px; color: #2c3e50; }
        .action-card p { font-size: 15px; color: #666; line-height: 1.6; }

        .action-btn {
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .action-btn:hover { background: #2e59d9; }

        @media (max-width: 768px) {
            .hero { padding: 30px 20px; }
            .hero h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>

    <div class="dashboard-container">
        <!-- Hero Section -->
        <div class="hero">
            <h1>Welcome back, <?php echo htmlspecialchars($userName); ?></h1>
            <p>Departmental Project Coordinator | <strong><?php echo htmlspecialchars($dept_name); ?></strong></p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-yellow">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending_topics; ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $approved_topics; ?></h3>
                    <p>Approved Topics</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-purple">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_topics; ?></h3>
                    <p>Total Submissions</p>
                </div>
            </div>
        </div>

        <!-- Main Actions -->
        <div class="action-grid">
            <div class="action-card">
                <i class="fas fa-tasks"></i>
                <h2>Validate Topics</h2>
                <p>Review student topic submissions, perform similarity checks against past projects, and approve or request revisions.</p>
                <a href="dpc_topic_validation.php" class="action-btn">
                    Launch Validation Page <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="action-card">
                <i class="fas fa-user-friends"></i>
                <h2>Manage Students</h2>
                <p>View and manage all students in your department. Register new students or update their profile information.</p>
                <a href="dpc_manage_students.php" class="action-btn">
                    Manage Students <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="action-card">
                <i class="fas fa-user-tie"></i>
                <h2>Manage Supervisors</h2>
                <p>Register and manage faculty members as project supervisors. Support for batch uploads and staff load monitoring.</p>
                <a href="dpc_manage_supervisors.php" class="action-btn">
                    Manage Supervisors <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="action-card">
                <i class="fas fa-sitemap"></i>
                <h2>Assign Supervisors</h2>
                <p>Efficiently allocate project supervisors to students using either automatic or manual assignment tools.</p>
                <a href="dpc_assign_supervisors.php" class="action-btn">
                    Assign Supervisors <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="action-card">
                <i class="fas fa-calendar-alt"></i>
                <h2>Submission Schedule</h2>
                <p>Define the start and end dates for project topic submissions. Control when students can access the submission portal.</p>
                <a href="dpc_submission_schedule.php" class="action-btn" style="background: #6f42c1;">
                    Manage Schedule <i class="fas fa-clock"></i>
                </a>
            </div>

            <div class="action-card">
                <i class="fas fa-user-shield"></i>
                <h2>Security Settings</h2>
                <p>Maintain your account security by updating your password regularly.</p>
                <a href="dpc_change_password.php" class="action-btn" style="background: #e74a3b;">
                    Change Password <i class="fas fa-key"></i>
                </a>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ .'/../includes/footer.php'; ?>
</body>
</html>

