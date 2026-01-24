<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lib') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$search = $_GET['search'] ?? '';
$session_filter = $_GET['session'] ?? '';

// Fetch all projects (Current Approved + Past)
// We'll use a combination of queries or a single one if we can harmonize them
$projects = [];

try {
    // Current Approved Projects
    $sql1 = "SELECT pt.id, pt.topic, pt.student_id as reg_no, pt.student_name, pt.session, pt.pdf_path, 'Active' as type, f.faculty as faculty_name
             FROM project_topics pt
             JOIN students s ON pt.student_id = s.id
             JOIN faculty f ON s.faculty_id = f.id
             WHERE pt.status = 'approved'";
    if ($search) $sql1 .= " AND (pt.topic LIKE :s OR pt.student_name LIKE :s OR pt.student_id LIKE :s)";
    if ($session_filter) $sql1 .= " AND pt.session = :sess";
    
    // Past Projects
    $sql2 = "SELECT pp.id, pp.topic, pp.reg_no, pp.student_name, pp.session, pp.pdf_path, 'Archive' as type, f.faculty as faculty_name
             FROM past_projects pp
             JOIN faculty f ON pp.faculty_id = f.id
             WHERE 1=1";
    if ($search) $sql2 .= " AND (pp.topic LIKE :s OR pp.student_name LIKE :s OR pp.reg_no LIKE :s)";
    if ($session_filter) $sql2 .= " AND pp.session = :sess";

    $stmt1 = $conn->prepare($sql1);
    $stmt2 = $conn->prepare($sql2);
    
    if ($search) {
        $stmt1->bindValue(':s', "%$search%");
        $stmt2->bindValue(':s', "%$search%");
    }
    if ($session_filter) {
        $stmt1->bindValue(':sess', $session_filter);
        $stmt2->bindValue(':sess', $session_filter);
    }
    
    $stmt1->execute();
    $active = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt2->execute();
    $archive = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    $projects = array_merge($active, $archive);
    
    // Sorting by session desc
    usort($projects, function($a, $b) {
        return strcmp($b['session'], $a['session']);
    });

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Get sessions for filter
$sessions = $conn->query("SELECT DISTINCT session FROM (SELECT session FROM project_topics UNION SELECT session FROM past_projects) as combined ORDER BY session DESC")->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Manager - School Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #5b21b6; --bg: #f8fafc; --text: #0f172a; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .filters { display: flex; gap: 15px; margin-bottom: 25px; }
        .form-control { padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-family: inherit; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f1f5f9; color: #64748b; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #e2e8f0; }
        
        .badge { padding: 4px 10px; border-radius: 50px; font-size: 12px; font-weight: 600; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-archive { background: #f1f5f9; color: #475569; }
        
        .btn-pdf { color: #dc2626; font-size: 20px; }
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <a href="lib_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="header">
            <div>
                <h1><i class="fas fa-copy"></i> Global Project Repository</h1>
                <p style="color: #64748b;">A unified view of all student projects across the university.</p>
            </div>
        </div>

        <div class="card">
            <form class="filters">
                <input type="text" name="search" class="form-control" placeholder="Search topic or student..." value="<?= htmlspecialchars($search) ?>" style="flex: 2;">
                <select name="session" class="form-control" style="flex: 1;">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s ?>" <?= $session_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn" style="background: var(--primary); color: white; border-radius: 12px; padding: 0 25px;">Filter</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Project Topic</th>
                        <th>Student / Reg No</th>
                        <th>Faculty</th>
                        <th>Session</th>
                        <th>Documentation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 50px;">No projects found matching your criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($projects as $p): ?>
                            <tr>
                                <td><span class="badge badge-<?= strtolower($p['type']) ?>"><?= $p['type'] ?></span></td>
                                <td style="max-width: 400px; font-weight: 600;"><?= htmlspecialchars($p['topic']) ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($p['student_name']) ?></div>
                                    <div style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($p['reg_no']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($p['faculty_name']) ?></td>
                                <td><span style="font-weight: 700;"><?= htmlspecialchars($p['session']) ?></span></td>
                                <td>
                                    <?php if ($p['pdf_path']): ?>
                                        <a href="<?= PROJECT_ROOT . $p['pdf_path'] ?>" target="_blank" class="btn-pdf"><i class="fas fa-file-pdf"></i></a>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-size: 20px;"><i class="fas fa-file-pdf"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
