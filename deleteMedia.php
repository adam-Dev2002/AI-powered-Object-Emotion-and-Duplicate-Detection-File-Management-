<?php
require_once 'config.php'; // Include database configuration

// Ensure database connection
if (!isset($conn) || $conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed.']));
}

// Decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['filepath']) || empty($data['filepath']) || !isset($data['fileName']) || empty($data['fileName'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request. Missing file path or file name.']);
    exit;
}

$filePath = trim($data['filepath']);
$fileName = trim($data['fileName']);
$featuredPath = '/Applications/XAMPP/xamppfiles/htdocs/testcreative/Featured';

// Function to recursively delete directories
function deleteDirectory($dir) {
    if (!is_dir($dir)) return is_file($dir) ? unlink($dir) : false;

    foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
        $filePath = "$dir/$file";
        is_dir($filePath) ? deleteDirectory($filePath) : unlink($filePath);
    }
    return rmdir($dir);
}

// Attempt to delete the file or directory
if (is_dir($filePath)) {
    $deleted = deleteDirectory($filePath);
} else if (file_exists($filePath)) {
    $deleted = unlink($filePath);
} else {
    echo json_encode(['status' => 'error', 'message' => 'File or directory not found.']);
    exit;
}

// If deletion was successful and it is within the specific path
if ($deleted) {
    if (strpos($filePath, $featuredPath) === 0) {
        // Deletion in 'Featured' directory, handle album deletion logic
        $stmtAlbum = $conn->prepare("SELECT id FROM albums WHERE name = ?");
        $stmtAlbum->bind_param("s", $fileName);
        $stmtAlbum->execute();
        $result = $stmtAlbum->get_result();
        if ($album = $result->fetch_assoc()) {
            $albumId = $album['id'];
            $stmtDeleteFiles = $conn->prepare("DELETE FROM album_files WHERE album_id = ?");
            $stmtDeleteFiles->bind_param("i", $albumId);
            $stmtDeleteFiles->execute();
            $stmtDeleteFiles->close();

            $stmtDeleteAlbum = $conn->prepare("DELETE FROM albums WHERE id = ?");
            $stmtDeleteAlbum->bind_param("i", $albumId);
            $stmtDeleteAlbum->execute();
            $stmtDeleteAlbum->close();

            echo json_encode(['status' => 'success', 'message' => 'Album and associated files deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Album not found. No deletion performed on album_files.']);
        }
        $stmtAlbum->close();
    } else {
        // Deletion outside 'Featured' directory, delete from 'addfolder' table
        $stmtDeleteFolder = $conn->prepare("DELETE FROM addfolder WHERE name = ?");
        $stmtDeleteFolder->bind_param("s", $fileName);
        if ($stmtDeleteFolder->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Folder deleted successfully from addfolder table.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error deleting folder from addfolder table: ' . $stmtDeleteFolder->error]);
        }
        $stmtDeleteFolder->close();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete the folder in the file system.']);
}

$conn->close();
?>
