<?php
session_start();
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/config.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$supervisor_id = $_SESSION['user_id'];
$message = '';

// Handle Topic Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $student_id = intval($_POST['student_id']);
    
    if ($_POST['action'] === 'approve') {
        $topic_id = intval($_POST['topic_id']);
        try {
            $conn->beginTransaction();
            
            // 1. Reset all topics for this student to rejected
            $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected' WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            // 2. Approve the selected one
            $stmt = $conn->prepare("UPDATE project_topics SET status = 'approved' WHERE id = ? AND student_id = ?");
            $stmt->execute([$topic_id, $student_id]);
            
            // 3. (Optional) You might want to update supervision status, but project_id is removed
            // $stmt = $conn->prepare("UPDATE supervision SET status = 'active' WHERE student_id = ? AND supervisor_id = ?");
            // $stmt->execute([$student_id, $supervisor_id]);
            
            $conn->commit();
            $message = "Topic approved successfully!";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "Error: " . $e->getMessage();
        }
    } 
    elseif ($_POST['action'] === 'reject_all') {
        $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected' WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $message = "All topics rejected for this student.";
    }
    elseif ($_POST['action'] === 'edit_topic') {
        $topic_id = intval($_POST['topic_id']);
        $new_topic = trim($_POST['new_topic']);
        if (!empty($new_topic)) {
            // Verify access: Does this topic belong to a student assigned to this supervisor?
            $stmt = $conn->prepare("
                SELECT pt.id 
                FROM project_topics pt
                JOIN supervision sp ON pt.student_id = sp.student_id
                WHERE pt.id = ? AND sp.supervisor_id = ? AND sp.status = 'active'
            ");
            $stmt->execute([$topic_id, $supervisor_id]);
            if ($stmt->fetch()) {
                $stmt = $conn->prepare("UPDATE project_topics SET topic = ? WHERE id = ?");
                $stmt->execute([$new_topic, $topic_id]);
                $message = "Topic updated successfully!";
            } else {
                $message = "Error: Access denied or topic not found.";
            }
        } else {
            $message = "Error: Topic cannot be empty.";
        }
    }
    elseif ($_POST['action'] === 'validate_topic') {
        $topic_id = intval($_POST['topic_id']);
        $stmt = $conn->prepare("SELECT topic FROM project_topics WHERE id = ?");
        $stmt->execute([$topic_id]);
        $topic_text = $stmt->fetchColumn();
        if ($topic_text) {
            $_SESSION['sup_validation_result'][$topic_id] = validate_hybrid($conn, $topic_text, $topic_id);
        }
    }
}

// Helper Functions
function validate_hybrid($conn, $topic, $currentId) {
    if (!$topic) return "No topic text provided.";
    $cleanInput = clean_text_local($topic);
    
    // Check Past Projects
    $stmt = $conn->prepare("SELECT topic, reg_no FROM past_projects");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        similar_text($cleanInput, clean_text_local($row['topic']), $perc);
        if ($perc > 75) return "⚠️ Similarity Match ($perc%): " . $row['topic'] . " [Reg No: " . $row['reg_no'] . "] (Past Project)";
    }
    
    // Check Other Student Submissions
    $stmt = $conn->prepare("SELECT pt.topic, s.reg_no FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE pt.id != ?");
    $stmt->execute([$currentId]);
    while ($row = $stmt->fetch()) {
        similar_text($cleanInput, clean_text_local($row['topic']), $perc);
        if ($perc > 75) return "⚠️ Similarity Match ($perc%): " . $row['topic'] . " [Reg No: " . $row['reg_no'] . "] (Other Student)";
    }
    
    return validate_with_ai($topic);
}

function clean_text_local($text) {
    if (!$text) return "";
    $common = ['the', 'a', 'an', 'of', 'for', 'in', 'on', 'at', 'to', 'using', 'based', 'study', 'design', 'implementation', 'system'];
    $text = strtolower(trim($text));
    return preg_replace('/\b(' . implode('|', $common) . ')\b/i', '', $text);
}

function validate_with_ai($topic) {
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) return "✅ No local matches found.";
    $url = "https://api.openai.com/v1/chat/completions";
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => "You are an academic validator. Detect if topics are unoriginal or common knowledge. Provide a very brief verdict."],
            ["role" => "user", "content" => "Is the following project topic unique and academically valid for a final year project? Answer in 1 short sentence. Topic: \"$topic\""]
        ],
        "max_tokens" => 60
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_API_KEY],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    $res = json_decode($response, true);
    curl_close($ch);
    return isset($res['choices'][0]['message']['content']) ? "🤖 AI: " . $res['choices'][0]['message']['content'] : "✅ No local matches found (AI Offline).";
}

// Fetch Students and their Topics
$stmt = $conn->prepare("
    SELECT s.id as stu_id, s.name as stu_name, s.reg_no, 
           p.id as topic_id, p.topic, p.status
    FROM students s
    JOIN supervision sp ON s.id = sp.student_id
    JOIN project_topics p ON s.id = p.student_id
    WHERE sp.supervisor_id = ? AND sp.status = 'active'
    ORDER BY s.name, p.id
");
$stmt->execute([$supervisor_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process into grouped array
$students = [];
foreach ($data as $row) {
    if (!isset($students[$row['stu_id']])) {
        $students[$row['stu_id']] = [
            'name' => $row['stu_name'],
            'reg_no' => $row['reg_no'],
            'topics' => []
        ];
    }
    $students[$row['stu_id']]['topics'][] = [
        'id' => $row['topic_id'],
        'title' => $row['topic'],
        'status' => $row['status']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Validation | Supervisor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --success: #1cc88a; --danger: #e74a3b; --warning: #f6c23e; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fc; margin: 0; color: #2d3436; }
        .page-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        
        .header-section { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-section h1 { font-size: 28px; color: #2c3e50; }
        
        .student-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; }
        .student-header { background: #f8f9fa; padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .student-info h3 { margin: 0; font-size: 18px; color: var(--primary); }
        .student-info p { margin: 5px 0 0; font-size: 13px; color: #636e72; font-weight: 600; }
        
        .topics-list { padding: 20px; }
        .topic-row { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 15px; border: 1px solid #f1f2f6; border-radius: 10px; margin-bottom: 10px;
            transition: 0.3s; flex-wrap: wrap;
        }
        .topic-row:hover { background: #fcfdfe; border-color: var(--primary); }
        .topic-content { flex: 1; min-width: 300px; margin-right: 20px; }
        .topic-title { font-size: 15px; font-weight: 500; }
        .topic-status { margin-top: 5px; display: flex; align-items: center; gap: 10px; }
        .ai-result { background: #fff3cd; color: #856404; padding: 8px 12px; border-radius: 8px; font-size: 12px; margin-top: 10px; border-left: 4px solid #ffca28; width: 100%; }

        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: #fff8e1; color: #ff8f00; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }

        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
        .btn-approve { background: var(--success); color: white; }
        .btn-reject { background: var(--danger); color: white; }
        .btn-edit { background: #e9ecef; color: #495057; }
        .btn-validate { background: #e0f2f1; color: #00897b; }
        .btn-back { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 15px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; font-size: 20px; color: var(--primary); }
        .close-modal { font-size: 24px; cursor: pointer; color: #999; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; box-sizing: border-box; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .alert { padding: 15px; border-radius: 10px; background: #e8f5e9; color: #2e7d32; margin-bottom: 25px; border: 1px solid #c8e6c9; }
        
        .empty-state { text-align: center; padding: 60px; color: #636e72; background: white; border-radius: 15px; }
        .empty-state i { font-size: 50px; color: #dfe6e9; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    </div> <!-- Close container from header -->

    <div class="page-container">
        <div class="header-section">
            <div>
                <h1><i class="fas fa-check-circle"></i> Topic Validation</h1>
                <p>Review and validate project proposals from your allocated students.</p>
            </div>
            <a href="sup_dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="alert"><i class="fas fa-check-circle"></i> <?= $message ?></div>
        <?php endif; ?>

        <?php if (empty($students)): ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <h2>No Topics to Review</h2>
                <p>None of your allocated students have submitted topics yet, or you have no students assigned.</p>
            </div>
        <?php else: ?>
            <?php foreach ($students as $sid => $s): 
                $hasApproved = false;
                foreach($s['topics'] as $tp) if($tp['status'] === 'approved') $hasApproved = true;
            ?>
                <div class="student-card">
                    <div class="student-header">
                        <div class="student-info">
                            <h3><?= htmlspecialchars($s['name']) ?></h3>
                            <p><i class="fas fa-id-badge"></i> <?= htmlspecialchars($s['reg_no']) ?></p>
                        </div>
                        <?php if (!$hasApproved): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to reject ALL topics for this student?')">
                                <input type="hidden" name="student_id" value="<?= $sid ?>">
                                <input type="hidden" name="action" value="reject_all">
                                <button type="submit" class="btn btn-reject"><i class="fas fa-times-circle"></i> Reject All</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="topics-list">
                        <?php 
                        $display_topics = $s['topics'];
                        if ($hasApproved) {
                            $display_topics = array_filter($s['topics'], function($tp) { return $tp['status'] === 'approved'; });
                        }
                        foreach ($display_topics as $idx => $t): ?>
                            <div class="topic-row">
                                <div class="topic-content">
                                    <div class="topic-title"><?= htmlspecialchars($t['title']) ?></div>
                                    <div class="topic-status">
                                        <span class="status-pill status-<?= $t['status'] ?>"><?= $t['status'] ?></span>
                                        <?php if (isset($_SESSION['sup_validation_result'][$t['id']])): ?>
                                            <span style="font-size: 11px; font-weight: bold; color: var(--primary);">Checked <i class="fas fa-check-double"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isset($_SESSION['sup_validation_result'][$t['id']])): ?>
                                        <div class="ai-result">
                                            <?= $_SESSION['sup_validation_result'][$t['id']] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="topic-actions" style="display: flex; gap: 5px; align-items: center;">
                                    <?php if ($t['status'] !== 'approved'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="student_id" value="<?= $sid ?>">
                                            <input type="hidden" name="action" value="validate_topic">
                                            <button type="submit" class="btn btn-validate" title="Check for Similarity/AI Validity"><i class="fas fa-robot"></i> Validate</button>
                                        </form>
                                        <button class="btn btn-edit" onclick="openEditModal(<?= $t['id'] ?>, '<?= addslashes(htmlspecialchars($t['title'])) ?>', <?= $sid ?>)" title="Edit Topic"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="student_id" value="<?= $sid ?>">
                                            <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-approve"><i class="fas fa-check"></i> Approve</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-edit" onclick="openEditModal(<?= $t['id'] ?>, '<?= addslashes(htmlspecialchars($t['title'])) ?>', <?= $sid ?>)" title="Edit Topic"><i class="fas fa-edit"></i></button>
                                        <span style="color: var(--success); font-weight: bold; margin-left: 10px;"><i class="fas fa-check-double"></i> Selected Topic</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>

    <!-- Edit Topic Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Project Topic</h2>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_topic">
                <input type="hidden" name="topic_id" id="edit_topic_id">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="form-group">
                    <label for="new_topic">Topic Title</label>
                    <textarea name="new_topic" id="edit_topic_title" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-back" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-approve">Update Topic</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(topicId, topicTitle, studentId) {
            document.getElementById('edit_topic_id').value = topicId;
            document.getElementById('edit_topic_title').value = topicTitle;
            document.getElementById('edit_student_id').value = studentId;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>

