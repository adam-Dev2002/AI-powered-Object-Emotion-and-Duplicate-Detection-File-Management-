<?php
require 'config.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Query to only count distinct filehashes where last_scanned is NULL, preventing duplicates
    $stmt = $conn->query("
        SELECT COUNT(DISTINCT filehash) AS new_files 
        FROM files 
        WHERE last_scanned IS NULL AND filehash IS NOT NULL
    ");
    $result = $stmt->fetch_assoc();

    if ($result['new_files'] > 0) {
        echo json_encode([
            "status" => "new_files_uploaded",
            "new_files" => $result['new_files']
        ]);
    } else {
        echo json_encode([
            "status" => "no_new_files",
            "new_files" => 0
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>
