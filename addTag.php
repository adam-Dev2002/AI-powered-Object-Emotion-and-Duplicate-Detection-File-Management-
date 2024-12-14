<?php
// Include database connection
require 'config.php';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['items']) || !is_array($data['items']) || empty($data['tag'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit;
}

$tag = trim($data['tag']);
$type = isset($data['type']) ? trim($data['type']) : null;
$validTypes = ['image', 'audio', 'video'];

// If type is provided, validate it
if ($type !== null && !in_array($type, $validTypes)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid type specified.']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Prepare the insert query
    $stmt = $conn->prepare("INSERT INTO add_tag (tag, type, filepath) VALUES (?, ?, ?)");

    foreach ($data['items'] as $item) {
        // Validate item structure
        if (!isset($item['filePath']) || empty($item['filePath'])) {
            throw new Exception('Invalid item structure. Each item must have a filePath.');
        }

        $filePath = trim($item['filePath']);

        // Bind parameters and execute
        $stmt->bind_param("sss", $tag, $type, $filePath);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Tags added successfully.']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database operation failed: ' . $e->getMessage()]);
} finally {
    // Close the statement and connection
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>
