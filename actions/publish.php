<?php
session_start();
require '../config.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filepath = $_POST['filepath'] ?? null; // File name only
    $filename = $_POST['filename'] ?? null; // Full file path

    // Retrieve admin ID from session
    $publishedBy = $_SESSION['employee_id'] ?? null;

    // Input validation
    if (!$filepath || !$filename) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input: missing filepath or filename.']);
        exit();
    }

    if (!$publishedBy) {
        echo json_encode(['status' => 'error', 'message' => 'Admin user not logged in.']);
        exit();
    }

    try {
        $conn = getDatabaseConnection();

        // Normalize the full file path (decode URL-encoded characters)
        $filename = urldecode($filename);

        // Debugging logs
        error_log("DEBUG: Received filepath: '$filepath'");
        error_log("DEBUG: Received filename: '$filename'");
        error_log("DEBUG: Published by: $publishedBy");

        // Fetch the file ID from the `files` table
        // Use the full path (filename) to query the database
        $stmt = $conn->prepare("SELECT id FROM files WHERE TRIM(filepath) = TRIM(?)");
        $stmt->bind_param("s", $filename);
        $stmt->execute();
        $stmt->bind_result($fileId);
        $stmt->fetch();
        $stmt->close();

        if (!$fileId) {
            error_log("DEBUG: No file found for filepath: '$filename'");
            echo json_encode(['status' => 'error', 'message' => 'File not found in database.']);
            exit();
        }

        // Verify the admin user exists
        $stmt = $conn->prepare("SELECT employee_id FROM admin_users WHERE employee_id = ?");
        $stmt->bind_param("i", $publishedBy);
        $stmt->execute();
        $stmt->bind_result($adminId);
        $stmt->fetch();
        $stmt->close();

        if (!$adminId) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid admin user.']);
            exit();
        }

        // Insert the file into the `published_files` table
        $status = 'published';
        $stmt = $conn->prepare(
            "INSERT INTO published_files (file_id, published_at, published_by, status) VALUES (?, NOW(), ?, ?)"
        );
        $stmt->bind_param("iis", $fileId, $publishedBy, $status);
        $stmt->execute();

        // Check if the insert was successful
        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'File published successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to publish the file.']);
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Unexpected error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}



?>