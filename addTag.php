<?php
// Include the database configuration file
require_once 'config.php';

// Function to normalize filepaths (remove double slashes)
function normalizePath($path) {
    return preg_replace('#/+#', '/', $path); // Replace multiple slashes with a single slash
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw input data
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['filepath'], $input['tag'], $input['type']) || empty($input['filepath']) || empty($input['tag']) || empty($input['type'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
        exit();
    }

    // Sanitize and normalize inputs
    $filepath = $conn->real_escape_string(normalizePath($input['filepath'])); // Normalize filepath
    $tag = $conn->real_escape_string($input['tag']);
    $type = $conn->real_escape_string($input['type']);

    // Ensure the type is one of the allowed values
    $allowedTypes = ['image', 'audio', 'video'];
    if (!in_array($type, $allowedTypes, true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type.']);
        exit();
    }

    // Insert the tag into the database
    $sql = "INSERT INTO tag (tag, type, filepath) VALUES ('$tag', '$type', '$filepath')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Tag added successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to add tag: ' . $conn->error]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
