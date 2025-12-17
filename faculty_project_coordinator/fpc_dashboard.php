<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

// Check if the user is logged in as FPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'fpc') {
    header("Location: __DIR__ . '/../index.php");
    exit();
}

// Fetch statistics
$totalDPCs = 0;
$activeDPCs = 0;
$totalTopics = 0;
$pendingTopics = 0;
$approvedTopics = 0;
$totalDepartments = 0;

try {
    // Count total DPCs
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'dpc'");
    $stmt->execute();
    $totalDPCs = $stmt->fetchColumn();
    
    // Count active DPCs
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'dpc' AND is_active = 1");
    $stmt->execute();
    $activeDPCs = $stmt->fetchColumn();
    
    // Count departments
    $stmt = $conn->prepare("SELECT COUNT(*) FROM departments");
    $stmt->execute();
    $totalDepartments = $stmt->fetchColumn();
    
    // Try to count topics (table might not exist)
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM topics");
        $stmt->execute();
        $totalTopics = $stmt->fetchColumn();
        
        // Count pending topics
        $stmt = $conn->prepare("SELECT COUNT(*) FROM topics WHERE status = 'pending'");
        $stmt->execute();
        $pendingTopics = $stmt->fetchColumn();
        
        // Count approved topics
        $stmt = $conn->prepare("SELECT COUNT(*) FROM topics WHERE status = 'approved'");
        $stmt->execute();
        $approvedTopics = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Topics table doesn't exist or has different structure
        $totalTopics = 0;
        $pendingTopics = 0;
        $approvedTopics = 0;
    }
    
} catch (PDOException $e) {
    // Log error for debugging
    error_log("Dashboard stats error: " . $e->getMessage());
    // Temporary debug output - remove after fixing
    echo "<!-- Database Error: " . htmlspecialchars($e->getMessage()) . " -->";
}

// Get user info
$userName = $_SESSION['name'] ?? $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FPC Dashboard - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header Section */
        .dashboard-header {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #667eea);
            background-size: 200% 100%;
            animation: gradientShift 3s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .welcome-text {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .dashboard-title {
            color: #667eea;
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .dashboard-subtitle {
            color: #666;
            font-size: 16px;
            margin-top: 10px;
        }
        
        /* Statistics Grid */
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            flex-shrink: 0;
        }
        
        .stat-icon.purple {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-icon.pink {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-icon.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-icon.orange {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-icon.green {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }
        
        .stat-icon.teal {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
        }
        
        /* Action Cards Grid */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        
        .action-card:hover::before {
            transform: scaleX(1);
        }
        
        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        
        .card-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .action-card h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 12px;
            font-weight: 700;
        }
        
        .action-card p {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .button:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .button i {
            font-size: 16px;
        }
        
        /* Quick Links Section */
        .quick-links {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }
        
        .quick-links h3 {
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quick-links h3 i {
            color: #667eea;
        }
        
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            background: #f8f9ff;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .quick-link:hover {
            background: white;
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        .quick-link i {
            color: #667eea;
            font-size: 18px;
        }
        
        .quick-link span {
            font-weight: 600;
            font-size: 14px;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .action-card:nth-child(1) {
            animation-delay: 0.1s;
        }
        
        .action-card:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .action-card:nth-child(3) {
            animation-delay: 0.3s;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-title {
                font-size: 32px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .links-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    
    <div class="container">
        <!-- Header Section -->
        <div class="dashboard-header">
            <p class="welcome-text">Welcome back, <strong><?php echo htmlspecialchars($userName); ?></strong>!</p>
            <h1 class="dashboard-title"><i class="fas fa-user-tie"></i> Faculty Project Coordinator</h1>
            <p class="dashboard-subtitle">Manage and oversee all departmental project coordination activities</p>
        </div>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Total DPCs</div>
                    <div class="stat-value"><?php echo $totalDPCs; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Active DPCs</div>
                    <div class="stat-value"><?php echo $activeDPCs; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pink">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Total Topics</div>
                    <div class="stat-value"><?php echo $totalTopics; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Pending Topics</div>
                    <div class="stat-value"><?php echo $pendingTopics; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Approved Topics</div>
                    <div class="stat-value"><?php echo $approvedTopics; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon teal">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Departments</div>
                    <div class="stat-value"><?php echo $totalDepartments; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Main Action Cards -->
        <div class="actions-grid">
            <div class="action-card fade-in-up">
                <div class="card-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h2>Manage DPCs</h2>
                <p>Create, edit, or delete Departmental Project Coordinators. Oversee department-level coordination and manage user accounts.</p>
                <a href="fpc_manage_dpc.php" class="button">
                    <i class="fas fa-arrow-right"></i>
                    Manage DPCs
                </a>
            </div>
            
            <div class="action-card fade-in-up">
                <div class="card-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h2>Upload Past Projects</h2>
                <p>Upload historical project topics and their details to build a comprehensive database for similarity checking.</p>
                <a href="fpc_upload_past_projects.php" class="button">
                    <i class="fas fa-arrow-right"></i>
                    Upload Projects
                </a>
            </div>
            
            <div class="action-card fade-in-up">
                <div class="card-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <h2>Manage Topics</h2>
                <p>View and manage all project topics across departments. Monitor validation status and oversee the approval process.</p>
                <a href="fpc_manage_topics.php" class="button">
                    <i class="fas fa-arrow-right"></i>
                    Manage Topics
                </a>
            </div>
        </div>
        
        <!-- Quick Links Section -->
        <div class="quick-links">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="links-grid">
                <a href="fpc_manage_dpc.php" class="quick-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New DPC</span>
                </a>
                <a href="fpc_manage_topics.php" class="quick-link">
                    <i class="fas fa-search"></i>
                    <span>Search Topics</span>
                </a>
                <a href="fpc_upload_past_projects.php" class="quick-link">
                    <i class="fas fa-upload"></i>
                    <span>Upload Data</span>
                </a>
                <a href="../logout.php" class="quick-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    
    <?php include_once __DIR__ .'/../includes/footer.php'; ?>
</body>
</html>