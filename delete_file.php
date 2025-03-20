<?php
require_once 'config.php';  // Adjust as needed for DB connection
header('Content-Type: application/json');  // Return JSON
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid request method. Use POST."
    ]);
    exit;
}

// 1) Check if 'filepath' is provided
if (!isset($_POST['filepath']) || empty(trim($_POST['filepath']))) {
    echo json_encode([
        "status"  => "error",
        "message" => "No filepath provided."
    ]);
    exit;
}

// 2) Clean up and define key variables
$filePath = trim($_POST['filepath']);
$baseDirectory = '/Applications/XAMPP/xamppfiles/htdocs/testcreative';
// ^ Adjust if your real base directory differs

// 3) Attempt to ensure $filePath is within your allowed directory
$realBase = realpath($baseDirectory);
$realFilePath = realpath($filePath);

if ($realFilePath === false || strpos($realFilePath, $realBase) !== 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "File path is invalid or outside base directory.",
        "debug"   => $filePath
    ]);
    exit;
}

// 4) Connect to DB
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([
        "status"  => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit;
}

// 5) Check if file actually exists on disk
if (!file_exists($realFilePath)) {
    // Optionally attempt fallback or just error
    // Let's just do a debug message and stop.
    echo json_encode([
        "status"  => "error",
        "message" => "File does not exist.",
        "debug"   => $filePath
    ]);
    $conn->close();
    exit;
}

// 6) Attempt to delete file from disk
if (!unlink($realFilePath)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to unlink (delete) the file from disk.",
        "debug"   => $filePath
    ]);
    $conn->close();
    exit;
}

// 7) If disk deletion succeeded, remove the record from DB
$stmt = $conn->prepare("DELETE FROM files WHERE filepath = ?");
$stmt->bind_param("s", $filePath);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // success
    echo json_encode([
        "status"  => "success",
        "message" => "File deleted successfully."
    ]);
} else {
    // no matching record
    echo json_encode([
        "status"  => "error",
        "message" => "File was deleted from disk, but no DB record found.",
        "debug"   => $filePath
    ]);
}

// 8) Cleanup
$stmt->close();
$conn->close();
exit;
?>