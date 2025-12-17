<?php
/**
 * Database Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file and rename it to 'db.php'
 * 2. Update the values below with your actual database credentials
 * 3. Make sure 'db.php' is listed in .gitignore to keep credentials secure
 */

$host = 'localhost';
$dbname = 'your_database_name';  // Change this to your database name
$username = 'root';               // Change this to your database username
$password = '';                   // Change this to your database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
