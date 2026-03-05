<?php
session_start();
echo "<h3>Session Diagnostic</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

if (!isset($_SESSION['test_count'])) {
    $_SESSION['test_count'] = 0;
}
$_SESSION['test_count']++;

echo "Test Count: " . $_SESSION['test_count'] . "<br>";
echo "If you refresh this page, the count should increase. If it stays at 1, sessions are not persisting.<br>";

echo "<h4>Server Info</h4>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Is writable: " . (is_writable(session_save_path() ?: sys_get_temp_dir()) ? "Yes" : "No") . "<br>";
?>
