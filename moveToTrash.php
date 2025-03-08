<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config.php';

// Updated base directory to reflect the new trash folder location
$base_directory = '/Applications/XAMPP/xamppfiles/htdocs/TRASH/';

// Updated to handle the new base URL for adjusting file paths
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

    if (!is_dir($base_directory)) {
        mkdir($base_directory, 0777, true);
    }

    $safeFileName = basename($fileName);
    $newPath = rtrim($base_directory, '/') . '/' . $safeFileName;

    if (file_exists($filePath)) {
        if (file_exists($newPath)) {
            $fileInfo = pathinfo($safeFileName);
            $newPath = rtrim($base_directory, '/') . '/' . $fileInfo['filename'] . '_' . time() . '.' . $fileInfo['extension'];
        }

        if (rename($filePath, $newPath)) {
            $sql = "DELETE FROM files WHERE filepath = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $filePath);
            $stmt->execute();

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
