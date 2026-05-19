<?php
include 'includes/db.php';
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS supervisor_allocation_status (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        supervisor_id INT, 
        session VARCHAR(100), 
        accepted_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
        UNIQUE KEY(supervisor_id, session)
    )");
    echo 'Table supervisor_allocation_status created';
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
