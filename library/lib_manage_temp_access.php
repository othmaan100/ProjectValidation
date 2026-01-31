<?php
include_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lib') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$message = '';
$status = '';

// Handle generating new passcode
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate' || $_POST['action'] === 'bulk_generate') {
        $count = isset($_POST['bulk_count']) ? min(50, max(1, intval($_POST['bulk_count']))) : 1;
        
        try {
            $conn->beginTransaction();
            for ($i = 0; $i < $count; $i++) {
                $username = 'GUEST' . rand(1000, 9999) . rand(10, 99);
                $passcode = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
                $stmt = $conn->prepare("INSERT INTO library_temp_access (username, passcode, created_by) VALUES (?, ?, ?)");
                $stmt->execute([$username, $passcode, $_SESSION['user_id']]);
            }
            $conn->commit();
            $message = $count > 1 ? "$count temporary access codes generated successfully." : "New temporary access generated.";
            $status = "success";
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "Error: " . $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM library_temp_access WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Access revoked successfully.";
        $status = "success";
    } elseif ($_POST['action'] === 'clear_used') {
        $stmt = $conn->query("DELETE FROM library_temp_access WHERE is_used = 1");
        $message = "History cleared.";
        $status = "success";
    }
}

// Stats for Accounting
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_issued,
        SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as used_count,
        SUM(CASE WHEN is_used = 0 THEN 1 ELSE 0 END) as active_count
    FROM library_temp_access
")->fetch(PDO::FETCH_ASSOC);

// Fetch active/recent temp access
$stmt = $conn->query("SELECT * FROM library_temp_access ORDER BY created_at DESC LIMIT 100");
$temp_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Access & Accounting - Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #5b21b6; --bg: #f8fafc; --text: #0f172a; --success: #10b981; --danger: #ef4444; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 50px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .header { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: var(--primary); font-size: 28px; display: flex; align-items: center; gap: 12px; margin: 0; }
        
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
        .stat-info h4 { margin: 0; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-info span { font-size: 24px; font-weight: 700; }

        .main-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 30px; }
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .btn { padding: 12px 24px; border-radius: 12px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-family: inherit; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-outline { border: 2px solid #e2e8f0; background: transparent; color: #64748b; }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-danger { background: #fee2e2; color: var(--danger); }
        .btn-sm { padding: 6px 12px; font-size: 11px; border-radius: 8px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #f1f5f9; border-radius: 10px; font-family: inherit; box-sizing: border-box; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 12px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .passcode { font-family: monospace; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight: 700; color: #1e293b; }
        .status-badge { padding: 4px 8px; border-radius: 50px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-used { background: #f1f5f9; color: #475569; }

        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: #dcfce7; color: #15803d; border-left: 4px solid var(--success); }
        
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--primary); text-decoration: none; font-weight: 600; margin-bottom: 20px; }

        /* Print Styles */
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; left: 0; top: 0; width: 100%; }
            .print-card { 
                border: 1px dashed #000; 
                padding: 20px; 
                margin: 10px; 
                width: 250px; 
                display: inline-block; 
                page-break-inside: avoid;
                text-align: center;
                background: white !important;
                color: black !important;
            }
            .print-card .title { font-weight: bold; font-size: 14px; margin-bottom: 5px; border-bottom: 1px solid #000; padding-bottom: 5px; }
            .print-card .code { font-size: 20px; font-family: monospace; font-weight: bold; margin: 10px 0; }
            .print-card .footer { font-size: 10px; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; }
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="header">
            <div>
                <h1><i class="fas fa-user-lock"></i> Access Control & Accounting</h1>
                <p style="color: #64748b; margin-top: 5px;">Manage student guest credentials and track distribution metrics.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> Print Unused Slips</button>
                <form method="POST" onsubmit="return confirm('Clear all recorded usage history?');">
                    <input type="hidden" name="action" value="clear_used">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-broom"></i> Clear History</button>
                </form>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background: #6366f1;"><i class="fas fa-ticket-alt"></i></div>
                <div class="stat-info"><h4>Total Issued</h4><span><?= $stats['total_issued'] ?></span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #10b981;"><i class="fas fa-check-double"></i></div>
                <div class="stat-info"><h4>Used Codes</h4><span><?= $stats['used_count'] ?: 0 ?></span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #f59e0b;"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h4>Available</h4><span><?= $stats['active_count'] ?: 0 ?></span></div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="main-grid">
            <div class="card">
                <h3 style="margin-top:0;"><i class="fas fa-plus-circle"></i> Bulk Generation</h3>
                <p style="font-size: 13px; color: #64748b;">Generate multiple slips at once for physical distribution.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="bulk_generate">
                    <div class="form-group">
                        <label>Number of Slips</label>
                        <input type="number" name="bulk_count" class="form-control" value="10" min="1" max="50">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Generate Stubs</button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-top:0;"><i class="fas fa-list"></i> Recent Credentials</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Passcode</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($temp_accounts)): ?>
                            <tr><td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">No credentials found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($temp_accounts as $acc): ?>
                                <tr>
                                    <td><strong><?= $acc['username'] ?></strong></td>
                                    <td><span class="passcode"><?= $acc['passcode'] ?></span></td>
                                    <td>
                                        <span class="status-badge status-<?= $acc['is_used'] ? 'used' : 'active' ?>">
                                            <?= $acc['is_used'] ? 'Used' : 'Active' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button onclick="printSingle('<?= $acc['username'] ?>', '<?= $acc['passcode'] ?>')" class="btn btn-outline btn-sm" title="Print Slip"><i class="fas fa-print"></i></button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Revoke access?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hidden Print Templates -->
    <div id="print-area-bulk" class="print-area" style="display:none;">
        <?php foreach ($temp_accounts as $acc): if(!$acc['is_used']): ?>
            <div class="print-card">
                <div class="title">LIBRARY GUEST ACCESS</div>
                <div style="font-size: 11px;">Username:</div>
                <div style="font-weight: bold;"><?= $acc['username'] ?></div>
                <div style="font-size: 11px; margin-top: 5px;">Passcode:</div>
                <div class="code"><?= $acc['passcode'] ?></div>
                <div class="footer">Expires automatically after logout. One-time use per login.</div>
            </div>
        <?php endif; endforeach; ?>
    </div>

    <div id="print-area-single" class="print-area" style="display:none;"></div>

    <script>
        function printSingle(user, pass) {
            const container = document.getElementById('print-area-single');
            container.innerHTML = `
                <div class="print-card">
                    <div class="title">LIBRARY GUEST ACCESS</div>
                    <div style="font-size: 11px;">Username:</div>
                    <div style="font-weight: bold;">${user}</div>
                    <div style="font-size: 11px; margin-top: 5px;">Passcode:</div>
                    <div class="code">${pass}</div>
                    <div class="footer">Expires automatically after logout. One-time use per login.</div>
                </div>
            `;
            
            // Temporary hide bulk print area to avoid conflict
            const bulk = document.getElementById('print-area-bulk');
            const oldBulk = bulk.className;
            bulk.className = '';
            
            container.style.display = 'block';
            container.className = 'print-area';
            
            window.print();
            
            container.style.display = 'none';
            bulk.className = oldBulk;
        }

        // Automatic bulk print handler
        window.onbeforeprint = function() {
            if (document.getElementById('print-area-single').innerHTML === '') {
                document.getElementById('print-area-bulk').style.display = 'block';
                document.getElementById('print-area-bulk').className = 'print-area';
            }
        };
        window.onafterprint = function() {
            document.getElementById('print-area-bulk').style.display = 'none';
            document.getElementById('print-area-single').innerHTML = '';
        };
    </script>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
