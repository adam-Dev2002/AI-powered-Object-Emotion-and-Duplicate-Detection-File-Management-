<?php
require 'config.php'; // Ensure this connects to your database

if (isset($_GET['query'])) {
    $searchTerm = "%" . $_GET['query'] . "%";
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

    // Query objects detected from 'files' table
    $stmt3 = $conn->prepare("SELECT DISTINCT detected_objects FROM files WHERE detected_objects IS NOT NULL AND detected_objects LIKE ?");
    $stmt3->bind_param("s", $searchTerm);
    $stmt3->execute();
    $result3 = $stmt3->get_result();

    // Query emotions from 'files' table
    $stmt4 = $conn->prepare("SELECT DISTINCT emotion FROM files WHERE emotion IS NOT NULL AND emotion LIKE ?");
    $stmt4->bind_param("s", $searchTerm);
    $stmt4->execute();
    $result4 = $stmt4->get_result();

    // Fetch tag results
    while ($row = $result1->fetch_assoc()) {
        $suggestions[] = $row['suggestion'];
    }

    // Fetch filename results
    while ($row = $result2->fetch_assoc()) {
        $suggestions[] = $row['suggestion'];
    }

    // Fetch detected objects results
    while ($row = $result3->fetch_assoc()) {
        $objects = json_decode($row['detected_objects'], true);
        if (is_array($objects)) {
            foreach ($objects as $obj) {
                $suggestions[] = $obj;
            }
        }
    }

    // Fetch emotions results
    while ($row = $result4->fetch_assoc()) {
        $suggestions[] = $row['emotion'];
    }

    // Remove duplicates and return JSON response
    echo json_encode(array_values(array_unique($suggestions)));

    // Close database connections
    $stmt1->close();
    $stmt2->close();
    $stmt3->close();
    $stmt4->close();
    $conn->close();
}

?>
