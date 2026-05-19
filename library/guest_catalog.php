<?php
session_start();
include __DIR__ . '/../includes/db.php';

// Check if user is logged in as guest
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guest') {
    header("Location: ../index.php");
    exit();
}

// Dynamically determine PROJECT_ROOT if not defined
if (!defined('PROJECT_ROOT')) {
    $script_directory = str_replace('\\', '/', dirname(__DIR__));
    $document_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $base_path = str_replace($document_root, '', $script_directory);
    $base_path = '/' . ltrim($base_path, '/') . '/';
    $base_path = str_replace('//', '/', $base_path);
    define('PROJECT_ROOT', $base_path);
}

$search = $_GET['search'] ?? '';
$session_filter = $_GET['session'] ?? '';

try {
    // Fetch projects (Combine Approved and Past)
    $sql = "
        (SELECT pt.topic, pt.student_name, pt.session, pt.pdf_path, f.faculty as faculty_name
         FROM project_topics pt
         JOIN students s ON pt.student_id = s.id
         JOIN faculty f ON s.faculty_id = f.id
         WHERE pt.status = 'approved')
        UNION ALL
        (SELECT pp.topic, pp.student_name, pp.session, pp.pdf_path, f.faculty as faculty_name
         FROM past_projects pp
         JOIN faculty f ON pp.faculty_id = f.id)
        ORDER BY session DESC, topic ASC
    ";
    
    // Simple filter in PHP for simplicity or rebuild query? 
    // Let's rebuild query with filters
    $sql = "
        SELECT * FROM (
            SELECT pt.topic, pt.student_name, pt.session, pt.pdf_path, f.faculty as faculty_name
            FROM project_topics pt
            JOIN students s ON pt.student_id = s.id
            JOIN faculty f ON s.faculty_id = f.id
            WHERE pt.status = 'approved'
            UNION ALL
            SELECT pp.topic, pp.student_name, pp.session, pp.pdf_path, f.faculty as faculty_name
            FROM past_projects pp
            JOIN faculty f ON pp.faculty_id = f.id
        ) as combined
        WHERE 1=1
    ";
    
    $params = [];
    if ($search) {
        $sql .= " AND (topic LIKE :s OR student_name LIKE :s)";
        $params[':s'] = "%$search%";
    }
    if ($session_filter) {
        $sql .= " AND session = :sess";
        $params[':sess'] = $session_filter;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sessions = $conn->query("SELECT DISTINCT session FROM (SELECT session FROM project_topics UNION SELECT session FROM past_projects) as c ORDER BY session DESC")->fetchAll(PDO::FETCH_COLUMN);

    $stats = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM project_topics WHERE status = 'approved') as active_count,
            (SELECT COUNT(*) FROM past_projects) as archive_count,
            (SELECT COUNT(*) FROM (SELECT pdf_path FROM project_topics WHERE pdf_path IS NOT NULL AND status='approved' UNION ALL SELECT pdf_path FROM past_projects WHERE pdf_path IS NOT NULL) as p) as pdf_count
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $projects = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Catalog | Guest Access</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --bg: #f8fafc; --text: #1e293b; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding-bottom: 50px; }
        
        nav { background: white; padding: 20px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        nav .logo { font-weight: 700; font-size: 20px; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        nav .user-info { display: flex; align-items: center; gap: 20px; font-size: 14px; }
        .btn-logout { background: #fee2e2; color: #b91c1c; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; }

        .hero { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; padding: 60px 40px; text-align: center; }
        .hero h1 { font-size: 36px; margin-bottom: 10px; }
        .hero p { font-size: 18px; opacity: 0.9; margin-bottom: 25px; }
        .hero-stats { display: flex; justify-content: center; gap: 40px; }
        .hero-stats .stat-item { display: flex; flex-direction: column; gap: 5px; }
        .hero-stats .stat-val { font-size: 24px; font-weight: 700; color: white; }
        .hero-stats .stat-lbl { font-size: 12px; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }

        .container { max-width: 1200px; margin: -50px auto 0; padding: 0 20px; }
        
        .search-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .filters { display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; }
        .form-control { padding: 12px 16px; border: 2px solid #f1f5f9; border-radius: 12px; font-family: inherit; font-size: 15px; width: 100%; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        .btn-search { background: var(--primary); color: white; border: none; border-radius: 12px; padding: 0 30px; font-weight: 600; cursor: pointer; height: 100%; }

        .project-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        .project-card { background: white; padding: 25px; border-radius: 18px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; transition: transform 0.3s; display: flex; flex-direction: column; }
        .project-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        
        .project-card h3 { font-size: 18px; margin-bottom: 12px; line-height: 1.4; color: #0f172a; }
        .project-card p.student { color: var(--primary); font-weight: 600; font-size: 14px; margin-bottom: 5px; }
        .project-card .meta { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 20px; }
        .session-tag { font-size: 12px; background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 50px; font-weight: 700; }
        
        .btn-download { background: #10b981; color: white; padding: 10px 18px; border-radius: 10px; text-decoration: none; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .btn-download:hover { background: #059669; }
        .no-pdf { color: #94a3b8; font-size: 13px; font-style: italic; }

        .empty-state { grid-column: 1 / -1; text-align: center; padding: 80px 20px; color: #94a3b8; }
        .empty-state i { font-size: 60px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <nav>
        <div class="logo"><i class="fas fa-book-open"></i> School Library Catalog</div>
        <div class="user-info">
            <span>Welcome, <strong>Guest Student</strong></span>
            <a href="../index.php?logout=1" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout & End Session</a>
        </div>
    </nav>

    <div class="hero">
        <h1>Digital Project Repository</h1>
        <p>Browse and access approved academic projects across all departments.</p>
        <div class="hero-stats">
            <div class="stat-item"><span class="stat-val"><?= $stats['active_count'] + $stats['archive_count'] ?></span><span class="stat-lbl">Projects</span></div>
            <div class="stat-item"><span class="stat-val"><?= $stats['pdf_count'] ?></span><span class="stat-lbl">PDF Resources</span></div>
            <div class="stat-item"><span class="stat-val"><?= count($sessions) ?></span><span class="stat-lbl">Sessions</span></div>
        </div>
    </div>

    <div class="container">
        <div class="search-card">
            <form class="filters">
                <input type="text" name="search" class="form-control" placeholder="Search by project title or student name..." value="<?= htmlspecialchars($search) ?>">
                <select name="session" class="form-control">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s ?>" <?= $session_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-search">Search Projects</button>
            </form>
        </div>

        <div class="project-grid">
            <?php if (empty($projects)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h2>No projects found</h2>
                    <p>Try adjusting your search terms or session filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $p): ?>
                    <div class="project-card">
                        <p class="student"><?= htmlspecialchars($p['student_name']) ?></p>
                        <h3><?= htmlspecialchars($p['topic']) ?></h3>
                        <div style="font-size: 13px; color: #64748b; margin-bottom: 15px;"><?= htmlspecialchars($p['faculty_name']) ?></div>
                        
                        <div class="meta">
                            <span class="session-tag"><?= htmlspecialchars($p['session']) ?></span>
                            <?php if ($p['pdf_path']): ?>
                                <a href="<?= PROJECT_ROOT . $p['pdf_path'] ?>" target="_blank" class="btn-download" download>
                                    <i class="fas fa-download"></i> Download PDF
                                </a>
                            <?php else: ?>
                                <span class="no-pdf">In-Library Copy Only</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div style="text-align: center; margin-top: 50px; color: #94a3b8; font-size: 12px;">
        &copy; <?= date('Y') ?> School Library Project Repository. All Rights Reserved.
    </div>
</body>
</html>
