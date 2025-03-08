<?php
require 'config.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $filepath = $data['filepath'];
    $filename = $data['filename'];
    $tag = $data['tag'] ?? '';
    $description = $data['description'] ?? '';

    // Ensure required fields are provided
    if (empty($filepath) || empty($filename)) {
        $response['message'] = 'Filepath and filename are required.';
        echo json_encode($response);
        exit;
    }

    // Check if the path refers to a folder
    $isFolder = is_dir($filepath);

    if ($isFolder) {
        // Rename the folder
        $newPath = dirname($filepath) . '/' . $filename;
        if (rename($filepath, $newPath)) {
            $response['status'] = 'success';
            $response['message'] = 'Folder renamed successfully.';
        } else {
            $response['message'] = 'Failed to rename the folder.';
        }
    } else {
        // Update file details in the database
        $stmt = $conn->prepare("UPDATE files SET filename = ?, tag = ?, description = ? WHERE filepath = ?");
        $stmt->bind_param('ssss', $filename, $tag, $description, $filepath);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'File details updated successfully.';
        } else {
            $response['message'] = 'Failed to update file details.';
        }

        $stmt->close();
    }
}

echo json_encode($response);
?>
