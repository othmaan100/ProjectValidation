<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Admin can manually open an evaluation window for a role group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open_window_for'])) {
    $role_groups = [
        'student'     => ['stu'],
        'supervisor'  => ['sup'],
        'coordinator' => ['dpc', 'fpc', 'hod'],
    ];
    $target = $_POST['open_window_for'];
    if (isset($role_groups[$target])) {
        $roles = $role_groups[$target];
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $conn->prepare("UPDATE users SET evaluation_due = 1 WHERE role IN ($placeholders) AND evaluation_submitted = 0 AND is_active = 1");
        $stmt->execute($roles);
        $_SESSION['eval_admin_msg'] = "Evaluation window opened for " . ucfirst($target) . " (" . $stmt->rowCount() . " user(s) notified).";
    }
    header("Location: sa_evaluation_results.php?role=" . urlencode($_GET['role'] ?? 'student'));
    exit();
}

$role_key = in_array($_GET['role'] ?? '', ['student', 'supervisor', 'coordinator']) ? $_GET['role'] : 'student';
$role_groups = [
    'student'     => ['stu'],
    'supervisor'  => ['sup'],
    'coordinator' => ['dpc', 'fpc', 'hod'],
];
$roles_for_key = $role_groups[$role_key];
$role_placeholders = implode(',', array_fill(0, count($roles_for_key), '?'));

// Filters
$f_department = $_GET['department_id'] ?? '';
$f_faculty = $_GET['faculty_id'] ?? '';
$f_session = $_GET['academic_session'] ?? '';

// Stats: invited vs submitted
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role IN ($role_placeholders) AND is_active = 1");
$stmt->execute($roles_for_key);
$total_invited = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM evaluations WHERE role = ?");
$stmt->execute([$role_key]);
$total_submitted = (int)$stmt->fetchColumn();

$completion_rate = $total_invited > 0 ? round(($total_submitted / $total_invited) * 100, 1) : 0;

// Filter dropdown sources
$faculties = $conn->query("SELECT id, faculty FROM faculty ORDER BY faculty ASC")->fetchAll(PDO::FETCH_ASSOC);
$departments = $conn->query("SELECT id, department_name, faculty_id FROM departments ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sessions = $conn->query("SELECT DISTINCT academic_session FROM evaluations WHERE academic_session IS NOT NULL AND academic_session != '' ORDER BY academic_session DESC")->fetchAll(PDO::FETCH_COLUMN);

// Browsable responses table
$where = ["e.role = ?"];
$params = [$role_key];
if ($f_department !== '') { $where[] = "e.department_id = ?"; $params[] = $f_department; }
if ($f_faculty !== '') { $where[] = "e.faculty_id = ?"; $params[] = $f_faculty; }
if ($f_session !== '') { $where[] = "e.academic_session = ?"; $params[] = $f_session; }
$where_sql = implode(' AND ', $where);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$count_stmt = $conn->prepare("SELECT COUNT(*) FROM evaluations e WHERE $where_sql");
$count_stmt->execute($params);
$total_rows = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $limit));

$list_stmt = $conn->prepare("
    SELECT e.id, e.respondent_role, e.department_name, e.faculty_name, e.academic_session, e.submitted_at, u.name, u.username
    FROM evaluations e
    JOIN users u ON u.id = e.user_id
    WHERE $where_sql
    ORDER BY e.submitted_at DESC
    LIMIT $limit OFFSET $offset
");
$list_stmt->execute($params);
$responses = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Results - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --warning: #f6c23e; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fe; margin: 0; padding-bottom: 50px; }
        .page-container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
        .page-header h1 { margin: 0; color: #2d3436; font-size: 26px; }

        .tabs { display: flex; gap: 8px; margin-bottom: 25px; flex-wrap: wrap; }
        .tab-link { padding: 10px 22px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 13px; background: #fff; color: #636e72; border: 1px solid #eee; }
        .tab-link.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 25px; }
        .stat-card { background: #fff; border-radius: 15px; padding: 22px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); }
        .stat-card h3 { font-size: 26px; margin: 0 0 4px; color: #2d3436; }
        .stat-card p { margin: 0; font-size: 12px; color: #888; text-transform: uppercase; font-weight: 700; }

        .main-card { background: #fff; border-radius: 18px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); margin-bottom: 20px; }
        .main-card h2 { font-size: 16px; color: #2d3436; margin: 0 0 18px; }

        .filter-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 15px; }
        .filter-row select { padding: 10px 14px; border-radius: 10px; border: 1px solid #dfe6e9; font-size: 13px; }
        .btn { padding: 10px 18px; border-radius: 10px; border: none; font-weight: 700; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-outline { background: #fff; border: 1px solid #dfe6e9; color: #2d3436; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 14px; border-bottom: 2px solid #f1f2f6; color: #636e72; font-size: 12px; text-transform: uppercase; }
        td { padding: 14px; border-bottom: 1px solid #f1f2f6; font-size: 13px; }

        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }
        .window-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-clipboard-question"></i> System Evaluation Results</h1>
            <div style="display:flex; gap:10px;">
                <a href="sa_evaluation_export.php?format=wide" class="btn btn-success"><i class="fas fa-file-excel"></i> Export to Excel</a>
                <a href="sa_evaluation_export.php?format=long" class="btn btn-outline" title="One row per question-response pair - useful for SPSS item-level analysis (e.g. Cronbach's alpha)"><i class="fas fa-table-list"></i> Long Format</a>
            </div>
        </div>

        <?php if (isset($_SESSION['eval_admin_msg'])): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['eval_admin_msg']) ?></div>
            <?php unset($_SESSION['eval_admin_msg']); ?>
        <?php endif; ?>

        <div class="tabs">
            <a href="?role=student" class="tab-link <?= $role_key === 'student' ? 'active' : '' ?>">Student</a>
            <a href="?role=supervisor" class="tab-link <?= $role_key === 'supervisor' ? 'active' : '' ?>">Supervisor</a>
            <a href="?role=coordinator" class="tab-link <?= $role_key === 'coordinator' ? 'active' : '' ?>">Coordinator (DPC/FPC/HOD)</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><h3><?= $total_invited ?></h3><p>Total Invited</p></div>
            <div class="stat-card"><h3><?= $total_submitted ?></h3><p>Total Submitted</p></div>
            <div class="stat-card"><h3><?= $completion_rate ?>%</h3><p>Completion Rate</p></div>
        </div>

        <div class="main-card">
            <h2><i class="fas fa-door-open"></i> Manually Open Evaluation Window</h2>
            <p style="color:#636e72; font-size:13px; margin-top:-10px; margin-bottom:15px;">Forces <code>evaluation_due</code> on for every active, not-yet-submitted user in a role group - useful for a fresh evaluation cycle.</p>
            <form method="POST" class="window-actions" onsubmit="return confirm('Open a new evaluation window for this role group?');">
                <input type="hidden" name="open_window_for" value="<?= htmlspecialchars($role_key) ?>">
                <button type="submit" class="btn btn-outline"><i class="fas fa-bell"></i> Open Window for <?= ucfirst($role_key) ?></button>
            </form>
        </div>

        <div class="main-card">
            <h2><i class="fas fa-filter"></i> Filter Responses</h2>
            <form method="GET" class="filter-row">
                <input type="hidden" name="role" value="<?= htmlspecialchars($role_key) ?>">
                <select name="faculty_id" onchange="this.form.submit()">
                    <option value="">All Faculties</option>
                    <?php foreach ($faculties as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= $f_faculty == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['faculty']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="department_id" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $f_department == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="academic_session" onchange="this.form.submit()">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $f_session === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($f_department || $f_faculty || $f_session): ?>
                    <a href="?role=<?= urlencode($role_key) ?>" class="btn btn-outline">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="main-card">
            <h2><i class="fas fa-list"></i> Individual Responses (<?= $total_rows ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Respondent</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Faculty</th>
                        <th>Session</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($responses)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px; color:#888;">No submissions found for this filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($responses as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['name'] ?: $r['username']) ?></strong></td>
                                <td style="text-transform:uppercase; font-size:11px; font-weight:700; color:var(--primary);"><?= htmlspecialchars($r['respondent_role']) ?></td>
                                <td><?= htmlspecialchars($r['department_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($r['faculty_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($r['academic_session'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($r['submitted_at']))) ?></td>
                                <td><a href="sa_evaluation_view.php?id=<?= $r['id'] ?>" class="btn btn-outline" style="padding:6px 12px;"><i class="fas fa-eye"></i> View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div style="display:flex; justify-content:center; gap:8px; margin-top:20px;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?role=<?= urlencode($role_key) ?>&department_id=<?= urlencode($f_department) ?>&faculty_id=<?= urlencode($f_faculty) ?>&academic_session=<?= urlencode($f_session) ?>&page=<?= $i ?>"
                           style="padding:8px 14px; border-radius:10px; text-decoration:none; font-weight:600; font-size:13px; <?= $i == $page ? 'background:var(--primary); color:#fff;' : 'background:#fff; color:var(--primary); border:1px solid #eee;' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
