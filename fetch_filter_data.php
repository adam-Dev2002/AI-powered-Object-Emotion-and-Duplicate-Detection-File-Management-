<?php
require "config.php";
header("Content-Type: application/json");

// Debug: Log incoming request
file_put_contents("debug.log", "Request received: " . json_encode($_GET) . "\n", FILE_APPEND);

// Ensure database connection
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

// Get the filter type from URL
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';

if ($type === 'objects') {
    $query = "SELECT DISTINCT detected_objects, classification FROM files 
              WHERE detected_objects IS NOT NULL AND detected_objects != ''";
} elseif ($type === 'emotions') {
    $query = "SELECT DISTINCT emotion FROM files 
              WHERE emotion IS NOT NULL AND emotion != ''";
} else {
    echo json_encode(["success" => false, "message" => "Invalid request.", "received_type" => $type]);
    exit;
}

// Run the query
$result = $conn->query($query);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Query failed: " . $conn->error]);
    exit;
}

// Process results
$uniqueFilters = [];
while ($row = $result->fetch_assoc()) {
    // Handling 'detected_objects' or 'emotion'
    if (isset($row['detected_objects']) && $row['detected_objects'] !== null) {
        $objects = explode(',', $row['detected_objects']);
        foreach ($objects as $object) {
            $object = trim($object);
            if (!empty($object) && strtolower($object) !== 'null') {
                if (!in_array($object, $uniqueFilters, true)) {
                    $uniqueFilters[] = $object;
                }
            }
        }
    }

    // Handling 'emotion'
    if (isset($row['emotion']) && $row['emotion'] !== null) {
        $emotion = trim($row['emotion']);
        if (!empty($emotion) && strtolower($emotion) !== 'null' && !in_array($emotion, $uniqueFilters, true)) {
            $uniqueFilters[] = $emotion;
        }
    }
}

// Close connection
$conn->close();

echo json_encode(["success" => true, "data" => $uniqueFilters]);
?>