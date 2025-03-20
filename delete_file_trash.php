<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['filepath'])) {
    $filepath = trim($_POST['filepath']);
    $success = true;

    // Check if the file exists on disk and try to delete it.
    if (file_exists($filepath)) {
        if (!unlink($filepath)) {
            error_log("❌ Error: Unable to delete file from disk: $filepath");
            $success = false;
        }
    } else {
        // Log that the file doesn't exist.
        error_log("⚠️ File does not exist on disk: $filepath");
        // Depending on your logic you may still want to proceed with the DB deletion.
    }

    // Remove the record from the trashfiles table.
    $stmt = $conn->prepare("DELETE FROM trashfiles WHERE filepath = ?");
    if ($stmt) {
        $stmt->bind_param("s", $filepath);
        if (!$stmt->execute()) {
            error_log("❌ Error: Database deletion failed for file: $filepath - " . $stmt->error);
            $success = false;
        }
        $stmt->close();
    } else {
        error_log("❌ Error: Failed to prepare statement - " . $conn->error);
        $success = false;
    }

    echo $success ? "success" : "error";
} else {
    echo "error";
}
?>
