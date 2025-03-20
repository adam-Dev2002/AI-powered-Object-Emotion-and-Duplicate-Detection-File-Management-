<?php
require 'config.php'; // Ensure this connects to your database

if (isset($_GET['query'])) {
    $searchTerm = "%" . $_GET['query'] . "%";
    $suggestions = [];

    // Combine all suggestions using UNION for efficiency
    $sql = "
        SELECT DISTINCT tag AS suggestion FROM tag WHERE tag LIKE ?
        UNION
        SELECT DISTINCT filename AS suggestion FROM files WHERE filename LIKE ?
        UNION
        SELECT DISTINCT detected_objects AS suggestion FROM files WHERE detected_objects IS NOT NULL AND detected_objects LIKE ?
        UNION
        SELECT DISTINCT emotion AS suggestion FROM files WHERE emotion IS NOT NULL AND emotion LIKE ?
        ORDER BY suggestion
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Optional: explode if multiple objects are comma-separated
        $items = explode(',', $row['suggestion']);
        foreach ($items as $item) {
            $trimmed = trim($item);
            if (!empty($trimmed)) {
                $suggestions[] = $trimmed;
            }
        }
    }

    // Remove duplicates and return as JSON
    echo json_encode(array_values(array_unique($suggestions)));

    $stmt->close();
    $conn->close();
}
?>
