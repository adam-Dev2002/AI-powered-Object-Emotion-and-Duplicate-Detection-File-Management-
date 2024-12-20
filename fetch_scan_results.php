<?php
require 'config.php'; // Ensure database configuration works

// Define the function locally to avoid errors
function convertFilePathToURL($filePath) {
    $baseDirectory = '/Volumes';
    $baseURL = 'http://172.16.152.45:8000'; // Adjust this as needed
    return str_replace($baseDirectory, $baseURL, $filePath);
}

header('Content-Type: application/json'); // Ensure JSON response

try {
    // Database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch last scanned files
    $stmt = $pdo->query("SELECT id, filename, filetype, filepath, dateupload FROM files ORDER BY dateupload DESC LIMIT 20");
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process data for JSON response
    $results = [];
    foreach ($files as $file) {
        $fileUrl = convertFilePathToURL($file['filepath']);
        $results[] = [
            'id' => $file['id'],
            'filename' => htmlspecialchars($file['filename']),
            'filetype' => htmlspecialchars($file['filetype']),
            'filepath' => htmlspecialchars($file['filepath']),
            'fileurl' => $fileUrl,
            'dateupload' => htmlspecialchars($file['dateupload'])
        ];
    }
    echo json_encode(['status' => 'success', 'files' => $results]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
