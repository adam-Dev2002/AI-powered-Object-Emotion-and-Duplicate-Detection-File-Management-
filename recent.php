<?php
session_start(); // Ensure this is the very first line with no whitespace above

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "capstone2425";
$dbname = "greyhoundhub";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the base directory path
$base_directory = '/Volumes/creative/categorizesample/FU-Events';
$current_directory = isset($_GET['dir']) ? urldecode($_GET['dir']) : $base_directory;

// Function to fetch recent file paths
function getRecentFilePaths($conn) {
    // Define allowed file extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'mov'];

    // Create a string of allowed extensions for SQL IN clause
    $allowedExtensionsSQL = "'" . implode("', '", $allowedExtensions) . "'";

    // Update the query to filter based on file extensions and order by timestamp
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

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Greyhound Hub</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <!-- Favicons -->
    <link href="assets/img/logoo.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
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
            z-index: 1000;
        }
        .preview-media {
            max-width: 90vw;
            max-height: 90vh;
            object-fit: contain;
        }
        .hidden { 
            display: none; /* Hide elements with this class */
        }
    </style>
</head>
<body>

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Recent Files</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
            <li class="breadcrumb-item active">My Folder</li>
        </ol>
    </div>

    <div class="container">
        <h3 class="hidden">Current Directory: <?php echo htmlspecialchars($current_directory); ?></h3> <!-- Hidden element -->
        <h3 class="hidden">Recent Files</h3> <!-- Hidden element -->
        <table class="table">
            <thead>
                <tr>
                    <th>File/Folder Name</th>
                    <th>Type</th>
                    <th>Filepath</th>
                    <th>Timestamp</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="recent-files-body">
                <?php if (empty($recentFiles)): ?>
                    <tr>
                        <td colspan="5">No recent files or folders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentFiles as $item): ?>
                        <tr>
                            <td>
                                <a href="javascript:void(0);" 
                                   class="file-folder-link" 
                                   data-type="<?php echo htmlspecialchars($item['item_type']); ?>" 
                                   data-path="<?php echo htmlspecialchars($item['filepath']); ?>">
                                   <?php echo htmlspecialchars($item['item_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($item['item_type']); ?></td>
                            <td><?php echo htmlspecialchars($item['filepath']); ?></td>
                            <td><?php echo htmlspecialchars($item['timestamp']); ?></td>
                            <td>
                                <?php if ($item['item_type'] === 'file'): ?>
                                    <button class="btn btn-info" onclick="openModal('<?php echo htmlspecialchars(str_replace('/Volumes/creative/categorizesample', '/creative/categorizesample', $item['filepath'])); ?>')">Preview</button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>Preview</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- File Preview Section -->
<div id="file-preview-overlay">
    <button id="close-preview-btn" onclick="closePreview()">&#10005;</button>
    <div id="file-preview-content"></div>
</div>

<!-- Vendor JS Files -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    // Function to open the preview overlay and display the image or video
    function openModal(fileUrl) {
        var overlay = document.getElementById("file-preview-overlay");
        var content = document.getElementById("file-preview-content");
        content.innerHTML = ""; // Clear previous content

        var img = document.createElement("img");
        img.src = fileUrl;
        img.className = "preview-media";

        img.onload = function() {
            content.appendChild(img); // Only add the image if it loads successfully
            overlay.style.display = "flex"; // Show the overlay
        };

        img.onerror = function() {
            overlay.style.display = "none"; // Hide the overlay if image fails to load
        };

        // If the file is a video, create a video element
        var ext = fileUrl.split('.').pop().toLowerCase(); // Get file extension
        if (ext.match(/(mp4|mp3|wav|mov)$/i)) {
            var video = document.createElement("video");
            video.src = fileUrl;
            video.className = "preview-media";
            video.controls = true;
            content.appendChild(video);
            overlay.style.display = "flex"; // Show the overlay
        }
    }

    // Function to close the preview overlay
    function closePreview() {
        var overlay = document.getElementById("file-preview-overlay");
        overlay.style.display = "none"; // Hide the overlay
    }

    // Handle double-click for file/folder links
    document.querySelectorAll('.file-folder-link').forEach(function(link) {
        link.addEventListener('dblclick', function() {
            var type = this.getAttribute('data-type');
            var path = this.getAttribute('data-path');
            
            if (type === 'folder') {
                window.location.href = '?dir=' + encodeURIComponent(path); // Navigate to the folder
            } else if (type === 'file') {
                openModal(path); // Open preview for the file
            }
        });
    });

    // AJAX Function to fetch recent files
    function fetchRecentFiles() {
        // Fetch recent files directly from the server
        fetch(window.location.href + '?fetch_recent_files=true') // Use current URL with query
            .then(response => response.json())
            .then(data => {
                const recentFilesBody = document.getElementById('recent-files-body');
                recentFilesBody.innerHTML = ''; // Clear existing content

                if (data.length === 0) {
                    recentFilesBody.innerHTML = '<tr><td colspan="5">No recent files or folders found.</td></tr>';
                } else {
                    data.forEach(item => {
                        const row = `<tr>
                            <td>
                                <a href="javascript:void(0);" class="file-folder-link" data-type="${item.item_type}" data-path="${item.filepath}">
                                    ${item.item_name}
                                </a>
                            </td>
                            <td>${item.item_type}</td>
                            <td>${item.filepath}</td>
                            <td>${item.timestamp}</td>
                            <td>
                                <button class="btn btn-info" onclick="openModal('${item.filepath}')">Preview</button>
                            </td>
                        </tr>`;
                        recentFilesBody.innerHTML += row;
                    });
                }
            })
            .catch(error => console.error('Error fetching recent files:', error));
    }

    // Polling to fetch recent files every 5 seconds
    setInterval(fetchRecentFiles, 5000);
</script>

</body>
</html>

