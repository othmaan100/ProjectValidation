<?php
include 'includes/db.php';

try {
    // Add 'hod' to the role enum in the users table
    // Note: In MySQL, we have to re-define the whole ENUM
    $conn->exec("ALTER TABLE users MODIFY COLUMN role ENUM('stu','dpc','fpc','sup','admin','lib','ext','hod')");
    echo "HOD schema update successful. User roles updated.\n";
} catch(PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>
