<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

// Check if the user is logged in as FPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'fpc') {
    header("Location: /projectval/");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        if ($_POST['action'] === 'create') {
            $deptName = trim($_POST['department_name']);
            if (empty($deptName)) throw new Exception("Department name is required.");
            
            $stmt = $conn->prepare("INSERT INTO departments (department_name, faculty_id) VALUES (?, ?)");
            $stmt->execute([$deptName, $faculty_id]);
            $response['success'] = true;
            $response['message'] = "Department created successfully!";
        } elseif ($_POST['action'] === 'update') {
            $id = intval($_POST['id']);
            $deptName = trim($_POST['department_name']);
            if (empty($deptName)) throw new Exception("Department name is required.");
            
            // Security check: Ensure department belongs to this FPC's faculty
            $stmt = $conn->prepare("UPDATE departments SET department_name = ? WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$deptName, $id, $faculty_id]);
            $response['success'] = true;
            $response['message'] = "Department updated successfully!";
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id']);
            
            // Security check + References check
            // Check if users (DPCs, Supervisors, Students) are assigned to this department
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE department = ? AND faculty_id = ?");
            $stmt->execute([$id, $faculty_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cannot delete department. It has active users assigned to it.");
            }

            $stmt = $conn->prepare("DELETE FROM departments WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$id, $faculty_id]);
            $response['success'] = true;
            $response['message'] = "Department deleted successfully!";
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
    exit();
}

// Fetch Departments for this Faculty
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM departments WHERE faculty_id = ?";
if ($search) $query .= " AND department_name LIKE :s";
$query .= " ORDER BY department_name ASC";
$stmt = $conn->prepare($query);
$stmt->bindValue(1, $faculty_id);
if ($search) $stmt->bindValue(':s', "%$search%");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Departments - FPC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; --glass: rgba(255, 255, 255, 0.95); }
        body { 
            font-family: 'Outfit', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh;
            padding: 20px; 
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: var(--primary); font-size: 28px; }
        
        .main-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .btn { padding: 12px 24px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 13px; }
        td { padding: 15px; border-bottom: 1px solid #e2e8f0; font-size: 15px; }
        
        /* Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: white; width: 450px; padding: 40px; border-radius: 24px; position: relative; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-family: inherit; }
        
        .toast { position: fixed; bottom: 20px; right: 20px; padding: 15px 25px; border-radius: 10px; background: #333; color: white; display: none; z-index: 1001; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-building"></i> Manage Departments</h1>
                <p style="color: #64748b; margin-top: 5px;">Define departments within your faculty for DPC assignment.</p>
            </div>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Department
            </button>
        </div>

        <div class="main-card">
            <div style="margin-bottom: 20px;">
                <input type="text" id="search" class="form-control" placeholder="Search departments..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter') window.location='?search='+this.value">
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Department Name</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($departments)): ?>
                        <tr><td colspan="3" style="text-align: center; padding: 40px;">No departments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td>#<?= $d['id'] ?></td>
                                <td><strong><?= htmlspecialchars($d['department_name']) ?></strong></td>
                                <td style="text-align: right;">
                                    <button class="btn" style="background: #f1f5f9; padding: 8px;" onclick='openEditModal(<?= json_encode($d) ?>)'><i class="fas fa-edit"></i></button>
                                    <button class="btn" style="background: #fee2e2; color: var(--danger); padding: 8px;" onclick="delDept(<?= $d['id'] ?>)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="deptModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 25px;">Add Department</h2>
            <form id="deptForm">
                <input type="hidden" name="action" id="f-action" value="create">
                <input type="hidden" name="id" id="f-id">
                
                <div class="form-group">
                    <label>Department Name</label>
                    <input type="text" name="department_name" id="f-name" class="form-control" required placeholder="e.g. Department of Computer Science">
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Department</button>
                    <button type="button" class="btn" style="background: #f1f5f9;" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const modal = document.getElementById('deptModal');
        const form = document.getElementById('deptForm');
        const toast = document.getElementById('toast');

        function showToast(msg) {
            toast.innerText = msg;
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 3000);
        }

        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Add Department';
            document.getElementById('f-action').value = 'create';
            document.getElementById('f-id').value = '';
            form.reset();
            modal.style.display = 'flex';
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = 'Edit Department';
            document.getElementById('f-action').value = 'update';
            document.getElementById('f-id').value = data.id;
            document.getElementById('f-name').value = data.department_name;
            modal.style.display = 'flex';
        }

        function closeModal() { modal.style.display = 'none'; }

        form.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('fpc_manage_departments.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json();
            showToast(data.message);
            if (data.success) setTimeout(() => window.location.reload(), 1000);
        };

        async function delDept(id) {
            if (!confirm('Are you sure? This will fail if users are already assigned to this department.')) return;
            const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
            const res = await fetch('fpc_manage_departments.php', { method: 'POST', body: fd });
            const data = await res.json();
            showToast(data.message);
            if (data.success) window.location.reload();
        }
    </script>
</body>
</html>
