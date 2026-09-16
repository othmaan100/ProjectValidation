<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT e.*, u.name, u.username, u.email
    FROM evaluations e
    JOIN users u ON u.id = e.user_id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evaluation) {
    header("Location: sa_evaluation_results.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM evaluation_responses WHERE evaluation_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sections = [];
foreach ($rows as $row) {
    $sections[$row['section']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Response - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7fe; margin: 0; padding-bottom: 50px; }
        .page-container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 20px; }
        .main-card { background: #fff; border-radius: 18px; padding: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); margin-bottom: 20px; }
        .meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 15px; }
        .meta-item p { margin: 0; font-size: 11px; color: #888; text-transform: uppercase; font-weight: 700; }
        .meta-item h4 { margin: 4px 0 0; font-size: 14px; color: #2d3436; }
        .section-title { font-size: 15px; color: var(--primary); font-weight: 700; margin: 0 0 15px; padding-bottom: 10px; border-bottom: 2px solid #f1f2f6; }
        .qa-row { padding: 12px 0; border-bottom: 1px solid #f6f6f6; }
        .qa-row:last-child { border-bottom: none; }
        .qa-q { font-size: 13px; color: #2d3436; font-weight: 600; margin-bottom: 6px; }
        .qa-a { font-size: 14px; color: #2e7d32; font-weight: 700; }
        .qa-a.text { color: #2d3436; font-weight: 400; font-style: italic; background: #f8faff; padding: 10px 14px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="page-container">
        <a href="sa_evaluation_results.php?role=<?= htmlspecialchars($evaluation['role']) ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Results</a>

        <div class="main-card">
            <h2 style="margin:0;"><?= htmlspecialchars($evaluation['name'] ?: $evaluation['username']) ?></h2>
            <div class="meta-grid">
                <div class="meta-item"><p>Role</p><h4><?= htmlspecialchars(strtoupper($evaluation['respondent_role'])) ?></h4></div>
                <div class="meta-item"><p>Department</p><h4><?= htmlspecialchars($evaluation['department_name'] ?? 'N/A') ?></h4></div>
                <div class="meta-item"><p>Faculty</p><h4><?= htmlspecialchars($evaluation['faculty_name'] ?? 'N/A') ?></h4></div>
                <div class="meta-item"><p>Session</p><h4><?= htmlspecialchars($evaluation['academic_session'] ?? 'N/A') ?></h4></div>
                <div class="meta-item"><p>Submitted</p><h4><?= htmlspecialchars(date('d M Y, H:i', strtotime($evaluation['submitted_at']))) ?></h4></div>
            </div>
        </div>

        <?php foreach ($sections as $section_title => $items): ?>
            <div class="main-card">
                <div class="section-title"><?= htmlspecialchars($section_title) ?></div>
                <?php foreach ($items as $item): ?>
                    <div class="qa-row">
                        <div class="qa-q"><?= htmlspecialchars($item['question_no']) ?>. <?= htmlspecialchars($item['question_text']) ?></div>
                        <?php if ($item['answer_value'] !== null): ?>
                            <div class="qa-a"><?= (int)$item['answer_value'] ?> / 5</div>
                        <?php else: ?>
                            <div class="qa-a text"><?= nl2br(htmlspecialchars($item['answer_text'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
