<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['files']) && is_array($_POST['files'])) {
    $files = $_POST['files'];
    $success = true;

    // Prepare a statement for deleting records from the trashfiles table.
    $stmt = $conn->prepare("DELETE FROM trashfiles WHERE filepath = ?");
    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error);
        echo "error";
        exit;
    }

    foreach ($files as $file) {
        $file = trim($file);

        // Delete file from disk if it exists
        if (file_exists($file)) {
            if (!unlink($file)) {
                error_log("Error deleting file: $file");
                $success = false;
                continue; // Skip deletion from DB if the file could not be removed
            }
        } else {
            error_log("File does not exist: $file");
            // Optionally you could decide whether a missing file is an error.
        }

        // Delete the record from the trashfiles table
        $stmt->bind_param("s", $file);
        if (!$stmt->execute()) {
            error_log("Error deleting DB record for file: $file - " . $stmt->error);
            $success = false;
        }
    }

    $stmt->close();
    echo $success ? "success" : "error";
} else {
    echo "error";
}
?>
