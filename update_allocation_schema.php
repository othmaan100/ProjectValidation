<?php
include 'includes/db.php';

try {
    // Create the supervisor_allocation_status table
    $sql = "CREATE TABLE IF NOT EXISTS supervisor_allocation_status (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        supervisor_id INT NOT NULL, 
        session VARCHAR(100) NOT NULL, 
        accepted_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
        UNIQUE KEY unique_supervisor_session (supervisor_id, session),
        FOREIGN KEY (supervisor_id) REFERENCES supervisors(id) ON DELETE CASCADE
    )";
    
    $conn->exec($sql);
    echo "Schema update successful. Table 'supervisor_allocation_status' created/verified successfully.\n";
} catch(PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>
