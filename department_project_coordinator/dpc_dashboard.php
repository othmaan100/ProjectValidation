<?php

include_once __DIR__ .'/../includes/auth.php';
include_once __DIR__ .'/../includes/db.php';

// Redirect if user is not DPC
if ($_SESSION['role'] !== 'dpc') {
    header("Location: index.php");
    exit();
}

// Fetch all topics with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Topics per page
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM project_topics join students on project_topics.student_id=students.id WHERE topic LIKE :search OR student_reg_no LIKE :search LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':search', "%$search%");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT * FROM project_topics join students on project_topics.student_id=students.id LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$topics = $stmt->fetchAll();

// Handle topic validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['validate_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);

        // Fetch the topic from the database
        $stmt = $conn->prepare("SELECT topic FROM project_topics WHERE id = ?");
        $stmt->execute([$topic_id]);
        $topic = $stmt->fetchColumn();

        // Validate topic against past projects with full details
        $validation_explanation = validate_topic_with_details($topic);

        // Store the validation result in the session
        $_SESSION['validation_result'][$topic_id] = $validation_explanation;
    } elseif (isset($_POST['approve_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("UPDATE project_topics SET status = 'approved' WHERE id = ?");
        $stmt->execute([$topic_id]);
        send_feedback_to_student($topic_id, 'approved');
    } elseif (isset($_POST['reject_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $reason = filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_STRING);
        $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $topic_id]);
        send_feedback_to_student($topic_id, 'rejected', $reason);
    }
    header("Refresh:60"); // Refresh the page after 2 seconds
}

// Enhanced validation function with full details
function validate_topic_with_details($topic) {
    global $conn;
    
    // 1. Check for exact match
    $stmt = $conn->prepare("SELECT * FROM past_projects WHERE BINARY topic = ?");
    $stmt->execute([$topic]);
    if ($exact_match = $stmt->fetch(PDO::FETCH_ASSOC)) {
        return format_match_details($exact_match, true);
    }

    // 2. Check for similar topics with full details
    $stmt = $conn->prepare("SELECT * FROM past_projects");
    $stmt->execute();
    
    $similar_topics = [];
    while ($past_project = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $similarity = calculate_similarity($topic, $past_project['topic']);
        if ($similarity >= 70) { // 70% similarity threshold
            $past_project['similarity'] = $similarity;
            $similar_topics[] = $past_project;
        }
    }

    if (!empty($similar_topics)) {
        usort($similar_topics, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        $output = "⚠️ Found " . count($similar_topics) . " similar projects:\n\n";
        foreach ($similar_topics as $project) {
            $output .= format_match_details($project) . "\n\n";
        }
        return $output;
    }
    
    return "✅ This topic appears to be unique";
}

// Calculate similarity between two topics
function calculate_similarity($text1, $text2) {
    // Remove common words that don't affect meaning
    $common_words = ['the', 'a', 'an', 'of', 'for', 'in', 'on', 'at', 'to'];
    $text1 = remove_common_words($text1, $common_words);
    $text2 = remove_common_words($text2, $common_words);
    
    // Calculate similarity percentage
    similar_text($text1, $text2, $similarity);
    return $similarity;
}

// Helper function to remove common words
function remove_common_words($str, $words) {
    $pattern = '/\b(' . implode('|', $words) . ')\b/i';
    return preg_replace($pattern, '', $str);
}

// Format match details for display
function format_match_details($project, $is_exact = false) {
    $type = $is_exact ? "EXACT MATCH" : "SIMILAR (" . $project['similarity'] . "%)";
    $pdf_link = $project['pdf_path'] ? "<a href='{$project['pdf_path']}' target='_blank'>View PDF</a>" : "No PDF available";
    
    return <<<DETAILS
    ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
    {$type}
    Topic: {$project['topic']}
    Student: {$project['student_name']} ({$project['student_reg_no']})
    Session: {$project['session']}
    Supervisor: {$project['supervisor_name']}
    Document: {$pdf_link}
    ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
    DETAILS;
}

// Send feedback to student
function send_feedback_to_student($topic_id, $decision, $reason = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT student_reg_no FROM project_topics WHERE id = ?");
    $stmt->execute([$topic_id]);
    $student_reg_no = $stmt->fetchColumn();

    $feedback_message = "Your project topic has been $decision.";
    if ($reason) {
        $feedback_message .= " Reason: $reason";
    }

    $stmt = $conn->prepare("INSERT INTO feedback (student_reg_no, message) VALUES (?, ?)");
    $stmt->execute([$student_reg_no, $feedback_message]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validate Topics</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .action-buttons { display: flex; gap: 5px; }
        .action-buttons button { 
            padding: 5px 10px; 
            font-size: 12px; 
            border: none; 
            border-radius: 3px; 
            cursor: pointer; 
        }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        .validation-explanation { 
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .match-details {
            font-family: monospace;
            white-space: pre-wrap;
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            border-left: 4px solid #6c757d;
        }
        .status-badge { 
            padding: 3px 8px; 
            border-radius: 12px; 
            font-size: 12px; 
            font-weight: bold; 
        }
        .status-pending { background-color: #ffcc00; color: #000; }
        .status-approved { background-color: #4CAF50; color: #fff; }
        .status-rejected { background-color: #f44336; color: #fff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .pagination { margin-top: 20px; }
        .pagination a { padding: 5px 10px; margin-right: 5px; text-decoration: none; }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    <div class="container">
        <h1>Validate Topics</h1>
        
        <!-- Search Bar -->
        <form method="GET" action="" style="margin-bottom: 20px;">
            <input type="text" name="search" placeholder="Search by topic or registration number" 
                   value="<?php echo htmlspecialchars($search); ?>" style="padding: 5px;">
            <button type="submit">Search</button>
        </form>
        
        <table>
            <thead>
                <tr>
                    <th>Topic</th>
                    <th>Student Registration</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?php echo htmlspecialchars($topic['topic']); ?></td>
                    <td><?php echo htmlspecialchars($topic['reg_no']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $topic['status']; ?>">
                            <?php echo ucfirst($topic['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($topic['status'] == 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                    <button type="submit" name="validate_topic">Validate</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                    <button type="submit" name="approve_topic" 
                                        <?php echo !isset($_SESSION['validation_result'][$topic['id']]) ? 'disabled' : ''; ?>>
                                        Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                    <input type="hidden" name="rejection_reason" value="Topic is too similar to an existing project">
                                    <button type="submit" name="reject_topic" 
                                        <?php echo !isset($_SESSION['validation_result'][$topic['id']]) ? 'disabled' : ''; ?>>
                                        Reject
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($_SESSION['validation_result'][$topic['id']])): ?>
                            <div class="validation-explanation">
                                <div class="match-details">
                                    <?php echo nl2br(htmlspecialchars($_SESSION['validation_result'][$topic['id']])); ?>
                                </div>
                            </div>
                            <?php unset($_SESSION['validation_result'][$topic['id']]); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
            <?php endif; ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
        </div>
    </div>
    <?php include_once __DIR__ .'/../includes/footer.php'; ?>
</body>
</html>