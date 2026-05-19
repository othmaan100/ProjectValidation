<?php
include_once __DIR__ . '/includes/db.php';

try {
    $conn->beginTransaction();

    // 1. Add 'ext' role to users table
    // Fetch current enum values first to avoid overwriting others if they exist
    // However, usually we can just re-define it. 
    // From conversation history/Databas.sql, role is enum('stu','dpc','fpc','sup')
    $conn->exec("ALTER TABLE users MODIFY COLUMN role ENUM('stu','dpc','fpc','sup','ext') NOT NULL");

    // 2. Create external_examiners table
    $conn->exec("CREATE TABLE IF NOT EXISTS external_examiners (
        id INT PRIMARY KEY, -- Links to users.id
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(50),
        affiliation VARCHAR(255), -- Organization or University
        department_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 3. Update defense_scores to make it clear it can be an external examiner too
    // We'll keep the column supervisor_id for now to avoid breaking existing queries, 
    // but we know it stores users.id (which can be sup or ext)
    
    $conn->commit();
    echo "External Examiners table and 'ext' role added successfully!";
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
