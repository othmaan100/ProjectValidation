<?php
/**
 * Clearance Migration Execution Script
 * 
 * Run this file in your browser to apply the clearance_migration.sql to your database.
 * Once executed successfully, you should delete this file for security purposes.
 */

// Include the database connection
require_once __DIR__ . '/includes/db.php';

echo "<h2>Database Migration</h2>";

try {
    // Read the SQL file
    $sql_file = __DIR__ . '/clearance_migration.sql';
    
    if (!file_exists($sql_file)) {
        die("<p style='color:red;'>Error: SQL file not found at " . htmlspecialchars($sql_file) . "</p>");
    }

    $sql = file_get_contents($sql_file);

    // Execute the SQL queries
    // Note: PDO::exec can execute multiple statements separated by semicolons if the driver supports it
    $affected_rows = $conn->exec($sql);

    echo "<p style='color:green;'><strong>Success!</strong> The database migration has been completed successfully.</p>";
    echo "<p>You should now delete this file (<code>run_migration.php</code>) and <code>clearance_migration.sql</code> from the server for security.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>General Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
