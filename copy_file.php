<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data from the request
    $data = json_decode(file_get_contents('php://input'), true);
    $filePath = $data['filePath'];

    // Check if the file exists
    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'error' => 'File does not exist.']);
        exit;
    }

    $directory = dirname($filePath); // Get the file's directory
    $originalName = basename($filePath); // Get the file's original name
    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION); // Get the file's extension
    $baseName = pathinfo($filePath, PATHINFO_FILENAME); // Get the file's name without extension

    // Generate the new file name
    $newName = "Copy of " . $baseName;
    if (!empty($fileExtension)) {
        $newName .= '.' . $fileExtension;
    }
    $newPath = $directory . '/' . $newName;

    // Resolve conflicts by appending a number if the file already exists
    $counter = 1;
    while (file_exists($newPath)) {
        $newName = "Copy of " . $baseName . " ($counter)";
        if (!empty($fileExtension)) {
            $newName .= '.' . $fileExtension;
        }
        $newPath = $directory . '/' . $newName;
        $counter++;
    }

    // Copy the file
    if (copy($filePath, $newPath)) {
        echo json_encode(['success' => true, 'newPath' => $newPath]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to copy file.']);
    }
}
