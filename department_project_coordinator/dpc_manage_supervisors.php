<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';

// Check if the user is logged in as DPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
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
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }
    
    try {
        $action = $_POST['action'] ?? '';

        // ADD SUPERVISOR
        if ($action === 'add_supervisor') {
            $staff_no = trim($_POST['staff_no']);
            $name = trim($_POST['name']);
            $email = trim($_POST['email'] ?? '');
            $max_students = intval($_POST['max_students'] ?: 10);

            if (empty($staff_no) || empty($name)) {
                throw new Exception("Staff Number and Name are required.");
            }

            // Check if exists in any department
            $stmt = $conn->prepare("SELECT id, department FROM supervisors WHERE staff_no = ?");
            $stmt->execute([$staff_no]);
            $existing = $stmt->fetch();
            if ($existing) {
                throw new Exception("Supervisor with staff number '$staff_no' already exists.");
            }

            $password = password_hash($staff_no, PASSWORD_DEFAULT);
            $conn->beginTransaction();
            try {
                // Insert into users first
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, email, department, is_active) VALUES (?, ?, 'sup', ?, ?, ?, 1)");
                $stmt->execute([$staff_no, $password, $name, $email, $dept_id]);
                $user_id = $conn->lastInsertId();

                // Insert into supervisors - Removed password column
                $stmt = $conn->prepare("INSERT INTO supervisors (id, staff_no, name, email, department, max_students, current_load) VALUES (?, ?, ?, ?, ?, ?, 0)");
                $stmt->execute([$user_id, $staff_no, $name, $email, $dept_id, $max_students]);
                
                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Supervisor added and user account created successfully!";
            } catch (Exception $e) { $conn->rollBack(); throw $e; }
        }

        // UPDATE SUPERVISOR
        elseif ($action === 'update_supervisor') {
            $id = intval($_POST['id']);
            $staff_no = trim($_POST['staff_no']);
            $name = trim($_POST['name']);
            $email = trim($_POST['email'] ?? '');
            $max_students = intval($_POST['max_students']);

            if (empty($staff_no) || empty($name)) {
                throw new Exception("Staff Number and Name are required.");
            }

            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("UPDATE supervisors SET staff_no = ?, name = ?, email = ?, max_students = ? WHERE id = ? AND department = ?");
                $stmt->execute([$staff_no, $name, $email, $max_students, $id, $dept_id]);
                
                if ($stmt->rowCount() === 0) throw new Exception("Supervisor not found or doesn't belong to your department.");

                $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, email = ? WHERE id = ? AND role = 'sup'");
                $stmt->execute([$staff_no, $name, $email, $id]);

                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Supervisor updated successfully!";
            } catch (Exception $e) { $conn->rollBack(); throw $e; }
        }

        // DELETE SUPERVISOR
        elseif ($action === 'delete_supervisor') {
            $id = intval($_POST['id']);
            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("DELETE FROM supervisors WHERE id = ? AND department = ?");
                $stmt->execute([$id, $dept_id]);

                if ($stmt->rowCount() > 0) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'sup'");
                    $stmt->execute([$id]);
                }

                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Supervisor record and login account deleted!";
            } catch (Exception $e) { $conn->rollBack(); throw $e; }
        }

        // RESET PASSWORD
        elseif ($action === 'reset_password') {
            $id = intval($_POST['id']);
            
            // Get staff_no
            $stmt = $conn->prepare("SELECT staff_no FROM supervisors WHERE id = ? AND department = ?");
            $stmt->execute([$id, $dept_id]);
            $staff_no = $stmt->fetchColumn();

            if (!$staff_no) throw new Exception("Supervisor not found.");

            $new_password = password_hash($staff_no, PASSWORD_DEFAULT);

            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'sup'");
                $stmt->execute([$new_password, $id]);

                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Password reset to Staff Number ($staff_no) successfully!";
            } catch (Exception $e) { $conn->rollBack(); throw $e; }
        }

        // BATCH UPLOAD
        elseif ($action === 'batch_upload') {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading file.");
            }

            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            fgetcsv($handle); // skip header (Expected: staff_no, name, email, max_students)

            $successCount = 0;
            $errorCount = 0;

            $conn->beginTransaction();
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 2) continue;
                $staff_no = trim($data[0]);
                $name = trim($data[1]);
                $email = trim($data[2] ?? '');
                $max_students = intval($data[3] ?? 10);

                try {
                    $password = password_hash($staff_no, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, email, department, is_active) VALUES (?, ?, 'sup', ?, ?, ?, 1)");
                    $stmt->execute([$staff_no, $password, $name, $email, $dept_id]);
                    $user_id = $conn->lastInsertId();

                    $stmt = $conn->prepare("INSERT INTO supervisors (id, staff_no, name, email, department, max_students, current_load) VALUES (?, ?, ?, ?, ?, ?, 0)");
                    $stmt->execute([$user_id, $staff_no, $name, $email, $dept_id, $max_students]);
                    $successCount++;
                } catch (Exception $e) { $errorCount++; continue; }
            }
            $conn->commit();
            fclose($handle);
            $response['success'] = true;
            $response['message'] = "Batch upload completed. $successCount added, $errorCount skipped.";
        }
    } catch (Exception $e) { if ($conn->inTransaction()) $conn->rollBack(); $response['success'] = false; $response['message'] = $e->getMessage(); }
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
    $whereClause .= " AND (staff_no LIKE :search OR name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

$countStmt = $conn->prepare("SELECT COUNT(*) FROM supervisors WHERE $whereClause");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$stmt = $conn->prepare("SELECT * FROM supervisors WHERE $whereClause ORDER BY name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Supervisors - <?= htmlspecialchars($dept_name) ?></title>
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
        .name-bold { font-weight: 600; color: var(--primary); }
        .icon-btn { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: 0.3s; }
        .btn-edit-i { background: #ebf3ff; color: #1e90ff; }
        .btn-delete-i { background: #fff0f3; color: #ff4757; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); align-items: flex-start; justify-content: center; padding-top: 60px; opacity: 0; transition: 0.3s; }
        .modal.active { display: flex; opacity: 1; }
        .modal-content { background: white; width: 95%; max-width: 700px; border-radius: 20px; overflow: hidden; transform: translateY(20px); transition: 0.3s; }
        .modal.active .modal-content { transform: translateY(0); }
        .btn-reset-i { background: #fff4e5; color: #ff9f43; }
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
        <?php echo csrf_field(); ?>
        <div class="header-card">
            <div>
                <h1><i class="fas fa-user-tie"></i> Manage Supervisors</h1>
                <p style="color: #636e72; margin-top: 5px;">Department of <?= htmlspecialchars($dept_name) ?></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAdd()"><i class="fas fa-plus"></i> Add Supervisor</button>
                <button class="btn btn-warning" onclick="openModal('batchModal')"><i class="fas fa-file-import"></i> CSV Import</button>
            </div>
        </div>

        <div class="main-card">
            <div class="search-container">
                <input type="text" id="search-in" class="search-input" placeholder="Search by name, staff no..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-primary" onclick="doSearch()"><i class="fas fa-search"></i> Search</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Staff Name</th>
                        <th>Staff Number</th>
                        <th>Email / Load</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($supervisors)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: #636e72;">No supervisors found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($supervisors as $s): ?>
                        <tr id="row-<?= $s['id'] ?>">
                            <td><span class="name-bold"><?= htmlspecialchars($s['name']) ?></span></td>
                            <td><code><?= htmlspecialchars($s['staff_no']) ?></code></td>
                            <td>
                                <div><small><i class="fas fa-envelope"></i> <?= htmlspecialchars($s['email'] ?: 'N/A') ?></small></div>
                                <div><small><i class="fas fa-users"></i> Load: <?= $s['current_load'] ?> / <?= $s['max_students'] ?></small></div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <button class="icon-btn btn-reset-i" onclick="resetPwd(<?= $s['id'] ?>, '<?= htmlspecialchars($s['staff_no']) ?>')" title="Reset Password to Staff ID"><i class="fas fa-key"></i></button>
                                    <button class="icon-btn btn-edit-i" onclick='openEdit(<?= json_encode($s) ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="icon-btn btn-delete-i" onclick="del(<?= $s['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

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
    <div id="supModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="m-title">Add Supervisor</h2>
                <i class="fas fa-times" onclick="closeModal('supModal')" style="cursor:pointer"></i>
            </div>
            <div class="modal-body">
                <form id="supForm">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="action" id="f-act" value="add_supervisor">
                    <input type="hidden" name="id" id="f-id">
                    
                    <div class="form-group">
                        <label>Staff Number</label>
                        <input type="text" name="staff_no" id="f-staff" class="form-control" placeholder="e.g. SP/CSC/001" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" id="f-name" class="form-control" placeholder="FullName" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" id="f-email" class="form-control" placeholder="staff@example.com">
                    </div>
                    <div class="form-group">
                        <label>Max Student Load</label>
                        <input type="number" name="max_students" id="f-max" class="form-control" value="10" min="1">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top: 10px;">Save Supervisor</button>
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
                        <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <small style="color: #636e72;">Format: staff_no, name, email, max_students</small>
                            <a href="../assets/supervisor_template.csv" download class="btn" style="padding: 5px 10px; font-size: 11px; background: #eee; color: #333;"><i class="fas fa-download"></i> Template</a>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning" style="width:100%; margin-top: 10px; color: #fff;">Start Upload</button>
                </form>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function openModal(id){ const m = document.getElementById(id); m.style.display = 'flex'; setTimeout(() => m.classList.add('active'), 10); }
        function closeModal(id){ const m = document.getElementById(id); m.classList.remove('active'); setTimeout(() => m.style.display = 'none', 300); }
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
            document.getElementById('m-title').innerText='Add New Supervisor';
            document.getElementById('f-act').value='add_supervisor';
            document.getElementById('supForm').reset();
            openModal('supModal');
        }
        function openEdit(d){
            document.getElementById('m-title').innerText='Edit Supervisor';
            document.getElementById('f-act').value='update_supervisor';
            document.getElementById('f-id').value=d.id;
            document.getElementById('f-staff').value=d.staff_no;
            document.getElementById('f-name').value=d.name;
            document.getElementById('f-email').value=d.email;
            document.getElementById('f-max').value=d.max_students;
            openModal('supModal');
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
                    const token = document.querySelector('input[name="csrf_token"]').value;
                    fd.append('csrf_token', token);
                    const res = await fetch('dpc_manage_supervisors.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    showT(data.message, data.success);
                    if(data.success) setTimeout(()=>location.reload(), 1000);
                } catch(err) { showT('An error occurred.', false); }
                finally { btn.disabled = false; btn.innerHTML = origTxt; }
            };
        });

        async function del(id){
            if(!confirm('Delete this supervisor?')) return;
            const fd = new FormData();
            fd.append('ajax', '1');
            fd.append('action', 'delete_supervisor');
            fd.append('id', id);
            const token = document.querySelector('input[name="csrf_token"]').value;
            fd.append('csrf_token', token);
            try {
                const res = await fetch('dpc_manage_supervisors.php', { method: 'POST', body: fd });
                const data = await res.json();
                showT(data.message, data.success);
                if(data.success) document.getElementById('row-'+id).remove();
            } catch(e) { showT('Error deleting.', false); }
        }

        async function resetPwd(id, staff_no){
            if(!confirm(`Reset password for supervisor to their Staff ID (${staff_no})?`)) return;
            const fd = new FormData();
            fd.append('ajax', '1');
            fd.append('action', 'reset_password');
            fd.append('id', id);
            const token = document.querySelector('input[name="csrf_token"]').value;
            fd.append('csrf_token', token);
            try {
                const res = await fetch('dpc_manage_supervisors.php', { method: 'POST', body: fd });
                const data = await res.json();
                showT(data.message, data.success);
            } catch(e) { showT('Error resetting password.', false); }
        }
        document.getElementById('search-in').onkeypress = (e) => { if(e.key === 'Enter') doSearch(); };
    </script>
</body>
</html>

