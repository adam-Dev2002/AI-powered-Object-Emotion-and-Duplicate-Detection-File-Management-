<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config.php';

/**
 * Recursively create any missing directories so we can restore the file.
 */
function ensureDirectoryExists($path) {
    $directory = dirname($path);
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0777, true)) {
            error_log("❌ Failed to create directory: $directory");
            return false;
        }
    }
    return true;
}

header('Content-Type: application/json');

// Decode JSON input. Supports both single and bulk restore.
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['filepath']) || empty($data['filepath'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameter: filepath']);
    exit;
}

// If a single filepath is provided, convert it to an array.
$filePaths = is_array($data['filepath']) ? $data['filepath'] : [$data['filepath']];

$successCount = 0;
$errors = [];
$restoredFiles = [];

foreach ($filePaths as $trashPath) {
    $trashPath = trim($trashPath);

    // --- Fallback: if file not found at given path, try to adjust it.
    if (!file_exists($trashPath)) {
        // Example: if $trashPath does not contain '/TRASH/', try to replace the original directory with TRASH.
        $adjustedPath = str_replace('/testcreative/objects/', '/TRASH/', $trashPath);
        if (file_exists($adjustedPath)) {
            $trashPath = $adjustedPath;
        } else {
            $errors[] = "File not found in trash: $trashPath";
            continue;
        }
    }

    // 1) Fetch trash record by real_filepath (which is stored as the trash file path)
    $stmt = $conn->prepare("SELECT * FROM trashfiles WHERE real_filepath = ?");
    $stmt->bind_param("s", $trashPath);
    $stmt->execute();
    $result = $stmt->get_result();
    $fileData = $result->fetch_assoc();
    $stmt->close();

    if (!$fileData) {
        $errors[] = "File not found in trash: $trashPath";
        continue;
    }

    // 2) Get the original path (where the file should be restored) from the 'filepath' column.
    $originalPath = $fileData['filepath'];

    // 3) Ensure the original directory exists.
    if (!ensureDirectoryExists($originalPath)) {
        $errors[] = "Failed to create directory for restoration: " . dirname($originalPath);
        continue;
    }

    // 4) Move the file from trash to its original location.
    if (!rename($trashPath, $originalPath)) {
        $errors[] = "Failed to restore file: $trashPath → $originalPath";
        continue;
    }

    // 5) Insert the file’s metadata back into the 'files' table.
    $sql = "INSERT INTO files 
            (filename, filepath, filetype, size, dateupload, description, real_filepath)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert = $conn->prepare($sql);
    if (!$insert) {
        $errors[] = "Prepare failed for $trashPath: " . $conn->error;
        continue;
    }
    $insert->bind_param("sssssss",
        $fileData['filename'],
        $originalPath,
        $fileData['filetype'],
        $fileData['size'],
        $fileData['dateupload'],
        $fileData['description'],
        $trashPath  // You may store this if you wish to keep a record
    );
    if (!$insert->execute()) {
        $errors[] = "Insert failed for $trashPath: " . $insert->error;
        $insert->close();
        continue;
    }
    $insert->close();

    // 6) Remove the record from the trashfiles table.
    $stmtDel = $conn->prepare("DELETE FROM trashfiles WHERE real_filepath = ?");
    $stmtDel->bind_param("s", $trashPath);
    $stmtDel->execute();
    $stmtDel->close();

    $successCount++;
    $restoredFiles[] = $originalPath;
}

if ($successCount > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => "$successCount file(s) restored successfully.",
        'restoredFiles' => $restoredFiles,
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => "No files were restored.",
        'errors' => $errors
    ]);
}
?>
