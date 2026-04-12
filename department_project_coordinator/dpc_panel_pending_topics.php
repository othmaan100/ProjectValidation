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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $new_topic = isset($_POST['new_topic']) ? trim($_POST['new_topic']) : '';
        
        if (!empty($new_topic)) {
            $stmt = $conn->prepare("UPDATE project_topics SET topic = ? WHERE id = ?");
            $stmt->execute([$new_topic, $topic_id]);
            $_SESSION['success'] = "Pending topic updated successfully.";
        }
    } elseif (isset($_POST['approve_topic'])) {
        $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_SANITIZE_NUMBER_INT);
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_NUMBER_INT);
        
        $conn->beginTransaction();
        try {
            // Approve selected
            $stmt = $conn->prepare("UPDATE project_topics SET status = 'approved' WHERE id = ?");
            $stmt->execute([$topic_id]);
            
            // Reject others
            $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected' WHERE student_id = ? AND id != ? AND status != 'approved'");
            $stmt->execute([$student_id, $topic_id]);
            
            // Notify student
            $stmt = $conn->prepare("SELECT reg_no FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $reg = $stmt->fetchColumn();
            
            // Insert feedback
            $msg1 = "Your topic was approved based on Panel decision.";
            $conn->prepare("INSERT INTO feedback (student_reg_no, message) VALUES (?, ?)")->execute([$reg, $msg1]);
            
            $msg2 = "Since one of your topics was approved, your other submissions have been automatically rejected.";
            $conn->prepare("INSERT INTO feedback (student_reg_no, message) VALUES (?, ?)")->execute([$reg, $msg2]);

            $conn->commit();
            $_SESSION['success'] = "Topic approved successfully.";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = "Approval failed: " . $e->getMessage();
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . ($search ? "?search=" . urlencode($search) : ""));
    exit();
}

// Fetch total records for pagination
$countQuery = "SELECT COUNT(DISTINCT pt.id) FROM project_topics pt JOIN students s ON s.id = pt.student_id WHERE s.department = :dept AND pt.status = 'pending'";
if ($search) $countQuery .= " AND (s.name LIKE :search OR s.reg_no LIKE :search OR pt.topic LIKE :search)";
$countStmt = $conn->prepare($countQuery);
$countStmt->bindValue(':dept', $dept_id);
if ($search) $countStmt->bindValue(':search', "%$search%");
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch pending topics
$topicQuery = "SELECT pt.id, pt.topic, pt.status, s.id as student_id, s.reg_no, s.name as student_name 
                 FROM project_topics pt 
                 JOIN students s ON s.id = pt.student_id 
                 WHERE s.department = :dept AND pt.status = 'pending'";
if ($search) $topicQuery .= " AND (s.name LIKE :search OR s.reg_no LIKE :search OR pt.topic LIKE :search)";
$topicQuery .= " ORDER BY s.name ASC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($topicQuery);
$stmt->bindValue(':dept', $dept_id);
if ($search) $stmt->bindValue(':search', "%$search%");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Panel Update Pending Topics - DPC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --info: #36b9cc; --warning: #f6c23e; --glass: rgba(255, 255, 255, 0.95); }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-bottom: 50px; }
        .page-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-card h1 { color: var(--warning); font-size: 28px; }
        .main-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .search-container { display: flex; gap: 10px; margin-bottom: 25px; }
        .search-input { flex: 1; padding: 14px; border: 2px solid #eee; border-radius: 12px; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--primary); }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; }
        th { background: #f8faff; padding: 18px; text-align: left; color: #747d8c; font-size: 13px; text-transform: uppercase; }
        td { padding: 20px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .student-name { font-weight: 700; color: #2d3436; font-size: 16px; display: block; }
        .student-reg { color: var(--primary); font-size: 12px; font-weight: 600; }
        .topic-text { font-style: italic; color: #2f3640; line-height: 1.4; display: block; margin-top: 5px; }
        .btn-sm { padding: 8px 15px; border-radius: 8px; font-size: 13px; cursor: pointer; border: none; transition: 0.2s; color: white; display: inline-flex; align-items: center; gap: 6px; }
        .btn-edit { background: var(--primary); }
        .btn-edit:hover { background: #5a6fe0; }
        .btn-approve { background: var(--success); }
        .btn-approve:hover { background: #17a673; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 15px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; font-size: 20px; color: var(--primary); }
        .close-modal { font-size: 24px; cursor: pointer; color: #999; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; color: white; }
        .btn-cancel { background: #6c757d; }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    </div>

    <div class="page-container">
        <div class="header-card">
            <div>
                <h1><i class="fas fa-clock"></i> Panel Updates: Pending Topics</h1>
                <p style="color: #636e72; margin-top: 5px;">View, modify, and approve topics that are pending Panel decision.</p>
            </div>
            <a href="dpc_panel_approved_topics.php" class="btn" style="background:#2d3436; text-decoration:none;"><i class="fas fa-arrow-left"></i> Go to Approved Topics</a>
        </div>

        <div class="main-card">
            <?php if (isset($_SESSION['success'])): ?>
                <div style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #2e7d32;">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: #fff5f5; color: #c62828; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid #c62828;">
                    <i class="fas fa-times-circle"></i> <?= $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="GET" class="search-container">
                <input type="text" name="search" class="search-input" placeholder="Search students or topics..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn" style="background: #2d3436; color:white;"><i class="fas fa-search"></i> Search</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th style="width: 250px;">Student Information</th>
                        <th>Pending Topic</th>
                        <th style="width: 200px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topics)): ?>
                        <tr><td colspan="3" style="text-align:center; padding: 60px; color: #636e72;">No pending topics found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topics as $t): ?>
                        <tr>
                            <td>
                                <span class="student-name"><?= htmlspecialchars($t['student_name']) ?></span>
                                <span class="student-reg"><code><?= htmlspecialchars($t['reg_no']) ?></code></span>
                            </td>
                            <td>
                                <span class="topic-text">"<?= htmlspecialchars($t['topic']) ?>"</span>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <button type="button" onclick='openEditModal(<?= $t['id'] ?>, <?= htmlspecialchars(json_encode($t['topic']), ENT_QUOTES, 'UTF-8') ?>)' class="btn-sm btn-edit" title="Update Topic">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to approve this topic? Other topics for this student will be rejected.');">
                                        <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                                        <input type="hidden" name="student_id" value="<?= $t['student_id'] ?>">
                                        <button type="submit" name="approve_topic" class="btn-sm btn-approve" title="Approve Topic">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                </div>
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

    <!-- Edit Topic Modal -->
    <div id="editTopicModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Pending Topic</h2>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="update_topic" value="1">
                <input type="hidden" name="topic_id" id="edit_topic_id" value="">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: #2d3436; font-weight: 600;">Panel Modified Topic Text</label>
                    <textarea name="new_topic" id="edit_topic_text" rows="5" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; font-family: inherit; font-size: 14px; outline: none;" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn" style="background: var(--primary);"><i class="fas fa-save"></i> Save Topic</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(topicId, currentTopicText) {
            document.getElementById('edit_topic_id').value = topicId;
            document.getElementById('edit_topic_text').value = currentTopicText;
            document.getElementById('editTopicModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editTopicModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editTopicModal')) closeEditModal();
        }
    </script>
    <?php include_once __DIR__ .'/../includes/footer.php'; ?>
</body>
</html>
