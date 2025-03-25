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
        mkdir($directory, 0777, true);
    }
}

header('Content-Type: application/json');

// Decode JSON input. Allow either a single string or an array.
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['filepath']) || empty($data['filepath'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameter: filepath']);
    exit;
}

// Normalize the input to always be an array.
$filePaths = is_array($data['filepath']) ? $data['filepath'] : [ trim($data['filepath']) ];

$successCount = 0;
$errors = [];
$restoredFiles = [];

foreach ($filePaths as $trashPath) {
    $trashPath = trim($trashPath);

    // Check that the file exists in TRASH
    if (!file_exists($trashPath)) {
        $errors[] = "File not found in trash: $trashPath";
        continue;
    }

    // Fetch the trash record using the TRASH path (real_filepath)
    $stmt = $conn->prepare("SELECT * FROM trashfiles WHERE real_filepath = ?");
    if (!$stmt) {
        $errors[] = "Prepare failed for file: $trashPath: " . $conn->error;
        continue;
    }
    $stmt->bind_param("s", $trashPath);
    $stmt->execute();
    $result = $stmt->get_result();
    $fileData = $result->fetch_assoc();
    $stmt->close();

    if (!$fileData) {
        $errors[] = "File not found in trash: $trashPath";
        continue;
    }

    // Get the original location from the filepath column.
    $originalPath = $fileData['filepath'];

    // Ensure the original directory exists.
    ensureDirectoryExists($originalPath);

    // Move the file from TRASH (real_filepath) back to its original location.
    if (!rename($trashPath, $originalPath)) {
        $errors[] = "Failed to restore file: $trashPath → $originalPath";
        continue;
    }

    // Insert the file’s metadata back into the 'files' table.
    $sql = "INSERT INTO files (filename, filepath, filetype, size, dateupload, description, real_filepath)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insert = $conn->prepare($sql);
    if (!$insert) {
        $errors[] = "Prepare failed for file: $trashPath: " . $conn->error;
        continue;
    }
    $insert->bind_param("sssssss",
        $fileData['filename'],
        $originalPath,
        $fileData['filetype'],
        $fileData['size'],
        $fileData['dateupload'],
        $fileData['description'],
        $trashPath   // For record purposes (optional)
    );
    if (!$insert->execute()) {
        $errors[] = "Insert failed for file: $trashPath: " . $insert->error;
        $insert->close();
        continue;
    }
    $insert->close();

    // Remove the record from the trashfiles table.
    $stmtDel = $conn->prepare("DELETE FROM trashfiles WHERE real_filepath = ?");
    if ($stmtDel) {
        $stmtDel->bind_param("s", $trashPath);
        $stmtDel->execute();
        $stmtDel->close();
    }

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
