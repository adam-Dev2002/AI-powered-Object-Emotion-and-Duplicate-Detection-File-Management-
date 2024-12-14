<?php
require 'config.php';

header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Retrieve and decode the JSON payload
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data) || !isset($data['action'], $data['selectedFiles']) || !is_array($data['selectedFiles'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data.']);
    exit;
}

$action = $data['action'];
$selectedFiles = array_filter($data['selectedFiles'], function ($file) {
    return !empty($file);
}); // Ensure no empty file paths

if (empty($selectedFiles)) {
    echo json_encode(['status' => 'error', 'message' => 'No valid files selected.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Unknown error occurred.'];

try {
    switch ($action) {
        case 'delete':
            handleDelete($selectedFiles);
            $response = [
                'status' => 'success',
                'message' => 'Selected files deleted successfully.'
            ];
            break;

        case 'download':
            $downloadLinks = generateDownloadLinks($selectedFiles);
            $response = [
                'status' => 'success',
                'message' => 'Download links generated successfully.',
                'downloadLinks' => $downloadLinks
            ];
            break;

        default:
            $response['message'] = 'Invalid action specified.';
            break;
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
}

echo json_encode($response);
exit;

// Handle delete logic
function handleDelete(array $files)
{
    global $pdo;

    foreach ($files as $filePath) {
        $stmt = $pdo->prepare("SELECT id, filepath FROM files WHERE filepath = :filepath");
        $stmt->bindValue(':filepath', $filePath, PDO::PARAM_STR);
        $stmt->execute();
        $fileRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fileRecord) {
            if (file_exists($fileRecord['filepath'])) {
                unlink($fileRecord['filepath']); // Delete the file
            }

            $deleteStmt = $pdo->prepare("DELETE FROM files WHERE id = :id");
            $deleteStmt->bindValue(':id', $fileRecord['id'], PDO::PARAM_INT);
            $deleteStmt->execute();
        }
    }
}

// Generate download links
function generateDownloadLinks(array $files)
{
    global $pdo;
    $downloadLinks = [];

    foreach ($files as $filePath) {
        $stmt = $pdo->prepare("SELECT filepath FROM files WHERE filepath = :filepath");
        $stmt->bindValue(':filepath', $filePath, PDO::PARAM_STR);
        $stmt->execute();
        $fileRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fileRecord) {
            $downloadLinks[] = convertFilePathToURL($fileRecord['filepath']);
        }
    }

    return $downloadLinks;
}

function convertFilePathToURL($filePath)
{
    $baseDirectory = '/Volumes/creative/greyhoundhub';
    $baseURL = 'http://172.16.152.45:8000/creative/greyhoundhub';

    $relativePath = str_replace($baseDirectory, '', $filePath);
    return htmlspecialchars(str_replace(' ', '%20', $baseURL . '/' . ltrim($relativePath, '/')));
}
