<?php
// Check if a file path is provided via GET
if (!isset($_GET['file'])) {
    http_response_code(400); // Bad Request
    echo "No file specified.";
    exit;
}

// Get the file path from the GET parameter
$filePath = urldecode($_GET['file']);

// Check if the file exists
if (!file_exists($filePath)) {
    http_response_code(404); // File Not Found
    echo "File not found.";
    exit;
}

// Validate the file path to prevent directory traversal attacks
$baseDirectory = '/Volumes/creative/greyhoundhub'; // Adjust based on your directory structure
$realBase = realpath($baseDirectory);
$realFile = realpath($filePath);

if (strpos($realFile, $realBase) !== 0) {
    http_response_code(403); // Forbidden
    echo "Access denied.";
    exit;
}

// Serve the file for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Read and output the file
readfile($filePath);
exit;
