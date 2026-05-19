<?php
$key="";
define('OPENAI_API_KEY', $key);

// Auth & DB
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Pagination & Search
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

// Build query
$params = [];
$sql = "SELECT * FROM project_topics";
if ($search) {
    $sql .= " WHERE topic LIKE :search OR student_reg_no LIKE :search";
    $params[':search'] = "%$search%";
}
$sql .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $type = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $type);
}
$stmt->execute();
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);
    if (!$topic_id) {
        die('Invalid topic ID');
    }

    // Fetch topic text
    $stmt = $conn->prepare("SELECT topic FROM project_topics WHERE id = ?");
    $stmt->execute([$topic_id]);
    $topic_text = $stmt->fetchColumn();
    if (!$topic_text) {
        die('Topic not found');
    }

    if (isset($_POST['validate_topic'])) {
        $explanation = validateTopicUniqueness($conn, $topic_text, $topic_id);
        $_SESSION['validation_result'][$topic_id] = $explanation;
        header("Refresh: 0");
        exit;

    } elseif (isset($_POST['approve_topic'])) {
        updateTopicStatus($conn, $topic_id, 'approved');
        sendFeedbackToStudent($conn, $topic_id, 'approved');
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;

    } elseif (isset($_POST['reject_topic'])) {
        $reason = $_POST['rejection_reason'] ?? 'Rejected by HOD.';
        updateTopicStatus($conn, $topic_id, 'rejected', $reason);
        sendFeedbackToStudent($conn, $topic_id, 'rejected', $reason);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// ------------------ VALIDATION FUNCTIONS ------------------

function validateTopicUniqueness(PDO $conn, string $topic, ?int $currentTopicId = null): string {
    $cleanTopic = cleanText($topic);
    $similarTopics = [];

    // 1. Fetch all past projects
    $stmt = $conn->prepare("SELECT topic, student_reg_no FROM past_projects");
    $stmt->execute();
    $pastProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch all other pending topics (exclude current if ID given)
    $pendingSql = "SELECT topic, student_reg_no FROM project_topics";
    $pendingParams = [];
    if ($currentTopicId !== null) {
        $pendingSql .= " WHERE id != :exclude_id";
        $pendingParams[':exclude_id'] = $currentTopicId;
    }
    $stmt = $conn->prepare($pendingSql);
    foreach ($pendingParams as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    }
    $stmt->execute();
    $pendingTopics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine
    $allTopics = array_merge($pastProjects, $pendingTopics);

    foreach ($allTopics as $record) {
        $otherTopic = trim($record['topic']);
        $regNo = $record['student_reg_no'] ?? 'Past Project';

        // Skip empty
        if (empty($otherTopic)) continue;

        // Exact match (case-insensitive)
        if (strtolower(trim($topic)) === strtolower($otherTopic)) {
            $similarTopics[] = [
                'topic' => $otherTopic,
                'student_reg_no' => $regNo,
                'source' => ($regNo === 'Past Project') ? 'past' : 'pending'
            ];
            continue;
        }

        // Similarity check
        $sim = calculateSimilarity($cleanTopic, cleanText($otherTopic));
        if ($sim >= 70) {
            $similarTopics[] = [
                'topic' => $otherTopic,
                'student_reg_no' => $regNo,
                'source' => ($regNo === 'Past Project') ? 'past' : 'pending',
                'similarity' => round($sim, 1)
            ];
        }
    }

    if (!empty($similarTopics)) {
        $details = [];
        foreach ($similarTopics as $match) {
            if ($match['student_reg_no'] === 'Past Project') {
                $details[] = "'{$match['topic']}' (Past Project)";
            } else {
                $details[] = "'{$match['topic']}' by {$match['student_reg_no']}";
            }
        }
        // Limit to 3 matches for clarity
        $list = implode("; ", array_slice($details, 0, 3));
        return "⚠️ Similar or duplicate topic found: $list";
    }

    // Only use AI if no local match
    return validateWithChatGPT($topic);
}

function cleanText(string $text): string {
    $text = strtolower(trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text)));
    $common = ['the', 'a', 'an', 'of', 'for', 'in', 'on', 'at', 'to', 'and', 'or', 'with', 'using', 'based', 'study', 'analysis', 'design', 'implementation'];
    $pattern = '/\b(' . implode('|', array_map('preg_quote', $common)) . ')\b/';
    $text = preg_replace($pattern, ' ', $text);
    return preg_replace('/\s+/', ' ', $text); // normalize whitespace
}

function calculateSimilarity(string $a, string $b): float {
    if ($a === $b) return 100.0;
    if (empty($a) || empty($b)) return 0.0;
    similar_text($a, $b, $pct);
    return (float)$pct;
}

function validateWithChatGPT(string $topic): string {
    $url = "https://api.openai.com/v1/chat/completions";
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => "You are an academic project validator."],
            ["role" => "user", "content" => "Is this project topic original and relevant? Respond only with 'Original', 'Not Original', or 'Unclear', followed by a short reason (max 1 sentence). Topic: \"$topic\""]
        ],
        "max_tokens" => 60,
        "temperature" => 0.5
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("OpenAI API Error ($httpCode): " . ($response ?? 'No response'));
        return match ($httpCode) {
            429 => "❌ API rate limit exceeded. Try again later.",
            401 => "❌ Invalid OpenAI API key.",
            default => "⚠️ AI validation failed (HTTP $httpCode)."
        };
    }

    $res = json_decode($response, true);
    if (!empty($res['choices'][0]['message']['content'])) {
        return "🤖 AI Check: " . trim($res['choices'][0]['message']['content']);
    }

    return "⚠️ Unexpected AI response.";
}

function updateTopicStatus(PDO $conn, int $topicId, string $status, ?string $reason = null): void {
    $stmt = $conn->prepare("UPDATE project_topics SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $topicId]);
}

function sendFeedbackToStudent(PDO $conn, int $topicId, string $decision, ?string $reason = null): void {
    $stmt = $conn->prepare("SELECT student_reg_no FROM project_topics WHERE id = ?");
    $stmt->execute([$topicId]);
    $regNo = $stmt->fetchColumn();

    if (!$regNo) return;

    $message = "Your project topic has been $decision.";
    if ($reason) $message .= " Reason: $reason";

    $stmt = $conn->prepare("INSERT INTO feedback (student_reg_no, message, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$regNo, $message]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validate Project Topics</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn {
            padding: 6px 12px; font-size: 13px; border: none; border-radius: 4px;
            cursor: pointer; white-space: nowrap;
        }
        .btn-validate { background: #1976d2; color: white; }
        .btn-approve { background: #388e3c; color: white; }
        .btn-reject { background: #d32f2f; color: white; }
        .validation-explanation {
            margin-top: 10px;
            padding: 10px;
            background: #fff8e1;
            border-left: 4px solid #ffa000;
            font-size: 13px;
            line-height: 1.5;
            color: #5d4037;
            border-radius: 0 4px 4px 0;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: bold;
        }
        .status-pending { background-color: #ffca28; color: #212121; }
        .status-approved { background-color: #4caf50; color: white; }
        .status-rejected { background-color: #f44336; color: white; }
        form { display: inline; }
    </style>
</head>
<body>
<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h1>Validate Project Topics</h1>

    <!-- Search Form -->
    <form method="GET" style="margin: 20px 0; display: flex; gap: 10px; flex-wrap: wrap;">
        <input
            type="text"
            name="search"
            placeholder="Search by topic or registration number"
            value="<?= htmlspecialchars($search) ?>"
            style="padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 4px;"
        >
        <button type="submit" style="padding: 8px 16px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Search
        </button>
        <?php if ($search): ?>
            <a href="?page=1" style="align-self: center; color: #1976d2; text-decoration: none;">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Topics Table -->
    <table>
        <thead>
            <tr>
                <th>Topic</th>
                <th>Student Reg. No.</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($topics)): ?>
            <tr><td colspan="4" style="text-align: center; color: #666;">No topics found.</td></tr>
        <?php else: ?>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?= htmlspecialchars($topic['topic']) ?></td>
                    <td><?= htmlspecialchars($topic['student_reg_no']) ?></td>
                    <td>
                        <span class="status-badge 
                            <?= match($topic['status']) {
                                'approved' => 'status-approved',
                                'rejected' => 'status-rejected',
                                default => 'status-pending'
                            } ?>">
                            <?= ucfirst(htmlspecialchars($topic['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($topic['status'] === 'pending'): ?>
                            <form method="POST">
                                <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                                <button type="submit" name="validate_topic" class="btn btn-validate">Validate</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Approve this topic?')">
                                <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                                <button type="submit" name="approve_topic" class="btn btn-approve">Approve</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Reject this topic?')">
                                <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                                <input type="hidden" name="rejection_reason" value="Topic is too similar to existing work.">
                                <button type="submit" name="reject_topic" class="btn btn-reject">Reject</button>
                            </form>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['validation_result'][$topic['id']])): ?>
                            <div class="validation-explanation">
                                <?= htmlspecialchars($_SESSION['validation_result'][$topic['id']]) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div style="text-align: center; margin: 20px 0;">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" style="margin: 0 10px; text-decoration: none; color: #1976d2;">← Previous</a>
        <?php endif; ?>
        <span>Page <?= $page ?></span>
        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" style="margin: 0 10px; text-decoration: none; color: #1976d2;">Next →</a>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
