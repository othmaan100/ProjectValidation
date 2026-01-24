<?php
include 'includes/db.php';
$sql = "CREATE TABLE IF NOT EXISTS submission_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNIQUE,
    submission_start DATETIME,
    submission_end DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
)";
try {
    $conn->exec($sql);
    echo "Table created successfully\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
