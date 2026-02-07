<?php
session_start();

// Redirect if the user is not logged in or is not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'stu') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

include_once __DIR__ . '/../includes/db.php';

$student_id = $_SESSION['user_id'];

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: stu_login.php");
    exit();
}

// Check submission schedule
$dept_id = $student['department'];
$can_submit = false;
$deadline_info = "No submission schedule set.";

// 1. Check for individual student override first
$stmt = $conn->prepare("SELECT * FROM student_submission_overrides WHERE student_id = ? AND is_active = 1");
$stmt->execute([$student_id]);
$override = $stmt->fetch();

$now = time();

if ($override) {
    $start_time = strtotime($override['submission_start']);
    $end_time = strtotime($override['submission_end']);
    
    if ($now >= $start_time && $now <= $end_time) {
        $can_submit = true;
    }
    $deadline_info = "Individual Extension: " . date('M d, Y H:i', $start_time) . " to " . date('M d, Y H:i', $end_time);
} else {
    // 2. Fallback to departmental schedule
    $stmt = $conn->prepare("SELECT * FROM submission_schedules WHERE department_id = ? AND is_active = 1");
    $stmt->execute([$dept_id]);
    $schedule = $stmt->fetch();

    if ($schedule) {
        $start_time = strtotime($schedule['submission_start']);
        $end_time = strtotime($schedule['submission_end']);
        
        if ($now >= $start_time && $now <= $end_time) {
            $can_submit = true;
        }
        $deadline_info = "Dept Window: " . date('M d, Y H:i', $start_time) . " to " . date('M d, Y H:i', $end_time);
    }
}

// Redirect only if absolutely no schedule exists (optional, but let's be graceful)
// if (!$schedule) { header("Location: stu_dashboard.php"); exit(); }

// Check how many topics they have already submitted
$stmt = $conn->prepare("SELECT COUNT(*) FROM project_topics WHERE student_id = ?");
$stmt->execute([$student_id]);
$submitted_count = $stmt->fetchColumn();

if ($submitted_count >= 3) {
    header("Location: stu_dashboard.php");
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_topics'])) {
    $topic1 = trim($_POST['topic1'] ?? '');
    $topic2 = trim($_POST['topic2'] ?? '');
    $topic3 = trim($_POST['topic3'] ?? '');

    $new_topics = array_filter([$topic1, $topic2, $topic3]);
    
    if (empty($new_topics)) {
        $error = "Please provide at least one project topic.";
    } elseif ($submitted_count + count($new_topics) > 3) {
        $error = "You can only submit up to 3 topics in total. You have already submitted $submitted_count.";
    } else {
        try {
            $conn->beginTransaction();
            $session = $current_session; // Use global session from settings
            $stmt = $conn->prepare("INSERT INTO project_topics (topic, student_id, student_name, session, status) VALUES (?, ?, ?, ?, 'pending')");
            
            foreach ($new_topics as $topic) {
                $stmt->execute([$topic, $student_id, $student['name'], $session]);
            }
            
            $conn->commit();
            header("Location: stu_dashboard.php?success=1");
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "An error occurred while saving your topics. Please try again.";
        }
    }
}

// Handle Similarity AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_similarity') {
    header('Content-Type: application/json');
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
    
    // Check Current Topics
    $stmt = $conn->prepare("SELECT topic FROM project_topics");
    $stmt->execute();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Topics | Project Pro</title>
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
        
        .limit-info { 
            background: #e3f2fd; color: #1976d2; padding: 12px; border-radius: 12px; 
            font-size: 13px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;
        }
        .similarity-warning {
            background: #fff3cd; color: #856404; padding: 10px; border-radius: 10px; 
            font-size: 12px; margin-top: 5px; border-left: 4px solid #ffca28; display: none;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div>

    <div class="page-container">
        <div class="card">
            <h1><i class="fas fa-paper-plane" style="color: var(--primary); margin-right: 10px;"></i>Topic Proposal</h1>
            <p>Enter your proposed project titles below for review and approval.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="limit-info" style="<?= !$can_submit ? 'background: #ffebee; color: #c62828;' : '' ?>">
                <i class="fas <?= $can_submit ? 'fa-info-circle' : 'fa-clock' ?>"></i>
                <div>
                    <div>Total limit: 3 topics. Currently submitted: <strong><?= $submitted_count ?></strong></div>
                    <div style="font-size: 11px; margin-top: 4px; font-weight: 600; opacity: 0.8;"><?= $deadline_info ?></div>
                </div>
            </div>

            <?php if (!$can_submit): ?>
                <div class="alert alert-danger" style="margin-bottom: 25px;">
                    <i class="fas fa-exclamation-triangle"></i> Submission window is currently closed.
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php for ($i = 1; $i <= (3 - $submitted_count); $i++): ?>
                    <div class="form-group" style="<?= !$can_submit ? 'opacity: 0.6;' : '' ?>">
                        <label>Proposed Topic #<?= $submitted_count + $i ?></label>
                        <textarea name="topic<?= $i ?>" class="textarea-styled topic-input" placeholder="<?= $can_submit ? 'Enter topic title...' : 'Submissions closed' ?>" <?= !$can_submit ? 'disabled' : '' ?> onblur="checkSimilarity(this)"></textarea>
                        <div class="similarity-warning"></div>
                    </div>
                <?php endfor; ?>

                <button type="submit" name="submit_topics" class="btn-submit" <?= !$can_submit ? 'disabled style="background: #ccc; cursor: not-allowed;"' : '' ?>>
                    <?= $can_submit ? 'Submit Proposals' : 'Submissions Closed' ?> <i class="fas <?= $can_submit ? 'fa-chevron-right' : 'fa-lock' ?>"></i>
                </button>
            </form>

            <a href="stu_dashboard.php" style="display: block; text-align: center; margin-top: 25px; color: #636e72; text-decoration: none; font-weight: 600; font-size: 14px;">
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

            try {
                const response = await fetch('stu_submit_topic.php', {
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

