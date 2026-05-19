<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lib') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$search = $_GET['search'] ?? '';
$session_filter = $_GET['session'] ?? '';
$pdf_filter = $_GET['pdf_status'] ?? ''; // 'yes', 'no', or empty

$projects = [];

try {
    // Current Approved Projects
    $sql1 = "SELECT pt.id, pt.topic, pt.student_id as reg_no, pt.student_name, pt.session, pt.pdf_path, 'Active' as type, f.faculty as faculty_name
             FROM project_topics pt
             JOIN students s ON pt.student_id = s.id
             JOIN faculty f ON s.faculty_id = f.id
             WHERE pt.status = 'approved'";
    
    // Past Projects
    $sql2 = "SELECT pp.id, pp.topic, pp.reg_no, pp.student_name, pp.session, pp.pdf_path, 'Archive' as type, f.faculty as faculty_name
             FROM past_projects pp
             JOIN faculty f ON pp.faculty_id = f.id
             WHERE 1=1";

    // Base Clauses
    $filter1 = "";
    $filter2 = "";
    $params = [];

    if ($search) {
        $filter1 .= " AND (pt.topic LIKE :s OR pt.student_name LIKE :s OR pt.student_id LIKE :s)";
        $filter2 .= " AND (pp.topic LIKE :s OR pp.student_name LIKE :s OR pp.reg_no LIKE :s)";
        $params[':s'] = "%$search%";
    }
    
    if ($session_filter) {
        $filter1 .= " AND pt.session = :sess";
        $filter2 .= " AND pp.session = :sess";
        $params[':sess'] = $session_filter;
    }
    
    // PDF status specific filters
    $pdf_clause1 = "";
    $pdf_clause2 = "";
    if ($pdf_filter === 'yes') {
        $pdf_clause1 = " AND (pt.pdf_path IS NOT NULL AND pt.pdf_path != '')";
        $pdf_clause2 = " AND (pp.pdf_path IS NOT NULL AND pp.pdf_path != '')";
    } elseif ($pdf_filter === 'no') {
        $pdf_clause1 = " AND (pt.pdf_path IS NULL OR pt.pdf_path = '')";
        $pdf_clause2 = " AND (pp.pdf_path IS NULL OR pp.pdf_path = '')";
    }

    $stmt1 = $conn->prepare($sql1 . $filter1 . $pdf_clause1);
    $stmt2 = $conn->prepare($sql2 . $filter2 . $pdf_clause2);
    
    foreach ($params as $k => $v) {
        $stmt1->bindValue($k, $v);
        $stmt2->bindValue($k, $v);
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

$sessions = $conn->query("SELECT DISTINCT session FROM (SELECT session FROM project_topics UNION SELECT session FROM past_projects) as combined ORDER BY session DESC")->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Project Repository - School Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #5b21b6; --bg: #f8fafc; --text: #0f172a; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 50px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .header { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .header h1 { color: var(--primary); font-size: 28px; display: flex; align-items: center; gap: 12px; }
        
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .filters { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; margin-bottom: 25px; }
        .form-control { padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 14px; }
        .btn-filter { background: var(--primary); color: white; border: none; border-radius: 12px; padding: 0 25px; font-weight: 600; cursor: pointer; }
        .btn-reset { background: #f1f5f9; color: #475569; border: none; border-radius: 12px; padding: 0 20px; text-decoration: none; display: flex; align-items: center; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; font-size: 15px; }
        
        .badge { padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-archive { background: #f1f5f9; color: #475569; }
        
        .btn-pdf { width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #fee2e2; color: #dc2626; text-decoration: none; transition: 0.3s; }
        .btn-pdf:hover { background: #dc2626; color: white; }
        .no-pdf { color: #cbd5e1; font-size: 14px; font-style: italic; display: flex; align-items: center; gap: 5px; }
        
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="header">
            <h1><i class="fas fa-archive"></i> Global Project Repository</h1>
            <p style="color: #64748b; margin-top: 5px;">Browse and filter student project topics across all years and departments.</p>
        </div>

        <div class="card">
            <form class="filters">
                <input type="text" name="search" class="form-control" placeholder="Search topic, student, or reg no..." value="<?= htmlspecialchars($search) ?>">
                
                <select name="session" class="form-control">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s ?>" <?= $session_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="pdf_status" class="form-control">
                    <option value="">Documentation (All)</option>
                    <option value="yes" <?= $pdf_filter === 'yes' ? 'selected' : '' ?>>With PDF Resources</option>
                    <option value="no" <?= $pdf_filter === 'no' ? 'selected' : '' ?>>Missing PDF</option>
                </select>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-filter">Search</button>
                    <a href="lib_manage_projects.php" class="btn-reset" title="Reset Filters"><i class="fas fa-undo"></i></a>
                </div>
            </form>

            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Type</th>
                        <th>Project Topic</th>
                        <th>Student Details</th>
                        <th>Faculty</th>
                        <th style="width: 100px;">Session</th>
                        <th style="width: 120px;">Documentation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 60px; color: #94a3b8;">
                            <i class="fas fa-folder-open" style="font-size: 40px; margin-bottom: 15px; display: block;"></i>
                            No projects found matching your filters.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($projects as $p): ?>
                            <tr>
                                <td><span class="badge badge-<?= strtolower($p['type']) ?>"><?= $p['type'] ?></span></td>
                                <td style="max-width: 450px;">
                                    <div style="font-weight: 700; color: #1e293b; line-height: 1.4;"><?= htmlspecialchars($p['topic']) ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($p['student_name']) ?></div>
                                    <div style="font-size: 13px; color: #64748b; margin-top: 2px;">ID: <?= htmlspecialchars($p['reg_no']) ?></div>
                                </td>
                                <td><span style="font-size: 14px; font-weight: 500;"><?= htmlspecialchars($p['faculty_name']) ?></span></td>
                                <td><div class="badge" style="background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe;"><?= htmlspecialchars($p['session']) ?></div></td>
                                <td>
                                    <?php if ($p['pdf_path']): ?>
                                        <a href="<?= PROJECT_ROOT . $p['pdf_path'] ?>" target="_blank" class="btn-pdf" title="View Documentation"><i class="fas fa-file-pdf"></i></a>
                                    <?php else: ?>
                                        <span class="no-pdf"><i class="fas fa-times-circle"></i> Missing</span>
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
