<?php
session_start();
header('Content-Type: application/json');

// Path to the lock file
$lockFile = __DIR__ . '/scan_running.lock';

// Check if the lock file exists
if (file_exists($lockFile)) {
    try {
        // Find and kill the Python process using the lock file
        $command = "ps aux | grep 'yolo_ai.py' | grep -v grep | awk '{print $2}'";
        $processId = shell_exec($command);

        if ($processId) {
            // Kill the Python process
            shell_exec("kill -9 $processId");
            unlink($lockFile); // Remove the lock file

            $_SESSION['scan_running'] = false;
            echo json_encode(['status' => 'success', 'message' => 'Scan stopped successfully.']);
            exit;
        } else {
            unlink($lockFile); // If no process found, remove the lock file
            $_SESSION['scan_running'] = false;
            echo json_encode(['status' => 'success', 'message' => 'Scan stopped successfully.']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to stop scan: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No scan is currently running.']);
    exit;
}
?>
