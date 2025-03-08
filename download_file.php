<?php
if (!isset($_GET['file'])) {
    http_response_code(400);
    echo "No file specified.";
    exit;
}

$filePath = urldecode($_GET['file']);

function adjustFilePath($filePath) {
    if (strpos($filePath, "http://localhost/testcreative/") !== false) {
        return str_replace("http://localhost/testcreative/", "/Applications/XAMPP/xamppfiles/htdocs/testcreative/", $filePath);
    }
    return $filePath;
}

$filePath = adjustFilePath($filePath);

if (!file_exists($filePath)) {
    http_response_code(404);
    echo "File not found.";
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;
?>
