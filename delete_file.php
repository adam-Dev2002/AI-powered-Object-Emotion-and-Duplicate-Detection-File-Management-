<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filepath'])) {
    $filePath = $_POST['filepath'];

    // Ensure the file is within the TRASH directory for security
    $trashDirectory = '/Applications/XAMPP/xamppfiles/htdocs/testcreative/TRASH';
    if (strpos(realpath($filePath), realpath($trashDirectory)) !== 0) {
        echo "Error: Invalid file path.";
        exit;
    }

    // Check if the file exists
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "success";
        } else {
            echo "Error: Unable to delete file.";
        }
    } else {
        echo "Error: File does not exist.";
    }
} else {
    echo "Error: Invalid request.";
}
?>
