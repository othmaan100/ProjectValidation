<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lib') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$userName = $_SESSION['name'] ?? $_SESSION['username'];

// Stats Containers
$overall_stats = [
    'with_pdf' => 0,
    'without_pdf' => 0,
    'total' => 0
];
$faculty_stats = [];
$dept_stats = [];

try {
    // 1. Overall PDF Stats (Active Approved + Archive)
    $stmt = $conn->query("
        SELECT 
            SUM(CASE WHEN pdf_path IS NOT NULL AND pdf_path != '' THEN 1 ELSE 0 END) as with_pdf,
            SUM(CASE WHEN pdf_path IS NULL OR pdf_path = '' THEN 1 ELSE 0 END) as without_pdf,
            COUNT(*) as total
        FROM (
            SELECT pdf_path FROM project_topics WHERE status = 'approved'
            UNION ALL
            SELECT pdf_path FROM past_projects
        ) as combined_projects
    ");
    $overall_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $overall_stats;

    // 2. Faculty Stats
    $faculty_stats = $conn->query("
        SELECT f.faculty as name, COUNT(*) as count 
        FROM (
            SELECT pt.topic, s.faculty_id FROM project_topics pt JOIN students s ON pt.student_id = s.id WHERE pt.status = 'approved'
            UNION ALL
            SELECT topic, faculty_id FROM past_projects
        ) as combined
        JOIN faculty f ON combined.faculty_id = f.id
        GROUP BY f.faculty
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Department Stats (Note: Past projects might not have department_id, so we only show active ones or handle null)
    $dept_stats = $conn->query("
        SELECT d.department_name as name, COUNT(*) as count 
        FROM project_topics pt 
        JOIN students s ON pt.student_id = s.id 
        JOIN departments d ON s.department = d.id 
        WHERE pt.status = 'approved' 
        GROUP BY d.department_name
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Report Generation Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Intelligence - Project Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5b21b6;
            --secondary: #0ea5e9;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 50px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 25px; transition: 0.3s; }
        .btn-back:hover { transform: translateX(-5px); }

        .header-section { margin-bottom: 40px; }
        .header-section h1 { font-size: 32px; font-weight: 700; color: var(--primary); }
        .header-section p { color: #64748b; font-size: 18px; }

        /* Summary Grid */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .summary-card { background: var(--card); padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; border: 1px solid #f1f5f9; }
        .s-icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .s-info h3 { font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 5px; }
        .s-info p { font-size: 32px; font-weight: 700; color: var(--text); }

        /* Tables Section */
        .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .report-card { background: var(--card); border-radius: 24px; padding: 30px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .card-header h2 { font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f8fafc; font-size: 15px; }
        .count-badge { background: #f1f5f9; padding: 4px 12px; border-radius: 50px; font-weight: 700; font-size: 14px; }

        @media (max-width: 900px) {
            .report-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="header-section">
            <h1><i class="fas fa-chart-line"></i> Repository Intelligence</h1>
            <p>Comprehensive analysis of academic project collections.</p>
        </div>

        <!-- Summary Grid -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="s-icon" style="background: var(--primary);"><i class="fas fa-database"></i></div>
                <div class="s-info"><h3>Total Managed Topics</h3><p><?= number_format($overall_stats['total']) ?></p></div>
            </div>
            <a href="lib_manage_projects.php?pdf_status=yes" class="summary-card" style="text-decoration: none; color: inherit;">
                <div class="s-icon" style="background: var(--success);"><i class="fas fa-file-pdf"></i></div>
                <div class="s-info"><h3>With PDF Resources</h3><p><?= number_format($overall_stats['with_pdf']) ?></p><small style="color: var(--success); font-weight: 600;">View all <i class="fas fa-arrow-right"></i></small></div>
            </a>
            <a href="lib_manage_projects.php?pdf_status=no" class="summary-card" style="text-decoration: none; color: inherit;">
                <div class="s-icon" style="background: var(--danger);"><i class="fas fa-file-excel"></i></div>
                <div class="s-info"><h3>PDFs Missing</h3><p><?= number_format($overall_stats['without_pdf']) ?></p><small style="color: var(--danger); font-weight: 600;">View all <i class="fas fa-arrow-right"></i></small></div>
            </a>
        </div>

        <div class="report-grid">
            <!-- Faculty Report -->
            <div class="report-card">
                <div class="card-header">
                    <h2><i class="fas fa-university"></i> Distribution by Faculty</h2>
                </div>
                <table>
                    <thead>
                        <tr><th>Faculty Name</th><th style="text-align: right;">Total Topics</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faculty_stats as $stat): ?>
                            <tr>
                                <td><?= htmlspecialchars($stat['name']) ?></td>
                                <td style="text-align: right;"><span class="count-badge"><?= $stat['count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($faculty_stats)): ?>
                            <tr><td colspan="2" style="text-align: center; padding: 30px; color: #94a3b8;">No data found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Department Report -->
            <div class="report-card">
                <div class="card-header">
                    <h2><i class="fas fa-building"></i> Distribution by Department</h2>
                </div>
                <table>
                    <thead>
                        <tr><th>Department</th><th style="text-align: right;">Active topics</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dept_stats as $stat): ?>
                            <tr>
                                <td><?= htmlspecialchars($stat['name']) ?></td>
                                <td style="text-align: right;"><span class="count-badge" style="background: #e0f2fe; color: #0369a1;"><?= $stat['count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($dept_stats)): ?>
                            <tr><td colspan="2" style="text-align: center; padding: 30px; color: #94a3b8;">No department data available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
