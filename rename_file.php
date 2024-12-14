<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $filePath = $data['filePath'];
    $newName = $data['newName'];
    
    $newPath = dirname($filePath) . '/' . $newName;

    if (rename($filePath, $newPath)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to rename file.']);
    }
}
