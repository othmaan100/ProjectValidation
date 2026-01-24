<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

// Check if the user is logged in as Library Staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lib') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Fetch some statistics for Library
$stats = [
    'total_projects' => 0,
    'current_approved' => 0,
    'past_projects' => 0,
    'with_pdf' => 0
];

try {
    $stats['current_approved'] = $conn->query("SELECT COUNT(*) FROM project_topics WHERE status = 'approved'")->fetchColumn();
    $stats['past_projects'] = $conn->query("SELECT COUNT(*) FROM past_projects")->fetchColumn();
    $stats['total_projects'] = $stats['current_approved'] + $stats['past_projects'];
    
    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM project_topics WHERE pdf_path IS NOT NULL AND status = 'approved') + 
            (SELECT COUNT(*) FROM past_projects WHERE pdf_path IS NOT NULL)
    ");
    $stats['with_pdf'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Library Dashboard stats error: " . $e->getMessage());
}

$userName = $_SESSION['name'] ?? $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5b21b6;
            --primary-light: #7c3aed;
            --secondary: #0ea5e9;
            --success: #10b981;
            --accent: #f59e0b;
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-body); color: var(--text-main); }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* Header */
        .dash-header {
            background: linear-gradient(135deg, var(--primary) 0%, #2e1065 100%);
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
        .bg-violet { background: #8b5cf6; }
        .bg-blue { background: #3b82f6; }
        .bg-emerald { background: #10b981; }
        .bg-amber { background: #f59e0b; }
        
        .stat-info h3 { font-size: 28px; font-weight: 700; margin-bottom: 2px; }
        .stat-info p { font-size: 14px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Action Cards */
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
            <h1>School Library Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($userName); ?>! Manage project archives and documentation.</p>
            <i class="fas fa-book-reader"></i>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-violet"><i class="fas fa-archive"></i></div>
                <div class="stat-info"><h3><?php echo $stats['total_projects']; ?></h3><p>Total Projects</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info"><h3><?php echo $stats['current_approved']; ?></h3><p>Current Active</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-emerald"><i class="fas fa-history"></i></div>
                <div class="stat-info"><h3><?php echo $stats['past_projects']; ?></h3><p>Past Projects</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-amber"><i class="fas fa-file-pdf"></i></div>
                <div class="stat-info"><h3><?php echo $stats['with_pdf']; ?></h3><p>With PDF Copy</p></div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-bolt"></i> Library Operations</h2>
        <div class="actions-grid">
            <a href="lib_manage_projects.php" class="action-card">
                <i class="fas fa-copy main-icon"></i>
                <h2>Manage All Projects</h2>
                <p>Browse through both current active projects and historical archives. Manage documentation and accessibility.</p>
                <div class="btn-act">Open Project Manager <i class="fas fa-arrow-right"></i></div>
            </a>

            <a href="lib_generate_reports.php" class="action-card">
                <i class="fas fa-chart-bar main-icon"></i>
                <h2>Library Statistics</h2>
                <p>Generate summary reports on project distributions across faculties and academic sessions.</p>
                <div class="btn-act">View Statistics <i class="fas fa-arrow-right"></i></div>
            </a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
