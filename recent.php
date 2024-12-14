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
    return str_replace($baseDirectory, $baseURL, $filePath);
}

// Function to fetch recent file paths
function getRecentFilePaths($conn) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'mov'];
    $allowedExtensionsSQL = "'" . implode("', '", $allowedExtensions) . "'";
    $query = "SELECT item_name, item_type, filepath, timestamp 
              FROM recent 
              WHERE item_type = 'file' AND LOWER(SUBSTRING_INDEX(filepath, '.', -1)) IN ($allowedExtensionsSQL)
              ORDER BY timestamp DESC
              LIMIT 10";
    $result = $conn->query($query);
    if (!$result) {
        die("Query Failed: " . $conn->error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch recent files
$recentFiles = getRecentFilePaths($conn);

// Function to convert timestamp to Philippine Time (PHT) in 12-hour format
function convertTimestamp($timestamp) {
    $dateTime = new DateTime($timestamp, new DateTimeZone('UTC'));
    $dateTime->setTimezone(new DateTimeZone('Asia/Manila'));
    return $dateTime->format('Y-m-d h:i:s A');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Recent Files</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" rel="stylesheet">
      <!-- Include Bootstrap, DataTables, and FontAwesome -->
      <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <style>
        .grid-view {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .grid-view .grid-item {
            display: flex;
            flex-direction: column;
            width: 23%;
            margin-bottom: 15px;
            text-align: center;
        }
        .grid-view img, .grid-view video {
            width: 100%;
            max-height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .grid-view .file-info {
            text-align: center;
        }
        #file-preview-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1050;
        }
        #file-preview-content {
            max-width: 80%;
            max-height: 80%;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #file-preview-close {
        position: absolute;
                top: 20px; /* Adjusted position from the top */
                left: 20px; /* Adjusted position from the left */
                font-size: 2rem; /* Font size for the close button */
                color: white; /* Color set to white for visibility */
                background: none; /* No background */
                border: none; /* No border */
                cursor: pointer; /* Pointer cursor on hover */
                z-index: 1100; /* Ensure it’s above everything else */
                margin-top: 40px; /* Adjust this value as needed */
    }
        .view-buttons {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        .view-buttons .btn-group {
            display: flex;
            border: 1px solid #ccc;
            border-radius: 50px;
            overflow: hidden;
            background-color: #f8f9fa;
        }
        .view-buttons .btn {
            border: none;
            background: none;
            color: #495057;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
        }
        .view-buttons .btn.active {
            background-color: #007bff;
            color: white;
        }
        .view-buttons .btn i {
            font-size: 18px;
        }
        #fileTable img, #fileTable video {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Recent Files</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
            <li class="breadcrumb-item active">Recent Files</li>
        </ol>
    </div>

    <div class="view-buttons">
        <div class="btn-group">
            <button class="btn active" onclick="switchToListView()" id="listViewBtn">
                <i class="fas fa-list"></i>
            </button>
            <button class="btn" onclick="switchToGridView()" id="gridViewBtn">
                <i class="fas fa-th"></i>
            </button>
        </div>
    </div>

    <div id="fileContainer" class="list-view table-responsive">
        <table class="table table-hover table-striped" id="fileTable">
            <thead>
                <tr>
                    <th>Thumbnail</th>
                    <th>File Name</th>
                    <th>Type</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentFiles as $file): ?>
                    <?php 
                    $fileUrl = convertFilePathToURL($file['filepath']);
                    $fileType = pathinfo($file['filepath'], PATHINFO_EXTENSION);
                    $localTimestamp = convertTimestamp($file['timestamp']);
                    ?>
                    <tr>
                        <td>
                            <?php if (preg_match('/(jpg|jpeg|png|gif)$/i', $fileType)): ?>
                                <img src="<?php echo $fileUrl; ?>" onclick="openPreviewModal('<?php echo $fileUrl; ?>', '<?php echo $fileType; ?>')">
                            <?php elseif (preg_match('/(mp4|mov)$/i', $fileType)): ?>
                                <video src="<?php echo $fileUrl; ?>" onclick="openPreviewModal('<?php echo $fileUrl; ?>', '<?php echo $fileType; ?>')"></video>
                            <?php else: ?>
                                <span>No Preview</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="javascript:void(0);" onclick="openPreviewModal('<?php echo $fileUrl; ?>', '<?php echo $fileType; ?>')">
                                <?php echo htmlspecialchars($file['item_name']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($fileType); ?></td>
                        <td><?php echo htmlspecialchars($localTimestamp); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="fileGrid" class="grid-view" style="display: none;">
        <?php foreach ($recentFiles as $file): ?>
            <?php 
            $fileUrl = convertFilePathToURL($file['filepath']);
            $fileType = pathinfo($file['filepath'], PATHINFO_EXTENSION);
            ?>
            <div class="grid-item">
                <?php if (preg_match('/(jpg|jpeg|png|gif)$/i', $fileType)): ?>
                    <img src="<?php echo $fileUrl; ?>" alt="<?php echo htmlspecialchars($file['item_name']); ?>" onclick="openPreviewModal('<?php echo $fileUrl; ?>', '<?php echo $fileType; ?>')">
                <?php elseif (preg_match('/(mp4|mov)$/i', $fileType)): ?>
                    <video src="<?php echo $fileUrl; ?>" controls onclick="openPreviewModal('<?php echo $fileUrl; ?>', '<?php echo $fileType; ?>')"></video>
                <?php else: ?>
                    <p>Preview not available</p>
                <?php endif; ?>
                <div class="file-info">
                    <p><?php echo htmlspecialchars($file['item_name']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<div id="file-preview-overlay">
    <button id="file-preview-close" onclick="closePreview()">&#10005;</button>
    <div id="file-preview-content"></div>
</div>

<script>
$(document).ready(function() {
    $('#fileTable').DataTable({
        "order": [[3, "desc"]], // Sort by the 4th column (timestamp) descending
        "pageLength": 5,        // Default number of rows per page
        "lengthMenu": [5, 10, 25, 50, 100], // Options for rows per page
        "dom": 'lfrtip'         // Show the page length dropdown (l), search box (f), and pagination controls (p)
    });
});


function openPreviewModal(fileUrl, fileType) {
    const overlay = document.getElementById('file-preview-overlay');
    const content = document.getElementById('file-preview-content');
    content.innerHTML = "";

    if (fileType.match(/(jpg|jpeg|png|gif)$/i)) {
        const img = document.createElement("img");
        img.src = fileUrl;
        img.style.maxWidth = "100%";
        img.style.maxHeight = "100%";
        content.appendChild(img);
    } else if (fileType.match(/(mp4|mov)$/i)) {
        const video = document.createElement("video");
        video.src = fileUrl;
        video.controls = true;
        video.style.maxWidth = "100%";
        video.style.maxHeight = "100%";
        content.appendChild(video);
    } else {
        content.innerHTML = "<p>Preview not available for this file type.</p>";
    }

    overlay.style.display = "flex";
}

function closePreview() {
    document.getElementById('file-preview-overlay').style.display = "none";
}

function switchToListView() {
    document.getElementById('fileContainer').style.display = "block";
    document.getElementById('fileGrid').style.display = "none";
    document.getElementById('listViewBtn').classList.add("active");
    document.getElementById('gridViewBtn').classList.remove("active");
}

function switchToGridView() {
    document.getElementById('fileContainer').style.display = "none";
    document.getElementById('fileGrid').style.display = "flex";
    document.getElementById('gridViewBtn').classList.add("active");
    document.getElementById('listViewBtn').classList.remove("active");
}
</script>
<?php require 'footer.php'; ?>
</body>
</html>
