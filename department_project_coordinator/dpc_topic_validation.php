<?php
include_once __DIR__ .'/../includes/auth.php';
include_once __DIR__ .'/../includes/db.php';
include_once __DIR__ .'/../includes/config.php';

// OpenAI Configuration is now handled in includes/config.php

// Redirect if user is not DPC
if ($_SESSION['role'] !== 'dpc') {
    header("Location: /projectval/");
    exit();
}

// Fetch DPC's department info
$stmt = $conn->prepare("SELECT department, name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $user_info['department'];

// Pagination Setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- LOGIC HANDLING ---

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Validate Single Topic
    if (isset($_POST['validate_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("SELECT topic FROM project_topics WHERE id = ?");
        $stmt->execute([$topic_id]);
        $topic_text = $stmt->fetchColumn();
        $_SESSION['validation_result'][$topic_id] = validate_hybrid($conn, $topic_text, $topic_id);
    } 
    // 2. Validate ALL Pending Topics
    elseif (isset($_POST['validate_all_pending'])) {
        $stmt = $conn->prepare("SELECT id, topic FROM project_topics WHERE status = 'pending' AND student_id IN (SELECT id FROM students WHERE department = ?)");
        $stmt->execute([$dept_id]);
        $pending = $stmt->fetchAll();
        foreach ($pending as $row) {
            $_SESSION['validation_result'][$row['id']] = validate_hybrid($conn, $row['topic'], $row['id']);
        }
    }
    // 3. Approve Topic
    elseif (isset($_POST['approve_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("UPDATE project_topics SET status = 'approved' WHERE id = ?");
        $stmt->execute([$topic_id]);
        send_feedback_to_student($topic_id, 'approved');
    } 
    // 4. Reject Topic
    elseif (isset($_POST['reject_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $reason = filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_STRING);
        $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$topic_id]);
        send_feedback_to_student($topic_id, 'rejected', $reason);
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . ($search ? "?search=" . urlencode($search) : ""));
    exit();
}

// --- HELPER FUNCTIONS ---

function validate_hybrid($conn, $topic, $currentId) {
    // Step A: Local database similarity check
    $cleanInput = clean_text_local($topic);
    
    // Check Past Projects
    $stmt = $conn->prepare("SELECT topic, reg_no FROM past_projects");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        similar_text($cleanInput, clean_text_local($row['topic']), $perc);
        if ($perc > 75) return "⚠️ Similarity Match ($perc%): " . $row['topic'] . " [Reg No: " . $row['reg_no'] . "] (Past Project)";
    }

    // Check Other Pending Topics
    $stmt = $conn->prepare("SELECT pt.topic, s.reg_no FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE pt.id != ?");
    $stmt->execute([$currentId]);
    while ($row = $stmt->fetch()) {
        similar_text($cleanInput, clean_text_local($row['topic']), $perc);
        if ($perc > 75) return "⚠️ Similarity Match ($perc%): " . $row['topic'] . " [Reg No: " . $row['reg_no'] . "] (Other Student)";
    }

    // Step B: AI Semantic Check (For paraphrasing)
    return validate_with_ai($topic);
}

function clean_text_local($text) {
    $common = ['the', 'a', 'an', 'of', 'for', 'in', 'on', 'at', 'to', 'using', 'based', 'study'];
    $text = strtolower(trim($text));
    return preg_replace('/\b(' . implode('|', $common) . ')\b/i', '', $text);
}

function validate_with_ai($topic) {
    $url = "https://api.openai.com/v1/chat/completions";
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => "You are an academic validator. Detect if topics are unoriginal or common knowledge."],
            ["role" => "user", "content" => "Is the following project topic unique and academically valid? Answer in 1 short sentence. Topic: \"$topic\""]
        ],
        "max_tokens" => 60
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_API_KEY]
    ]);
    $response = curl_exec($ch);
    $res = json_decode($response, true);
    curl_close($ch);

    return isset($res['choices'][0]['message']['content']) 
           ? "🤖 AI: " . $res['choices'][0]['message']['content'] 
           : "✅ No local matches found (AI Offline).";
}

function send_feedback_to_student($topic_id, $decision, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT s.reg_no FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE pt.id = ?");
    $stmt->execute([$topic_id]);
    $reg = $stmt->fetchColumn();
    $msg = "Your topic was $decision. " . ($reason ? "Reason: $reason" : "");
    $conn->prepare("INSERT INTO feedback (student_reg_no, message) VALUES (?, ?)")->execute([$reg, $msg]);
}

// --- DATA FETCHING ---
$query = "SELECT pt.*, s.reg_no, s.name as student_name 
          FROM project_topics pt 
          JOIN students s ON pt.student_id = s.id 
          WHERE s.department = :dept";
if ($search) $query .= " AND (pt.topic LIKE :search OR s.reg_no LIKE :search)";
$query .= " ORDER BY pt.status DESC, pt.id DESC LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindValue(':dept', $dept_id);
if ($search) $stmt->bindValue(':search', "%$search%");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$topics = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Topic Validation Queue - DPC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #667eea; 
            --secondary: #764ba2; 
            --success: #1cc88a; 
            --danger: #e74a3b; 
            --info: #36b9cc;
            --glass: rgba(255, 255, 255, 0.95); 
        }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            margin: 0;
            padding-bottom: 50px; 
        }
        /* Overwrite header styles if necessary or adapt */
        .page-container { max-width: 1300px; margin: 0 auto; padding: 20px; }
        .header-card { 
            background: var(--glass); 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            margin-bottom: 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 20px; 
        }
        .header-card h1 { color: var(--primary); font-size: 28px; margin: 0; }
        .main-card { 
            background: var(--glass); 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
        }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; margin-top: 20px; }
        th { background: #f8faff; padding: 18px; text-align: left; color: #747d8c; font-size: 13px; text-transform: uppercase; }
        td { padding: 16px; border-bottom: 1px solid #eee; font-size: 14px; }
        .btn { padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: white; font-size: 14px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-primary { background: var(--primary); }
        .btn-success { background: var(--success); }
        .btn-danger { background: var(--danger); }
        .btn-info { background: var(--info); }
        .status-pill { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3e0; color: #ef6c00; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .ai-box { background: #fff3cd; padding: 15px; border-radius: 10px; margin-top: 10px; font-size: 13px; border-left: 5px solid #ffca28; color: #856404; }
        .search-container { display: flex; gap: 10px; margin-bottom: 25px; }
        .search-input { flex: 1; padding: 14px; border: 2px solid #eee; border-radius: 12px; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--primary); }
        .topic-display { display: flex; flex-direction: column; gap: 5px; }
        .student-info { font-weight: 600; color: var(--primary); font-size: 15px; }
        .topic-text { color: #2d3436; font-style: italic; }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    </div> <!-- Close header's container -->

    <div class="page-container">
        <!-- Header Card -->
        <div class="header-card">
            <div>
                <h1><i class="fas fa-tasks"></i> Topic Validation Queue</h1>
                <p style="color: #636e72; margin-top: 5px;">Review and validate student project topics using automated checks.</p>
            </div>
            <form method="POST">
                <button type="submit" name="validate_all_pending" class="btn btn-primary">
                    <i class="fas fa-robot"></i> Validate All Pending
                </button>
            </form>
        </div>

        <div class="main-card">
            <!-- Search Section -->
            <form method="GET" class="search-container">
                <input type="text" name="search" class="search-input" placeholder="Search by student name, reg no, or topic..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn" style="background: #2d3436;"><i class="fas fa-search"></i> Search</button>
            </form>

            <!-- Table Section -->
            <table>
                <thead>
                    <tr>
                        <th>Student & Topic Details</th>
                        <th>Current Status</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topics)): ?>
                        <tr><td colspan="3" style="text-align:center; padding: 40px; color: #636e72;">No topics found matching your criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topics as $t): ?>
                        <tr>
                            <td>
                                <div class="topic-display">
                                    <span class="student-info"><?= htmlspecialchars($t['student_name']) ?> (<?= htmlspecialchars($t['reg_no']) ?>)</span>
                                    <span class="topic-text">"<?= htmlspecialchars($t['topic']) ?>"</span>
                                    
                                    <?php if (isset($_SESSION['validation_result'][$t['id']])): ?>
                                        <div class="ai-box">
                                            <i class="fas fa-info-circle"></i> <?= htmlspecialchars($_SESSION['validation_result'][$t['id']]) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill status-<?= $t['status'] ?>">
                                    <?= strtoupper($t['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($t['status'] == 'pending'): ?>
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <form method="POST">
                                            <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                            <button type="submit" name="validate_topic" class="btn btn-info" title="Run Similarity Check">
                                                <i class="fas fa-microscope"></i> Check
                                            </button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                            <button type="submit" name="approve_topic" class="btn btn-success" title="Approve Topic">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="rejection_reason" value="Similarity found during validation.">
                                            <button type="submit" name="reject_topic" class="btn btn-danger" title="Reject Topic">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align: center; color: #b2bec3; font-size: 12px; font-weight: 600;">
                                        PROCESSED
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination (if more pages) -->
            <?php
            // Calculate total pages for this department's topics
            $countQuery = "SELECT COUNT(*) FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE s.department = :dept";
            if ($search) $countQuery .= " AND (pt.topic LIKE :search OR s.reg_no LIKE :search)";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bindValue(':dept', $dept_id);
            if ($search) $countStmt->bindValue(':search', "%$search%");
            $countStmt->execute();
            $totalCount = $countStmt->fetchColumn();
            $totalPages = ceil($totalCount / $limit);
            ?>

            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 5px; margin-top: 25px;">
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? "&search=".urlencode($search) : "" ?>" 
                           style="padding: 8px 15px; border-radius: 8px; text-decoration: none; font-weight: 600; <?= $i == $page ? 'background: var(--primary); color: white;' : 'background: white; color: var(--primary);' ?>">
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