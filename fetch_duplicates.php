<?php
require_once "config.php";

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $query = $conn->prepare("
        SELECT f1.id, f1.filename, f1.filepath, f1.filetype, f1.datecreated, 
               f1.filehash, f1.content_hash, f1.duplicate_of, f2.filename AS original_filename
        FROM files AS f1
        INNER JOIN files AS f2 
            ON (f1.filehash = f2.filehash OR f1.content_hash = f2.content_hash) 
            AND f1.id != f2.id
        WHERE f1.duplicate_of IS NOT NULL -- ✅ Only fetch duplicates
        ORDER BY f1.datecreated DESC
    ");
    
    $query->execute();
    $duplicates = $query->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(["success" => true, "duplicates" => $duplicates]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
