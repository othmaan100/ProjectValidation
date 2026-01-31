<?php
include 'includes/db.php';
try {
    $conn->query("CREATE TABLE IF NOT EXISTS defense_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        supervisor_id INT NOT NULL,
        panel_id INT NOT NULL,
        score DECIMAL(5,2),
        comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY student_sup_panel (student_id, supervisor_id, panel_id)
    )");
    echo "Table defense_scores created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
