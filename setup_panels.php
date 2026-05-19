<?php
include 'includes/db.php';
try {
    // 1. Create defense_panels table
    $conn->query("CREATE TABLE IF NOT EXISTS defense_panels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        panel_name VARCHAR(100) NOT NULL,
        department_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Create panel_members table
    $conn->query("CREATE TABLE IF NOT EXISTS panel_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        panel_id INT NOT NULL,
        supervisor_id INT NOT NULL,
        FOREIGN KEY (panel_id) REFERENCES defense_panels(id) ON DELETE CASCADE
    )");

    // 3. Create student_panel_assignments table
    $conn->query("CREATE TABLE IF NOT EXISTS student_panel_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        panel_id INT NOT NULL,
        academic_session VARCHAR(20),
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (panel_id) REFERENCES defense_panels(id) ON DELETE CASCADE
    )");

    echo "Panel tables created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
