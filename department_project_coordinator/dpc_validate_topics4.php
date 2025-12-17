<?php
// Define the API key at the top of the script
define('OPENAI_API_KEY', 'Ask-proj-uelDOmJAyg7aZ14zKyoRejVAXhjxkUX0TYt_HiFAZyM7T9WXVxKZf4E3HdRcKmuHm5HRyTfPRRT3BlbkFJO5t3UgkgatOK8rF_1Q4OnvqxaAPDbnCozIcQu3QWqkyKZQTot1Q4hTNQlYoDMuBrOj69R0RrAA');


// Include other files
include_once __DIR__ .'/../includes/auth.php';
include_once __DIR__ .'/../includes/db.php';
//include __DIR__ . '/includes/functions.php';

// Redirect if user is not DPC
// if ($_SESSION['role'] !== 'dpc') {
//     header("Location: index.php");
//     exit();
// }

// Fetch all topics with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Topics per page
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM project_topics WHERE topic LIKE :search OR student_reg_no LIKE :search LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':search', "%$search%");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT * FROM project_topics LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$topics = $stmt->fetchAll();

// Handle topic validation
// Handle topic validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['validate_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);

        // Fetch the topic from the database
        $stmt = $conn->prepare("SELECT topic FROM project_topics WHERE id = ?");
        $stmt->execute([$topic_id]);
        $topic = $stmt->fetchColumn();

        // Validate topic against past projects
        $validation_explanation = validate_topic_with_chatgpt($topic);

        // Store the validation result in the session
        $_SESSION['validation_result'][$topic_id] = $validation_explanation;
    } elseif (isset($_POST['approve_topic'])) {
        // ... [keep your existing approval code] ...
    } elseif (isset($_POST['reject_topic'])) {
        // ... [keep your existing rejection code] ...
    }
    header("Refresh:2"); // Refresh the page after 2 seconds
}

// Function to validate topic against past projects
function validate_topic_locally($topic) {
    global $conn;
    
    // Clean and prepare the topic for comparison
    $cleaned_topic = trim(strtolower($topic));

    // Step 1: Check for exact matches (case-insensitive)
    $stmt = $conn->prepare("SELECT topic FROM past_projects WHERE LOWER(topic) = ?");
    $stmt->execute([$cleaned_topic]);
    $exact_match = $stmt->fetchColumn();

    if ($exact_match) {
        return "Error: This exact topic already exists in past projects: '$exact_match'";
    }

    // Step 2: Check for similar topics (more advanced comparison)
    $stmt = $conn->prepare("SELECT topic FROM past_projects");
    $stmt->execute();
    $all_past_topics = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $similar_topics = [];
    foreach ($all_past_topics as $past_topic) {
        $similarity = calculate_similarity($cleaned_topic, strtolower($past_topic));
        if ($similarity >= 70) { // 70% similarity threshold
            $similar_topics[] = $past_topic;
        }
    }

    if (!empty($similar_topics)) {
        $similar_list = implode("', '", $similar_topics);
        return "Warning: Found similar topics in past projects: '$similar_list'";
    }

    // If no matches found
    return "Success: This topic appears to be unique and valid.";
}

// Function to calculate similarity between two strings
function calculate_similarity($str1, $str2) {
    // Remove common words that don't affect meaning
    $common_words = ['the', 'a', 'an', 'of', 'for', 'in', 'on', 'at', 'to'];
    $str1 = remove_common_words($str1, $common_words);
    $str2 = remove_common_words($str2, $common_words);
    
    // Calculate similarity percentage
    similar_text($str1, $str2, $similarity);
    return $similarity;
}

// Helper function to remove common words
function remove_common_words($str, $words) {
    $pattern = '/\b(' . implode('|', $words) . ')\b/i';
    return preg_replace($pattern, '', $str);
}



// Function to validate topic using ChatGPT API
function validate_topic_with_chatgpt($topic) {
    global $conn;

    // Step 1: Check for exact matches in past_projects table
    $stmt = $conn->prepare("SELECT topic FROM past_projects WHERE topic = ?");
    $stmt->execute([$topic]);
    $exact_match = $stmt->fetchColumn();

    if ($exact_match) {
        return "Error: This topic already exists in past projects.";
    }

    // Step 2: Check for similar topics in past_projects table
    $stmt = $conn->prepare("SELECT topic FROM past_projects WHERE topic LIKE ?");
    $stmt->execute(["%$topic%"]);
    $similar_topics = $stmt->fetchAll();

    if (!empty($similar_topics)) {
        $similar_topics_list = implode(", ", array_column($similar_topics, 'topic'));
        return "Warning: Similar topics found in past projects: $similar_topics_list";
    }

    // Step 3: Validate the topic using ChatGPT API
    $url = "https://api.openai.com/v1/completions";
    $data = [
        "model" => "text-davinci-003",
        "prompt" => "Validate the following project topic: '$topic'. Is it original and relevant? Provide a short explanation (1-2 sentences) for your answer.",
        "max_tokens" => 100,
        "temperature" => 0.7
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code == 429) {
        return "Error: API rate limit exceeded. Please try again later.";
    } elseif ($http_code == 401) {
        return "Error: Invalid API key. Please check your OpenAI API key.";
    } elseif ($http_code != 200) {
        return "Error: Unable to validate the topic. HTTP Code: $http_code";
    }

    curl_close($ch);

    $response_data = json_decode($response, true);
    if (isset($response_data['choices'][0]['text'])) {
        return trim($response_data['choices'][0]['text']);
    }

    return "Error: Unable to validate the topic. Please try again.";
}

// Function to send feedback to the student
function send_feedback_to_student($topic_id, $decision, $reason = '') {
    global $conn;

    // Fetch the student's registration number
    $stmt = $conn->prepare("SELECT student_reg_no FROM project_topics WHERE id = ?");
    $stmt->execute([$topic_id]);
    $student_reg_no = $stmt->fetchColumn();

    // Prepare the feedback message
    $feedback_message = "Your project topic has been $decision.";
    if ($reason) {
        $feedback_message .= " Reason: $reason";
    }

    // Store the feedback in the database
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
        .action-buttons button { padding: 5px 10px; font-size: 12px; border: none; border-radius: 3px; cursor: pointer; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        .validation-explanation { margin-top: 5px; font-size: 12px; color: #555; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .status-pending { background-color: #ffcc00; color: #000; }
        .status-approved { background-color: #4CAF50; color: #fff; }
        .status-rejected { background-color: #f44336; color: #fff; }
    </style>
    <script>
        function confirmAction(action, topicId) {
            if (confirm(`Are you sure you want to ${action} this topic?`)) {
                document.getElementById(`form-${topicId}`).submit();
            }
        }
    </script>
</head>
<body>
     <?php include_once __DIR__ .'/../includes/header.php';?>
    <div class="container">
        <h1>Validate Topics</h1>
        <!-- Search Bar -->
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by topic or registration number" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
        <table>
            <tr>
                <th>Topic</th>
                <th>Student Registration Number</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?php echo htmlspecialchars($topic['topic']); ?></td>
                    <td><?php echo htmlspecialchars($topic['student_reg_no']); ?></td>
                    <td>
                        <span class="status-badge 
                            <?php
                                if ($topic['status'] == 'pending') echo 'status-pending';
                                elseif ($topic['status'] == 'approved') echo 'status-approved';
                                elseif ($topic['status'] == 'rejected') echo 'status-rejected';
                            ?>">
                            <?php echo ucfirst($topic['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($topic['status'] == 'pending'): ?>
                                <form id="form-validate-<?php echo $topic['id']; ?>" method="POST" style="display: inline;">
                                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                    <button type="submit" name="validate_topic">Validate</button>
                                </form>
                                <form id="form-approve-<?php echo $topic['id']; ?>" method="POST" style="display: inline;">
                                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                    <button type="button" onclick="confirmAction('approve', <?php echo $topic['id']; ?>)">Approve</button>
                                </form>
                                <form id="form-reject-<?php echo $topic['id']; ?>" method="POST" style="display: inline;">
                                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                    <input type="hidden" name="rejection_reason" value="Topic is too similar to an existing topic.">
                                    <button type="button" onclick="confirmAction('reject', <?php echo $topic['id']; ?>)">Reject</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($_SESSION['validation_result'][$topic['id']])): ?>
                            <div class="validation-explanation">
                                <?php echo $_SESSION['validation_result'][$topic['id']]; ?>
                                <?php //unset($_SESSION['validation_result'][$topic['id']]); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
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