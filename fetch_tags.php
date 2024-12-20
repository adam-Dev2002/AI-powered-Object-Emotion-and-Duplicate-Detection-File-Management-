<?php
require 'config.php';

header('Content-Type: application/json');

$query = "SELECT DISTINCT tag FROM files WHERE tag IS NOT NULL AND tag != ''";
$result = $conn->query($query);

$tags = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row['tag'];
    }
}

echo json_encode($tags);
?>
