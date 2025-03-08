<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require 'config.php';

// ✅ Capture RAW request data for debugging
$rawData = file_get_contents('php://input');
error_log("📩 RAW POST DATA: " . $rawData);

// ✅ Check if JSON is valid
$data = json_decode($rawData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("❌ JSON Decode Error: " . json_last_error_msg());
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input.']);
    exit;
}

// ✅ Validate required data
if (empty($data['action']) || !isset($data['selectedFiles']) || !is_array($data['selectedFiles'])) {
    error_log("❌ Invalid request data: " . json_encode($data));
    echo json_encode(['status' => 'error', 'message' => 'Invalid request data.']);
    exit;
}

$action = $data['action'];
$selectedFiles = $data['selectedFiles'];

error_log("📌 Action: $action, Selected Files: " . json_encode($selectedFiles)); // ✅ Log data

// Define Trash Directory
$trashDirectory = '/Applications/XAMPP/xamppfiles/htdocs/TRASH';

// Ensure the trash directory exists
if (!file_exists($trashDirectory)) {
    if (!mkdir($trashDirectory, 0777, true) && !is_dir($trashDirectory)) {
        error_log("❌ ERROR: Failed to create trash directory: $trashDirectory");
        echo json_encode(['status' => 'error', 'message' => 'Trash directory creation failed.']);
        exit;
    }
}

// ✅ Switch case for action handling
try {
    switch ($action) {
        case 'delete':
            handleBulkDelete($selectedFiles, $conn);
            break;
        case 'move_to_trash':
            handleBulkMoveToTrash($selectedFiles, $conn, $trashDirectory);
            break;
        case 'download':
            handleBulkDownload($selectedFiles);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action specified.']);
            exit;
    }
} catch (Exception $e) {
    error_log("❌ Exception occurred: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    exit;
}

/**
 * ✅ Handle Bulk Delete
 */
function handleBulkDelete(array $files, $conn)
{
    $successCount = 0;
    $errors = [];

    foreach ($files as $file) {
        $filepath = $file['filepath'] ?? null;
        $filename = $file['filename'] ?? 'Unknown';

        if (!$filepath || !file_exists($filepath)) {
            error_log("❌ File does not exist: $filepath");
            $errors[] = "File not found: $filename";
            continue;
        }

        error_log("Deleting file: $filename ($filepath)");

        // ✅ Remove from filesystem
        if (!unlink($filepath)) {
            error_log("❌ Failed to delete file from filesystem: $filepath");
            $errors[] = "Failed to delete: $filename";
            continue;
        }

        // ✅ Remove from database
        $stmt = $conn->prepare("DELETE FROM files WHERE filepath = ?");
        if ($stmt) {
            $stmt->bind_param("s", $filepath);
            if ($stmt->execute()) {
                $successCount++;
            } else {
                error_log("❌ Failed to delete from database: $filepath");
                $errors[] = "Database delete failed for: $filename";
            }
            $stmt->close();
        } else {
            error_log("❌ Database error: " . $conn->error);
            $errors[] = "Database error for: $filename";
        }
    }

    echo json_encode([
        'status' => empty($errors) ? 'success' : 'error',
        'message' => empty($errors) ? 'Files deleted successfully.' : 'Some files failed to delete.',
        'successCount' => $successCount,
        'errors' => $errors
    ]);
}

/**
 * ✅ Handle Bulk Move to Trash
 */
function handleBulkMoveToTrash(array $files, $conn, $trashDirectory)
{
    $movedFiles = 0;
    $errors = [];

    // ✅ Create Trash directory if it doesn't exist
    if (!is_dir($trashDirectory)) {
        mkdir($trashDirectory, 0777, true);
    }

    foreach ($files as $file) {
        $oldPath = trim($file['filepath'] ?? '');
        $filename = trim($file['filename'] ?? basename($oldPath));

        if (!$oldPath || !file_exists($oldPath)) {
            error_log("❌ ERROR: File does not exist: $oldPath");
            $errors[] = "File not found: $filename";
            continue;
        }

        // ✅ Ensure unique filenames in trash (Prevent overwriting)
        $safeFileName = basename($filename);
        $newPath = rtrim($trashDirectory, '/') . '/' . $safeFileName;

        if (file_exists($newPath)) {
            $fileInfo = pathinfo($safeFileName);
            $newPath = rtrim($trashDirectory, '/') . '/' . $fileInfo['filename'] . '_' . time() . '.' . $fileInfo['extension'];
        }

        // ✅ Move file to trash
        if (rename($oldPath, $newPath)) {
            // ✅ Delete from database (Same as your working individual move-to-trash)
            $stmt = $conn->prepare("DELETE FROM files WHERE filepath = ?");
            if ($stmt) {
                $stmt->bind_param("s", $oldPath);
                if ($stmt->execute()) {
                    $movedFiles++;
                } else {
                    error_log("❌ ERROR: Database delete failed for $filename");
                    $errors[] = "Database delete failed for: $filename";
                }
                $stmt->close();
            }
        } else {
            error_log("❌ ERROR: Failed to move file: $oldPath → $newPath");
            $errors[] = "Failed to move $filename to trash.";
        }
    }

    // ✅ Return JSON response
    echo json_encode([
        'status' => empty($errors) ? 'success' : 'error',
        'message' => empty($errors) ? "$movedFiles file(s) moved to trash." : 'Some files failed to move.',
        'successCount' => $movedFiles,
        'errors' => $errors
    ]);
}

/**
 * ✅ Handle Bulk Download
 */
function handleBulkDownload(array $files)
{
    $zip = new ZipArchive();
    $zipFilename = 'downloads/bulk_download_' . time() . '.zip';
    $zipPath = __DIR__ . '/' . $zipFilename;

    if (!file_exists(__DIR__ . '/downloads')) {
        mkdir(__DIR__ . '/downloads', 0777, true);
    }

    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create ZIP file.']);
        return;
    }

    $addedFiles = 0;
    $errors = [];

    foreach ($files as $file) {
        $filePath = $file['filepath'];
        if (!file_exists($filePath)) {
            error_log("❌ ERROR: File not found: $filePath");
            $errors[] = "File not found: $filePath";
            continue;
        }

        $zip->addFile($filePath, basename($filePath));
        $addedFiles++;
    }

    $zip->close();

    if ($addedFiles === 0) {
        unlink($zipPath);
        echo json_encode(['status' => 'error', 'message' => 'No valid files found for download.']);
        return;
    }

    echo json_encode([
        'status' => 'success',
        'message' => "$addedFiles file(s) added to ZIP.",
        'downloadUrl' => $zipFilename,
        'errors' => $errors
    ]);
}
?>
