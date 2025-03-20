<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config.php';

// Updated base directory to reflect the new trash folder location
$base_directory = '/Applications/XAMPP/xamppfiles/htdocs/TRASH/';

// Function to adjust file path if necessary
function adjustFilePath($filePath) {
    if (strpos($filePath, "http://localhost/") !== false) {
        return str_replace("http://localhost/", "/Applications/XAMPP/xamppfiles/htdocs/", $filePath);
    }
    return $filePath;
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['filepath']) && isset($data['fileName'])) {
    $filePath = adjustFilePath(trim($data['filepath']));
    $fileName = trim($data['fileName']);

    // Ensure trash directory exists
    if (!is_dir($base_directory)) {
        mkdir($base_directory, 0777, true);
    }

    $safeFileName = basename($fileName);
    $newPath = rtrim($base_directory, '/') . '/' . $safeFileName;

    if (file_exists($filePath)) {
        // If a file with the same name already exists in TRASH, append timestamp
        if (file_exists($newPath)) {
            $fileInfo = pathinfo($safeFileName);
            $newPath = rtrim($base_directory, '/') . '/'
                . $fileInfo['filename'] . '_' . time()
                . '.' . $fileInfo['extension'];
        }

        // Attempt to move the file
        if (rename($filePath, $newPath)) {
            // 1) Insert record into trash table
            //    (Make sure your trash table has these columns: filename, filepath, filehash, filetype, size, date_moved, description)
            $stmtTrash = $conn->prepare("
                INSERT INTO trash (filename, filepath, filehash, filetype, size, date_moved, description)
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ");
            if (!$stmtTrash) {
                echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
                exit;
            }

            // Example data
            $filename    = basename($newPath);                       // The name in TRASH
            $filepath    = $newPath;                                 // The new path in TRASH
            $filehash    = md5_file($newPath);                      // Or some other hashing logic
            $filetype    = strtolower(pathinfo($newPath, PATHINFO_EXTENSION));
            $size        = filesize($newPath);                      // File size in bytes
            $description = 'Moved from ' . $filePath;               // Any extra info

            $stmtTrash->bind_param("ssssis",
                $filename,
                $filepath,
                $filehash,
                $filetype,
                $size,
                $description
            );
            if (!$stmtTrash->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Insert failed: ' . $stmtTrash->error]);
                exit;
            }
            $stmtTrash->close();

            // 2) Remove record from the original files table
            $sql = "DELETE FROM files WHERE filepath = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $filePath);
            $stmt->execute();
            $stmt->close();

            // 3) Return success response
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
