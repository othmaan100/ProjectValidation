<?php
// Primary MySQL Configuration
$my_host = 'localhost';
$my_dbname = 'my_project_topics2';
$my_username = 'root';
$my_password = '';

try {
    // Attempt primary connection
    $conn = new PDO("mysql:host=$my_host;dbname=$my_dbname", $my_username, $my_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e_mysql) {
    // Log primary connection failure
    error_log("Primary MySQL Connection failed: " . $e_mysql->getMessage());

    try {
        // Fallback MySQL Configuration
        $fallback_dbname = 'fcetpoti_projectval';
        $fallback_username = 'fcetpoti_projectval';
        $fallback_password = 'Othmaan100!!!';

        // Attempt fallback connection
        $conn = new PDO("mysql:host=$my_host;dbname=$fallback_dbname", $fallback_username, $fallback_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e_fallback) {
        // Log fallback connection failure
        error_log("Fallback MySQL Connection failed: " . $e_fallback->getMessage());
        die("Database connection failed. Please contact administrator.");
    }
}
?>
