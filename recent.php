<?php
require 'head.php';
require 'login-check.php';
require 'config.php';

$pageTitle = 'Recent';

// Set the base directory path
$base_directory = '/Applications/XAMPP/xamppfiles/htdocs/testcreative';
$current_directory = isset($_GET['dir']) ? urldecode($_GET['dir']) : $base_directory;

// Function to convert file path to URL
function convertFilePathToURL($filePath) {
    $basePath = '/Applications/XAMPP/xamppfiles/htdocs/testcreative';
    $baseURL = 'http://172.16.152.47/testcreative';

    // Ensure the file path is inside the base directory
    if (strpos($filePath, $basePath) === 0) {
        $relative_path = substr($filePath, strlen($basePath)); // Get relative path
        $relative_path = ltrim($relative_path, '/'); // Remove leading slash
        return $baseURL . '/' . str_replace(' ', '%20', $relative_path); // Append relative path
    }
    
    return false; // Return false if the path is invalid
}

    
    // **Check if the directory exists before scanning**
    if (!is_dir($current_directory)) {
        die("Error: Directory '$current_directory' does not exist or is not accessible.");
    }
    
    // Get and filter the directory contents
    $items = scandir($current_directory);
    if ($items === false) {
        die("Error: Unable to read directory contents.");
    }
    
    // Exclude unwanted files
    $items = array_filter($items, function ($item) use ($current_directory) {
        return $item !== '.' && $item !== '..' && $item !== '.DS_Store' && file_exists($current_directory . '/' . $item);
    });
    
    // Display the directory listing
    foreach ($items as $item) {
        $filePath = realpath($current_directory . '/' . $item);
        $fileURL = convertFilePathToURL($filePath);
        
        if ($fileURL) {
            
        }
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
    <!-- Proper order of scripts in the head or at the end of the body -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

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
       
    #file-preview-content img,
    #file-preview-content video {
    max-width: 90vw; /* Prevents zooming outside screen */
    max-height: 90vh; /* Prevents excessive zooming */
    object-fit: contain; /* Ensures full image is visible */
    border-radius: 8px;
}

    /* Ensure container uses full page width */
    .container {
        max-width: 100%; /* Remove any container width restrictions */
        padding-left: 10px;
        padding-right: 10px;
        margin: 0 auto; /* Center the container if necessary */
    }

        /* Grid View Styles */
        .grid-view {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 10px; /* Add spacing between grid items */
    }

    .grid-view .grid-item {
        display: flex;
        flex-direction: column;
        width: 23%;
        margin-bottom: 15px;
        text-align: center;
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .grid-view .grid-item:hover {
        transform: scale(1.03); /* Slight zoom effect on hover */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .grid-view img, .grid-view video {
        width: 100%;
        max-height: 150px;
        object-fit: cover;
        border-radius: 5px;
        margin-bottom: 10px;
        cursor: pointer;
    }

    .grid-view .file-info {
        text-align: center;
        font-size: 14px;
    }

    .grid-view .actions {
        margin-top: 10px;
        display: flex;
        justify-content: space-around;
        gap: 5px;
    }

    .grid-view .actions button {
        font-size: 12px;
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

        #prev-btn {
        left: 10px;
    }

        #next-btn {
            right: 10px;
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

        /* Apply truncation to file names */
.grid-item .file-info {
    max-width: 150px;  /* Adjust width as needed */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Show full file name on hover */
.grid-item .file-info:hover {
    overflow: visible;
    white-space: normal;
    background-color: rgba(255, 255, 255, 0.9);
    padding: 5px;
    border-radius: 4px;
    position: absolute;
    z-index: 10;
}

    </style>
</head>
<body>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>



    <main id="main" class="main">
    <div class="pagetitle">
        <h1><?php echo $pageTitle; ?></h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
        </ol>
    </div>
    
    <div class="d-flex justify-content-end align-items-center mb-3">
    <div class="btn-group ms-2">
        <!-- List View Button -->
        <button id="listViewBtn" class="btn btn-outline-primary active" title="List View" onclick="switchToListView()">
            <i class="fas fa-list"></i>
        </button>
        
        <!-- Grid View Button -->
        <button id="gridViewBtn" class="btn btn-outline-secondary" title="Grid View" onclick="switchToGridView()">
            <i class="fas fa-th-large"></i>
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
                            <img class="file-thumbnail" 
                                 src="<?php echo $fileUrl; ?>" 
                                 data-file-url="<?php echo $fileUrl; ?>" 
                                 data-file-type="<?php echo $fileType; ?>" 
                                 onclick="openPreviewModal('<?php echo $fileUrl; ?>', '<?php echo $fileType; ?>')">
                        <?php elseif (preg_match('/(mp4|mov)$/i', $fileType)): ?>
                            <video class="file-thumbnail" 
                                   src="<?php echo $fileUrl; ?>" 
                                   controls 
                                   data-file-url="<?php echo $fileUrl; ?>" 
                                   data-file-type="<?php echo $fileType; ?>" 
                                   onclick="openPreviewModal('<?php echo $fileUrl; ?>', '<?php echo $fileType; ?>')"></video>
                        <?php else: ?>
                            <span>No Preview</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="javascript:void(0);" class="file-thumbnail"
                           data-file-url="<?php echo $fileUrl; ?>" 
                           data-file-type="<?php echo $fileType; ?>">
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
                <img class="file-thumbnail" 
                     src="<?php echo $fileUrl; ?>" 
                     alt="<?php echo htmlspecialchars($file['item_name']); ?>" 
                     data-file-url="<?php echo $fileUrl; ?>" 
                     data-file-type="<?php echo $fileType; ?>">
            <?php elseif (preg_match('/(mp4|mov)$/i', $fileType)): ?>
                <video class="file-thumbnail" 
                       src="<?php echo $fileUrl; ?>" 
                       controls 
                       data-file-url="<?php echo $fileUrl; ?>" 
                       data-file-type="<?php echo $fileType; ?>"></video>
            <?php else: ?>
                <p>Preview not available</p>
            <?php endif; ?>
            <div class="file-info">
                <p class="file-name" title="<?php echo htmlspecialchars($file['item_name']); ?>">
                    <?php echo htmlspecialchars($file['item_name']); ?>
                </p>
            </div>
        </div>
    <?php endforeach; ?>
</div>



</main>

<!-- File Preview Overlay (Same as Trash) -->
<div id="file-preview-overlay" style="display: none;">
    <button id="prev-btn" class="navigation-btn">&#8249;</button> <!-- Previous -->
    <div id="file-preview-content"></div>
    <button id="next-btn" class="navigation-btn">&#8250;</button> <!-- Next -->
    <button id="close-preview-btn" class="close-btn">&#10005;</button> <!-- Close -->
</div>



<div id="file-preview-overlay">
    <button id="close-preview-btn">&#10005;</button>
    <div id="file-preview-content"></div>
</div>


<script>
$(document).ready(function () {
    let currentIndex = 0;
    let currentFiles = [];

    // ✅ Function to update the file list (Grid & List View)
    function updateFileList() {
        currentFiles = $('.file-thumbnail').map(function () {
            return {
                url: $(this).attr('data-file-url'),
                type: $(this).attr('data-file-type')
            };
        }).get();
    }

    // ✅ Function to open file preview modal
    function openModal(fileUrl, fileType) {
        let previewHTML;
        if (fileType.match(/(jpg|jpeg|png|gif)$/i)) {
            previewHTML = `<img src="${fileUrl}" alt="File Preview">`;
        } else if (fileType.match(/(mp4|mov)$/i)) {
            previewHTML = `<video controls><source src="${fileUrl}" type="video/mp4">Your browser does not support video.</video>`;
        } else {
            previewHTML = `<p>Unsupported file type</p>`;
        }

        $('#file-preview-content').html(previewHTML);
        $('#file-preview-overlay').fadeIn();
    }

    // ✅ Function to navigate between files
    function navigateFile(direction) {
        if (direction === 'next' && currentIndex < currentFiles.length - 1) {
            currentIndex++;
        } else if (direction === 'prev' && currentIndex > 0) {
            currentIndex--;
        } else {
            console.log("Navigation limit reached");
            return;
        }

        const currentFile = currentFiles[currentIndex];
        openModal(currentFile.url, currentFile.type);
    }

    // ✅ Click event for file thumbnails (Grid & List View)
    $(document).on('click', '.file-thumbnail', function () {
        updateFileList(); // Refresh file list
        currentIndex = $('.file-thumbnail').index(this); // Get index of clicked file
        const currentFile = currentFiles[currentIndex];
        openModal(currentFile.url, currentFile.type);
    });

    // ✅ Next & Previous buttons
    $('#next-btn').off().on('click', () => navigateFile('next'));
    $('#prev-btn').off().on('click', () => navigateFile('prev'));

    // ✅ Close preview
    $('#close-preview-btn').off().on('click', () => $('#file-preview-overlay').fadeOut());

    // ✅ Close on overlay click (outside content)
    $('#file-preview-overlay').off().on('click', function (e) {
        if (e.target.id === 'file-preview-overlay') {
            $('#file-preview-overlay').fadeOut();
        }
    });
});





</script>
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
    content.innerHTML = ""; // Clear previous content

    let previewElement;

    if (fileType.match(/(jpg|jpeg|png|gif)$/i)) {
        previewElement = document.createElement("img");
    } else if (fileType.match(/(mp4|mov)$/i)) {
        previewElement = document.createElement("video");
        previewElement.controls = true;
    } else {
        content.innerHTML = "<p>Preview not available for this file type.</p>";
        overlay.style.display = "flex";
        return;
    }

    previewElement.src = fileUrl;
    previewElement.style.maxWidth = "90vw";  // Keep inside viewport
    previewElement.style.maxHeight = "90vh"; // Keep inside viewport
    previewElement.style.objectFit = "contain"; // Ensure full image/video fits
    previewElement.style.borderRadius = "8px"; // Optional: rounded edges

    content.appendChild(previewElement);
    overlay.style.display = "flex";
}

// ✅ Close preview on button click
document.getElementById('close-preview-btn').addEventListener("click", function () {
    document.getElementById('file-preview-overlay').style.display = "none";
});


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



<script src="assets/js/main.js"></script>

<?php require 'footer.php'; ?>
</body>
</html>
