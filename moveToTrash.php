<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config.php';

// Define the physical trash directory
$trash_directory = '/Applications/XAMPP/xamppfiles/htdocs/TRASH/';

// Adjust file path if it has "http://localhost/" prefix
function adjustFilePath($filePath) {
    if (strpos($filePath, "http://localhost/") !== false) {
        return str_replace("http://localhost/", "/Applications/XAMPP/xamppfiles/htdocs/", $filePath);
    }
    return $filePath;
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['filepath']) && isset($data['fileName'])) {
    // Original path from the 'files' table
    $originalFilePath = adjustFilePath(trim($data['filepath']));
    $fileName         = trim($data['fileName']);

    // Ensure the /TRASH/ directory exists
    if (!is_dir($trash_directory)) {
        mkdir($trash_directory, 0777, true);
    }

    // Construct the new physical path inside /TRASH/
    $safeFileName = basename($fileName); // e.g. trashsample.jpeg
    $newPath      = rtrim($trash_directory, '/') . '/' . $safeFileName;

    // Check if the original file actually exists
    if (file_exists($originalFilePath)) {
        // If a file with the same name already exists in /TRASH/, append timestamp
        if (file_exists($newPath)) {
            $fileInfo = pathinfo($safeFileName);
            $newPath  = rtrim($trash_directory, '/') . '/'
                . $fileInfo['filename'] . '_' . time()
                . '.' . $fileInfo['extension'];
        }

        // Physically move the file from original path to the /TRASH/ path
        if (rename($originalFilePath, $newPath)) {
            // 1) Transfer metadata from `files` to `trashfiles`
            //    This keeps the same filepath (the original) in `trashfiles.filepath`
            $stmtMove = $conn->prepare("
                INSERT INTO trashfiles 
                SELECT * 
                FROM files 
                WHERE filepath = ?
            ");
            if (!$stmtMove) {
                echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
                exit;
            }
            $stmtMove->bind_param("s", $originalFilePath);
            if (!$stmtMove->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Insert failed: ' . $stmtMove->error]);
                exit;
            }
            $stmtMove->close();

            // 2) Update the `real_filepath` column in `trashfiles` to the new physical trash location
            //    but keep `filepath` as the original path
            $stmtUpdate = $conn->prepare("
                UPDATE trashfiles 
                SET real_filepath = ? 
                WHERE filepath = ?
            ");
            $stmtUpdate->bind_param("ss", $newPath, $originalFilePath);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // 3) Delete the record from `files`
            $stmtDelete = $conn->prepare("
                DELETE FROM files 
                WHERE filepath = ?
            ");
            $stmtDelete->bind_param("s", $originalFilePath);
            $stmtDelete->execute();
            $stmtDelete->close();

            // Success response
            echo json_encode([
                'status'    => 'success',
                'message'   => 'File moved to trash successfully.',
                'trashPath' => $newPath
            ]);
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
