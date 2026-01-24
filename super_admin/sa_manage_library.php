<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

// Check if the user is logged in as Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        if ($_POST['action'] === 'create' || $_POST['action'] === 'update') {
            $username = trim($_POST['username']);
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'] ?? '';
            
            if (empty($username)) {
                throw new Exception("Username is required.");
            }
            
            if ($_POST['action'] === 'create') {
                if (empty($password)) throw new Exception("Password is required for new accounts.");
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                // Assign faculty_id 0 or NULL for global library staff
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, email, faculty_id, is_active) VALUES (?, ?, 'lib', ?, ?, 0, 1)");
                $stmt->execute([$username, $hashed, $name, $email]);
                $response['message'] = "Library account created successfully!";
            } else {
                $id = intval($_POST['id']);
                $sql = "UPDATE users SET username = ?, name = ?, email = ? WHERE id = ? AND role = 'lib'";
                $params = [$username, $name, $email, $id];
                
                if (!empty($password)) {
                    $sql = "UPDATE users SET username = ?, name = ?, email = ?, password = ? WHERE id = ? AND role = 'lib'";
                    $params = [$username, $name, $email, password_hash($password, PASSWORD_DEFAULT), $id];
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $response['message'] = "Library account updated successfully!";
            }
            $response['success'] = true;
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'lib'");
            $stmt->execute([$id]);
            $response['success'] = true;
            $response['message'] = "Library account deleted successfully!";
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
    exit();
}

// Fetch Library Accounts
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users WHERE role = 'lib'";
if ($search) $query .= " AND (name LIKE :s OR username LIKE :s OR email LIKE :s)";
$stmt = $conn->prepare($query);
if ($search) $stmt->bindValue(':s', "%$search%");
$stmt->execute();
$librarians = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage School Library - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4338ca; --secondary: #db2777; --success: #059669; --danger: #dc2626; --glass: rgba(255, 255, 255, 0.95); }
        body { font-family: 'Outfit', sans-serif; background: #f1f5f9; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: var(--primary); font-size: 28px; }
        
        .main-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn { padding: 12px 24px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 13px; }
        td { padding: 15px; border-bottom: 1px solid #e2e8f0; font-size: 15px; }
        
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: white; width: 500px; padding: 40px; border-radius: 24px; position: relative; }
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
                <h1><i class="fas fa-book-reader"></i> Library Staff</h1>
                <p style="color: #64748b; margin-top: 5px;">Manage accounts for School Library management.</p>
            </div>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Library Staff
            </button>
        </div>

        <div class="main-card">
            <div style="margin-bottom: 20px;">
                <input type="text" id="search" class="form-control" placeholder="Search accounts..." value="<?= htmlspecialchars($search) ?>" onkeyup="if(event.key==='Enter') window.location='?search='+this.value">
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($librarians)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px;">No library accounts found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($librarians as $l): ?>
                            <tr>
                               <td><strong><?= htmlspecialchars($l['username']) ?></strong></td>
                                <td><?= htmlspecialchars($l['name']) ?></td>
                                <td><?= htmlspecialchars($l['email']) ?></td>
                                <td style="text-align: right;">
                                    <button class="btn" style="background: #f1f5f9; padding: 8px;" onclick='openEditModal(<?= json_encode($l) ?>)'><i class="fas fa-edit"></i></button>
                                    <button class="btn" style="background: #fee2e2; color: var(--danger); padding: 8px;" onclick="delLibrarian(<?= $l['id'] ?>)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="libModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 25px;">Add Staff</h2>
            <form id="libForm">
                <input type="hidden" name="action" id="f-action" value="create">
                <input type="hidden" name="id" id="f-id">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="f-username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="f-name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="f-email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Password (Required for new, leave blank to keep current if editing)</label>
                    <input type="password" name="password" id="f-password" class="form-control">
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Account</button>
                    <button type="button" class="btn" style="background: #f1f5f9;" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const modal = document.getElementById('libModal');
        const form = document.getElementById('libForm');
        const toast = document.getElementById('toast');

        function showToast(msg) {
            toast.innerText = msg;
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 3000);
        }

        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Add Library Staff';
            document.getElementById('f-action').value = 'create';
            document.getElementById('f-id').value = '';
            form.reset();
            modal.style.display = 'flex';
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = 'Edit Library Staff';
            document.getElementById('f-action').value = 'update';
            document.getElementById('f-id').value = data.id;
            document.getElementById('f-username').value = data.username;
            document.getElementById('f-name').value = data.name;
            document.getElementById('f-email').value = data.email;
            document.getElementById('f-password').value = '';
            modal.style.display = 'flex';
        }

        function closeModal() { modal.style.display = 'none'; }

        form.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('sa_manage_library.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json();
            showToast(data.message);
            if (data.success) setTimeout(() => window.location.reload(), 1000);
        };

        async function delLibrarian(id) {
            if (!confirm('Are you sure you want to delete this library account?')) return;
            const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
            const res = await fetch('sa_manage_library.php', { method: 'POST', body: fd });
            const data = await res.json();
            showToast(data.message);
            if (data.success) window.location.reload();
        }
    </script>
</body>
</html>
