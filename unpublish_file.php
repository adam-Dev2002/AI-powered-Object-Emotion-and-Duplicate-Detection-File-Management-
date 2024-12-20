<?php
$configPath = 'config.php';

if (!file_exists($configPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Configuration file not found.']);
    exit();
}

require $configPath;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    // Get the published ID from the request
    $publishedId = $_POST['published_id'] ?? null;

    if (!$publishedId) {
        $response['status'] = 'error';
        $response['message'] = 'Invalid input: missing published_id.';
        echo json_encode($response);
        exit();
    }

    try {
        // Use $conn from config.php to execute the query
        $stmt = $conn->prepare("DELETE FROM published_files WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare SQL statement: " . $conn->error);
        }

        $stmt->bind_param("i", $publishedId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $response['status'] = 'success';
            $response['message'] = 'File unpublished successfully.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Failed to unpublish the file. File not found.';
        }

        $stmt->close();
    } catch (Exception $e) {
        $response['status'] = 'error';
        $response['message'] = 'Unexpected error: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}
