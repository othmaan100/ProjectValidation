<?php
$src = __DIR__ . '/faculty_project_coordinator/fpc_manage_dpc.php';
$dst = __DIR__ . '/faculty_project_coordinator/fpc_manage_hod.php';

$content = file_get_contents($src);

// Replace DPC with HOD
$content = str_replace("'dpc'", "'hod'", $content);
$content = str_replace('"dpc"', '"hod"', $content);
$content = str_replace("role = 'dpc'", "role = 'hod'", $content);
$content = preg_replace("/\\bDPC\\b/", "HOD", $content);
$content = preg_replace("/\\bDPCs\\b/", "HODs", $content);
$content = preg_replace("/\\bdpc\\b/", "hod", $content);
$content = str_replace("fpc_manage_dpc.php", "fpc_manage_hod.php", $content);

file_put_contents($dst, $content);
echo "Created fpc_manage_hod.php successfully.\n";
?>
