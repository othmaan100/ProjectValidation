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

        // ADD EXTERNAL EXAMINER
        if ($action === 'add_examiner') {
            $username = trim($_POST['username']); // Usually their initial or a code
            $name = trim($_POST['name']);
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $affiliation = trim($_POST['affiliation'] ?? '');

            if (empty($username) || empty($name)) {
                throw new Exception("Username and Name are required.");
            }

            // Check if exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception("Username '$username' is already taken.");
            }

            $password = password_hash($username, PASSWORD_DEFAULT);
            $conn->beginTransaction();
            try {
                // Insert into users
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, email, department, is_active) VALUES (?, ?, 'ext', ?, ?, ?, 1)");
                $stmt->execute([$username, $password, $name, $email, $dept_id]);
                $user_id = $conn->lastInsertId();

                // Insert into external_examiners
                $stmt = $conn->prepare("INSERT INTO external_examiners (id, name, email, phone, affiliation, department_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $name, $email, $phone, $affiliation, $dept_id]);
                
                $conn->commit();
                $response['success'] = true;
                $response['message'] = "External examiner account created successfully!";
            } catch (Exception $e) { $conn->rollBack(); throw $e; }
        }

        // UPDATE EXTERNAL EXAMINER
        elseif ($action === 'update_examiner') {
            $id = intval($_POST['id']);
            $username = trim($_POST['username']);
            $name = trim($_POST['name']);
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $affiliation = trim($_POST['affiliation'] ?? '');

            if (empty($username) || empty($name)) {
                throw new Exception("Username and Name are required.");
            }

            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("UPDATE external_examiners SET name = ?, email = ?, phone = ?, affiliation = ? WHERE id = ? AND department_id = ?");
                $stmt->execute([$name, $email, $phone, $affiliation, $id, $dept_id]);
                
                if ($stmt->rowCount() === 0) {
                    // Check if it exists at least
                    $check = $conn->prepare("SELECT id FROM external_examiners WHERE id = ? AND department_id = ?");
                    $check->execute([$id, $dept_id]);
                    if (!$check->fetch()) throw new Exception("Examiner not found or doesn't belong to your department.");
                }

                $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, email = ? WHERE id = ? AND role = 'ext'");
                $stmt->execute([$username, $name, $email, $id]);

                $conn->commit();
                $response['success'] = true;
                $response['message'] = "External examiner updated successfully!";
            } catch (Exception $e) { $conn->rollBack(); throw $e; }
        }

        // DELETE
        elseif ($action === 'delete_examiner') {
            $id = intval($_POST['id']);
            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("DELETE FROM external_examiners WHERE id = ? AND department_id = ?");
                $stmt->execute([$id, $dept_id]);

                if ($stmt->rowCount() > 0) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'ext'");
                    $stmt->execute([$id]);
                }

                $conn->commit();
                $response['success'] = true;
                $response['message'] = "External examiner deleted!";
            } catch (Exception $e) { $conn->rollBack(); throw $e; }
        }

        // RESET PASSWORD
        elseif ($action === 'reset_password') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND role = 'ext'");
            $stmt->execute([$id]);
            $username = $stmt->fetchColumn();

            if (!$username) throw new Exception("Examiner not found.");

            $new_password = password_hash($username, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'ext'");
            $stmt->execute([$new_password, $id]);

            $response['success'] = true;
            $response['message'] = "Password reset to username ($username) successfully!";
        }
    } catch (Exception $e) { if ($conn->inTransaction()) $conn->rollBack(); $response['success'] = false; $response['message'] = $e->getMessage(); }
    echo json_encode($response);
    exit();
}

// Fetch Logic
$search = $_GET['search'] ?? '';
$whereClause = "department_id = :dept";
$params = [':dept' => $dept_id];
if (!empty($search)) {
    $whereClause .= " AND (name LIKE :search OR affiliation LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

$stmt = $conn->prepare("SELECT * FROM external_examiners WHERE $whereClause ORDER BY name ASC");
$stmt->execute($params);
$examiners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add username back from users table for display
foreach ($examiners as &$ex) {
    $ustmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $ustmt->execute([$ex['id']]);
    $ex['username'] = $ustmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage External Examiners - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --primary-dark: #3730a3; --success: #10b981; --danger: #ef4444; --glass: rgba(255, 255, 255, 0.95); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; color: #1e293b; padding-bottom: 50px; }
        .page-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-card h1 { color: var(--primary); font-size: 24px; font-weight: 800; }
        .btn { padding: 12px 24px; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #fff; font-size: 14px; }
        .btn-primary { background: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .main-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .search-container { display: flex; gap: 10px; margin-bottom: 25px; }
        .search-input { flex: 1; padding: 14px; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; padding: 15px; text-align: left; color: #64748b; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; }
        .name-bold { font-weight: 700; color: var(--primary); }
        .affiliation { font-size: 13px; color: #64748b; font-style: italic; }
        .icon-btn { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: 0.2s; }
        .btn-edit-i { background: #e0e7ff; color: #4338ca; }
        .btn-delete-i { background: #fee2e2; color: #ef4444; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
        .modal.active { display: flex; opacity: 1; }
        .modal-content { background: white; width: 90%; max-width: 500px; border-radius: 24px; padding: 30px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 13px; color: #64748b; text-transform: uppercase; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-family: inherit; }
        .form-control:focus { border-color: var(--primary); }
        #toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 3000; }
        .toast { background: #1e293b; color: white; padding: 16px 24px; border-radius: 12px; margin-top: 10px; animation: slideUp 0.3s ease-out; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>

    <div class="page-container">
        <div class="header-card">
            <div>
                <h1><i class="fas fa-user-shield"></i> External Examiners</h1>
                <p style="color: #64748b;">Manage accounts for non-staff project assessors.</p>
            </div>
            <button class="btn btn-primary" onclick="openAdd()"><i class="fas fa-plus"></i> Add Examiner</button>
        </div>

        <div class="main-card">
            <form class="search-container" method="GET">
                <input type="text" name="search" class="search-input" placeholder="Search by name, institution..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Name & Username</th>
                        <th>Institution / Affiliation</th>
                        <th>Contact info</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($examiners)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 40px; color: #64748b;">No external examiners registered.</td></tr>
                    <?php else: ?>
                        <?php foreach ($examiners as $ex): ?>
                        <tr id="row-<?= $ex['id'] ?>">
                            <td>
                                <div class="name-bold"><?= htmlspecialchars($ex['name']) ?></div>
                                <code style="font-size: 12px;"><?= htmlspecialchars($ex['username']) ?></code>
                            </td>
                            <td><div class="affiliation"><?= htmlspecialchars($ex['affiliation'] ?: 'Not Specified') ?></div></td>
                            <td>
                                <div style="font-size: 13px;"><i class="fas fa-envelope"></i> <?= htmlspecialchars($ex['email'] ?: 'N/A') ?></div>
                                <div style="font-size: 13px;"><i class="fas fa-phone"></i> <?= htmlspecialchars($ex['phone'] ?: 'N/A') ?></div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button class="icon-btn btn-edit-i" onclick='openEdit(<?= json_encode($ex) ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="icon-btn btn-delete-i" onclick="del(<?= $ex['id'] ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="exModal" class="modal">
        <div class="modal-content">
            <h2 id="m-title" style="margin-bottom: 25px;">Add External Examiner</h2>
            <form id="exForm">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="action" id="f-act" value="add_examiner">
                <input type="hidden" name="id" id="f-id">
                
                <div class="form-group">
                    <label>Login Username</label>
                    <input type="text" name="username" id="f-username" class="form-control" placeholder="e.g. EX_JOHN" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="f-name" class="form-control" placeholder="Prof. Jane Doe" required>
                </div>
                <div class="form-group">
                    <label>Affiliation / Institution</label>
                    <input type="text" name="affiliation" id="f-affiliation" class="form-control" placeholder="University of X, ABC Corp etc.">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="f-email" class="form-control" placeholder="jane@example.com">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" id="f-phone" class="form-control" placeholder="+234...">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top: 10px;">Save Account</button>
                <button type="button" class="btn" style="width:100%; margin-top: 10px; background: #f1f5f9; color: #64748b;" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <div id="toast-container"></div>
    <?php echo csrf_field(); ?>

    <script>
        function openModal(){ const m = document.getElementById('exModal'); m.style.display = 'flex'; setTimeout(() => m.classList.add('active'), 10); }
        function closeModal(){ const m = document.getElementById('exModal'); m.classList.remove('active'); setTimeout(() => m.style.display = 'none', 300); }
        function showT(msg){
            const t = document.createElement('div');
            t.className = 'toast';
            t.innerText = msg;
            document.getElementById('toast-container').appendChild(t);
            setTimeout(() => t.remove(), 4000);
        }

        function openAdd(){
            document.getElementById('m-title').innerText='Add New External Examiner';
            document.getElementById('f-act').value='add_examiner';
            document.getElementById('exForm').reset();
            openModal();
        }
        function openEdit(d){
            document.getElementById('m-title').innerText='Edit External Examiner';
            document.getElementById('f-act').value='update_examiner';
            document.getElementById('f-id').value=d.id;
            document.getElementById('f-username').value=d.username;
            document.getElementById('f-name').value=d.name;
            document.getElementById('f-affiliation').value=d.affiliation;
            document.getElementById('f-email').value=d.email;
            document.getElementById('f-phone').value=d.phone;
            openModal();
        }

        document.getElementById('exForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true; btn.innerText = 'Processing...';
            
            try {
                const fd = new FormData(e.target);
                fd.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                const res = await fetch('dpc_manage_external_examiners.php', { method: 'POST', body: fd });
                const data = await res.json();
                showT(data.message);
                if(data.success) setTimeout(()=>location.reload(), 1000);
            } catch(err) { showT('An error occurred.'); }
            finally { btn.disabled = false; btn.innerText = 'Save Account'; }
        };

        async function del(id){
            if(!confirm('Delete this external examiner account?')) return;
            const fd = new FormData();
            fd.append('ajax', '1');
            fd.append('action', 'delete_examiner');
            fd.append('id', id);
            fd.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            try {
                const res = await fetch('dpc_manage_external_examiners.php', { method: 'POST', body: fd });
                const data = await res.json();
                showT(data.message);
                if(data.success) document.getElementById('row-'+id).remove();
            } catch(e) { showT('Error deleting.'); }
        }
    </script>
</body>
</html>
