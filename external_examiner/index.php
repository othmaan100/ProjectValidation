<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Check if role is external examiner
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ext') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// Fetch examiner info
$stmt = $conn->prepare("SELECT * FROM external_examiners WHERE id = ?");
$stmt->execute([$user_id]);
$examiner = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch assigned panels
$stmt = $conn->prepare("
    SELECT dp.*, 
    (SELECT COUNT(*) FROM student_panel_assignments WHERE panel_id = dp.id) as student_count
    FROM defense_panels dp
    JOIN panel_members pm ON dp.id = pm.panel_id
    WHERE pm.supervisor_id = ? AND dp.panel_type = 'external'
");
$stmt->execute([$user_id]);
$panels = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>External Examiner Dashboard - Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --secondary: #ec4899; --glass: rgba(255, 255, 255, 0.95); --text-main: #1e293b; --text-muted: #64748b; }
        * { margin:0; padding:0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: #f1f5f9; color: var(--text-main); }
        .dashboard-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .welcome-card { background: linear-gradient(135deg, var(--primary) 0%, #312e81 100%); color: white; padding: 40px; border-radius: 30px; margin-bottom: 30px; box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2); }
        .welcome-card h1 { font-size: 32px; margin-bottom: 10px; }
        .welcome-card p { opacity: 0.9; font-size: 18px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .panel-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; transition: transform 0.3s; }
        .panel-card:hover { transform: translateY(-5px); }
        .panel-name { font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 15px; }
        .panel-info { display: flex; align-items: center; gap: 10px; color: var(--text-muted); font-size: 15px; margin-bottom: 20px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; background: var(--primary); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn:hover { background: #4338ca; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.2); }
        .stats-badge { background: #e0e7ff; color: var(--primary); padding: 5px 15px; border-radius: 50px; font-size: 13px; font-weight: 700; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="dashboard-container">
        <div class="welcome-card">
            <p>External Assessor Portal</p>
            <h1>Welcome, <?= htmlspecialchars($examiner['name'] ?? $name) ?></h1>
            <p><i class="fas fa-university"></i> <?= htmlspecialchars($examiner['affiliation'] ?: 'Visiting Examiner') ?></p>
        </div>

        <section>
            <h2 style="margin-bottom: 20px;">Your Assigned Panels</h2>
            <?php if (empty($panels)): ?>
                <div style="background: white; padding: 60px; border-radius: 20px; text-align: center; color: var(--text-muted);">
                    <i class="fas fa-folder-open" style="font-size: 50px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <p>No panels have been assigned to you yet. Please check back later.</p>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($panels as $panel): ?>
                        <div class="panel-card">
                            <div class="panel-name"><?= htmlspecialchars($panel['panel_name']) ?></div>
                            <div class="panel-info">
                                <i class="fas fa-graduation-cap"></i> External Project Defense
                                <span class="stats-badge"><?= $panel['student_count'] ?> Students</span>
                            </div>
                            <a href="ext_score_students.php?panel_id=<?= $panel['id'] ?>" class="btn">
                                <i class="fas fa-pen-nib"></i> Grade Students
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Mobile Nav padding -->
    <div style="height: 50px;"></div>
    
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
