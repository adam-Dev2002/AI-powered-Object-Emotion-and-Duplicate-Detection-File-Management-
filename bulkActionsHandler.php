<?php
require 'config.php';
header('Content-Type: application/json');

// Validate the request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Decode JSON payload
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['action']) || !is_array($data['selectedFiles'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data.']);
    exit;
}

$action = $data['action'];
$selectedFiles = $data['selectedFiles'];

// Perform the requested action
try {
    switch ($action) {
        case 'download':
            handleDownload($selectedFiles);
            break;

        case 'delete':
            handleDelete($selectedFiles, $conn);
            echo json_encode([
                'status' => 'success',
                'message' => 'Selected files deleted successfully.',
                'successCount' => count($selectedFiles)
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
            exit;
    }
} catch (Exception $e) {
    error_log("Error occurred: " . $e->getMessage()); // Log error for debugging
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    exit;
}

/**
 * Handle the bulk download logic
 */
function handleDownload(array $filepaths)
{
    // Create a temporary ZIP file
    $zip = new ZipArchive();
    $tempFile = tempnam(sys_get_temp_dir(), 'zip');
    if ($zip->open($tempFile, ZipArchive::CREATE) !== TRUE) {
        throw new Exception("Unable to create ZIP file.");
    }

    foreach ($filepaths as $filepath) {
        if (file_exists($filepath)) {
            $zip->addFile($filepath, basename($filepath)); // Add file with its name in the ZIP
        } else {
            // Log missing files but continue creating the ZIP
            error_log("File does not exist: $filepath");
        }
    }

    $zip->close();

    // Ensure ZIP file exists
    if (!file_exists($tempFile)) {
        throw new Exception("ZIP file creation failed.");
    }

    // Send the ZIP file to the client
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="downloaded_files_' . time() . '.zip"');
    header('Content-Length: ' . filesize($tempFile));

    readfile($tempFile);

    // Clean up temporary file
    unlink($tempFile);

    exit; // Stop further script execution
}

/**
 * Handle the bulk delete logic
 */
function handleDelete(array $files, $conn)
{
    foreach ($files as $file) {
        $filepath = $file['filepath'];
        $filename = $file['filename'];

        // Log the file being processed
        error_log("Attempting to delete: $filename ($filepath)");

        // Fetch file record from database
        $stmt = $conn->prepare("SELECT id FROM files WHERE filepath = ? AND filename = ?");
        $stmt->bind_param("ss", $filepath, $filename);
        $stmt->execute();
        $result = $stmt->get_result();
        $fileRecord = $result->fetch_assoc();

        if ($fileRecord) {
            // Delete file from filesystem
            if (file_exists($filepath)) {
                if (!unlink($filepath)) {
                    error_log("Failed to delete file from filesystem: $filepath");
                }
            }

            // Delete file record from database
            $deleteStmt = $conn->prepare("DELETE FROM files WHERE id = ?");
            $deleteStmt->bind_param("i", $fileRecord['id']);
            $deleteStmt->execute();
            $deleteStmt->close();
        } else {
            error_log("File not found in database: $filename ($filepath)");
        }
        $stmt->close();
    }
}
