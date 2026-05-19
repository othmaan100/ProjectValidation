<?php
session_start();

// Redirect if the user is not logged in or is not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'stu') {
    header("Location: ../index.php");
    exit();
}

include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';

$student_id = $_SESSION['user_id'];
$topic_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$topic_id) {
    header("Location: stu_view_status.php");
    exit();
}

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: stu_login.php");
    exit();
}

// Fetch the topic to ensure it belongs to the student and is rejected
$stmt = $conn->prepare("SELECT * FROM project_topics WHERE id = ? AND student_id = ?");
$stmt->execute([$topic_id, $student_id]);
$topic_data = $stmt->fetch();

if (!$topic_data) {
    header("Location: stu_view_status.php");
    exit();
}

if ($topic_data['status'] !== 'rejected') {
    header("Location: stu_view_status.php?error=not_rejected");
    exit();
}

$message = '';
$error = '';

// Handle similarity check AJAX (Copied from stu_submit_topic.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_similarity') {
    header('Content-Type: application/json');
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit();
    }
    $topic = trim($_POST['topic'] ?? '');
    
    if (empty($topic)) {
        echo json_encode(['status' => 'clear']);
        exit();
    }

    $cleanInput = strtolower(trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $topic)));
    $common = ['the', 'a', 'an', 'of', 'for', 'in', 'on', 'at', 'to', 'using', 'based', 'study', 'design', 'implementation', 'system'];
    $cleanInput = preg_replace('/\b(' . implode('|', $common) . ')\b/i', '', $cleanInput);

    $matches = [];
    
    // Check Past Projects
    $stmt = $conn->prepare("SELECT topic FROM past_projects");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        similar_text($cleanInput, strtolower($row['topic']), $perc);
        if ($perc > 75) $matches[] = ["topic" => $row['topic'], "source" => "Past Project"];
    }
    
    // Check Current Topics (Excluding current topic being edited)
    $stmt = $conn->prepare("SELECT topic FROM project_topics WHERE id != ?");
    $stmt->execute([$topic_id]);
    while ($row = $stmt->fetch()) {
        similar_text($cleanInput, strtolower($row['topic']), $perc);
        if ($perc > 75) $matches[] = ["topic" => $row['topic'], "source" => "Other Student Submission"];
    }

    if (!empty($matches)) {
        echo json_encode(['status' => 'match', 'matches' => array_slice($matches, 0, 2)]);
    } else {
        echo json_encode(['status' => 'clear']);
    }
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_topic'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Session expired. Please refresh and try again.";
    } else {
        $new_topic = trim($_POST['topic'] ?? '');

        if (empty($new_topic)) {
            $error = "Please provide a project topic.";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE project_topics SET topic = ?, status = 'pending' WHERE id = ? AND student_id = ?");
                $stmt->execute([$new_topic, $topic_id, $student_id]);
                
                header("Location: stu_view_status.php?success=updated");
                exit();
            } catch (Exception $e) {
                $error = "An error occurred while updating your topic. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Topic | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; }
        .page-container { max-width: 700px; margin: 50px auto; padding: 20px; }
        .card { 
            background: var(--glass); padding: 40px; border-radius: 25px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.2); backdrop-filter: blur(15px);
        }
        .card h1 { color: #2d3436; font-size: 26px; margin-bottom: 10px; text-align: center; }
        .card p { color: #636e72; font-size: 14px; text-align: center; margin-bottom: 30px; }
        
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: 700; color: #2d3436; font-size: 14px; }
        .textarea-styled { 
            width: 100%; padding: 15px; border: 2px solid #eee; border-radius: 15px; 
            font-family: inherit; font-size: 15px; transition: 0.3s; box-sizing: border-box;
            resize: vertical; min-height: 80px;
        }
        .textarea-styled:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 10px rgba(102, 126, 234, 0.1); }
        
        .btn-submit { 
            width: 100%; padding: 16px; border: none; border-radius: 15px; 
            background: var(--primary); color: white; font-weight: 700; cursor: pointer; 
            transition: 0.3s; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-submit:hover { background: var(--secondary); transform: translateY(-2px); }
        
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-size: 14px; font-weight: 600; text-align: center; }
        .alert-danger { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        .similarity-warning {
            background: #fff3cd; color: #856404; padding: 10px; border-radius: 10px; 
            font-size: 12px; margin-top: 5px; border-left: 4px solid #ffca28; display: none;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    <div class="page-container">
        <div class="card">
            <h1><i class="fas fa-edit" style="color: var(--primary); margin-right: 10px;"></i>Update Rejected Topic</h1>
            <p>Modify your rejected topic title below and resubmit for approval. Once updated, the status will return to pending.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label>Project Topic Title</label>
                    <textarea name="topic" class="textarea-styled topic-input" placeholder="Enter new topic title..." onblur="checkSimilarity(this)"><?= htmlspecialchars($topic_data['topic']) ?></textarea>
                    <div class="similarity-warning"></div>
                </div>

                <button type="submit" name="update_topic" class="btn-submit">
                    Update and Resubmit <i class="fas fa-chevron-right"></i>
                </button>
            </form>

            <a href="stu_view_status.php" style="display: block; text-align: center; margin-top: 25px; color: #636e72; text-decoration: none; font-weight: 600; font-size: 14px;">
                <i class="fas fa-arrow-left"></i> Cancel and Return
            </a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
    <script>
        async function checkSimilarity(textarea) {
            const topic = textarea.value.trim();
            const warningBox = textarea.nextElementSibling;
            
            if (topic.length < 10) {
                warningBox.style.display = 'none';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'check_similarity');
            formData.append('topic', topic);
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('stu_update_topic.php?id=<?= $topic_id ?>', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'match') {
                    let html = '<strong>⚠️ Similarity Alert:</strong> Similar projects found:<br>';
                    result.matches.forEach(m => {
                        html += `• "${m.topic}" (${m.source})<br>`;
                    });
                    html += '<em>Suggest modifying your topic to ensure uniqueness.</em>';
                    warningBox.innerHTML = html;
                    warningBox.style.display = 'block';
                } else {
                    warningBox.style.display = 'none';
                }
            } catch (err) {
                console.error(err);
            }
        }
    </script>
</body>
</html>
