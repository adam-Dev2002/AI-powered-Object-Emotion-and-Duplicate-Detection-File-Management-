<?php
// deleteMedia.php

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if the necessary parameters are present
if (isset($data['filepath']) && isset($data['fileName'])) {
    $filePath = trim($data['filepath']);
    $fileName = trim($data['fileName']);

    // Log the file path for debugging
    error_log("Delete request for file: $filePath");

    // Check if the file exists
    if (file_exists($filePath)) {
        // Attempt to delete the file
        if (unlink($filePath)) {
            // If deletion is successful
            echo json_encode(['status' => 'success', 'message' => 'File deleted successfully.']);
        } else {
            // If the deletion fails
            error_log("Failed to delete file: $filePath");
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete the file.']);
        }
    } else {
        // If the file doesn't exist
        error_log("File does not exist: $filePath");
        echo json_encode(['status' => 'error', 'message' => 'File does not exist.']);
    }
} else {
    // If required parameters are missing
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
}


// Log raw POST data for debugging
error_log("Raw POST data: " . file_get_contents('php://input'));

// Decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Log decoded data
error_log("Decoded request: " . print_r($data, true));

?>
