<?php
/**
 * Full Database Exporter
 *
 * Dumps every table (structure + data) in the live database to a
 * downloadable .sql file, so you can import it straight into your
 * local XAMPP/MySQL for development.
 *
 * SECURITY: this exports EVERYTHING, including password hashes and
 * personal data for every user. Do not leave it reachable.
 *
 * Instructions:
 * 1. Change EXPORT_SECRET_KEY below to your own random string.
 * 2. Upload this file to the root of your project on the LIVE server.
 * 3. Visit it in your browser and enter the key when prompted -
 *    it will download a .sql file.
 * 4. Import it locally, e.g.:
 *      "C:\xampp\mysql\bin\mysql.exe" -u root your_local_db < the_downloaded_file.sql
 *    or via phpMyAdmin -> your local database -> Import.
 * 5. Delete this file from the live server immediately after use.
 */

define('EXPORT_SECRET_KEY', 'NRF2026@');

require_once __DIR__ . '/includes/db.php';

$provided_key = $_GET['key'] ?? $_POST['key'] ?? '';

if (!hash_equals(EXPORT_SECRET_KEY, $provided_key)) {
    if (EXPORT_SECRET_KEY === 'change-me-to-something-random') {
        http_response_code(403);
        echo "<!DOCTYPE html><html><body style='font-family:sans-serif; max-width:600px; margin:60px auto;'>";
        echo "<h2 style='color:#c0392b;'>Set your export key first</h2>";
        echo "<p>Open <code>export_database.php</code> and change <code>EXPORT_SECRET_KEY</code> to a random string before using this on a live server.</p>";
        echo "</body></html>";
        exit();
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Export</title>
        <style>
            body { font-family: 'Segoe UI', sans-serif; background: #f4f7fe; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .card { background: #fff; border-radius: 16px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 380px; width: 100%; }
            h1 { font-size: 20px; margin: 0 0 10px; color: #2d3436; }
            p { color: #636e72; font-size: 13px; line-height: 1.5; }
            input { width: 100%; padding: 12px 14px; border: 2px solid #eef1f8; border-radius: 10px; font-size: 14px; margin: 12px 0; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background: #667eea; color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
            .error { color: #e74a3b; font-size: 13px; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Database Export</h1>
            <p>Enter the export key set in <code>export_database.php</code> to download a full <code>.sql</code> dump of this database.</p>
            <?php if ($provided_key !== ''): ?><p class="error">Incorrect key.</p><?php endif; ?>
            <form method="GET">
                <input type="password" name="key" placeholder="Export key" autofocus required>
                <button type="submit">Download .sql Dump</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// --- Key verified: stream the dump ---

set_time_limit(0);
while (ob_get_level() > 0) { ob_end_clean(); }

$db_name = $conn->query("SELECT DATABASE()")->fetchColumn();
$filename = 'db_export_' . preg_replace('/[^A-Za-z0-9_-]/', '', $db_name) . '_' . date('Ymd_His') . '.sql';

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

echo "-- Full export of database `$db_name`\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- Import locally with: mysql -u root your_local_db < this_file.sql\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n";
echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
echo "SET NAMES utf8mb4;\n\n";
flush();

$tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$batch_size = 500;

foreach ($tables as $table) {
    echo "-- --------------------------------------------------\n";
    echo "-- Table: `$table`\n";
    echo "-- --------------------------------------------------\n";
    echo "DROP TABLE IF EXISTS `$table`;\n";

    $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    echo $create['Create Table'] . ";\n\n";
    flush();

    $total = (int)$conn->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    if ($total === 0) {
        continue;
    }

    $columns = array_column($conn->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $col_list = '`' . implode('`, `', $columns) . '`';

    echo "-- Data for `$table` ($total row" . ($total === 1 ? '' : 's') . ")\n";

    $offset = 0;
    while ($offset < $total) {
        $stmt = $conn->query("SELECT * FROM `$table` LIMIT $batch_size OFFSET $offset");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        if (empty($rows)) break;

        $value_groups = [];
        foreach ($rows as $row) {
            $escaped = array_map(function ($v) use ($conn) {
                return $v === null ? 'NULL' : $conn->quote($v);
            }, $row);
            $value_groups[] = '(' . implode(', ', $escaped) . ')';
        }

        echo "INSERT INTO `$table` ($col_list) VALUES\n" . implode(",\n", $value_groups) . ";\n";
        flush();

        $offset += $batch_size;
    }
    echo "\n";
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
exit();
