<?php
// Include database configuration
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode JSON input
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input data
    if (isset($data['filePath']) && isset($data['newName'])) {
        $filePath = trim($data['filePath']); // Existing file path
        $newName = trim($data['newName']);   // New file name

        // Construct the new file path
        $directory = dirname($filePath);
        $newPath = $directory . '/' . $newName;

        // Step 1: Rename the file in the filesystem
        if (file_exists($filePath)) {
            if (rename($filePath, $newPath)) {
                // Step 2: Update the database
                $stmt = $conn->prepare("UPDATE files SET filepath = ?, filename = ? WHERE filepath = ?");
                $stmt->bind_param("sss", $newPath, $newName, $filePath);

                if ($stmt->execute()) {
                    // Successful response
                    echo json_encode(['success' => true, 'message' => 'File renamed successfully.']);
                } else {
                    // Database update failed
                    echo json_encode(['success' => false, 'error' => 'Failed to update database: ' . $conn->error]);
                }

                $stmt->close();
            } else {
                // Filesystem rename failed
                echo json_encode(['success' => false, 'error' => 'Failed to rename file in the filesystem.']);
            }
        } else {
            // File does not exist
            echo json_encode(['success' => false, 'error' => 'File does not exist.']);
        }
    } else {
        // Missing input parameters
        echo json_encode(['success' => false, 'error' => 'Invalid input parameters.']);
    }
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}

// Close the database connection
$conn->close();
?>
