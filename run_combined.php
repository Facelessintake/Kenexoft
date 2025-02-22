<?php
session_start();

// Set Python path (if needed)
$python_path = 'python'; // Use 'python' if it works, otherwise use full path

// Set script path (Make sure the path is correct!)
$script_path = __DIR__ . DIRECTORY_SEPARATOR . 'combined.py';

// Execute Python script
$output = shell_exec("$python_path $script_path 2>&1");

// Log output for debugging
file_put_contents("logs/combined_log.txt", $output);

// Store message in session to show in dashboard
$_SESSION['script_status'] = "Script execution output: " . htmlspecialchars($output);

// Redirect back to dashboard
header("Location: dashboard.php");
exit();
?>
