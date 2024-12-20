<?php
require 'head.php';
require 'login-check.php';
require 'config.php';


// Handle file upload and redirect logic
// Set the base directory path
$base_directory = '/Volumes/creative/categorizesample';
$current_directory = isset($_GET['dir']) ? urldecode($_GET['dir']) : $base_directory;

// Function to convert file path to URL
function convertFilePathToURL($filePath) {
    $baseDirectory = '/Volumes';
    $baseURL = 'http://172.16.152.45:8000';

    // Replace the base directory with the base URL
    $relative_path = str_replace($baseDirectory, $baseURL, $filePath);

    // Encode special characters in the URL
    return str_replace(' ', '%20', $relative_path); // Ensure proper URL encoding
}


// Capture employee ID for activity tracking
$employee_id = $_SESSION['employee_id'] ?? null;




// Function to insert an activity record into the recent table
function recordActivity($conn, $employee_id, $item_type, $item_name, $filepath) {
    $timestamp = date("Y-m-d H:i:s");
    $stmt = $conn->prepare("INSERT INTO recent (employee_id, item_type, item_name, filepath, timestamp) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $employee_id, $item_type, $item_name, $filepath, $timestamp);
    $stmt->execute();
    $stmt->close();
}


// Function to perform search on both files and folders
function searchFilesAndFolders($conn, $searchTerm) {
    $searchTerm = '%' . $conn->real_escape_string($searchTerm) . '%';

    // Prepare SQL queries to search in both tables
    $fileQuery = $conn->prepare("SELECT * FROM files WHERE filename LIKE ? OR filepath LIKE ? OR tag LIKE ?");
    $fileQuery->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $fileQuery->execute();
    $fileResults = $fileQuery->get_result();

    $folderQuery = $conn->prepare("SELECT * FROM folders WHERE filename LIKE ? OR filepath LIKE ?");
    $folderQuery->bind_param("ss", $searchTerm, $searchTerm);
    $folderQuery->execute();
    $folderResults = $folderQuery->get_result();

    // Combine results
    $results = [
        'files' => $fileResults->fetch_all(MYSQLI_ASSOC),
        'folders' => $folderResults->fetch_all(MYSQLI_ASSOC),
    ];

    // Close statements
    $fileQuery->close();
    $folderQuery->close();

    return $results;
}


// Capture search term from URL if present
$searchResults = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $searchResults = searchFilesAndFolders($conn, $searchTerm);
}




//Upload File
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $response = ['status' => 'success', 'message' => ''];
    $fileCount = count($_FILES['file']['name']);
    $description = isset($_POST['description']) ? $_POST['description'] : NULL;
    $tag = isset($_POST['tag']) ? trim($_POST['tag']) : NULL; // Capture and trim tag input

    // Validate the tag to ensure it contains only one word (no commas or spaces)
    if ($tag && preg_match('/[,\s]/', $tag)) {
        $response['status'] = 'error';
        $response['message'] = "Tags should not contain multiple words or commas. Please enter a single tag.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = $_FILES['file']['name'][$i];
        $fileTmpPath = $_FILES['file']['tmp_name'][$i];
        $fileType = $_FILES['file']['type'][$i];
        $fileSize = $_FILES['file']['size'][$i];
        $fileError = $_FILES['file']['error'][$i];
        $filePath = $current_directory . '/' . basename($fileName);

        // Check for upload errors
        if ($fileError === UPLOAD_ERR_OK) {
            if (move_uploaded_file($fileTmpPath, $filePath)) {
                // Insert file details into the database
                $stmt = $conn->prepare("INSERT INTO files(filename, filepath, filetype, size, dateupload, description, tag) VALUES (?, ?, ?, ?, NOW(), ?, ?)");
                if ($stmt === false) {
                    $response['status'] = 'error';
                    $response['message'] .= "Error preparing statement for $fileName. ";
                    continue;
                }

                $stmt->bind_param("sssiss", $fileName, $filePath, $fileType, $fileSize, $description, $tag);

                if (!$stmt->execute()) {
                    $response['status'] = 'error';
                    $response['message'] .= "Error uploading $fileName to database. ";
                }
                $stmt->close();
            } else {
                $response['status'] = 'error';
                $response['message'] .= "Error moving $fileName to directory. ";
            }
        } else {
            $response['status'] = 'error';
            $response['message'] .= "Upload error code $fileError for $fileName. ";
        }
    }

    // Return response for AJAX
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}


// Handle folder creation logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['folderName'])) {
    $folderName = trim($_POST['folderName']);
    $description = isset($_POST['description']) ? $_POST['description'] : NULL;

    if (!empty($folderName)) {
        $folderPath = $current_directory . '/' . $folderName;

        // Create the folder in the file system
        if (!file_exists($folderPath)) {
            if (mkdir($folderPath, 0777, true)) {
                // Insert folder details into the database
                $stmt = $conn->prepare("INSERT INTO folders(filename, filepath, size, datecreated, description) VALUES (?, ?, ?, NOW(), ?)");
                $size = 0; // Size is 0 for folders
                $stmt->bind_param("ssis", $folderName, $folderPath, $size, $description);

                if ($stmt->execute()) {
                    $_SESSION['alert'] = "Folder created and database entry added successfully.";
                } else {
                    $_SESSION['alert'] = "Error adding folder to database: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['alert'] = "Failed to create folder in the file system.";
            }
        } else {
            $_SESSION['alert'] = "Folder already exists.";
        }
    } else {
        $_SESSION['alert'] = "Folder name cannot be empty.";    
    }
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>File Management</title>
   <!-- Favicons -->
   <link href="assets/img/logoo.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

     <!-- Vendor CSS Files -->
      
      
     <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  
   

    

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Ensure the table scrolls horizontally on smaller screens */
        #tagDropdown {
        position: absolute;
        top: 100%; /* Position below the input */
        left: 0;
        z-index: 1050;
        border: 1px solid #ddd;
        background-color: white;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
    }

    #tagDropdown .dropdown-item:hover {
        background-color: #f8f9fa;
    }

        /* Style for truncating long file paths */
.file-path-wrapper {
    max-width: 300px; /* Adjust as needed */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-path {
    display: inline-block;
    width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: pointer;
}

.file-path:hover {
    color: #0056b3; /* Optional: Add hover color */
    text-decoration: underline; /* Optional: Add underline effect */
}


.table-responsive {
    overflow-x: auto;
}

/* Limit the width of the file path column */
.file-path-column {
    max-width: 300px; /* Adjust as needed */
    overflow: hidden;
    text-overflow: ellipsis; /* Add ellipsis for truncated text */
    white-space: nowrap; /* Prevent wrapping */
}

/* Make file path clickable and readable */
.file-path-wrapper a {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
    text-decoration: none;
    color: #007bff; /* Bootstrap link color */
}

        /* Your existing styles */
        .col-lg-12,
        .table-responsive,
        table {
            width: 100%;
        }

        table {
            border-collapse: collapse; /* Optional: removes spacing between cells */
        }

        th, td {
            
            padding: 8px; /* Adjust padding as needed */
            text-align: left;
        }

        #file-list img {
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer; /* Make images clickable */
        }

        #file-list li {
            list-style: none;
        }

        /* Styles for the preview overlay and content */
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
            overflow: hidden; /* Prevents scrolling of the entire overlay */
        }

        #file-preview-content {
            max-width: 95vw;
            max-height: 95vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden; /* Prevents scrollbars inside the content container */
        }

        .preview-media {
            width: 100vw; /* Full width of the viewport */
            height: 80vh; /* Full height of the viewport */
            object-fit: contain; /* Maintain aspect ratio */
        }

        #close-preview-btn {
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

        #close-preview-btn:hover {
            opacity: 0.7; /* Slightly transparent on hover */
        }

        .dropdown-toggle.no-caret::after {
            display: none;
        }

        /* Custom styles for the button container */
        .button-container {
            display: flex;
            gap: 10px; /* Space between buttons */
            margin-bottom: 20px; /* Space below the button container */

        }

        .table {
    table-layout: auto; /* Allow columns to adjust automatically */
    width: 100%; /* Ensure the table spans the full container width */
}

.table th, .table td {
    white-space: nowrap; /* Prevent text from wrapping */
    text-align: left; /* Align content to the left */
    vertical-align: middle; /* Align content vertically */
}

.table th.actions-column, .table td.actions-column {
    width: 15%; /* Allocate enough space for the Actions dropdown */
}

.dropdown-menu {
    z-index: 1050; /* Ensure dropdown appears above other elements */
}

.datatable {
    overflow-x: auto; /* Enable horizontal scrolling if the table is too wide */
}

.table-responsive {
    overflow-x: visible; /* Ensure dropdowns are not cut off in smaller containers */
}

.filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tag-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

    </style>
</head>

<body>
<?php include 'header.php'; ?><?php include 'sidebar.php'; ?>


<main id="main" class="main">
    <div class="pagetitle">
        <h1 id="pageTitle">Directory Listing</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Folder</li>
        </ol>
    </div><!-- End Page Title -->

    <!-- Search Bar and Filter Options -->
    <div class="search-bar1 mb-5 d-flex align-items-center">
        <i class="bi bi-search text-secondary"></i>
        <input type="text" class="form-control ms-3" placeholder="Search in Drive" id="search-bar">
    </div>

    <div class="filter-buttons d-flex gap-2">
        <!-- Type Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary type-dropdown no-caret" type="button" id="dropdownType" data-bs-toggle="dropdown" aria-expanded="false">
                <span>Type</span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownType">
                <li><a class="dropdown-item" href="#" data-value="Photos & Images"><i class="bi bi-image me-2" style="color: #F4B400;"></i>Photos & Images</a></li>
                <li><a class="dropdown-item" href="#" data-value="Audio"><i class="bi bi-file-music me-2" style="color: #34A853;"></i>Audio</a></li>
                <li><a class="dropdown-item" href="#" data-value="Video"><i class="bi bi-file-earmark-play me-2" style="color: #DB4437;"></i>Video</a></li>
                <li><a class="dropdown-item" href="#" data-value="Folder"><i class="bi bi-folder-fill me-2" style="color: #4285F4;"></i>Folder</a></li>
            </ul>
        </div>

        

        <!-- People Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary people-dropdown no-caret" type="button" id="dropdownPeople" data-bs-toggle="dropdown" aria-expanded="false">
                <span>People</span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownPeople">
                <li><a class="dropdown-item" href="#" data-value="Foundation University"><i class="bi bi-building me-2" style="color: #4285F4;"></i>Foundation University</a></li>
                <li><a class="dropdown-item" href="#" data-value="Anyone with the link"><i class="bi bi-link me-2" style="color: #F4B400;"></i>Anyone with the link</a></li>
            </ul>
        </div>

        <!-- Modified Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary modified-dropdown no-caret" type="button" id="dropdownModified" data-bs-toggle="dropdown" aria-expanded="false">
                <span>Modified</span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownModified">
                <li><a class="dropdown-item" href="#" data-value="Today"><i class="bi bi-clock me-2" style="color: #34A853;"></i>Today</a></li>
                <li><a class="dropdown-item" href="#" data-value="Last Week"><i class="bi bi-calendar me-2" style="color: #DB4437;"></i>Last Week</a></li>
                <li><a class="dropdown-item" href="#" data-value="Last Month"><i class="bi bi-calendar2-week me-2" style="color: #4285F4;"></i>Last Month</a></li>
                <li><a class="dropdown-item" href="#" data-value="This year (2024)"><i class="bi bi-calendar3 me-2" style="color: #F4B400;"></i>This year (2024)</a></li>
                <li><a class="dropdown-item" href="#" data-value="Last Year (2023)"><i class="bi bi-calendar-check me-2" style="color: #DB4437;"></i>Last Year (2023)</a></li>
            </ul>
        </div>

       <!-- Tags Dropdown -->
       <div class="dropdown">
    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="tagsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        Tags
    </button>
    <div class="dropdown-menu p-3" style="max-height: 300px; overflow-y: auto;" aria-labelledby="tagsDropdown">
        <?php
        $query = "SELECT DISTINCT tag FROM files WHERE tag IS NOT NULL AND tag != ''";
        $result = $conn->query($query);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tag = htmlspecialchars($row['tag']);
                if (!empty($tag)) {
                    echo "<div class='form-check'>
                            <input class='form-check-input tag-filter' type='checkbox' value='$tag' id='tag-$tag'>
                            <label class='form-check-label' for='tag-$tag'>$tag</label>
                          </div>";
                }
            }
        }
        ?>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit File/Folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <div class="mb-3">
                        <label for="editFilename" class="form-label">Filename</label>
                        <input type="text" class="form-control" id="editFilename" name="filename">
                    </div>
                    <div class="mb-3">
                        <label for="editTag" class="form-label">Tag</label>
                        <input type="text" class="form-control" id="editTag" name="tag">
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description"></textarea>
                    </div>
                    <input type="hidden" id="editFilePath" name="filepath">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveFileChanges()">Save Changes</button>
            </div>
        </div>
    </div>
</div>


<!-- Tags Checkbox Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tagCheckboxes = document.querySelectorAll('.tag-filter');
    const tableBody = document.querySelector('#fileTable tbody');
    let originalFileList = []; // Backup the original file list on page load

    // Backup the original table rows on page load
    originalFileList = Array.from(tableBody.querySelectorAll('tr')).map(row => row.cloneNode(true));

    // Function to fetch files dynamically (for backend logic)
    function fetchFilesByTags(selectedTags) {
        fetch('fetch_files_by_tags.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tags: selectedTags }),
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.status === 'success') {
                updateFileTable(data.files); // Update the table with the backend response
            } else {
                tableBody.innerHTML = '<tr><td colspan="7">No files found for the selected tags.</td></tr>';
            }
        })
        .catch((error) => {
            console.error('Error fetching files:', error);
            tableBody.innerHTML = '<tr><td colspan="7">An error occurred while fetching files.</td></tr>';
        });
    }

    // Function to update the file table
    function updateFileTable(files) {
        tableBody.innerHTML = ''; // Clear existing rows

        if (files.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7">No files found for the selected tags.</td></tr>';
            return;
        }

        files.forEach((file) => {
            const row = document.createElement('tr');
            row.setAttribute('data-tags', file.tags ? file.tags.join(',') : ''); // Set data-tags attribute for filtering

            row.innerHTML = `
                <td><input type="checkbox" class="rowCheckbox"></td>
                <td>
                    <a href="${file.filepath}" target="_blank">${file.filename}</a>
                </td>
                <td>${file.filetype || 'File'}</td>
                <td>Unknown</td>
                <td class="file-path-column">
                    <div class="file-path-wrapper">
                        <a href="${file.filepath}" target="_blank">${file.filepath}</a>
                    </div>
                </td>
                <td>Creative</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs"></i> Actions
                        </button>
                        <ul class="dropdown-menu">
                             <li>
                    <a class="dropdown-item" href="javascript:void(0);" onclick="openEditModal('<?php echo addslashes(htmlspecialchars($filepath)); ?>', '<?php echo addslashes(htmlspecialchars($filename)); ?>', '<?php echo addslashes(htmlspecialchars($tag)); ?>', '<?php echo addslashes(htmlspecialchars($description)); ?>')">
    <i class="fas fa-edit"></i> Edit
</a>

                                </li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="copyMedia('${file.filepath}')">Copy</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);" onclick="downloadMedia('${file.filepath}')">Download</a></li>
                            <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteMedia('${file.filepath}', '${file.filename}')">Delete</a></li>
                        </ul>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    // Add event listener to each tag checkbox
    tagCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', function () {
            const selectedTags = Array.from(tagCheckboxes)
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value);

            if (selectedTags.length === 0) {
                // Restore the original table rows if no tags are selected
                tableBody.innerHTML = '';
                originalFileList.forEach(row => tableBody.appendChild(row));
            } else {
                // Use backend or frontend filtering
                if (typeof fetchFilesByTags === 'function') {
                    fetchFilesByTags(selectedTags); // Fetch dynamically using backend (optional)
                } else {
                    // Filter rows directly from original file list for frontend filtering
                    const filteredRows = originalFileList.filter(row => {
                        const rowTags = row.getAttribute('data-tags') ? row.getAttribute('data-tags').split(',') : [];
                        return selectedTags.some(tag => rowTags.includes(tag));
                    });

                    tableBody.innerHTML = ''; // Clear the table
                    if (filteredRows.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="7">No files found for the selected tags.</td></tr>';
                    } else {
                        filteredRows.forEach(row => tableBody.appendChild(row)); // Add filtered rows
                    }
                }
            }
        });
    });
});
</script>



        <!-- Location Dropdown -->
        <div class="dropdown">
            <!-- <button class="btn btn-outline-secondary location-dropdown no-caret" type="button" id="dropdownLocation" data-bs-toggle="dropdown" aria-expanded="false">
                <span>Location</span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownLocation">
                <li><a class="dropdown-item" href="#" data-value="Creative"><i class="bi bi-palette me-2" style="color: #4285F4;"></i>Creative</a></li>
            </ul> -->
        </div>
    </div>

    <!-- Button Container for Add New Folder and Upload File -->
    <div class="button-container">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFolderModal">Add New Folder</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">Upload File</button>
    </div>

     <!-- Modal for Adding New Folder -->
     <div class="modal fade" id="addFolderModal" tabindex="-1" aria-labelledby="addFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addFolderModalLabel">New Folder</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="folderName" class="form-label">Folder Name</label>
                            <input type="text" class="form-control" id="folderName" name="folderName" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description for the folder"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
 

<!-- Modal for Uploading File with Progress Bar and Cancel Button -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload Files</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <!-- File Input -->
                    <div class="mb-3">
                        <label for="fileToUpload" class="form-label">Select Files</label>
                        <input type="file" class="form-control" id="fileToUpload" name="file[]" multiple required style="display: none;">
                        <div id="fileDisplay" class="form-control" onclick="document.getElementById('fileToUpload').click()">
                            Click here or choose files to select multiple files
                        </div>
                        <small id="fileError" class="form-text text-danger" style="display: none;">Please select at least one file to upload.</small>
                    </div>

                <!-- Tag Input with Hover Dropdown -->
                    <div class="mb-3 position-relative">
                        <label for="tag" class="form-label">Tag</label>
                        <input type="text" class="form-control" id="tag" name="tag" placeholder="Enter a single tag (no commas or spaces)">
                        <small id="tagError" class="form-text text-danger" style="display: none;">Tag is required and must not contain spaces or commas.</small>
                        <div id="tagDropdown" class="dropdown-menu p-2" style="display: none; max-height: 200px; overflow-y: auto;">
                            <!-- Tags will be dynamically inserted here -->
                        </div>
                    </div>

                    <!-- Description Input -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter description for the files"></textarea>
                    </div>

                    <!-- Upload Button -->
                    <button type="button" class="btn btn-primary" onclick="startUpload()">Upload Files</button>
                </form>

                <!-- Progress bar and Cancel button -->
                <div id="progressContainer" style="display: none; margin-top: 20px;">
                    <progress id="uploadProgress" value="0" max="100" style="width: 100%;"></progress>
                    <div id="progressPercentage" class="text-center mt-1">0%</div>
                    <button id="cancelUploadButton" class="btn btn-danger mt-2" onclick="cancelUpload()">Cancel Upload</button>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', function () {
    const tagInput = document.getElementById('tag');
    const tagDropdown = document.getElementById('tagDropdown');

    // Fetch existing tags dynamically
    fetch('fetch_tags.php')
        .then(response => response.json())
        .then(tags => {
            tags.forEach(tag => {
                const tagOption = document.createElement('div');
                tagOption.className = 'dropdown-item';
                tagOption.textContent = tag;
                tagOption.style.cursor = 'pointer';
                tagOption.addEventListener('click', () => {
                    tagInput.value = tag; // Set the input value to the selected tag
                    tagDropdown.style.display = 'none'; // Hide dropdown after selection
                });
                tagDropdown.appendChild(tagOption);
            });
        })
        .catch(error => console.error('Error fetching tags:', error));

    // Show dropdown on focus
    tagInput.addEventListener('focus', () => {
        tagDropdown.style.display = 'block';
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', (event) => {
        if (!tagInput.contains(event.target) && !tagDropdown.contains(event.target)) {
            tagDropdown.style.display = 'none';
        }
    });

    // Hide dropdown when typing in the tag input
    tagInput.addEventListener('input', () => {
        tagDropdown.style.display = tagInput.value.trim() === '' ? 'block' : 'none';
    });
});

$(document).ready(function () {
    // Hide error messages and progress bar initially
    $('#fileError').hide();
    $('#tagError').hide();
    $('#progressContainer').hide();

    // Validate file selection
    $('#fileToUpload').on('change', function () {
        if (this.files.length > 0) {
            $('#fileError').hide();
        }
    });

    // Validate tag input
    $('#tag').on('input', function () {
        const tagValue = $(this).val().trim();
        if (tagValue.length > 0) {
            $('#tagError').hide();
        }
    });

    // Start upload function
    window.startUpload = function () {
        let isValid = true;

        // Validate file input
        if (!$('#fileToUpload')[0].files.length) {
            $('#fileError').text('Please select at least one file to upload.').show();
            isValid = false;
        }

        // Validate tag input
        const tagValue = $('#tag').val().trim();
        if (!tagValue) {
            $('#tagError').text('Tag is required.').show();
            isValid = false;
        } else if (/[,\s]/.test(tagValue)) {
            $('#tagError').text('Tag must not contain spaces or commas.').show();
            isValid = false;
        }

        if (!isValid) return; // Stop if validation fails

        // Proceed with file upload (description is optional)
        const formData = new FormData(document.getElementById('uploadForm'));
        const xhr = new XMLHttpRequest();

        xhr.open("POST", window.location.href, true);

        // Show progress bar
        $('#progressContainer').show();
        $('#uploadProgress').val(0);

        // Handle progress updates
        xhr.upload.addEventListener('progress', function (event) {
            if (event.lengthComputable) {
                const percentComplete = Math.round((event.loaded / event.total) * 100);
                $('#uploadProgress').val(percentComplete);
                $('#progressPercentage').text(`${percentComplete}%`);
            }
        });

        // Handle upload completion
        xhr.onload = function () {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    $('#uploadProgress').val(100); // Set progress to 100%
                    $('#progressContainer').hide();
                    location.reload(); // Refresh the page to show the new files
                } else {
                    $('#progressContainer').hide();
                    $('#fileError').text(response.message || 'Failed to upload files.').show();
                }
            } else {
                $('#progressContainer').hide();
                $('#fileError').text('An error occurred during the upload.').show();
            }
        };

        // Handle cancellation
        xhr.onabort = function () {
            $('#progressContainer').hide();
            $('#fileError').text('Upload canceled.').show();
        };

        // Start the upload
        xhr.send(formData);
        window.currentUpload = xhr; // Track the current upload request
    };

    // Cancel upload function
    window.cancelUpload = function () {
        if (window.currentUpload) {
            window.currentUpload.abort();
            window.currentUpload = null; // Reset upload reference
        }
    };

    // Display selected files
    $('#fileToUpload').on('change', function () {
        const fileList = this.files;
        $('#fileDisplay').html(
            fileList.length > 0
                ? Array.from(fileList).map((file) => file.name).join('<br>')
                : 'Click here or choose files to select multiple files'
        );
    });
});

</script>


<script>
// JavaScript to display selected file names in a custom div
document.getElementById('fileToUpload').addEventListener('change', function() {
    const fileList = this.files;
    const fileDisplay = document.getElementById('fileDisplay');
    fileDisplay.innerHTML = "";  // Clear previous content

    if (fileList.length > 0) {
        const fileNames = Array.from(fileList).map(file => file.name);
        fileDisplay.innerHTML = fileNames.join('<br>');  // Display file names separated by line breaks
    } else {
        fileDisplay.innerHTML = "Click here or choose files to select multiple files";  // Reset prompt if no files
    }
});
</script>

    
   

<!-- Breadcrumb Navigation -->
<div class="breadcrumb-container mb-3">
                    <?php
                    $breadcrumbs = [];
                    $relative_path = str_replace($base_directory, '', $current_directory); // Get relative path from base
                    $path_segments = explode('/', trim($relative_path, '/')); // Split into individual folder names

                    $path_accumulated = $base_directory;
                    echo '<a href="?dir=' . urlencode($base_directory) . '">Home</a>'; // Add "Home" as the root folder

                    foreach ($path_segments as $segment) {
                        if (!empty($segment)) {
                            $path_accumulated .= '/' . $segment;
                            echo ' / <a href="?dir=' . urlencode($path_accumulated) . '">' . htmlspecialchars($segment) . '</a>';
                        }
                    }
                    ?>
                </div>


                <div class="table-responsive">
    <table class="datatable table table-hover table-striped" id="fileTable">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAllCheckbox"></th>
                <th>File/Folder Name</th>
                <th>Type</th>
                <th>Owner</th>
                <th class="file-path-column">File Path</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($_GET['search'])): ?>
                <?php
                $items = scandir($current_directory);
                $items = array_diff($items, ['.', '..']); // Remove '.' and '..' from the listing

                foreach ($items as $item):
                    $item_path = $current_directory . '/' . $item;
                    $is_dir = is_dir($item_path);
                    $item_type = $is_dir ? 'folder' : 'file';
                    $web_url = convertFilePathToURL($item_path); // Convert the file path to a web URL
                ?>
                    <tr>
                        <td><input type="checkbox" class="rowCheckbox"></td>
                        <td>
                            <?php if ($is_dir): ?>
                                <!-- Folder -->
                                <a href="?dir=<?php echo urlencode($item_path); ?>" class="file-folder-link" 
                                   onclick="recordActivity('<?php echo addslashes($item); ?>', 'folder', '<?php echo htmlspecialchars($item_path); ?>')">
                                    <?php echo htmlspecialchars($item); ?>
                                </a>
                            <?php else: ?>
                                <!-- File -->
                                <a href="javascript:void(0);" class="file-folder-link" 
                                   data-url="<?php echo htmlspecialchars($web_url); ?>" 
                                   data-type="<?php echo htmlspecialchars(pathinfo($item, PATHINFO_EXTENSION)); ?>" 
                                   onclick="openModal('<?php echo htmlspecialchars($web_url); ?>', '<?php echo htmlspecialchars(pathinfo($item, PATHINFO_EXTENSION)); ?>')">
                                    <?php echo htmlspecialchars($item); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $is_dir ? 'Folder' : 'File'; ?></td>
                        <td>Unknown</td>
                        <td class="file-path-column">
                            <div class="file-path-wrapper">
                                <a href="<?php echo htmlspecialchars($web_url); ?>" target="_blank" class="file-path">
                                    <?php echo htmlspecialchars($web_url); ?>
                                </a>
                            </div>
                        </td>
                        <td>Creative</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-cogs"></i> Actions
                                </button>
                                <ul class="dropdown-menu">
                                <li>
                                <a class="dropdown-item" href="javascript:void(0);" onclick="openEditModal('<?php echo addslashes(htmlspecialchars($filepath)); ?>', '<?php echo addslashes(htmlspecialchars($filename)); ?>', '<?php echo addslashes(htmlspecialchars($tag)); ?>', '<?php echo addslashes(htmlspecialchars($description)); ?>')">
    <i class="fas fa-edit"></i> Edit
</a>

                                </li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="copyMedia('<?php echo htmlspecialchars($item_path); ?>')"><i class="fas fa-copy"></i> Copy</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="downloadMedia('<?php echo htmlspecialchars($item_path); ?>')"><i class="fas fa-download"></i> Download</a></li>
                                    <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteMedia('<?php echo htmlspecialchars($item_path); ?>', '<?php echo htmlspecialchars($item); ?>')"><i class="fas fa-trash"></i> Delete</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if (!empty($searchResults['folders']) || !empty($searchResults['files'])): ?>
                    <?php foreach ($searchResults['folders'] as $folder): ?>
                        <tr>
                            <td><input type="checkbox" class="rowCheckbox"></td>
                            <td>
                                <a href="?dir=<?php echo urlencode($folder['filepath']); ?>" 
                                   onclick="recordActivity('<?php echo addslashes($folder['filename']); ?>', 'folder')">
                                    <?php echo htmlspecialchars($folder['filename']); ?>
                                </a>
                            </td>
                            <td>Folder</td>
                            <td>Unknown</td>
                            <td class="file-path-column">
                                <div class="file-path-wrapper">
                                    <a href="<?php echo htmlspecialchars(convertFilePathToURL($folder['filepath'])); ?>" target="_blank" class="file-path">
                                        <?php echo htmlspecialchars(convertFilePathToURL($folder['filepath'])); ?>
                                    </a>
                                </div>
                            </td>
                            <td>Creative</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cogs"></i> Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                    <li>
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="openEditModal('<?php echo addslashes(htmlspecialchars($filepath)); ?>', '<?php echo addslashes(htmlspecialchars($filename)); ?>', '<?php echo addslashes(htmlspecialchars($tag)); ?>', '<?php echo addslashes(htmlspecialchars($description)); ?>')">
    <i class="fas fa-edit"></i> Edit
</a>

                                </li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteMedia('<?php echo htmlspecialchars($folder['filepath']); ?>', '<?php echo htmlspecialchars($folder['filename']); ?>')"><i class="fas fa-trash"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php foreach ($searchResults['files'] as $file): ?>
                        <tr>
                            <td><input type="checkbox" class="rowCheckbox"></td>
                            <td>
                                <a href="javascript:void(0);" class="file-folder-link" 
                                   data-url="<?php echo htmlspecialchars(convertFilePathToURL($file['filepath'])); ?>" 
                                   data-type="<?php echo htmlspecialchars(pathinfo($file['filepath'], PATHINFO_EXTENSION)); ?>" 
                                   onclick="openModal('<?php echo htmlspecialchars(convertFilePathToURL($file['filepath'])); ?>', '<?php echo htmlspecialchars(pathinfo($file['filepath'], PATHINFO_EXTENSION)); ?>')">
                                    <?php echo htmlspecialchars($file['filename']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($file['filetype']); ?></td>
                            <td>Database</td>
                            <td class="file-path-column">
                                <div class="file-path-wrapper">
                                    <a href="<?php echo htmlspecialchars(convertFilePathToURL($file['filepath'])); ?>" target="_blank" class="file-path">
                                        <?php echo htmlspecialchars(convertFilePathToURL($file['filepath'])); ?>
                                    </a>
                                </div>
                            </td>
                            <td>Creative</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-danger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cogs"></i> Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                    <li>
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="openEditModal('<?php echo addslashes(htmlspecialchars($filepath)); ?>', '<?php echo addslashes(htmlspecialchars($filename)); ?>', '<?php echo addslashes(htmlspecialchars($tag)); ?>', '<?php echo addslashes(htmlspecialchars($description)); ?>')">
    <i class="fas fa-edit"></i> Edit
</a>

                                </li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="copyMedia('<?php echo htmlspecialchars($file['filepath']); ?>')"><i class="fas fa-copy"></i> Copy</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" onclick="downloadMedia('<?php echo htmlspecialchars($file['filepath']); ?>')"><i class="fas fa-download"></i> Download</a></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteMedia('<?php echo htmlspecialchars($file['filepath']); ?>', '<?php echo htmlspecialchars($file['filename']); ?>')"><i class="fas fa-trash"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No matching files or folders found.</td></tr>
                <?php endif; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>



<script>
// Function to open the edit modal
function openEditModal(filepath, filename, tag = '', description = '') {
    // Debugging
    console.log({ filepath, filename, tag, description });

    // Detect if the item is a folder
    const isFolder = filepath.endsWith('/');

    // Populate the modal fields
    const editFilename = document.getElementById('editFilename');
    const editFilePath = document.getElementById('editFilePath');
    const tagField = document.getElementById('editTag');
    const descriptionField = document.getElementById('editDescription');
    const tagContainer = document.getElementById('tagContainer');
    const descriptionContainer = document.getElementById('descriptionContainer');

    editFilename.value = filename;
    editFilePath.value = filepath;

    if (isFolder) {
        // Only enable editing the folder name
        tagContainer.style.display = 'none';
        descriptionContainer.style.display = 'none';
        tagField.value = '';
        descriptionField.value = '';
    } else {
        // Enable editing the file details
        tagContainer.style.display = 'block';
        descriptionContainer.style.display = 'block';
        tagField.value = tag || '';
        descriptionField.value = description || '';
    }

    // Show the modal
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// Function to save file/folder changes
function saveFileChanges() {
    const filepath = document.getElementById('editFilePath').value;
    const filename = document.getElementById('editFilename').value.trim();
    const tag = document.getElementById('editTag').value.trim();
    const description = document.getElementById('editDescription').value.trim();

    // Validate filename
    if (!filename) {
        document.getElementById('filenameError').style.display = 'block';
        return;
    } else {
        document.getElementById('filenameError').style.display = 'none';
    }

    // Prepare data to send
    const isFolder = filepath.endsWith('/');
    const data = {
        filepath,
        filename,
    };

    if (!isFolder) {
        data.tag = tag;
        data.description = description;
    }

    // Send AJAX request to update file/folder details
    fetch('update_files_details.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
        .then((response) => response.json())
        .then((result) => {
            if (result.status === 'success') {
                alert('Details updated successfully!');
                location.reload(); // Refresh the table
            } else {
                alert('Error updating details: ' + result.message);
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert('An error occurred while updating the details.');
        });
}
</script>



<script>
    // Apply dynamic hover behavior to truncated file paths
    document.addEventListener("DOMContentLoaded", function () {
        const filePathElements = document.querySelectorAll(".file-path");

        filePathElements.forEach((element) => {
            const isOverflowing = element.scrollWidth > element.clientWidth;

            // If the text overflows, set the title attribute for the hover effect
            if (isOverflowing) {
                element.setAttribute("title", element.textContent.trim());
            } else {
                element.removeAttribute("title");
            }
        });
    });
</script>

    <script>
    function recordActivity(itemName, itemType, filePath) {
        fetch("record_activity.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                item_name: itemName,
                item_type: itemType,
                filepath: filePath // Include the file path here
            })
        }).then(response => {
            if (response.ok) {
                console.log("Activity recorded successfully for " + itemName);
            } else {
                console.error("Failed to record activity for " + itemName);
            }
        }).catch(error => console.error("Error:", error));
    }
</script>



<script>
let debounceTimeout;

document.getElementById('search-bar').addEventListener('input', function() {
    const searchTerm = this.value.trim();

    clearTimeout(debounceTimeout); // Clear previous timer

    // Only search if the input is greater than 3 characters, or if the input is empty
    if (searchTerm.length >= 3 || searchTerm.length === 0) {
        debounceTimeout = setTimeout(() => {
            // If search bar is empty, reload without search parameter; else, run the search
            if (searchTerm.length === 0) {
                window.location.href = window.location.pathname; // Clears search and reloads page
            } else {
                window.location.href = `?search=${encodeURIComponent(searchTerm)}`;
            }
        }, 500); // 500ms delay
    }
});
</script>



    <!-- Scroll to Top Button -->
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>
</main><!-- End #main -->

<!-- File Preview Section -->
<div id="file-preview-overlay">
    <button id="close-preview-btn" onclick="closePreview()">&#10005;</button>
    <button id="prev-btn" class="navigation-btn" onclick="navigateFile('prev')">&#8249;</button>
    <button id="next-btn" class="navigation-btn" onclick="navigateFile('next')">&#8250;</button>
    <div id="file-preview-content"></div>
</div>

<!-- Preloader JavaScript -->
<script>
    window.addEventListener("load", function() {
        const preloader = document.getElementById("preloader");
        preloader.style.display = "none"; // Hide the preloader when the page is fully loaded
    });
</script>



<!-- Template Main JS File -->
<script src="assets/js/main.js"></script>

<script>
// Declare global variables for tracking the current file index and the list of files
var currentFiles = [];
var currentIndex = 0;

// Function to open the preview overlay and display the image or video
function openModal(fileUrl, fileType) {
    var overlay = document.getElementById("file-preview-overlay");
    var content = document.getElementById("file-preview-content");
    content.innerHTML = ""; // Clear previous content

    // Add cache-busting query string
    const updatedFileUrl = `${fileUrl}?t=${new Date().getTime()}`;

    // Create the appropriate preview element based on file type
    if (fileType.match(/(jpg|jpeg|png|gif)$/i)) {
        var img = document.createElement("img");
        img.src = updatedFileUrl;
        img.className = "preview-media";
        content.appendChild(img);
    } else if (fileType.match(/(mp4|mp3|wav|mov)$/i)) {
        var video = document.createElement("video");
        video.src = updatedFileUrl;
        video.className = "preview-media";
        video.controls = true;
        content.appendChild(video);
    } else if (fileType === 'folder') {
        var text = document.createElement("p");
        text.textContent = "This is a folder. Preview is not available.";
        content.appendChild(text);
    }

    overlay.style.display = "flex"; // Show the overlay
}




// Function to close the preview overlay
function closePreview() {
    var overlay = document.getElementById("file-preview-overlay");
    var content = document.getElementById("file-preview-content");

    // Check if there's a video or audio element inside the content and pause it
    var media = content.querySelector("video, audio");
    if (media) {
        media.pause(); // Pause the media
        media.currentTime = 0; // Optional: Reset to the start of the media
    }

    overlay.style.display = "none"; // Hide the overlay
}

// Function to navigate to the previous or next file in the list
function navigateFile(direction) {
    if (direction === 'next' && currentIndex < currentFiles.length - 1) {
        currentIndex++;
    } else if (direction === 'prev' && currentIndex > 0) {
        currentIndex--;
    }

    // Load the new file based on the updated currentIndex
    var currentFile = currentFiles[currentIndex];
    openModal(currentFile.url, currentFile.type, currentFiles);
}

// Helper function to gather file URLs and types
function getFileList() {
    var fileElements = document.querySelectorAll('.file-link');
    var files = [];

    fileElements.forEach(function (element, index) {
        var fileUrl = element.getAttribute('data-url');
        var fileType = element.getAttribute('data-type');
        files.push({ url: fileUrl, type: fileType });
    });

    return files;
}

function renameMedia(filePath, fileName) {
    const newName = prompt("Enter the new name for the file:", fileName);
    if (newName) {
        fetch('rename_file.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filePath: filePath, newName: newName })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("File renamed successfully!");
                location.reload();
            } else {
                alert("Error renaming file: " + data.error);
            }
        })
        .catch(error => console.error("Error:", error));
    }
}


function deleteMedia(filePath, fileName) {
    if (!confirm(`Are you sure you want to delete "${fileName}"? This action cannot be undone.`)) {
        return;
    }

    $.ajax({
        url: 'deleteMedia.php',
        type: 'POST',
        data: { filepath: filePath },
        success: function (response) {
            try {
                response = JSON.parse(response);
            } catch (e) {
                alert('Unexpected response format.');
                return;
            }

            if (response.status === 'success') {
                alert('File deleted successfully!');
                location.reload(); // Refresh the page to update the file listing
            } else if (response.message === 'File does not exist.') {
                alert('The file was already deleted.');
                location.reload(); // Handle cases where the file was removed but not updated in the UI
            } else {
                alert('Error: ' + (response.message || 'Unable to delete file.'));
            }
        },
        error: function () {
            alert('An error occurred while trying to delete the file.');
        }
    });
}


function copyMedia(filePath) {
    // Send the filePath to the backend to handle the copy
    fetch('copy_file.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ filePath: filePath })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("File copied successfully!");
            location.reload(); // Reload the page to show the duplicated file
        } else {
            alert("Error copying file: " + data.error);
        }
    })
    .catch(error => console.error("Error:", error));
}
function downloadMedia(filePath) {
    // Create a URL for the file download
    const downloadUrl = `download_file.php?file=${encodeURIComponent(filePath)}`;

    // Redirect to the download URL
    window.location.href = downloadUrl;
}




document.addEventListener('DOMContentLoaded', function() {
    // Gather all file links on the page
    var files = getFileList();

    // Assign the modal open event to each file link
    var fileElements = document.querySelectorAll('.file-link');
    fileElements.forEach(function (element, index) {
        element.addEventListener('click', function() {
            var fileUrl = element.getAttribute('data-url');
            var fileType = element.getAttribute('data-type');
            openModal(fileUrl, fileType, index, files);
        });
    });

    // Dropdown selection with option replacement and "x" button logic
    document.querySelectorAll('.dropdown-menu a').forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            const parentDropdown = this.closest('.dropdown');
            const dropdownButton = parentDropdown.querySelector('button');
            const span = dropdownButton.querySelector('span');
            const selectedValue = this.getAttribute('data-value');

            // Update the button with the selected option and show "x" button
            span.innerHTML = `${selectedValue} <button class="btn btn-sm btn-outline-secondary ms-2 remove-selection" type="button">&times;</button>`;
            
            // Close other dropdowns
            closeOtherDropdowns(parentDropdown);
        });
    });

    // Add functionality to reset the dropdown when "x" is clicked
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-selection')) {
            const parentDropdown = event.target.closest('.dropdown');
            const dropdownButton = parentDropdown.querySelector('button');
            const span = dropdownButton.querySelector('span');

            span.textContent = dropdownButton.id.replace('dropdown', ''); // Reset to original text
        }
    });

    // Function to close other dropdowns
    function closeOtherDropdowns(currentDropdown) {
        document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
            const parentDropdown = dropdown.closest('.dropdown');
            if (parentDropdown !== currentDropdown) {
                bootstrap.Dropdown.getInstance(dropdown.previousElementSibling)?.hide();
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileToUpload');

    // Optional: Drag-and-drop support
    uploadForm.addEventListener('dragover', (event) => {
        event.preventDefault();
        uploadForm.classList.add('dragging');
    });

    uploadForm.addEventListener('dragleave', () => {
        uploadForm.classList.remove('dragging');
    });

    uploadForm.addEventListener('drop', (event) => {
        event.preventDefault();
        uploadForm.classList.remove('dragging');
        fileInput.files = event.dataTransfer.files; // Set the dropped files as input files
    });
});

</script>




</body>
</html>