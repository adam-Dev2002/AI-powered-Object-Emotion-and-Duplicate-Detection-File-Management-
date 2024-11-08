<?php
session_start();
include 'config.php';

// Set the time zone to the Philippines
date_default_timezone_set('Asia/Manila');

$employee_id = $_SESSION['employee_id'] ?? null;
if (!$employee_id) {
    http_response_code(403); // Forbidden
    echo json_encode(["error" => "Not authenticated"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$item_type = $data['item_type'] ?? null;
$item_name = $data['item_name'] ?? null;
$filepath = $data['filepath'] ?? null; // Add this line to capture the file path

if ($item_type && $item_name && $filepath) { // Check if filepath is also provided
    // Get the current timestamp in the Philippines timezone
    $timestamp = date("Y-m-d H:i:s");
    
    $stmt = $conn->prepare("INSERT INTO recent (employee_id, item_type, item_name, filepath, timestamp) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $employee_id, $item_type, $item_name, $filepath, $timestamp);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }

    $stmt->close();
} else {
    http_response_code(400); // Bad request
    echo json_encode(["error" => "Invalid data"]);
}

$conn->close();
?>
