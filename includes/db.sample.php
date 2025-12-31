<?php
// Get database credentials from environment variables for Fallback (PostgreSQL)
$pg_host = getenv('DB_HOST') ?: 'localhost';
$pg_dbname = getenv('DB_NAME') ?: 'my_project_topics2';
$pg_username = getenv('DB_USER') ?: 'my_project_topics2_user';
$pg_password = getenv('DB_PASSWORD') ?: 'jEpLGc75UkYnu6mRdZ3FxWugU0YfKtuO';
$pg_port = getenv('DB_PORT') ?: '5432';

// MySQL Configuration (Primary attempt)
$my_host = 'localhost';
$my_dbname = 'my_project_topics2';
$my_username = 'root';
$my_password = '';

try {
    // Attempt 1: MySQL PDO Connection
    $conn = new PDO("mysql:host=$my_host;dbname=$my_dbname", $my_username, $my_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e_mysql) {
    // Attempt 2: PostgreSQL Fallback
    try {
        $conn = new PDO("pgsql:host=$pg_host;port=$pg_port;dbname=$pg_dbname", $pg_username, $pg_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e_pg) {
        // Both failed
        error_log("MySQL Connection failed: " . $e_mysql->getMessage());
        error_log("PostgreSQL Connection failed: " . $e_pg->getMessage());
        die("Database connection failed. Please contact administrator.");
    }
}
?>