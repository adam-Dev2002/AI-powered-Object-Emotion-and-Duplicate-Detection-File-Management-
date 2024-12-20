<?php
session_start();
header('Content-Type: application/json');

set_time_limit(0); // Allow PHP to run indefinitely

$lockFile = __DIR__ . '/scan_running.lock';
$logFile = __DIR__ . '/scan_progress.log';

// Cleanup stale lock file
if (file_exists($lockFile)) {
    $pid = file_get_contents($lockFile);
    if (!is_process_running($pid)) unlink($lockFile);
}

try {
    // Clear the previous progress log
    if (file_exists($logFile)) unlink($logFile);

    // Start the scan process
    $pid = start_background_process($logFile);
    file_put_contents($lockFile, $pid);

    echo json_encode(['status' => 'success', 'message' => 'Scan started successfully.']);
    exit;
} catch (Exception $e) {
    if (file_exists($lockFile)) unlink($lockFile);
    echo json_encode(['status' => 'error', 'message' => 'Failed to start scan: ' . $e->getMessage()]);
    exit;
}

// Function to start the Python script
function start_background_process($logFile) {
    $command = "nohup python3 " . escapeshellarg(__DIR__ . "/yolo_ai.py") . " > " . escapeshellarg($logFile) . " 2>&1 & echo $!";
    exec($command, $output);
    return (int)$output[0];
}

function is_process_running($pid) {
    exec("ps -p $pid", $output);
    return count($output) > 1;
}
?>
