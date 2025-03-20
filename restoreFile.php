<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config.php';

// Function to ensure the original directory exists before restoring the file
function ensureDirectoryExists($path) {
    $directory = dirname($path);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

// Read JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['filepath'])) {
    $trashFilePath = trim($data['filepath']);

    // Fetch file details from `trashfiles`
    $stmtFetch = $conn->prepare("SELECT * FROM trashfiles WHERE real_filepath = ?");
    $stmtFetch->bind_param("s", $trashFilePath);
    $stmtFetch->execute();
    $result = $stmtFetch->get_result();
    $fileData = $result->fetch_assoc();
    $stmtFetch->close();

    if (!$fileData) {
        echo json_encode(['status' => 'error', 'message' => 'File not found in trash.']);
        exit;
    }

    // Get the original path from `files.filepath` before the file was moved to trash
    $originalFilePath = $fileData['filepath']; // This is the original location
    $realTrashFilePath = $fileData['real_filepath']; // Current physical location

    // Ensure the original directory exists before restoring
    ensureDirectoryExists($originalFilePath);

    // Move the file back to its original location
    if (rename($realTrashFilePath, $originalFilePath)) {
        // Restore file metadata back to `files`
        $stmtRestore = $conn->prepare("INSERT INTO files SELECT * FROM trashfiles WHERE real_filepath = ?");
        $stmtRestore->bind_param("s", $trashFilePath);
        if (!$stmtRestore->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to restore file metadata: ' . $stmtRestore->error]);
            exit;
        }
        $stmtRestore->close();

        // Remove record from `trashfiles`
        $stmtDelete = $conn->prepare("DELETE FROM trashfiles WHERE real_filepath = ?");
        $stmtDelete->bind_param("s", $trashFilePath);
        $stmtDelete->execute();
        $stmtDelete->close();

        echo json_encode(['status' => 'success', 'message' => 'File restored successfully.', 'originalPath' => $originalFilePath]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to move file back to its original location.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
}
?>
