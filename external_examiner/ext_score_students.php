<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Check role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ext') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$panel_id = isset($_GET['panel_id']) ? intval($_GET['panel_id']) : 0;

if (!$panel_id) {
    header("Location: index.php");
    exit();
}

// Verify panel belongs to this examiner
$stmt = $conn->prepare("SELECT dp.* FROM defense_panels dp JOIN panel_members pm ON dp.id = pm.panel_id WHERE dp.id = ? AND pm.supervisor_id = ?");
$stmt->execute([$panel_id, $user_id]);
$panel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$panel) {
    header("Location: index.php");
    exit();
}

// Handle Scoring
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_scores'])) {
    $scores = $_POST['scores']; // student_id => score
    $comments = $_POST['comments']; // student_id => comment
    
    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("INSERT INTO defense_scores (student_id, supervisor_id, panel_id, score, comments) 
                                VALUES (?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE score = VALUES(score), comments = VALUES(comments)");
        
        foreach ($scores as $stu_id => $val) {
            if ($val === '') continue; // Skip empty scores
            $comm = $comments[$stu_id] ?? '';
            $stmt->execute([$stu_id, $user_id, $panel_id, $val, $comm]);
        }
        
        $conn->commit();
        $msg = "Scores saved successfully!";
    } catch (Exception $e) {
        $conn->rollBack();
        $msg = "Error: " . $e->getMessage();
    }
}

// Fetch students assigned to this panel
$stmt = $conn->prepare("
    SELECT s.id, s.name, s.reg_no, pt.topic, 
    (SELECT score FROM defense_scores WHERE student_id = s.id AND supervisor_id = ? AND panel_id = ?) as current_score,
    (SELECT comments FROM defense_scores WHERE student_id = s.id AND supervisor_id = ? AND panel_id = ?) as current_comment
    FROM student_panel_assignments spa
    JOIN students s ON spa.student_id = s.id
    LEFT JOIN project_topics pt ON (s.id = pt.student_id AND pt.status = 'approved')
    WHERE spa.panel_id = ?
    ORDER BY s.name ASC
");
$stmt->execute([$user_id, $panel_id, $user_id, $panel_id, $panel_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Students - <?= htmlspecialchars($panel['panel_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --bg-body: #f8fafc; --text-main: #1e293b; --text-muted: #64748b; }
        * { margin:0; padding:0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); color: var(--text-main); }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .header { background: white; padding: 25px 40px; border-radius: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header h1 { font-size: 22px; color: var(--primary); }
        .alert { background: #dcfce7; color: #15803d; padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; }
        .table-card { background: white; border-radius: 20px; padding: 0; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 20px; text-align: left; font-size: 13px; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #f1f5f9; }
        td { padding: 20px; border-bottom: 1px solid #f1f5f9; }
        .topic-cell { font-size: 13px; color: var(--text-muted); max-width: 300px; }
        .score-input { width: 80px; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-weight: 700; text-align: center; }
        .comment-input { width: 100%; min-width: 200px; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        .btn { display: inline-flex; align-items: center; gap: 8px; background: var(--primary); color: white; padding: 12px 30px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 16px; }
        .btn:hover { background: #4338ca; transform: translateY(-2px); }
        .btn-back { background: #f1f5f9; color: var(--text-muted); text-decoration: none; padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600; }
        .btn-back:hover { background: #e2e8f0; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <h1 style="margin-top: 15px;"><?= htmlspecialchars($panel['panel_name']) ?> Assessment</h1>
            </div>
            <div style="text-align: right;">
                <p style="color: var(--text-muted); font-size: 14px;">External Project Defense</p>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Student / Registration No</th>
                            <th>Project Title</th>
                            <th>Score (Max 100)</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stu): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary);"><?= htmlspecialchars($stu['name']) ?></strong><br>
                                    <span style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($stu['reg_no']) ?></span>
                                </td>
                                <td class="topic-cell"><?= htmlspecialchars($stu['topic'] ?: 'No approved topic found') ?></td>
                                <td>
                                    <input type="number" name="scores[<?= $stu['id'] ?>]" 
                                           class="score-input" min="0" max="100" step="0.5"
                                           value="<?= htmlspecialchars($stu['current_score'] ?? '') ?>" placeholder="--">
                                </td>
                                <td>
                                    <input type="text" name="comments[<?= $stu['id'] ?>]" 
                                           class="comment-input" value="<?= htmlspecialchars($stu['current_comment'] ?? '') ?>"
                                           placeholder="Observations, strengths, weaknesses...">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="padding: 30px; text-align: right; background: #f8fafc; border-top: 1px solid #f1f5f9;">
                    <button type="submit" name="save_scores" class="btn">
                        <i class="fas fa-save"></i> Save Assessment Results
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Mobile Nav padding -->
    <div style="height: 50px;"></div>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
