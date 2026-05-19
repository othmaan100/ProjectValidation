<?php
include_once __DIR__ . '/includes/db.php';

try {
    // Attempt to add the venue, panel_date, and panel_time columns to the defense_panels table.
    $sql = "ALTER TABLE defense_panels 
            ADD COLUMN venue VARCHAR(255) NULL, 
            ADD COLUMN panel_date DATE NULL, 
            ADD COLUMN panel_time TIME NULL;";
            
    $conn->exec($sql);
    echo "<div style='font-family: sans-serif; display: inline-block; margin: 20px; padding: 20px; border: 1px solid #10b981; background: #ecfdf5; color: #065f46; border-radius: 8px;'>";
    echo "<strong>Success!</strong> Database schema updated successfully. Added `venue`, `panel_date`, and `panel_time` columns to `defense_panels`.";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='font-family: sans-serif; display: inline-block; margin: 20px; padding: 20px; border: 1px solid #ef4444; background: #fef2f2; color: #991b1b; border-radius: 8px;'>";
    
    // Code '42S21' or '42000' usually means duplicate column name
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<strong>Update Skipped:</strong> The columns already exist in the database.";
    } else {
        echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    }
    echo "</div>";
}
?>
