<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['files'])) {
    $files = $_POST['files'];
    $success = true;

    foreach ($files as $file) {
        if (file_exists($file)) {
            if (!unlink($file)) {
                $success = false;
            }
        }
    }

    echo $success ? "success" : "error";
}
?>
