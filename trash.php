<?php
require 'head.php';
require 'login-check.php';
require 'config.php';

// Set the base directory path
$base_directory = '/Volumes/creative/greyhoundhub/FU_Events';
$current_directory = isset($_GET['dir']) ? urldecode($_GET['dir']) : $base_directory;

// Function to convert file path to URL
function convertFilePathToURL($filePath) {
    $baseDirectory = '/Volumes';
    $baseURL = 'http://172.16.152.45:8000';

    // Replace the base directory with the base URL
    $relative_path = str_replace($baseDirectory, $baseURL, $filePath);

    // Encode special characters in the URL
    return str_replace(' ', '%20', $relative_path);
}

// Function to fetch deleted files (from Trash)
function getTrashFilePaths($conn) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'mov'];
    $allowedExtensionsSQL = "'" . implode("', '", $allowedExtensions) . "'";
    $query = "SELECT item_name, item_type, filepath, timestamp 
              FROM trash 
              WHERE item_type = 'file' AND LOWER(SUBSTRING_INDEX(filepath, '.', -1)) IN ($allowedExtensionsSQL)
              ORDER BY timestamp DESC
              LIMIT 10";
    $result = $conn->query($query);
    if (!$result) {
        die("Query Failed: " . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch deleted files from the trash
$trashFiles = getTrashFilePaths($conn);

// Function to convert timestamp to Philippine Time (PHT) in 12-hour format
function convertTimestamp($timestamp) {
    // Create a DateTime object in UTC
    $dateTime = new DateTime($timestamp, new DateTimeZone('UTC'));
    
    // Set the time zone to Philippine Time (PHT)
    $dateTime->setTimezone(new DateTimeZone('Asia/Manila'));
    
    // Return the formatted timestamp in 'Y-m-d h:i:s A' format (12-hour format with AM/PM)
    return $dateTime->format('Y-m-d h:i:s A');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Trash</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Styles remain the same */
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Trash</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
            <li class="breadcrumb-item active">Trash</li>
        </ol>
    </div>

    <div class="table-responsive">
        <table class="datatable table table-hover table-striped" id="fileTable">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Type</th>
                    <th>File Path</th>
                    <th>Timestamp</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trashFiles as $file): ?>
                    <?php 
                    $fileUrl = convertFilePathToURL($file['filepath']);
                    $fileType = pathinfo($file['filepath'], PATHINFO_EXTENSION);
                    $localTimestamp = convertTimestamp($file['timestamp']);
                    ?>
                    <tr>
                        <td>
                            <a href="javascript:void(0);" 
                               onclick="openModal('<?php echo $fileUrl; ?>', '<?php echo $fileType; ?>')">
                               <?php echo htmlspecialchars($file['item_name']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($fileType); ?></td>
                        <td class="file-path-wrapper">
                            <a href="<?php echo $fileUrl; ?>" target="_blank"><?php echo htmlspecialchars($file['filepath']); ?></a>
                        </td>
                        <td><?php echo htmlspecialchars($localTimestamp); ?></td>
                        <td>
                            <!-- Restore and Delete buttons -->
                            <button class="btn btn-warning btn-sm" onclick="restoreFile('<?php echo $file['filepath']; ?>')">Restore</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteFile('<?php echo $file['filepath']; ?>')">Delete Permanently</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<div id="file-preview-overlay">
    <button id="close-preview-btn" onclick="closePreview()">&#10005;</button>
    <div id="file-preview-content"></div>
    <button id="prev-btn" class="navigation-btn" onclick="navigateFile('prev')">&#8249;</button>
    <button id="next-btn" class="navigation-btn" onclick="navigateFile('next')">&#8250;</button>
</div>

<script>
// JavaScript functions for preview and managing files
let currentFiles = []; // Array to store file data
let currentIndex = 0; // Track current file index

// Open the modal and display the selected file
function openModal(fileUrl, fileType) {
    const overlay = document.getElementById("file-preview-overlay");
    const content = document.getElementById("file-preview-content");
    content.innerHTML = "";

    if (fileType.match(/(jpg|jpeg|png|gif)$/i)) {
        const img = document.createElement("img");
        img.src = fileUrl;
        img.className = "preview-media";
        content.appendChild(img);
    } else if (fileType.match(/(mp4|mov)$/i)) {
        const video = document.createElement("video");
        video.src = fileUrl;
        video.controls = true;
        video.className = "preview-media";
        content.appendChild(video);
    } else {
        content.innerHTML = "<p>Preview not available for this file type.</p>";
    }

    overlay.style.display = "flex";
}

// Close the modal
function closePreview() {
    document.getElementById("file-preview-overlay").style.display = "none";
}

// Restore file from trash
function restoreFile(filePath) {
    if (confirm('Are you sure you want to restore this file?')) {
        // Send AJAX request to restore the file
        window.location.href = 'restore.php?file=' + encodeURIComponent(filePath);
    }
}

// Permanently delete file from trash
function deleteFile(filePath) {
    if (confirm('Are you sure you want to delete this file permanently?')) {
        // Send AJAX request to permanently delete the file
        window.location.href = 'delete.php?file=' + encodeURIComponent(filePath);
    }
}
</script>
<script src="assets/js/main.js"></script>

</body>
</html>
