<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

// Check if the user is logged in as DPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: /projectval/");
    exit();
}

$dpc_id = $_SESSION['user_id'];

// Fetch the DPC's department info
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name FROM users u JOIN departments d ON u.department = d.id WHERE u.id = ?");
$stmt->execute([$dpc_id]);
$dpc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $dpc_info['dept_id'];
$dept_name = $dpc_info['department_name'];

// Initialize response array for AJAX requests
$response = ['success' => false, 'message' => ''];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';

        // ADD STUDENT
        if ($action === 'add_student') {
            $reg_no = trim($_POST['reg_no']);
            $name = trim($_POST['name']);
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($reg_no) || empty($name)) {
                throw new Exception("Registration Number and Name are required.");
            }

            // Check if exists
            $stmt = $conn->prepare("SELECT id FROM students WHERE reg_no = ?");
            $stmt->execute([$reg_no]);
            if ($stmt->fetch()) {
                throw new Exception("Student with registration number '$reg_no' already exists.");
            }

            // Insert - Using reg_no as default password (hashed)
            $password = password_hash($reg_no, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO students (reg_no, name, phone, email, department, password, first_login) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$reg_no, $name, $phone, $email, $dept_id, $password]);

            $response['success'] = true;
            $response['message'] = "Student added successfully!";
        }

        // UPDATE STUDENT
        elseif ($action === 'update_student') {
            $id = intval($_POST['id']);
            $reg_no = trim($_POST['reg_no']);
            $name = trim($_POST['name']);
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($reg_no) || empty($name)) {
                throw new Exception("Registration Number and Name are required.");
            }

            $stmt = $conn->prepare("UPDATE students SET reg_no = ?, name = ?, phone = ?, email = ? WHERE id = ? AND department = ?");
            $stmt->execute([$reg_no, $name, $phone, $email, $id, $dept_id]);

            $response['success'] = true;
            $response['message'] = "Student updated successfully!";
        }

        // DELETE STUDENT
        elseif ($action === 'delete_student') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ? AND department = ?");
            $stmt->execute([$id, $dept_id]);
            $response['success'] = true;
            $response['message'] = "Student record deleted!";
        }

        // BATCH UPLOAD
        elseif ($action === 'batch_upload') {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading file.");
            }

            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            fgetcsv($handle); // skip header (Expected: reg_no, name, phone, email)

            $successCount = 0;
            $errorCount = 0;

            $conn->beginTransaction();
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 2) continue;
                $reg_no = trim($data[0]);
                $name = trim($data[1]);
                $phone = trim($data[2] ?? '');
                $email = trim($data[3] ?? '');

                // Check if already exists
                $stmt = $conn->prepare("SELECT id FROM students WHERE reg_no = ?");
                $stmt->execute([$reg_no]);
                if ($stmt->fetch()) {
                    $errorCount++;
                    continue;
                }

                $password = password_hash($reg_no, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO students (reg_no, name, phone, email, department, password, first_login) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$reg_no, $name, $phone, $email, $dept_id, $password]);
                $successCount++;
            }
            $conn->commit();
            fclose($handle);

            $response['success'] = true;
            $response['message'] = "Batch upload completed. $successCount added, $errorCount skipped (already exists).";
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

$whereClause = "department = :dept";
$params = [':dept' => $dept_id];
if (!empty($search)) {
    $whereClause .= " AND (reg_no LIKE :search OR name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

$countStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE $whereClause");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $conn->prepare("SELECT * FROM students WHERE $whereClause ORDER BY name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --glass: rgba(255, 255, 255, 0.95); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding-bottom: 50px; }
        .page-container { max-width: 1300px; margin: 0 auto; padding: 20px; }
        .header-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-card h1 { color: var(--primary); font-size: 28px; }
        .header-actions { display: flex; gap: 12px; }
        .btn { padding: 12px 24px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #fff; font-size: 14px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-primary { background: var(--primary); }
        .btn-success { background: var(--success); }
        .btn-warning { background: #feca57; }
        .main-card { background: var(--glass); padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .search-container { display: flex; gap: 10px; margin-bottom: 25px; }
        .search-input { flex: 1; padding: 14px; border: 2px solid #eee; border-radius: 12px; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--primary); }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; }
        th { background: #f8faff; padding: 18px; text-align: left; color: #747d8c; font-size: 13px; text-transform: uppercase; }
        td { padding: 16px; border-bottom: 1px solid #eee; font-size: 14px; }
        .student-name { font-weight: 600; color: var(--primary); }
        .icon-btn { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: 0.3s; }
        .btn-edit-i { background: #ebf3ff; color: #1e90ff; }
        .btn-delete-i { background: #fff0f3; color: #ff4757; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
        .modal.active { display: flex; opacity: 1; }
        .modal-content { background: white; width: 95%; max-width: 500px; border-radius: 20px; overflow: hidden; transform: translateY(20px); transition: 0.3s; }
        .modal.active .modal-content { transform: translateY(0); }
        .modal-header { padding: 20px; background: var(--primary); color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #2d3436; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 10px; font-family: inherit; transition: 0.3s; }
        .form-control:focus { border-color: var(--primary); outline: none; }
        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); margin-bottom: 10px; border-left: 5px solid var(--primary); animation: slideIn 0.3s; }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 25px; }
        .page-link { padding: 10px 18px; background: white; border-radius: 10px; text-decoration: none; color: var(--primary); font-weight: 600; border: 1px solid #eee; }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    </div> <!-- Close header's container -->

    <div class="page-container">
        <!-- Header Card -->
        <div class="header-card">
            <div>
                <h1><i class="fas fa-user-graduate"></i> Manage Students</h1>
                <p style="color: #636e72; margin-top: 5px;">Department of <?= htmlspecialchars($dept_name) ?></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAdd()"><i class="fas fa-user-plus"></i> Add Student</button>
                <button class="btn btn-warning" onclick="openModal('batchModal')"><i class="fas fa-file-import"></i> CSV Import</button>
            </div>
        </div>

        <div class="main-card">
            <!-- Search Section -->
            <div class="search-container">
                <input type="text" id="search-in" class="search-input" placeholder="Search by name, reg no..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" onclick="doSearch()"><i class="fas fa-search"></i> Search</button>
            </div>

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Registration No</th>
                        <th>Email / Phone</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: #636e72;">No students found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                        <tr id="row-<?= $s['id'] ?>">
                            <td><span class="student-name"><?= htmlspecialchars($s['name']) ?></span></td>
                            <td><code><?= htmlspecialchars($s['reg_no']) ?></code></td>
                            <td>
                                <div><small><i class="fas fa-envelope"></i> <?= htmlspecialchars($s['email'] ?: 'N/A') ?></small></div>
                                <div><small><i class="fas fa-phone"></i> <?= htmlspecialchars($s['phone'] ?: 'N/A') ?></small></div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <button class="icon-btn btn-edit-i" onclick='openEdit(<?= json_encode($s) ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="icon-btn btn-delete-i" onclick="del(<?= $s['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modals -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="m-title">Add Student</h2>
                <i class="fas fa-times" onclick="closeModal('studentModal')" style="cursor:pointer"></i>
            </div>
            <div class="modal-body">
                <form id="studentForm">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="action" id="f-act" value="add_student">
                    <input type="hidden" name="id" id="f-id">
                    
                    <div class="form-group">
                        <label>Registration Number</label>
                        <input type="text" name="reg_no" id="f-reg" class="form-control" placeholder="e.g. FCP/CSC/19/1001" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" id="f-name" class="form-control" placeholder="Student's name" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" id="f-email" class="form-control" placeholder="student@example.com">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" id="f-phone" class="form-control" placeholder="080...">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top: 10px;">Save Student</button>
                </form>
            </div>
        </div>
    </div>

    <div id="batchModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Batch CSV Upload</h2>
                <i class="fas fa-times" onclick="closeModal('batchModal')" style="cursor:pointer"></i>
            </div>
            <div class="modal-body">
                <form id="batchForm">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="action" value="batch_upload">
                    <div class="form-group">
                        <label>Select CSV File</label>
                        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                        <small style="color: #636e72; margin-top: 8px; display: block;">File format: reg_no, name, phone, email (first row skipped)</small>
                    </div>
                    <button type="submit" class="btn btn-warning" style="width:100%; margin-top: 10px; color: #fff;">Start Upload</button>
                </form>
            </div>
        </div>
    </div>

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
        function showT(msg, success=true){
            const t = document.createElement('div');
            t.className = 'toast';
            t.style.borderLeftColor = success ? 'var(--success)' : 'var(--danger)';
            t.innerHTML = `<i class="fas fa-${success?'check-circle':'exclamation-circle'}" style="color:${success?'var(--success)':'var(--danger)'}; margin-right:10px;"></i> ${msg}`;
            document.getElementById('toast-container').appendChild(t);
            setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(), 500); }, 3000);
        }
        function doSearch(){ location.href='?search='+encodeURIComponent(document.getElementById('search-in').value); }
        function openAdd(){
            document.getElementById('m-title').innerText='Add New Student';
            document.getElementById('f-act').value='add_student';
            document.getElementById('studentForm').reset();
            openModal('studentModal');
        }
        function openEdit(d){
            document.getElementById('m-title').innerText='Edit Student Details';
            document.getElementById('f-act').value='update_student';
            document.getElementById('f-id').value=d.id;
            document.getElementById('f-reg').value=d.reg_no;
            document.getElementById('f-name').value=d.name;
            document.getElementById('f-email').value=d.email;
            document.getElementById('f-phone').value=d.phone;
            openModal('studentModal');
        }

        document.querySelectorAll('form').forEach(f => {
            f.onsubmit = async (e) => {
                e.preventDefault();
                const btn = f.querySelector('button');
                const origTxt = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                try {
                    const fd = new FormData(f);
                    const res = await fetch('dpc_manage_students.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    showT(data.message, data.success);
                    if(data.success) setTimeout(()=>location.reload(), 1000);
                } catch(err) {
                    showT('A connection error occurred.', false);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = origTxt;
                }
            };
        });

        async function del(id){
            if(!confirm('Are you sure you want to delete this student record?')) return;
            const fd = new FormData();
            fd.append('ajax', '1');
            fd.append('action', 'delete_student');
            fd.append('id', id);
            try {
                const res = await fetch('dpc_manage_students.php', { method: 'POST', body: fd });
                const data = await res.json();
                showT(data.message, data.success);
                if(data.success) document.getElementById('row-'+id).remove();
            } catch(e) { showT('Error deleting record.', false); }
        }

        // Enter key for search
        document.getElementById('search-in').onkeypress = (e) => { if(e.key === 'Enter') doSearch(); };
    </script>
</body>
</html>

