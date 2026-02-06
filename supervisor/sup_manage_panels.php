<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sup') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$supervisor_id = $_SESSION['user_id'];

// Get current session
$session_stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_session'");
$active_session = $session_stmt->fetchColumn() ?: date('Y') . '/' . (date('Y') + 1);

$message = '';
$message_type = '';

// Handle assessment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assessment'])) {
    $student_id = $_POST['student_id'];
    $panel_id = $_POST['panel_id'];
    $score = $_POST['score'];
    $comments = trim($_POST['comments']);

    try {
        $stmt = $conn->prepare("
            INSERT INTO defense_scores (student_id, supervisor_id, panel_id, score, comments)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE score = VALUES(score), comments = VALUES(comments)
        ");
        $stmt->execute([$student_id, $supervisor_id, $panel_id, $score, $comments]);
        $message = "Assessment saved successfully!";
        $message_type = "success";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Fetch panels where current supervisor is a member
$panel_stmt = $conn->prepare("
    SELECT dp.id, dp.panel_name, dp.panel_type
    FROM defense_panels dp
    JOIN panel_members pm ON dp.id = pm.panel_id
    WHERE pm.supervisor_id = ?
    ORDER BY FIELD(dp.panel_type, 'proposal', 'internal', 'external'), dp.panel_name
");
$panel_stmt->execute([$supervisor_id]);
$panels = $panel_stmt->fetchAll(PDO::FETCH_ASSOC);

// For each panel, fetch assigned students and their current assessment
$panels_data = [];
foreach ($panels as $panel) {
    $student_stmt = $conn->prepare("
        SELECT s.id as student_id, s.name as student_name, s.reg_no, 
               pt.topic as project_title,
               ds.score, ds.comments
        FROM students s
        JOIN student_panel_assignments spa ON s.id = spa.student_id
        LEFT JOIN project_topics pt ON s.id = pt.student_id AND pt.status = 'approved'
        LEFT JOIN defense_scores ds ON s.id = ds.student_id AND ds.panel_id = ? AND ds.supervisor_id = ?
        WHERE spa.panel_id = ? AND spa.academic_session = ? AND spa.panel_type = ?
        GROUP BY s.id
    ");
    $student_stmt->execute([$panel['id'], $supervisor_id, $panel['id'], $active_session, $panel['panel_type']]);
    $students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $panels_data[] = [
        'panel_name' => $panel['panel_name'],
        'panel_type' => $panel['panel_type'],
        'panel_id' => $panel['id'],
        'students' => $students
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Panels & Assessments | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #4e73df; 
            --success: #1cc88a; 
            --danger: #e74a3b; 
            --info: #36b9cc; 
            --bg: #f8f9fc;
        }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; color: #2d3436; }
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        
        .header { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; color: var(--primary); margin: 0; }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-danger { background: #f8d7da; color: #842029; }

        .panel-card { background: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden; }
        .panel-header { background: var(--primary); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .panel-header h2 { margin: 0; font-size: 20px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px 30px; background: #f1f3f9; color: #4b6584; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 18px 30px; border-bottom: 1px solid #f1f3f9; vertical-align: middle; }
        
        .student-info { display: flex; flex-direction: column; }
        .student-name { font-weight: 700; color: #2c3e50; }
        .student-reg { font-size: 13px; color: #7f8c8d; }
        
        .project-title { font-size: 14px; color: #34495e; font-style: italic; max-width: 300px; }
        
        .score-badge { padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 14px; }
        .score-yes { background: #e8f5e9; color: #2e7d32; }
        .score-no { background: #fff3e0; color: #ef6c00; }

        .type-badge { padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-left: 10px; }
        .type-proposal { background: #e0f2fe; color: #0369a1; }
        .type-internal { background: #fef3c7; color: #92400e; }
        .type-external { background: #dcfce7; color: #166534; }

        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; transition: 0.3s; border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-assess { background: var(--info); color: white; }
        .btn-assess:hover { background: #2da4b9; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background: white; margin: 60px auto; padding: 30px; border-radius: 20px; width: 650px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; color: var(--primary); }
        .close { font-size: 28px; cursor: pointer; color: #999; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; }
        input[type="number"], textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-family: inherit; font-size: 16px; box-sizing: border-box; }
        textarea { height: 120px; resize: none; }
        input:focus, textarea:focus { border-color: var(--primary); outline: none; }

        .submit-btn { width: 100%; padding: 15px; background: var(--success); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .submit-btn:hover { background: #17a673; transform: translateY(-2px); }

    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1>Project Defense Panels</h1>
                <p style="color: #858796; margin: 5px 0 0 0;">Manage assessments for students in your assigned panels.</p>
            </div>
            <a href="index.php" class="btn btn-assess" style="background: #eaecf4; color: #5a5c69;"><i class="fas fa-home"></i> Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (empty($panels_data)): ?>
            <div style="text-align: center; padding: 100px 20px; background: white; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <i class="fas fa-users-slash" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></i>
                <h2>No Panels Assigned</h2>
                <p style="color: #777;">You are not currently assigned to any defense panels for this session.</p>
            </div>
        <?php else: ?>
            <?php foreach ($panels_data as $data): ?>
                <div class="panel-card">
                    <div class="panel-header">
                        <div style="display: flex; align-items: center;">
                            <h2><i class="fas fa-users"></i> <?= htmlspecialchars($data['panel_name']) ?></h2>
                            <span class="type-badge type-<?= $data['panel_type'] ?>"><?= $data['panel_type'] ?></span>
                        </div>
                        <span style="font-size: 14px; opacity: 0.9;">Academic Session: <?= $active_session ?></span>
                    </div>
                    <?php if (empty($data['students'])): ?>
                        <div style="padding: 40px; text-align: center; color: #888;">No students assigned to this panel yet.</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Approved Project Title</th>
                                    <th>Assessed</th>
                                    <th>Score</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['students'] as $stu): ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <span class="student-name"><?= htmlspecialchars($stu['student_name']) ?></span>
                                                <span class="student-reg"><?= htmlspecialchars($stu['reg_no']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="project-title"><?= htmlspecialchars($stu['project_title'] ?: 'No approved project yet') ?></div>
                                        </td>
                                        <td>
                                            <?php if ($stu['score'] !== null): ?>
                                                <span class="score-badge score-yes"><i class="fas fa-check"></i> Yes</span>
                                            <?php else: ?>
                                                <span class="score-badge score-no"><i class="fas fa-times"></i> No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= $stu['score'] !== null ? $stu['score'] : '--' ?></strong>
                                        </td>
                                        <td style="text-align: right;">
                                            <button class="btn btn-assess" onclick="openAssessmentModal(
                                                '<?= $stu['student_id'] ?>', 
                                                '<?= $data['panel_id'] ?>', 
                                                '<?= htmlspecialchars($stu['student_name']) ?>', 
                                                '<?= $stu['score'] ?>', 
                                                '<?= htmlspecialchars($stu['comments'] ?: '') ?>'
                                            )">
                                                <i class="fas fa-vial"></i> <?= $stu['score'] !== null ? 'Edit Score' : 'Assess' ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Assessment Modal -->
    <div id="assessmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalStudentName">Assess Student</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="student_id" id="modalStudentId">
                <input type="hidden" name="panel_id" id="modalPanelId">
                
                <div class="form-group">
                    <label for="score">Score (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="score" id="modalScore" required placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="comments">Reviewer Comments</label>
                    <textarea name="comments" id="modalComments" placeholder="Enter feedback or notes for this student..."></textarea>
                </div>
                
                <button type="submit" name="submit_assessment" class="submit-btn">Save Assessment</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("assessmentModal");

        function openAssessmentModal(studentId, panelId, studentName, score, comments) {
            document.getElementById("modalStudentId").value = studentId;
            document.getElementById("modalPanelId").value = panelId;
            document.getElementById("modalStudentName").innerText = "Assess: " + studentName;
            document.getElementById("modalScore").value = score !== '' ? score : '';
            document.getElementById("modalComments").value = comments;
            modal.style.display = "block";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
