<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$logFile = __DIR__ . '/scan_progress.log'; // Log file path

if (!file_exists($logFile)) {
    echo json_encode(['status' => 'not_started', 'progress' => 0]);
    exit;
}

// Force reading latest log file content
clearstatcache(); // Clear file cache to fetch updated content
$progress = 0;

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lines) {
    foreach (array_reverse($lines) as $line) { // Read lines in reverse for efficiency
        if (preg_match('/Progress: (\d+(\.\d+)?)%/', $line, $matches)) {
            $progress = floatval($matches[1]);
            break; // Stop once the latest progress line is found
        }
    }
}


if ($progress >= 100) {
    $_SESSION['scan_running'] = false;
    echo json_encode(['status' => 'completed', 'progress' => $progress]);
} else {
    echo json_encode(['status' => 'running', 'progress' => $progress]);
}
exit;

?>
