<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

// Check if the user is logged in as FPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'fpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Initialize response array for AJAX requests
$response = ['success' => false, 'message' => ''];

// Get current session for defaults
$currentSessionYear = date('Y') . '/' . (date('Y') + 1);

// EXPORT APPROVED TOPICS
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="approved_topics_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Topic', 'Reg_No', 'Student_Name', 'Session']);
    
    $faculty_id = $_SESSION['faculty_id'];
    $stmt = $conn->prepare("SELECT pt.topic, s.reg_no, pt.student_name, pt.session 
                           FROM project_topics pt 
                           LEFT JOIN students s ON pt.student_id = s.id 
                           WHERE pt.status = 'approved' AND s.faculty_id = ?
                           ORDER BY pt.id DESC");
    $stmt->execute([$faculty_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['topic'],
            $row['reg_no'] ?? 'N/A',
            $row['student_name'],
            $row['session']
        ]);
    }
    
    fclose($output);
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';

        // CREATE/ADD TOPIC
        if ($action === 'add_topic') {
            $topic = trim($_POST['topic']);
            $student_reg_no = trim($_POST['student_reg_no']);
            $student_name = trim($_POST['student_name'] ?? '');
            $session = trim($_POST['session']);
            $status = $_POST['status'] ?? 'pending';
            $departmentId = intval($_POST['department_id'] ?? 0);

            if (empty($topic) || empty($student_reg_no) || empty($session)) {
                throw new Exception("Topic, Student Reg No, and Session are required.");
            }

            // Find student ID by Reg No
            $stmt = $conn->prepare("SELECT id FROM students WHERE reg_no = ?");
            $stmt->execute([$student_reg_no]);
            $student = $stmt->fetch();

            if (!$student) {
                // If student doesn't exist, create them
                if (!$departmentId) throw new Exception("Department is required for new students.");
                
                $faculty_id = $_SESSION['faculty_id'];
                // Create user account first
                $hashed_pw = password_hash($student_reg_no, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, department, faculty_id, is_active) VALUES (?, ?, 'stu', ?, ?, ?, 1)");
                $stmt->execute([$student_reg_no, $hashed_pw, $student_name, $departmentId, $faculty_id]);
                $student_id = $conn->lastInsertId();

                // Create student profile profile
                $stmt = $conn->prepare("INSERT INTO students (id, reg_no, name, department, faculty_id, first_login) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$student_id, $student_reg_no, $student_name, $departmentId, $faculty_id]);
            } else {
                $student_id = $student['id'];
                if (!empty($student_name)) {
                    $stmt = $conn->prepare("UPDATE students SET name = ? WHERE id = ?");
                    $stmt->execute([$student_name, $student_id]);
                }
            }

            $stmt = $conn->prepare("INSERT INTO project_topics (topic, student_id, student_name, session, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$topic, $student_id, $student_name, $session, $status]);

            $response['success'] = true;
            $response['message'] = "Project topic added successfully.";
        }

        // UPDATE TOPIC
        elseif ($action === 'update_topic') {
            $id = intval($_POST['id']);
            $topic = trim($_POST['topic']);
            $student_name = trim($_POST['student_name'] ?? '');
            $session = trim($_POST['session']);
            $status = $_POST['status'] ?? 'pending';

            if (empty($topic) || empty($session)) {
                throw new Exception("Topic and Session are required.");
            }

            $faculty_id = $_SESSION['faculty_id'];
            $stmt = $conn->prepare("UPDATE project_topics pt JOIN students s ON pt.student_id = s.id SET pt.topic = ?, pt.student_name = ?, pt.session = ?, pt.status = ? WHERE pt.id = ? AND s.faculty_id = ?");
            $stmt->execute([$topic, $student_name, $session, $status, $id, $faculty_id]);

            $response['success'] = true;
            $response['message'] = "Topic updated successfully!";
        }

        // DELETE TOPIC
        elseif ($action === 'delete_topic') {
            $id = intval($_POST['id']);
            $faculty_id = $_SESSION['faculty_id'];
            $stmt = $conn->prepare("DELETE pt FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE pt.id = ? AND s.faculty_id = ?");
            $stmt->execute([$id, $faculty_id]);
            $response['success'] = true;
            $response['message'] = "Topic deleted successfully!";
        }

        // BATCH UPLOAD
        elseif ($action === 'batch_upload') {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading file.");
            }

            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            fgetcsv($handle); // skip header

            $successCount = 0;
            $errorCount = 0;

            $conn->beginTransaction();
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 2) continue;
                $topicStr = trim($data[0]);
                $regNo = trim($data[1]);
                $name = trim($data[2] ?? '');
                $sessionStr = trim($data[3] ?? $currentSessionYear);

                $stmt = $conn->prepare("SELECT id FROM students WHERE reg_no = ?");
                $stmt->execute([$regNo]);
                $student = $stmt->fetch();

                if (!$student) {
                    $faculty_id = $_SESSION['faculty_id'];
                    // Create user account first
                    $hashed_pw = password_hash($regNo, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, department, faculty_id, is_active) VALUES (?, ?, 'stu', ?, 1, ?, 1)");
                    $stmt->execute([$regNo, $hashed_pw, $name, $faculty_id]);
                    $student_id = $conn->lastInsertId();

                    // Create student profile
                    $stmt = $conn->prepare("INSERT INTO students (id, reg_no, name, department, faculty_id, first_login) VALUES (?, ?, ?, 1, ?, 1)");
                    $stmt->execute([$student_id, $regNo, $name, $faculty_id]);
                } else {
                    $student_id = $student['id'];
                }

                $stmt = $conn->prepare("INSERT INTO project_topics (topic, student_id, student_name, session, status) VALUES (?, ?, ?, ?, 'approved')");
                $stmt->execute([$topicStr, $student_id, $name, $sessionStr]);
                if ($stmt->rowCount() > 0) $successCount++; else $errorCount++;
            }
            $conn->commit();
            fclose($handle);

            $response['success'] = true;
            $response['message'] = "Batch upload completed. $successCount added, $errorCount errors.";
        }

        // UPLOAD PDF
        elseif ($action === 'upload_pdf') {
            $topic_id = intval($_POST['topic_id']);
            if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading PDF.");
            }

            $upload_dir = __DIR__ . '/../assets/uploads/past_projects/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['pdf_file']['name']));
            $target_path = $upload_dir . $file_name;
            $db_path = 'assets/uploads/past_projects/' . $file_name;

            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_path)) {
                $faculty_id = $_SESSION['faculty_id'];
                $stmt = $conn->prepare("UPDATE project_topics pt JOIN students s ON pt.student_id = s.id SET pt.pdf_path = ? WHERE pt.id = ? AND s.faculty_id = ?");
                $stmt->execute([$db_path, $topic_id, $faculty_id]);
                $response['success'] = true;
                $response['message'] = "PDF uploaded successfully!";
            } else {
                throw new Exception("Failed to save file.");
            }
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
    exit();
}

// Fetch Logic
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$faculty_id = $_SESSION['faculty_id'];
$whereClause = "s.faculty_id = ?";
$params = [$faculty_id];
if (!empty($search)) {
    $whereClause .= " AND (pt.topic LIKE ? OR pt.student_name LIKE ? OR s.reg_no LIKE ? OR pt.session LIKE ?)";
    $ps = "%$search%";
    array_push($params, $ps, $ps, $ps, $ps);
}

$countStmt = $conn->prepare("SELECT COUNT(*) FROM project_topics pt LEFT JOIN students s ON pt.student_id = s.id WHERE $whereClause");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $conn->prepare("SELECT pt.*, s.reg_no FROM project_topics pt LEFT JOIN students s ON pt.student_id = s.id WHERE $whereClause ORDER BY pt.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments for this faculty
$featDeptsStmt = $conn->prepare("SELECT id, department_name FROM departments WHERE faculty_id = ? ORDER BY department_name ASC");
$featDeptsStmt->execute([$faculty_id]);
$faculty_departments = $featDeptsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Topics - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #4facfe; --danger: #fa709a; --glass: rgba(255, 255, 255, 0.95); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-bottom: 50px; }
        .container { max-width: 1300px; margin: 0 auto; padding: 20px; }
        .header-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-card h1 { color: var(--primary); font-size: 28px; }
        .header-actions { display: flex; gap: 12px; }
        .btn { padding: 12px 24px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-warning { background: #feca57; color: white; }
        .btn-info { background: #0984e3; color: white; }
        .main-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .search-container { display: flex; gap: 10px; margin-bottom: 25px; }
        .search-input { flex: 1; padding: 14px; border: 2px solid #eee; border-radius: 12px; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--primary); }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; }
        th { background: #f8faff; padding: 18px; text-align: left; color: #747d8c; font-size: 13px; text-transform: uppercase; }
        td { padding: 16px; border-bottom: 1px solid #eee; font-size: 14px; }
        .topic-title { font-weight: 600; color: var(--primary); }
        .status-pill { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fff3e0; color: #ef6c00; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .icon-btn { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: 0.3s; }
        .btn-edit-i { background: #ebf3ff; color: #1e90ff; }
        .btn-delete-i { background: #fff0f3; color: #ff4757; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; width: 95%; max-width: 600px; border-radius: 20px; overflow: hidden; }
        .modal-header { padding: 20px; background: var(--primary); color: white; display: flex; justify-content: space-between; }
        .modal-body { padding: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 10px; border: 2px solid #eee; border-radius: 8px; }
        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { background: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); margin-bottom: 10px; border-left: 5px solid var(--primary); animation: slide 0.3s; }
        @keyframes slide { from { transform: translateX(100%); } to { transform: translateX(0); } }
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-link { padding: 8px 15px; background: white; border-radius: 8px; text-decoration: none; color: var(--primary); }
        .page-link.active { background: var(--primary); color: white; }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    </div> <!-- Close header's container -->
    <div class="container">
        <!-- Header -->
        <div class="header-card">
            <div>
                <h1><i class="fas fa-book-open"></i> Manage Topics</h1>
                <p>Track and manage project titles and student assignments</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAdd()"><i class="fas fa-plus"></i> Add New</button>
                <button class="btn btn-warning" onclick="openModal('batchModal')"><i class="fas fa-upload"></i> Import CSV</button>
                <a href="?export=1" class="btn btn-success" style="color: white; background: #10ac84;"><i class="fas fa-file-export"></i> Export Approved</a>
                <a href="fpc_dashboard.php" class="btn btn-info"><i class="fas fa-home"></i> Home</a>
            </div>
        </div>
        <div class="main-card">
            <div class="search-container">
                <input type="text" id="search-in" class="search-input" placeholder="Search topics, students..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" onclick="doSearch()">Search</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Topic & Student</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topics)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:40px;">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topics as $t): ?>
                            <tr id="r-<?php echo $t['id']; ?>">
                                <td><div class="topic-title"><?php echo htmlspecialchars($t['topic']); ?></div>
                                <small><?php echo htmlspecialchars($t['student_name']); ?> (<?php echo htmlspecialchars($t['reg_no'] ?? 'N/A'); ?>)</small></td>
                                <td><?php echo htmlspecialchars($t['session']); ?></td>
                                <td><span class="status-pill status-<?php echo strtolower($t['status']); ?>"><?php echo $t['status']; ?></span></td>
                                <td style="display:flex; gap:5px;">
                                    <button class="icon-btn btn-edit-i" onclick='openEdit(<?php echo json_encode($t); ?>)'><i class="fas fa-edit"></i></button>
                                    <button class="icon-btn btn-delete-i" onclick="del(<?php echo $t['id']; ?>)"><i class="fas fa-trash"></i></button>
                                    <button class="icon-btn" onclick="openPDF(<?php echo $t['id']; ?>)" style="background:#e8f5e9;color:#2e7d32;" title="Upload/View PDF"><i class="fas fa-file-pdf"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for($i=1;$i<=$totalPages;$i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modals -->
    <div id="topicModal" class="modal"><div class="modal-content">
        <div class="modal-header"><h2 id="m-title">Add Topic</h2><span onclick="closeModal('topicModal')" style="cursor:pointer">&times;</span></div>
        <div class="modal-body"><form id="topicForm">
            <input type="hidden" name="ajax" value="1"><input type="hidden" name="action" id="f-act" value="add_topic"><input type="hidden" name="id" id="f-id">
            <div class="form-group"><label>Topic Title</label><textarea name="topic" id="f-topic" class="form-control" required></textarea></div>
            <div class="form-group" id="reg-wrap"><label>Student Reg No</label><input type="text" name="student_reg_no" id="f-reg" class="form-control"></div>
            <div class="form-group" id="dept-wrap">
                <label>Department (for new students)</label>
                <select name="department_id" id="f-dept" class="form-control">
                    <option value="">Select Department</option>
                    <?php foreach ($faculty_departments as $d): ?>
                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Student Name</label><input type="text" name="student_name" id="f-name" class="form-control"></div>
            <div class="form-group"><label>Session</label><input type="text" name="session" id="f-sess" class="form-control" value="<?php echo $currentSessionYear; ?>"></div>
            <div class="form-group"><label>Status</label><select name="status" id="f-stat" class="form-control"><option value="pending">Pending</option><option value="approved">Approved</option></select></div>
            <button type="submit" class="btn btn-primary" style="width:100%">Save</button>
        </form></div>
    </div></div>

    <div id="batchModal" class="modal"><div class="modal-content">
        <div class="modal-header"><h2>Import CSV</h2><span onclick="closeModal('batchModal')" style="cursor:pointer">&times;</span></div>
        <div class="modal-body"><form id="batchForm"><input type="hidden" name="ajax" value="1"><input type="hidden" name="action" value="batch_upload">
            <div class="form-group"><label>CSV File</label><input type="file" name="csv_file" accept=".csv" class="form-control" required></div>
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; background: #f8faff; padding: 10px; border-radius: 8px; border: 1px dashed #667eea;">
                <small style="color: #636e72;">Format: Topic, Reg_No, Name, Session</small>
                <a href="../assets/topic_template.csv" download class="btn" style="padding: 5px 12px; font-size: 11px; background: #eee; color: #333; height: auto;"><i class="fas fa-download"></i> Template</a>
            </div>
            <button type="submit" class="btn btn-warning" style="width:100%">Upload</button>
        </form></div>
    </div></div>

    <div id="pdfModal" class="modal"><div class="modal-content">
        <div class="modal-header"><h2>Upload PDF</h2><span onclick="closeModal('pdfModal')" style="cursor:pointer">&times;</span></div>
        <div class="modal-body"><form id="pdfForm"><input type="hidden" name="ajax" value="1"><input type="hidden" name="action" value="upload_pdf"><input type="hidden" name="topic_id" id="pdf-id">
            <div class="form-group"><label>PDF File</label><input type="file" name="pdf_file" accept=".pdf" class="form-control" required></div>
            <button type="submit" class="btn btn-primary" style="width:100%">Upload</button>
        </form></div>
    </div></div>

    <div id="toast-container"></div>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
    <script>
        function openModal(id){ 
            const m = document.getElementById(id);
            m.style.display = 'flex';
            setTimeout(() => m.classList.add('active'), 10);
        }
        function closeModal(id){ 
            const m = document.getElementById(id);
            m.classList.remove('active');
            setTimeout(() => m.style.display = 'none', 300);
        }
        function showT(m, s=true){
            const t = document.createElement('div'); t.className = 'toast'; t.innerText = m;
            document.getElementById('toast-container').appendChild(t);
            setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(), 500); }, 3000);
        }
        function doSearch(){ location.href='?search='+encodeURIComponent(document.getElementById('search-in').value); }
        function openAdd(){ 
            document.getElementById('m-title').innerText='Add Topic'; document.getElementById('f-act').value='add_topic';
            document.getElementById('topicForm').reset(); 
            document.getElementById('reg-wrap').style.display='block';
            document.getElementById('dept-wrap').style.display='block';
            openModal('topicModal');
        }
        function openEdit(d){
            document.getElementById('m-title').innerText='Edit Topic'; document.getElementById('f-act').value='update_topic';
            document.getElementById('f-id').value=d.id; document.getElementById('f-topic').value=d.topic;
            document.getElementById('f-name').value=d.student_name; document.getElementById('f-sess').value=d.session;
            document.getElementById('f-stat').value=d.status;
            document.getElementById('reg-wrap').style.display='none'; 
            document.getElementById('dept-wrap').style.display='none';
            openModal('topicModal');
        }
        function openPDF(id){ document.getElementById('pdf-id').value=id; openModal('pdfModal'); }
        
        document.querySelectorAll('form').forEach(f => {
            f.onsubmit = async (e) => {
                e.preventDefault();
                const btn = f.querySelector('button');
                const orig = btn.innerText; btn.disabled = true; btn.innerText = 'Processing...';
                try {
                    const res = await fetch('fpc_manage_topics.php', { method:'POST', body:new FormData(f) });
                    const data = await res.json();
                    showT(data.message);
                    if(data.success) setTimeout(()=>location.reload(), 1000);
                } catch(e) { showT('Error occurred!'); }
                btn.disabled = false; btn.innerText = orig;
            };
        });

        async function del(id){
            if(!confirm('Delete this topic?')) return;
            const fd = new FormData(); fd.append('ajax','1'); fd.append('action','delete_topic'); fd.append('id',id);
            const res = await fetch('fpc_manage_topics.php', { method:'POST', body:fd });
            const data = await res.json(); showT(data.message);
            if(data.success) document.getElementById('r-'+id).remove();
        }
    </script>
</body>
</html>

