<?php
$src_dir = __DIR__ . '/department_project_coordinator';
$dst_dir = __DIR__ . '/hod';

// 1. Create directory
if (!is_dir($dst_dir)) {
    mkdir($dst_dir, 0777, true);
}

// 2. Scan source directory
$files = scandir($src_dir);
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $src_file = $src_dir . '/' . $file;
    if (is_file($src_file)) {
        // Change prefix
        $new_file_name = str_replace('dpc_', 'hod_', $file);
        $dst_file = $dst_dir . '/' . $new_file_name;
        
        $content = file_get_contents($src_file);
        
        // 3. Replace content
        // Role check
        $content = str_replace('$_SESSION[\'role\'] !== \'dpc\'', '$_SESSION[\'role\'] !== \'hod\'', $content);
        $content = str_replace('$_SESSION["role"] !== \'dpc\'', '$_SESSION["role"] !== \'hod\'', $content);
        $content = str_replace("role !== 'dpc'", "role !== 'hod'", $content);
        $content = preg_replace("/\\b(DPC)\\b/", "HOD", $content);
        $content = str_replace("dpc_", "hod_", $content);
        $content = str_replace('$dpc_id', '$hod_id', $content);
        $content = str_replace('$dpc_info', '$hod_info', $content);
        
        // Save
        file_put_contents($dst_file, $content);
        echo "Created: $dst_file\n";
    }
}
echo "HOD module created successfully.\n";
?>
