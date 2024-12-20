<?php
// Include database configuration
require_once 'config.php';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if the necessary parameters are present
if (isset($data['filepath']) && isset($data['fileName'])) {
    $filePath = trim($data['filepath']);
    $fileName = trim($data['fileName']);

    // Log the file path for debugging
    error_log("Delete request for file: $filePath");

    // Check if the file exists in the filesystem
    if (file_exists($filePath)) {
        // Attempt to delete the file from the filesystem
        if (unlink($filePath)) {
            // File deleted successfully from the filesystem
            
            // Prepare SQL to delete the record from the `files` table
            $stmt = $conn->prepare("DELETE FROM files WHERE filepath = ?");
            $stmt->bind_param("s", $filePath);

            if ($stmt->execute()) {
                // Record successfully deleted from the database
                echo json_encode(['status' => 'success', 'message' => 'File deleted successfully from filesystem and database.']);
            } else {
                // Failed to delete the record from the database
                error_log("Failed to delete record from database: " . $conn->error);
                echo json_encode(['status' => 'error', 'message' => 'File deleted from filesystem, but failed to delete record from database.']);
            }

            $stmt->close();
        } else {
            // If deletion fails from filesystem
            error_log("Failed to delete file: $filePath");
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete the file from the filesystem.']);
        }
    } else {
        // File does not exist in the filesystem
        error_log("File does not exist: $filePath");

        // Still attempt to delete the record in the database
        $stmt = $conn->prepare("DELETE FROM files WHERE filepath = ?");
        $stmt->bind_param("s", $filePath);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'File not found, but record deleted from database.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'File not found and failed to delete record from database.']);
        }

        $stmt->close();
    }
} else {
    // If required parameters are missing
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
}

// Close database connection
$conn->close();

// Log raw POST data for debugging
error_log("Raw POST data: " . file_get_contents('php://input'));

// Decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Log decoded data
error_log("Decoded request: " . print_r($data, true));
?>
