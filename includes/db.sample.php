<?php
// MySQL Configuration
$my_host = 'localhost';
$my_dbname = 'my_project_topics2';
$my_username = 'root';
$my_password = '';

try {
    // MySQL PDO Connection
    $conn = new PDO("mysql:host=$my_host;dbname=$my_dbname", $my_username, $my_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e_mysql) {
    // Connection failed
    error_log("MySQL Connection failed: " . $e_mysql->getMessage());
    die("Database connection failed. Please contact administrator.");
}
?>
