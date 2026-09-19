<?php
/**
 * Session-Scoped Student Data - Database Migration
 *
 * Adds a `session` column to `students` so each academic session has its
 * own isolated cohort of registrations, topics, and allocations. Existing
 * rows are backfilled with whatever session is currently active, so
 * nothing already in the database disappears.
 *
 * Instructions:
 * 1. Upload this file to the root of your project directory on the live server.
 * 2. Navigate to this file in your browser (e.g. https://yourdomain.com/install_session_scoping.php).
 * 3. Confirm every step below shows green.
 * 4. Delete this file immediately after it runs successfully for security.
 */

require_once __DIR__ . '/includes/db.php';

echo "<h1>Session-Scoped Student Data - Database Migration</h1>";
echo "<ul>";

function column_exists($conn, $table, $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function index_exists($conn, $table, $index) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

function step($label, $callback) {
    global $conn;
    try {
        $callback($conn);
        echo "<li style='color: green;'>OK - $label</li>";
    } catch (PDOException $e) {
        echo "<li style='color: orange;'>Skipped - $label (" . htmlspecialchars($e->getMessage()) . ")</li>";
    }
}

// 1. Add the session column (nullable for now - backfilled below)
if (!column_exists($conn, 'students', 'session')) {
    step("Add students.session column", function ($conn) {
        $conn->exec("ALTER TABLE students ADD COLUMN session VARCHAR(50) DEFAULT NULL AFTER department");
    });
} else {
    echo "<li style='color: green;'>OK - students.session column already exists</li>";
}

// 2. Backfill existing rows with the currently active session
step("Backfill existing students with the active session", function ($conn) {
    $stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_session'");
    $active_session = $stmt->fetchColumn() ?: (date('Y') . '/' . (date('Y') + 1));

    $stmt = $conn->prepare("UPDATE students SET session = ? WHERE session IS NULL OR session = ''");
    $stmt->execute([$active_session]);
    echo " (backfilled with '" . htmlspecialchars($active_session) . "', " . $stmt->rowCount() . " row(s))";
});

// 3. Lock the column to NOT NULL now that every row has a value
step("Make students.session NOT NULL", function ($conn) {
    $conn->exec("ALTER TABLE students MODIFY COLUMN session VARCHAR(50) NOT NULL");
});

// 4. Replace the global reg_no unique key with a per-session one, so the
//    same Reg No can be re-registered in a different session.
if (index_exists($conn, 'students', 'reg_no')) {
    step("Drop the old global-unique reg_no index", function ($conn) {
        $conn->exec("ALTER TABLE students DROP INDEX reg_no");
    });
} else {
    echo "<li style='color: green;'>OK - old reg_no index already absent</li>";
}

if (!index_exists($conn, 'students', 'reg_no_session')) {
    step("Add per-session unique key (reg_no, session)", function ($conn) {
        $conn->exec("ALTER TABLE students ADD UNIQUE KEY reg_no_session (reg_no, session)");
    });
} else {
    echo "<li style='color: green;'>OK - reg_no_session unique key already exists</li>";
}

echo "</ul>";
echo "<h2 style='color: blue;'>Migration Complete!</h2>";
echo "<p><strong>Important:</strong> Please delete this file (<code>install_session_scoping.php</code>) from your server immediately for security reasons.</p>";
?>
