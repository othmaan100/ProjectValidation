<?php
// Create directories if they don't exist
$directories = [
    'dpc' => 'department_project_coordinator',
    'fpc' => 'faculty_project_coordinator',
    'stu' => 'student',
    'sup' => 'supervisor',
    'includes' => 'includes',
    'assets' => 'assets'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Move files to appropriate directories
$files = glob('*.php');
foreach ($files as $file) {
    if (strpos($file, 'dpc_') === 0) {
        rename($file, 'department_project_coordinator/' . $file);
    } elseif (strpos($file, 'fpc_') === 0) {
        rename($file, 'faculty_project_coordinator/' . $file);
    } elseif (strpos($file, 'stu_') === 0) {
        rename($file, 'student/' . $file);
    } elseif (strpos($file, 'sup_') === 0) {
        rename($file, 'supervisor/' . $file);
    }
}

echo "Files have been organized successfully!";
?>