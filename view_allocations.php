<?php
session_start();
include 'includes/db.php';

// Check if user is authorized
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Different views based on role
if ($_SESSION['role'] === 'dpc' || $_SESSION['role'] === 'admin') {
    // Admin/DPC sees all allocations
    $allocations = $conn->query("
        SELECT s.name AS supervisor_name, 
               st.name AS student_name, 
               st.reg_no, 
               p.topic AS project_title,
               sup.allocation_date,
               sup.status
        FROM supervision sup
        JOIN supervisors s ON sup.supervisor_id = s.id
        JOIN students st ON sup.student_id = st.id
        JOIN project_topics p ON sup.project_id = p.id
        ORDER BY s.name, st.name
    ")->fetchAll();
} elseif ($_SESSION['role'] === 'sup') {
    // Supervisor sees only their allocations
    $allocations = $conn->prepare("
        SELECT s.name AS supervisor_name, 
               st.name AS student_name, 
               st.reg_no, 
               p.topic AS project_title,
               sup.allocation_date,
               sup.status
        FROM supervision sup
        JOIN supervisors s ON sup.supervisor_id = s.id
        JOIN students st ON sup.student_id = st.id
        JOIN projects p ON sup.project_id = p.id
        WHERE sup.supervisor_id = ?
        ORDER BY st.name
    ");
    $allocations->execute([$_SESSION['user_id']]);
    $allocations = $allocations->fetchAll();
} else {
    header("Location: unauthorized.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Allocations</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-completed {
            color: blue;
        }
        .status-terminated {
            color: red;
        }
        .summary {
            background-color: #f2f2f2;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .action-btn {
            padding: 5px 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .action-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Supervision Allocations</h1>
        
        <?php if ($_SESSION['role'] === 'dpc' || $_SESSION['role'] === 'admin'): ?>
            <div class="summary">
                <h3>Supervisor Workload Summary</h3>
                <?php
                $summary = $conn->query("
                    SELECT s.name, s.current_load, s.max_students,
                           (s.max_students - s.current_load) AS remaining_capacity
                    FROM supervisors s
                    ORDER BY s.name
                ")->fetchAll();
                ?>
                <table>
                    <tr>
                        <th>Supervisor</th>
                        <th>Current Load</th>
                        <th>Max Capacity</th>
                        <th>Remaining Capacity</th>
                    </tr>
                    <?php foreach ($summary as $supervisor): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($supervisor['name']); ?></td>
                        <td><?php echo $supervisor['current_load']; ?></td>
                        <td><?php echo $supervisor['max_students']; ?></td>
                        <td><?php echo $supervisor['remaining_capacity']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <a href="dpc_assign_supervisors.php" class="action-btn">Allocate New Student</a>
        <?php endif; ?>
        
        <h2><?php echo ($_SESSION['role'] === 'sup') ? 'My' : 'All'; ?> Allocations</h2>
        
        <table>
            <thead>
                <tr>
                    <?php if ($_SESSION['role'] !== 'sup'): ?>
                        <th>Supervisor</th>
                    <?php endif; ?>
                    <th>Student Name</th>
                    <th>Registration No</th>
                    <th>Project Title</th>
                    <th>Allocation Date</th>
                    <th>Status</th>
                    <?php if ($_SESSION['role'] === 'dpc' || $_SESSION['role'] === 'admin'): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allocations as $allocation): ?>
                <tr>
                    <?php if ($_SESSION['role'] !== 'sup'): ?>
                        <td><?php echo htmlspecialchars($allocation['supervisor_name']); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($allocation['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($allocation['reg_no']); ?></td>
                    <td><?php echo htmlspecialchars($allocation['project_title']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($allocation['allocation_date'])); ?></td>
                    <td class="status-<?php echo strtolower($allocation['status']); ?>">
                        <?php echo ucfirst($allocation['status']); ?>
                    </td>
                    <?php if ($_SESSION['role'] === 'dpc' || $_SESSION['role'] === 'admin'): ?>
                        <td>
                            <a href="edit_allocation.php?id=<?php echo $allocation['id']; ?>" class="action-btn">Edit</a>
                            <a href="delete_allocation.php?id=<?php echo $allocation['id']; ?>" class="action-btn" 
                               onclick="return confirm('Are you sure you want to remove this allocation?');">Remove</a>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>