<?php
include 'includes/db.php';

try {
    // Add num_chapters to departments
    $conn->exec("ALTER TABLE departments ADD COLUMN num_chapters INT DEFAULT 5");
    echo "Added num_chapters to departments table.\n";
} catch (PDOException $e) {
    echo "Note: num_chapters column might already exist or: " . $e->getMessage() . "\n";
}

try {
    // Create chapter_approvals table
    $sql = "CREATE TABLE IF NOT EXISTS chapter_approvals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        supervisor_id INT NOT NULL,
        chapter_number INT NOT NULL,
        status ENUM('pending', 'approved') DEFAULT 'pending',
        approval_date DATETIME DEFAULT NULL,
        academic_session VARCHAR(50),
        UNIQUE KEY student_chapter (student_id, chapter_number)
    )";
    $conn->exec($sql);
    echo "Created chapter_approvals table.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
