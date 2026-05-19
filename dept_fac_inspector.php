<?php
include 'includes/db.php';
try {
    echo "--- departments ---\n";
    $depts = $conn->query("SELECT * FROM departments LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    print_r($depts);

    echo "\n--- faculty ---\n";
    $facs = $conn->query("SELECT * FROM faculty LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    print_r($facs);
} catch (Exception $e) {
    echo $e->getMessage();
}
