<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

// Check if the user is logged in as Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /projectval/");
    exit();
}

$userName = $_SESSION['name'] ?? $_SESSION['username'];

// Fetch statistics
$stats = [
    'faculties' => 0,
    'fpc' => 0,
    'projects' => 0,
    'students' => 0,
    'approved' => 0,
    'pending' => 0
];

try {
    // Count Faculties
    $stats['faculties'] = $conn->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    
    // Count FPCs
    $stats['fpc'] = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'fpc'")->fetchColumn();
    
    // Count Projects (Project Topics + Past Projects)
    $stmt1 = $conn->query("SELECT COUNT(*) FROM project_topics");
    $stmt2 = $conn->query("SELECT COUNT(*) FROM past_projects");
    $stats['projects'] = $stmt1->fetchColumn() + $stmt2->fetchColumn();
    
    // Count Approved/Pending
    $stats['approved'] = $conn->query("SELECT COUNT(*) FROM project_topics WHERE status = 'approved'")->fetchColumn();
    $stats['pending'] = $conn->query("SELECT COUNT(*) FROM project_topics WHERE status = 'pending'")->fetchColumn();
    
    // Count Students
    $stats['students'] = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
} catch (PDOException $e) {
    error_log("SA Dashboard stats error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --primary-light: #6366f1;
            --secondary: #db2777;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --bg-body: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-body); color: var(--text-main); }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* Header */
        .dash-header {
            background: linear-gradient(135deg, var(--primary) 0%, #1e1b4b 100%);
            padding: 40px;
            border-radius: 24px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        .dash-header h1 { font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        .dash-header p { font-size: 18px; opacity: 0.9; }
        .dash-header i { position: absolute; right: 40px; top: 50%; transform: translateY(-50%); font-size: 80px; opacity: 0.1; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon {
            width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white;
        }
        .bg-indigo { background: #4f46e5; }
        .bg-pink { background: #db2777; }
        .bg-emerald { background: #059669; }
        .bg-amber { background: #d97706; }
        
        .stat-info h3 { font-size: 28px; font-weight: 700; margin-bottom: 2px; }
        .stat-info p { font-size: 14px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Quick Actions */
        .section-title { font-size: 22px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        .action-card {
            background: var(--card-bg);
            padding: 32px;
            border-radius: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .action-card:hover {
            border-color: var(--primary-light);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }
        .action-card i.main-icon { font-size: 48px; color: var(--primary-light); }
        .action-card h2 { font-size: 24px; font-weight: 700; }
        .action-card p { font-size: 16px; color: var(--text-muted); line-height: 1.5; }
        .action-card .btn-act {
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            font-weight: 700;
            font-size: 16px;
        }

    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="dash-header">
            <h1>Super Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($userName); ?>! System-wide overview and management.</p>
            <i class="fas fa-user-shield"></i>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-indigo"><i class="fas fa-university"></i></div>
                <div class="stat-info"><h3><?php echo $stats['faculties']; ?></h3><p>Faculties</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-pink"><i class="fas fa-user-tie"></i></div>
                <div class="stat-info"><h3><?php echo $stats['fpc']; ?></h3><p>COORDINATORS</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-emerald"><i class="fas fa-file-contract"></i></div>
                <div class="stat-info"><h3><?php echo $stats['projects']; ?></h3><p>Total Projects</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-amber"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-info"><h3><?php echo $stats['students']; ?></h3><p>Total Students</p></div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-bolt"></i> Management Controls</h2>
        <div class="actions-grid">
            <a href="sa_manage_faculties.php" class="action-card">
                <i class="fas fa-university main-icon"></i>
                <h2>Manage Faculties</h2>
                <p>Register and manage university faculties. These faculties are required when setting up new Faculty Project Coordinators.</p>
                <div class="btn-act">Configure Faculties <i class="fas fa-arrow-right"></i></div>
            </a>

            <a href="sa_manage_fpc.php" class="action-card">
                <i class="fas fa-users-cog main-icon"></i>
                <h2>Manage Coordinators</h2>
                <p>Assign and manage Faculty Project Coordinators (FPCs) for each faculty. Create, update, or deactivate FPC accounts.</p>
                <div class="btn-act">Access Management <i class="fas fa-arrow-right"></i></div>
            </a>

            <a href="sa_reports.php" class="action-card">
                <i class="fas fa-chart-pie main-icon"></i>
                <h2>Global Project Reports</h2>
                <p>Generate comprehensive reports on project status across all faculties and departments. Export data for academic audits.</p>
                <div class="btn-act">View Reports <i class="fas fa-arrow-right"></i></div>
            </a>

            <a href="sa_settings.php" class="action-card">
                <i class="fas fa-cogs main-icon"></i>
                <h2>General System Settings</h2>
                <p>Manage system-wide configuration, including session dates, global academic sessions, and user role permissions.</p>
                <div class="btn-act">Open Settings <i class="fas fa-arrow-right"></i></div>
            </a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
