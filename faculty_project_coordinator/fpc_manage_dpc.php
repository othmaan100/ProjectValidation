<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

// Check if the user is logged in as FPC
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'fpc') {
    header("Location: ../index.php");
    exit();
}

// Initialize response array for AJAX requests
$response = ['success' => false, 'message' => ''];

// Get current session
$currentSession = date('Y') . '/' . (date('Y') + 1);

// Constant temporary password for all DPCs
$constantTempPassword = 'TempPassword123';
$hashedTempPassword = password_hash($constantTempPassword, PASSWORD_BCRYPT);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        // CREATE DPC
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $username = trim($_POST['username']);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $departmentId = intval($_POST['department']);
            
            // Validate inputs
            if (empty($username) || empty($departmentId)) {
                throw new Exception("Username and department are required.");
            }
            
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format.");
            }
            
            // Verify department exists
            $stmt = $conn->prepare("SELECT id FROM departments WHERE id = ?");
            $stmt->execute([$departmentId]);
            if ($stmt->rowCount() === 0) {
                throw new Exception("Invalid department selected.");
            }
            
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username '$username' already exists. Please choose a different username.");
            }
            
            // Insert new DPC
            $stmt = $conn->prepare("INSERT INTO users (username, name, email, password, role, department, session, is_active) VALUES (?, ?, ?, ?, 'dpc', ?, ?, 1)");
            $stmt->execute([$username, $name, $email, $hashedTempPassword, $departmentId, $currentSession]);
            
            $response['success'] = true;
            $response['message'] = "DPC created successfully! Username: $username, Temporary Password: $constantTempPassword";
            $response['tempPassword'] = $constantTempPassword;
        }
        
        // UPDATE DPC
        elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
            $id = intval($_POST['id']);
            $username = trim($_POST['username']);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $departmentId = intval($_POST['department']);
            
            // Validate inputs
            if (empty($username) || empty($departmentId)) {
                throw new Exception("Username and department are required.");
            }
            
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format.");
            }
            
            // Verify department exists
            $stmt = $conn->prepare("SELECT id FROM departments WHERE id = ?");
            $stmt->execute([$departmentId]);
            if ($stmt->rowCount() === 0) {
                throw new Exception("Invalid department selected.");
            }
            
            // Check if username already exists for another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username '$username' is already taken by another user.");
            }
            
            // Update DPC
            $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, email = ?, department = ? WHERE id = ? AND role = 'dpc'");
            $stmt->execute([$username, $name, $email, $departmentId, $id]);
            
            $response['success'] = true;
            $response['message'] = "DPC updated successfully!";
        }
        
        // DELETE DPC
        elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $id = intval($_POST['id']);
            
            // Delete DPC
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'dpc'");
            $stmt->execute([$id]);
            
            $response['success'] = true;
            $response['message'] = "DPC deleted successfully!";
        }
        
        // TOGGLE ACTIVE STATUS
        elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
            $id = intval($_POST['id']);
            $isActive = intval($_POST['is_active']);
            
            // Toggle status
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'dpc'");
            $stmt->execute([$isActive, $id]);
            
            $response['success'] = true;
            $response['message'] = "Status updated successfully!";
        }
        
        // RESET PASSWORD
        elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
            $id = intval($_POST['id']);
            
            // Reset password
            $stmt = $conn->prepare("UPDATE users SET password = ?, password_changed = 0 WHERE id = ? AND role = 'dpc'");
            $stmt->execute([$hashedTempPassword, $id]);
            
            $response['success'] = true;
            $response['message'] = "Password reset successfully! New temporary password: $constantTempPassword";
            $response['tempPassword'] = $constantTempPassword;
        }
        
        // UPDATE SESSION
        elseif (isset($_POST['action']) && $_POST['action'] === 'update_session') {
            $newSession = trim($_POST['new_session']);
            
            if (empty($newSession)) {
                throw new Exception("Session cannot be empty.");
            }
            
            // Deactivate DPCs from previous sessions
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE role = 'dpc' AND session != ?");
            $stmt->execute([$newSession]);
            
            $currentSession = $newSession;
            
            $response['success'] = true;
            $response['message'] = "Session updated to $newSession. DPCs from previous sessions have been deactivated.";
        }
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// Fetch all DPCs with pagination and search
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query with JOIN to departments table
$whereClause = "u.role = 'dpc'";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (u.username LIKE ? OR u.name LIKE ? OR d.department_name LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

// Get total count
$countStmt = $conn->prepare("SELECT COUNT(*) FROM users u LEFT JOIN departments d ON u.department = d.id WHERE $whereClause");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Fetch DPCs with department names
$query = "SELECT u.*, d.department_name, d.id as dept_id FROM users u LEFT JOIN departments d ON u.department = d.id WHERE $whereClause ORDER BY u.is_active DESC, u.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$dpcs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments from database
$deptStmt = $conn->prepare("SELECT id, department_name FROM departments ORDER BY department_name ASC");
$deptStmt->execute();
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage DPCs - Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 32px;
            font-weight: 700;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 87, 108, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }
        
        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 14px 20px;
            border: 2px solid #e1e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e1e8f0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid #e1e8f0;
            font-size: 14px;
        }
        
        tbody tr {
            transition: all 0.2s ease;
        }
        
        tbody tr:hover {
            background: #f8f9ff;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .icon-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .icon-btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-edit {
            background: #4facfe;
            color: white;
        }
        
        .btn-delete {
            background: #fa709a;
            color: white;
        }
        
        .btn-reset {
            background: #feca57;
            color: white;
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 26px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: white;
            margin: auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            transition: all 0.3s ease;
        }
        
        .close:hover {
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: none;
            z-index: 2000;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast.success {
            border-left: 4px solid #28a745;
        }
        
        .toast.error {
            border-left: 4px solid #dc3545;
        }
        
        .toast-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .toast-icon {
            font-size: 24px;
        }
        
        .toast.success .toast-icon {
            color: #28a745;
        }
        
        .toast.error .toast-icon {
            color: #dc3545;
        }
        
        .toast-message {
            flex: 1;
            font-size: 14px;
            color: #333;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        /* Loading Spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 10px;
            }
            
            .modal-content {
                margin: auto;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-users-cog"></i> Manage DPCs</h1>
                <p style="color: #666; margin-top: 8px;">Current Session: <strong><?php echo htmlspecialchars($currentSession); ?></strong></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="openSessionModal()">
                    <i class="fas fa-calendar-alt"></i> Update Session
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Add New DPC
                </button>
                <a href="fpc_dashboard.php" class="btn btn-success">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="search-bar">
                <input type="text" class="search-input" id="searchInput" placeholder="Search by username, name, department, or email..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" onclick="performSearch()">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($search)): ?>
                <button class="btn btn-secondary" onclick="clearSearch()">
                    <i class="fas fa-times"></i> Clear
                </button>
                <?php endif; ?>
            </div>
            
            <div class="table-container">
                <?php if (count($dpcs) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Session</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dpcs as $dpc): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dpc['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($dpc['name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($dpc['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($dpc['department_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($dpc['session'] ?? '-'); ?></td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" <?php echo $dpc['is_active'] ? 'checked' : ''; ?> onchange="toggleStatus(<?php echo $dpc['id']; ?>, this.checked)">
                                    <span class="slider"></span>
                                </label>
                                <span class="status-badge <?php echo $dpc['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $dpc['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="icon-btn btn-edit" onclick='openEditModal(<?php echo json_encode($dpc); ?>)' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="icon-btn btn-reset" onclick="resetPassword(<?php echo $dpc['id']; ?>)" title="Reset Password">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button class="icon-btn btn-delete" onclick="deleteDPC(<?php echo $dpc['id']; ?>, '<?php echo htmlspecialchars($dpc['username']); ?>')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No DPCs Found</h3>
                    <p><?php echo !empty($search) ? 'No results match your search criteria.' : 'Click "Add New DPC" to create your first departmental project coordinator.'; ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Create/Edit DPC Modal -->
    <div id="dpcModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New DPC</h2>
                <span class="close" onclick="closeModal('dpcModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="dpcForm">
                    <input type="hidden" id="dpcId" name="id">
                    <input type="hidden" id="formAction" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="username">Username <span style="color: red;">*</span></label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department <span style="color: red;">*</span></label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['id']); ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('dpcModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Save DPC
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Session Update Modal -->
    <div id="sessionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Academic Session</h2>
                <span class="close" onclick="closeModal('sessionModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="sessionForm">
                    <input type="hidden" name="action" value="update_session">
                    
                    <div class="form-group">
                        <label for="newSession">New Session <span style="color: red;">*</span></label>
                        <input type="text" id="newSession" name="new_session" placeholder="e.g., 2024/2025" required>
                        <small style="color: #666; display: block; margin-top: 8px;">
                            <i class="fas fa-info-circle"></i> This will deactivate all DPCs from previous sessions.
                        </small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('sessionModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Update Session
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <i class="toast-icon fas fa-check-circle"></i>
            <div class="toast-message" id="toastMessage"></div>
        </div>
    </div>
    
    <?php include_once __DIR__ .'/../includes/footer.php'; ?>
    
    <script>
        // Modal Functions
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add New DPC';
            document.getElementById('dpcForm').reset();
            document.getElementById('dpcId').value = '';
            document.getElementById('formAction').value = 'create';
            document.getElementById('dpcModal').style.display = 'block';
        }
        
        function openEditModal(dpc) {
            document.getElementById('modalTitle').textContent = 'Edit DPC';
            document.getElementById('dpcId').value = dpc.id;
            document.getElementById('username').value = dpc.username;
            document.getElementById('name').value = dpc.name || '';
            document.getElementById('email').value = dpc.email || '';
            document.getElementById('department').value = dpc.department;
            document.getElementById('formAction').value = 'update';
            document.getElementById('dpcModal').style.display = 'block';
        }
        
        function openSessionModal() {
            document.getElementById('sessionModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = toast.querySelector('.toast-icon');
            
            toast.className = 'toast ' + type;
            toastMessage.textContent = message;
            
            if (type === 'success') {
                toastIcon.className = 'toast-icon fas fa-check-circle';
            } else {
                toastIcon.className = 'toast-icon fas fa-exclamation-circle';
            }
            
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 5000);
        }
        
        // Form Submission
        document.getElementById('dpcForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Saving...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            formData.append('ajax', '1');
            
            try {
                const response = await fetch('fpc_manage_dpc.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    closeModal('dpcModal');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
        
        // Session Form Submission
        document.getElementById('sessionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to update the session? This will deactivate all DPCs from previous sessions.')) {
                return;
            }
            
            const formData = new FormData(this);
            formData.append('ajax', '1');
            
            try {
                const response = await fetch('fpc_manage_dpc.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    closeModal('sessionModal');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        });
        
        // Delete DPC
        async function deleteDPC(id, username) {
            if (!confirm(`Are you sure you want to delete DPC "${username}"? This action cannot be undone.`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'delete');
            formData.append('id', id);
            
            try {
                const response = await fetch('fpc_manage_dpc.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }
        
        // Toggle Status
        async function toggleStatus(id, isActive) {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'toggle_status');
            formData.append('id', id);
            formData.append('is_active', isActive ? 1 : 0);
            
            try {
                const response = await fetch('fpc_manage_dpc.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }
        
        // Reset Password
        async function resetPassword(id) {
            if (!confirm('Are you sure you want to reset this DPC\'s password?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'reset_password');
            formData.append('id', id);
            
            try {
                const response = await fetch('fpc_manage_dpc.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }
        
        // Search Functions
        function performSearch() {
            const search = document.getElementById('searchInput').value;
            window.location.href = 'fpc_manage_dpc.php?search=' + encodeURIComponent(search);
        }
        
        function clearSearch() {
            window.location.href = 'fpc_manage_dpc.php';
        }
        
        // Enter key to search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    </script>
</body>
</html>