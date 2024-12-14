<?php
// Base directory for trash
$base_directory = '/Volumes/creative/TRASH/';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if required parameters are provided
if (isset($data['filepath']) && isset($data['fileName'])) {
    $filePath = trim($data['filepath']);
    $fileName = trim($data['fileName']);

    // Check if the trash directory exists, create it if not
    if (!is_dir($base_directory)) {
        if (!mkdir($base_directory, 0777, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create trash directory.']);
            exit;
        }
    }

    // Sanitize the file name to prevent path traversal attacks
    $safeFileName = basename($fileName);

    // Construct the new path in the trash directory
    $newPath = rtrim($base_directory, '/') . '/' . $safeFileName;

    // Ensure the file exists before moving
    if (file_exists($filePath)) {
        // Check if a file with the same name already exists in the trash
        if (file_exists($newPath)) {
            // Append a unique suffix to the file name
            $fileInfo = pathinfo($safeFileName);
            $newPath = rtrim($base_directory, '/') . '/' . $fileInfo['filename'] . '_' . time() . '.' . $fileInfo['extension'];
        }

        // Attempt to move the file
        if (rename($filePath, $newPath)) {
            echo json_encode(['status' => 'success', 'message' => 'File moved to trash successfully.', 'trashPath' => $newPath]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to move the file to trash.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File does not exist.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
}
?>
