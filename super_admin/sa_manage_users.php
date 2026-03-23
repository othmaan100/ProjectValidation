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
        if ($_POST['action'] === 'update') {
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'] ?? '';
            $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
            $new_role = $_POST['role'] ?? null;
            
            if (empty($name)) {
                throw new Exception("Name is required.");
            }
            
            $sql = "UPDATE users SET name = ?, email = ?, is_active = ?";
            $params = [$name, $email, $is_active];
            
            if (!empty($new_role) && $new_role !== 'unassigned') {
                $sql .= ", role = ?";
                $params[] = $new_role;
            }
            
            if (!empty($password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            // Also update related tables if applicable based on role
            // Get the role of this user
            $role_stmt = $conn->prepare("SELECT role, username FROM users WHERE id = ?");
            $role_stmt->execute([$id]);
            $user_info = $role_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_info) {
                if ($user_info['role'] === 'stu') {
                    $upd_stu = $conn->prepare("UPDATE students SET name = ?, email = ? WHERE id = ?");
                    $upd_stu->execute([$name, $email, $id]);
                } elseif ($user_info['role'] === 'ext') {
                    $upd_ext = $conn->prepare("UPDATE external_examiners SET name = ?, email = ? WHERE id = ?");
                    $upd_ext->execute([$name, $email, $id]);
                }
            }

            $response['message'] = "User account updated successfully!";
            $response['success'] = true;

        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id']);
            
            // Note: Deleting users might violate foreign key constraints if they have submitted projects, scores, etc.
            // A safer approach is to just deactivate, but we'll try to delete and catch the exception to show a friendly error.
            try {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $response['success'] = true;
                $response['message'] = "User deleted successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    throw new Exception("Cannot delete this user because they have associated records in the system (e.g., projects, scores, or messages). Deactivate them instead.");
                } else {
                    throw clone $e;
                }
            }
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
    exit();
}

$roles_map = [
    'stu' => 'Students',
    'sup' => 'Supervisors',
    'dpc' => 'Dept. Project Coordinators (DPC)',
    'fpc' => 'Faculty Project Coordinators (FPC)',
    'lib' => 'Library Staff',
    'ext' => 'External Examiners',
    'admin' => 'Super Admins',
    'unassigned' => 'Unassigned/Empy Role'
];

$selected_role = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';

// Pagination variables
$limit = 50; // number of users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$query = "SELECT u.id, u.username, u.name, u.email, u.role, u.is_active, u.created_at, u.department, u.faculty_id,
          d.department_name, f.faculty as faculty_name
          FROM users u
          LEFT JOIN departments d ON u.department = d.id
          LEFT JOIN faculty f ON (u.faculty_id = f.id OR d.faculty_id = f.id)
          WHERE 1=1";
          
$count_query = "SELECT COUNT(*) as total FROM users u WHERE 1=1";

$params = [];

if ($selected_role !== 'all' && array_key_exists($selected_role, $roles_map)) {
    if ($selected_role === 'unassigned') {
        $query .= " AND (u.role IS NULL OR u.role = '')";
        $count_query .= " AND (u.role IS NULL OR u.role = '')";
    } else {
        $query .= " AND u.role = :role";
        $count_query .= " AND u.role = :role";
        $params[':role'] = $selected_role;
    }
} else {
    // Only fetch manageable roles if 'all'
    $query .= " AND (u.role IN ('stu', 'sup', 'dpc', 'fpc', 'lib', 'ext', 'admin') OR u.role IS NULL OR u.role = '')";
    $count_query .= " AND (u.role IN ('stu', 'sup', 'dpc', 'fpc', 'lib', 'ext', 'admin') OR u.role IS NULL OR u.role = '')";
}

if ($search) {
    $query .= " AND (u.name LIKE :s OR u.username LIKE :s OR u.email LIKE :s)";
    $count_query .= " AND (u.name LIKE :s OR u.username LIKE :s OR u.email LIKE :s)";
    $params[':s'] = "%$search%";
}

// Get total records
$stmt_count = $conn->prepare($count_query);
foreach ($params as $k => $v) {
    if ($k == ':limit' || $k == ':offset') continue;
    $stmt_count->bindValue($k, $v);
}
$stmt_count->execute();
$total_records = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $limit);

$query .= " ORDER BY u.role ASC, u.name ASC LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage All Users - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4338ca; --secondary: #db2777; --success: #059669; --danger: #dc2626; --warning: #eab308; }
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; margin: 0; padding: 0; color: #1e293b; }
        .container { max-width: 1300px; margin: 0 auto; padding: 30px; }
        
        .header { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; border: 1px solid #e2e8f0; }
        .header h1 { color: var(--primary); font-size: 28px; margin: 0; display: flex; align-items: center; gap: 12px; }
        
        .filters { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; }
        .search-input { flex: 1; min-width: 250px; padding: 12px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--primary); }
        .role-select { padding: 12px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-weight: 600; color: #475569; outline: none; cursor: pointer; background: white; }
        .btn { padding: 12px 24px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #3730a3; transform: translateY(-2px); }
        
        .main-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow-x: auto;}
        
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 12px; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        
        .badge { padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; color: white; display: inline-block; }
        .bg-stu { background: #3b82f6; } /* Blue */
        .bg-sup { background: #10b981; } /* Emerald */
        .bg-dpc { background: #f59e0b; } /* Amber */
        .bg-fpc { background: #db2777; } /* Pink */
        .bg-lib { background: #8b5cf6; } /* Purple */
        .bg-ext { background: #6366f1; } /* Indigo */
        .bg-admin { background: #1e293b; } /* Slate */
        
        .status-badge { padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .pagination-info { color: #64748b; font-size: 14px; }
        .pagination-controls { display: flex; gap: 8px; }
        .page-btn { padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #475569; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .page-btn:hover:not(.disabled) { border-color: var(--primary); color: var(--primary); }
        .page-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .page-btn.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); z-index: 1000; align-items: flex-start; justify-content: center; padding-top: 60px; overflow-y: auto; }
        .modal-content { background: white; width: 600px; max-width: 90%; padding: 40px; border-radius: 24px; position: relative; margin-bottom: 40px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-content h2 { margin-top: 0; color: var(--primary); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; outline: none; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary); }
        
        .toast { position: fixed; bottom: 20px; right: 20px; padding: 15px 25px; border-radius: 10px; background: #1e293b; color: white; display: none; z-index: 1001; animation: slideUp 0.3s; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-users-cog"></i> Global User Management</h1>
                <p style="color: #64748b; margin-top: 5px; font-size: 15px;">Monitor, update, and manage all users across the entire system.</p>
            </div>
            
            <form class="filters" method="GET">
                <select name="role" class="role-select" onchange="this.form.submit()">
                    <option value="all" <?= $selected_role == 'all' ? 'selected' : '' ?>>All Roles</option>
                    <?php foreach ($roles_map as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $selected_role == $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" class="search-input" placeholder="Search by name, username, or email..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>

        <div class="main-card">
            <table>
                <thead>
                    <tr>
                        <th>User Details</th>
                        <th>Role</th>
                        <th>Affiliation</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">No users found matching the criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary); font-size: 15px; margin-bottom: 2px;"><?= htmlspecialchars($u['name']) ?></div>
                                    <div style="font-size: 13px; color: #64748b; margin-bottom: 2px;"><code><?= htmlspecialchars($u['username']) ?></code></div>
                                    <?php if($u['email']): ?>
                                        <div style="font-size: 12px; color: #94a3b8;"><i class="fas fa-envelope"></i> <?= htmlspecialchars($u['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(empty($u['role']) || $u['role'] === 'unassigned'): ?>
                                        <span class="badge" style="background:#64748b;">UNASSIGNED</span>
                                        <div style="font-size: 11px; margin-top: 5px; color: var(--danger); font-weight: bold;">Needs correction</div>
                                    <?php else: ?>
                                        <span class="badge bg-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span>
                                        <div style="font-size: 11px; margin-top: 5px; color: #94a3b8;"><?= $roles_map[$u['role']] ?? 'Unknown' ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['department_name']): ?>
                                        <div style="font-size: 13px; font-weight: 600;"><i class="fas fa-building" style="color: #94a3b8;"></i> <?= htmlspecialchars($u['department_name']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($u['faculty_name']): ?>
                                        <div style="font-size: 12px; color: #64748b; margin-top: 3px;"><?= htmlspecialchars($u['faculty_name']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!$u['department_name'] && !$u['faculty_name']): ?>
                                        <div style="font-size: 13px; color: #94a3b8; font-style: italic;">System Wide</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['is_active']): ?>
                                        <span class="status-badge status-active"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <!-- Protect Super Admins from being casually edited unless it's their own account maybe, but as SA they can edit everyone -->
                                    <button class="btn" style="background: #f1f5f9; color: var(--primary); padding: 8px 12px;" onclick='openEditModal(<?= json_encode($u) ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <?php if ($u['role'] !== 'admin'): ?>
                                    <button class="btn" style="background: #fee2e2; color: var(--danger); padding: 8px 12px; margin-left: 5px;" onclick="deleteUser(<?= $u['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <div class="pagination-info">
                    Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $limit, $total_records) ?> of <?= $total_records ?> entries
                </div>
                <div class="pagination-controls">
                    <a href="?role=<?= urlencode($selected_role) ?>&search=<?= urlencode($search) ?>&page=1" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" title="First Page"><i class="fas fa-angle-double-left"></i></a>
                    <a href="?role=<?= urlencode($selected_role) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" title="Previous Page"><i class="fas fa-angle-left"></i></a>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        if ($start_page > 2) {
                            echo '<span style="padding: 8px; color: #64748b;">...</span>';
                        }
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <a href="?role=<?= urlencode($selected_role) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span style="padding: 8px; color: #64748b;">...</span>';
                        }
                    }
                    ?>
                    
                    <a href="?role=<?= urlencode($selected_role) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>" title="Next Page"><i class="fas fa-angle-right"></i></a>
                    <a href="?role=<?= urlencode($selected_role) ?>&search=<?= urlencode($search) ?>&page=<?= $total_pages ?>" class="page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>" title="Last Page"><i class="fas fa-angle-double-right"></i></a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($total_pages <= 1 && $total_records > 0): ?>
            <div class="pagination">
                <div class="pagination-info">
                    Showing all <?= $total_records ?> entries
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 25px;"><i class="fas fa-user-edit"></i> Edit User Details</h2>
            <form id="userForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="f-id">
                
                <div class="form-group">
                    <label>Username (System Login)</label>
                    <input type="text" id="f-username" class="form-control" readonly style="background: #f1f5f9; cursor: not-allowed; color: #94a3b8;">
                    <small style="color: #94a3b8;">Usernames cannot be changed once created.</small>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="f-name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="f-email" class="form-control">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="f-role" class="form-control">
                        <option value="">Keep Current</option>
                        <option value="stu">Student (stu)</option>
                        <option value="sup">Supervisor (sup)</option>
                        <option value="dpc">Dept Project Coordinator (dpc)</option>
                        <option value="fpc">Faculty Project Coordinator (fpc)</option>
                        <option value="lib">Library Staff (lib)</option>
                        <option value="ext">External Examiner (ext)</option>
                        <option value="admin">Super Admin (admin)</option>
                    </select>
                    <small style="color: #94a3b8;">Use this to assign or correct an empty role.</small>
                </div>
                
                <div class="form-group">
                    <label>Account Status</label>
                    <select name="is_active" id="f-active" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive / Suspended</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Reset Password (Optional)</label>
                    <input type="text" name="password" id="f-password" class="form-control" placeholder="Leave blank to keep current password">
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;"><i class="fas fa-save"></i> Save Changes</button>
                    <button type="button" class="btn" style="background: #e2e8f0; color: #475569;" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');
        const toast = document.getElementById('toast');

        function showToast(msg, isError = false) {
            toast.innerText = msg;
            toast.style.background = isError ? '#dc2626' : '#1e293b';
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 4000);
        }

        function openEditModal(data) {
            document.getElementById('f-id').value = data.id;
            document.getElementById('f-username').value = data.username;
            document.getElementById('f-name').value = data.name;
            document.getElementById('f-email').value = data.email;
            document.getElementById('f-active').value = data.is_active;
            document.getElementById('f-password').value = '';
            document.getElementById('f-role').value = data.role && data.role !== 'unassigned' ? data.role : '';

            
            modal.style.display = 'flex';
        }

        function closeModal() { 
            modal.style.display = 'none'; 
        }

        form.onsubmit = async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                const res = await fetch('sa_manage_users.php', { method: 'POST', body: new FormData(form) });
                const data = await res.json();
                
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message, true);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                }
            } catch (err) {
                showToast("A network error occurred.", true);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
            }
        };

        async function deleteUser(id) {
            if (!confirm('Are you absolutely sure you want to delete this user? This may fail if the user is linked to projects.')) return;
            
            const fd = new FormData(); 
            fd.append('action', 'delete'); 
            fd.append('id', id);

            try {
                const res = await fetch('sa_manage_users.php', { method: 'POST', body: fd });
                const data = await res.json();
                
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message, true);
                }
            } catch (err) {
                showToast("A network error occurred.", true);
            }
        }
    </script>
</body>
</html>
