<?php
require 'config.php'; // Ensure this connects to your database

header('Content-Type: application/json'); // Ensure JSON response

// Ensure database connection
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// Decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['query']) || empty($data['query'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request. Missing query.']);
    exit;
}

$searchTerm = "%" . $data['query'] . "%";
$suggestions = [];

// Query tags from 'tag' table
$stmt1 = $conn->prepare("SELECT DISTINCT tag AS suggestion FROM tag WHERE tag LIKE ?");
$stmt1->bind_param("s", $searchTerm);
$stmt1->execute();
$result1 = $stmt1->get_result();

// Query filenames from 'files' table
$stmt2 = $conn->prepare("SELECT DISTINCT filename AS suggestion FROM files WHERE filename LIKE ?");
$stmt2->bind_param("s", $searchTerm);
$stmt2->execute();
$result2 = $stmt2->get_result();

// Fetch tag results
while ($row = $result1->fetch_assoc()) {
    $suggestions[] = $row['suggestion'];
}

// Fetch filename results
while ($row = $result2->fetch_assoc()) {
    $suggestions[] = $row['suggestion'];
}

// Remove duplicates and return JSON response
echo json_encode(array_values(array_unique($suggestions)));

// Close database connections
$stmt1->close();
$stmt2->close();
$conn->close();
?>
