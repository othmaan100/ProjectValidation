<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lib') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Simple summary logic
$faculty_stats = [];
try {
    $faculty_stats = $conn->query("
        SELECT f.faculty, COUNT(*) as count 
        FROM project_topics pt 
        JOIN students s ON pt.student_id = s.id 
        JOIN faculty f ON s.faculty_id = f.id 
        WHERE pt.status = 'approved' 
        GROUP BY f.faculty
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Statistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #5b21b6; --bg: #f8fafc; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    <div class="container">
        <a href="lib_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="card">
            <h1><i class="fas fa-chart-pie"></i> Library Statistics</h1>
            <p>Summary of approved projects by faculty.</p>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background: #f1f5f9;">
                        <th style="padding: 15px; text-align: left;">Faculty</th>
                        <th style="padding: 15px; text-align: right;">Approved Projects</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faculty_stats)): ?>
                        <tr><td colspan="2" style="text-align: center; padding: 20px;">No data available.</td></tr>
                    <?php else: ?>
                        <?php foreach($faculty_stats as $fs): ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 15px;"><?= htmlspecialchars($fs['faculty']) ?></td>
                                <td style="padding: 15px; text-align: right; font-weight: 700;"><?= $fs['count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
