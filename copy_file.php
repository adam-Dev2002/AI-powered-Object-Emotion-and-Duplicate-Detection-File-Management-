<?php
require_once 'config.php'; // Ensure database connection

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['filePath']) || empty(trim($data['filePath']))) {
        echo json_encode(['success' => false, 'error' => 'Invalid file path received.']);
        exit();
    }

    // Function to adjust the file path from URL to actual path
    function adjustFilePath($filePath) {
        if (strpos($filePath, "http://localhost/testcreative/") !== false) {
            return str_replace("http://localhost/testcreative/", "/Applications/XAMPP/xamppfiles/htdocs/testcreative/", $filePath);
        }
        return $filePath;
    }

    $filePath = adjustFilePath(trim($data['filePath']));

    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File does not exist.']);
        exit();
    }

    $directory = dirname($filePath);
    $originalName = basename($filePath);
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    $baseName = pathinfo($filePath, PATHINFO_FILENAME);
    $newName = "Copy_of_" . $baseName . "." . $fileExtension;
    $newPath = $directory . '/' . $newName;

    // Ensure unique filename
    $counter = 1;
    while (file_exists($newPath)) {
        $newName = "Copy_of_" . $baseName . "_$counter." . $fileExtension;
        $newPath = $directory . '/' . $newName;
        $counter++;
    }

    // Perform file copy operation
    if (copy($filePath, $newPath)) {
        // Generate file hash and content hash
        $filehash = hash_file('sha256', $newPath);
        $content_hash = md5(file_get_contents($newPath));
        $size = filesize($newPath); // Get file size
        $dateupload = date('Y-m-d H:i:s'); // Timestamp for when the file was copied
        $datecreated = filectime($newPath) ? date('Y-m-d H:i:s', filectime($newPath)) : $dateupload; // Original creation date

        // Insert copied file into the database with correct values
        $stmt = $conn->prepare("
            INSERT INTO files (filename, filepath, filetype, size, dateupload, datecreated, filehash, content_hash, is_published, category_id, parent_folder_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NULL, NULL)
        ");
        $stmt->bind_param("ssssssss", $newName, $newPath, $fileExtension, $size, $dateupload, $datecreated, $filehash, $content_hash);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'File copied successfully.', 'newPath' => $newPath]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to insert into database: ' . $conn->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to copy file.']);
    }
}

$conn->close();
?>
