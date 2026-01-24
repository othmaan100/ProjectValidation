<?php
include_once __DIR__ .'/../includes/auth.php';
include_once __DIR__ .'/../includes/db.php';
include_once __DIR__ .'/../includes/config.php';

// Redirect if user is not DPC
if ($_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Fetch DPC's department info
$stmt = $conn->prepare("SELECT department, name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $user_info['department'];

// Pagination & Search
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- LOGIC HANDLING ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['validate_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("SELECT topic FROM project_topics WHERE id = ?");
        $stmt->execute([$topic_id]);
        $topic_text = $stmt->fetchColumn();
        $_SESSION['validation_result'][$topic_id] = validate_hybrid($conn, $topic_text, $topic_id);
    } 
    elseif (isset($_POST['validate_all_pending'])) {
        $stmt = $conn->prepare("SELECT id, topic FROM project_topics WHERE status = 'pending' AND student_id IN (SELECT id FROM students WHERE department = ?)");
        $stmt->execute([$dept_id]);
        $pending = $stmt->fetchAll();
        foreach ($pending as $row) {
            $_SESSION['validation_result'][$row['id']] = validate_hybrid($conn, $row['topic'], $row['id']);
        }
    }
    elseif (isset($_POST['approve_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_NUMBER_INT);
        
        $conn->beginTransaction();
        try {
            // Approve the selected topic
            $stmt = $conn->prepare("UPDATE project_topics SET status = 'approved' WHERE id = ?");
            $stmt->execute([$topic_id]);
            
            // Reject all other topics for this student that are NOT already rejected
            $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected' WHERE student_id = ? AND id != ? AND status != 'approved'");
            $stmt->execute([$student_id, $topic_id]);
            
            send_feedback_to_student($topic_id, 'approved');
            // Also notify that others were rejected
            $stmt = $conn->prepare("SELECT reg_no FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $reg = $stmt->fetchColumn();
            $msg = "Since one of your topics was approved, your other submissions have been automatically rejected.";
            $conn->prepare("INSERT INTO feedback (student_reg_no, message) VALUES (?, ?)")->execute([$reg, $msg]);

            $conn->commit();
        } catch (Exception $e) { $conn->rollBack(); $_SESSION['error'] = "Approval failed: " . $e->getMessage(); }
    } 
    elseif (isset($_POST['reject_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $reason = filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_STRING) ?: 'Similarity found or topic rejected.';
        $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$topic_id]);
        send_feedback_to_student($topic_id, 'rejected', $reason);
    }
    header("Location: " . $_SERVER['PHP_SELF'] . ($search ? "?search=" . urlencode($search) : ""));
    exit();
}

// Helper Functions
function validate_hybrid($conn, $topic, $currentId) {
    $cleanInput = clean_text_local($topic);
    $faculty_id = $_SESSION['faculty_id'] ?? 0;

    // Check against past projects in the same faculty
    $stmt = $conn->prepare("SELECT topic, reg_no FROM past_projects WHERE faculty_id = ?");
    $stmt->execute([$faculty_id]);
    while ($row = $stmt->fetch()) {
        similar_text($cleanInput, clean_text_local($row['topic']), $perc);
        if ($perc > 75) return "⚠️ Similarity Match ($perc%): " . $row['topic'] . " [Reg No: " . $row['reg_no'] . "] (Past Project)";
    }

    // Check against other student topics in the same faculty
    $stmt = $conn->prepare("SELECT pt.topic, s.reg_no FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE pt.id != ? AND s.faculty_id = ?");
    $stmt->execute([$currentId, $faculty_id]);
    while ($row = $stmt->fetch()) {
        similar_text($cleanInput, clean_text_local($row['topic']), $perc);
        if ($perc > 75) return "⚠️ Similarity Match ($perc%): " . $row['topic'] . " [Reg No: " . $row['reg_no'] . "] (Other Student)";
    }
    return validate_with_ai($topic);
}
function clean_text_local($text) {
    if (!$text) return "";
    $common = ['the', 'a', 'an', 'of', 'for', 'in', 'on', 'at', 'to', 'using', 'based', 'study'];
    $text = strtolower(trim($text));
    return preg_replace('/\b(' . implode('|', $common) . ')\b/i', '', $text);
}
function validate_with_ai($topic) {
    if (!defined('OPENAI_API_KEY')) return "✅ No local matches found.";
    $url = "https://api.openai.com/v1/chat/completions";
    $data = [ "model" => "gpt-3.5-turbo", "messages" => [["role" => "system", "content" => "You are an academic validator. Detect if topics are unoriginal or common knowledge."], ["role" => "user", "content" => "Is the following project topic unique and academically valid for a final year project? Answer in 1 short sentence. Topic: \"$topic\""]], "max_tokens" => 60 ];
    $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($data), CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_API_KEY]]);
    $response = curl_exec($ch); $res = json_decode($response, true); curl_close($ch);
    return isset($res['choices'][0]['message']['content']) ? "🤖 AI: " . $res['choices'][0]['message']['content'] : "✅ No local matches found (AI Offline).";
}
function send_feedback_to_student($topic_id, $decision, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT s.reg_no FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE pt.id = ?");
    $stmt->execute([$topic_id]);
    $reg = $stmt->fetchColumn(); $msg = "Your topic was $decision. " . ($reason ? "Reason: $reason" : "");
    $conn->prepare("INSERT INTO feedback (student_reg_no, message) VALUES (?, ?)")->execute([$reg, $msg]);
}

// FETCH DATA - Group by Student
$countQuery = "SELECT COUNT(DISTINCT s.id) FROM students s JOIN project_topics pt ON s.id = pt.student_id WHERE s.department = :dept";
if ($search) $countQuery .= " AND (s.name LIKE :search OR s.reg_no LIKE :search OR pt.topic LIKE :search)";
$countStmt = $conn->prepare($countQuery);
$countStmt->bindValue(':dept', $dept_id);
if ($search) $countStmt->bindValue(':search', "%$search%");
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

$studentQuery = "SELECT DISTINCT s.id, s.reg_no, s.name as student_name 
                 FROM students s 
                 JOIN project_topics pt ON s.id = pt.student_id 
                 WHERE s.department = :dept";
if ($search) $studentQuery .= " AND (s.name LIKE :search OR s.reg_no LIKE :search OR pt.topic LIKE :search)";
$studentQuery .= " ORDER BY s.name ASC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($studentQuery);
$stmt->bindValue(':dept', $dept_id);
if ($search) $stmt->bindValue(':search', "%$search%");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map topics to students
$all_topics = [];
if (!empty($students)) {
    $student_ids = array_column($students, 'id');
    $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
    $topicStmt = $conn->prepare("SELECT student_id, id, topic, status, pdf_path FROM project_topics WHERE student_id IN ($placeholders) ORDER BY id ASC");
    $topicStmt->execute($student_ids);
    $all_topics = $topicStmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Topic Validation Queue - DPC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --info: #36b9cc; --glass: rgba(255, 255, 255, 0.95); }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-bottom: 50px; }
        .page-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-card h1 { color: var(--primary); font-size: 28px; }
        .main-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .search-container { display: flex; gap: 10px; margin-bottom: 25px; }
        .search-input { flex: 1; padding: 14px; border: 2px solid #eee; border-radius: 12px; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--primary); }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; }
        th { background: #f8faff; padding: 18px; text-align: left; color: #747d8c; font-size: 13px; text-transform: uppercase; }
        td { padding: 20px; border-bottom: 1px solid #eee; vertical-align: top; }
        .student-box { border-bottom: 2px solid #f1f2f6; padding-bottom: 10px; margin-bottom: 10px; }
        .student-name { font-weight: 700; color: #2d3436; font-size: 16px; display: block; }
        .student-reg { color: var(--primary); font-size: 12px; font-weight: 600; }
        .topic-row { display: grid; grid-template-columns: 1fr 140px 180px; gap: 20px; padding: 15px; background: #fbfbfc; border-radius: 12px; margin-top: 10px; border-left: 5px solid #dcdde1; transition: 0.2s; }
        .topic-row:hover { background: #f1f2f6; }
        .topic-row.approved { border-left-color: var(--success); background: #f0fff4; }
        .topic-row.rejected { border-left-color: var(--danger); background: #fff5f5; opacity: 0.8; }
        .topic-row.pending { border-left-color: var(--info); }
        .topic-content { display: flex; flex-direction: column; gap: 8px; }
        .topic-text { font-style: italic; color: #2f3640; line-height: 1.4; }
        .ai-box { background: #fff3cd; padding: 10px; border-radius: 8px; font-size: 12px; border-left: 3px solid #ffca28; color: #856404; margin-top: 5px; }
        .btn-sm { padding: 6px 12px; border-radius: 8px; font-size: 12px; cursor: pointer; border: none; transition: 0.2s; color: white; display: inline-flex; align-items: center; gap: 5px; }
        .btn-check { background: var(--info); }
        .btn-approve { background: var(--success); }
        .btn-reject { background: var(--danger); }
        .status-badge { font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 12px; display: inline-block; }
        .badge-pending { background: #fff3e0; color: #ef6c00; }
        .badge-approved { background: #e8f5e9; color: #2e7d32; }
        .badge-rejected { background: #ffebee; color: #c62828; }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    </div> <!-- Close header container -->

    <div class="page-container">
        <div class="header-card">
            <div>
                <h1><i class="fas fa-layer-group"></i> Grouped Topic Validation</h1>
                <p style="color: #636e72; margin-top: 5px;">Manage all proposals for each student in a single view.</p>
            </div>
            <form method="POST">
                <button type="submit" name="validate_all_pending" class="btn" style="background: var(--primary); color:white; padding: 12px 24px; border-radius: 15px;">
                    <i class="fas fa-robot"></i> Validate All Department Pending
                </button>
            </form>
        </div>

        <div class="main-card">
            <form method="GET" class="search-container">
                <input type="text" name="search" class="search-input" placeholder="Search students or topics..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn" style="background: #2d3436; color:white;"><i class="fas fa-search"></i> Search</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th style="width: 250px;">Student Information</th>
                        <th>Project Proposals (Up to 3)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="2" style="text-align:center; padding: 60px; color: #636e72;">No student topics found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): 
                            $st_topics = $all_topics[$s['id']] ?? []; 
                            $hasApproved = false;
                            foreach($st_topics as $tp) if($tp['status'] === 'approved') $hasApproved = true;
                        ?>
                        <tr>
                            <td>
                                <div class="student-box">
                                    <span class="student-name"><?= htmlspecialchars($s['student_name']) ?></span>
                                    <span class="student-reg"><code><?= htmlspecialchars($s['reg_no']) ?></code></span>
                                </div>
                                <div style="font-size: 11px; color:#7f8c8d;">
                                    Total Topics: <?= count($st_topics) ?>
                                </div>
                            </td>
                            <td>
                                 <?php 
                                 $display_topics = $st_topics;
                                 if ($hasApproved) {
                                     $display_topics = array_filter($st_topics, function($tp) { return $tp['status'] === 'approved'; });
                                 }
                                 foreach ($display_topics as $idx => $t): ?>
                                    <div class="topic-row <?= $t['status'] ?>">
                                        <div class="topic-content">
                                            <span style="font-weight: 700; color: #7f8c8d; font-size: 11px;">PROPOSAL #<?= $idx + 1 ?></span>
                                            <span class="topic-text">"<?= htmlspecialchars($t['topic']) ?>"</span>
                                            
                                            <?php if (isset($_SESSION['validation_result'][$t['id']])): ?>
                                                <div class="ai-box">
                                                    <i class="fas fa-brain"></i> <?= htmlspecialchars($_SESSION['validation_result'][$t['id']]) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="text-align: center;">
                                            <span class="status-badge badge-<?= $t['status'] ?>"><?= $t['status'] ?></span>
                                        </div>
                                        <div style="display: flex; gap: 5px; justify-content: flex-end; align-items: start;">
                                            <?php if ($t['status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                                    <button type="submit" name="validate_topic" class="btn-sm btn-check" title="Check Similarity"><i class="fas fa-shield-alt"></i></button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this topic? This will reject all other proposals for this student.')">
                                                    <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                                    <button type="submit" name="approve_topic" class="btn-sm btn-approve" title="Approve"><i class="fas fa-check"></i></button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                                    <button type="submit" name="reject_topic" class="btn-sm btn-reject" title="Reject"><i class="fas fa-times"></i></button>
                                                </form>
                                            <?php elseif($t['status'] == 'approved'): ?>
                                                <i class="fas fa-check-double" style="color: var(--success); font-size: 20px;" title="Selected Project"></i>
                                            <?php else: ?>
                                                <small style="color: #bdc3c7; font-weight: 700;">ARCHIVED</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 30px;">
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? "&search=".urlencode($search) : "" ?>" 
                           style="padding: 10px 16px; border-radius: 12px; text-decoration: none; font-weight: 600; <?= $i == $page ? 'background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(102,126,234,0.4);' : 'background: white; color: var(--primary); border: 1px solid #eee;' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include_once __DIR__ .'/../includes/footer.php'; ?>
</body>
</html>
