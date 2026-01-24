<?php
include 'includes/db.php';
try {
    // Add 'lib' to the role enum in users table
    // Note: In MySQL, we have to re-define the whole ENUM
    $conn->exec("ALTER TABLE users MODIFY COLUMN role ENUM('stu','dpc','fpc','sup','admin','lib')");
    echo "User roles updated successfully.\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
