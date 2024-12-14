<?php
require 'head.php';
require "config.php";
require 'login-check.php';

$base_directory = '/Volumes/creative/greyhoundhub/FU_EVENTS/Dal-uy';
$current_directory = isset($_GET['dir']) ? urldecode($_GET['dir']) : $base_directory;

// Function to convert file path to URL
function convertFilePathToURL($filePath) {
    $baseDirectory = '/Volumes';
    $baseURL = 'http://172.16.152.45:8000';
    return str_replace($baseDirectory, $baseURL, $filePath);
}


// PDO Connection
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Page title
$pageTitle = 'iFound AI Search';

// Fetch AI search results
$aiSearchResults = [];
$searchTerm = $_POST['searchTerm'] ?? null;

try {
    if ($searchTerm) {
        $searchTerms = explode(' ', trim($searchTerm)); // Split search terms by spaces
        $placeholders = [];
        $queryParams = [];

        foreach ($searchTerms as $index => $term) {
            $placeholder = ":searchTerm$index";

            // Convert human-readable months to numeric format
            $termLower = strtolower($term);
            $monthMap = [
                'january' => '01', 'february' => '02', 'march' => '03', 'april' => '04', 'may' => '05', 'june' => '06',
                'july' => '07', 'august' => '08', 'september' => '09', 'october' => '10', 'november' => '11', 'december' => '12'
            ];
            if (isset($monthMap[$termLower])) {
                $term = $monthMap[$termLower]; // Replace month names with numbers
            }

            // Allow partial matches on all fields
            $placeholders[] = "
                filename LIKE $placeholder 
                OR filepath LIKE $placeholder
                OR filetype LIKE $placeholder 
                OR size LIKE $placeholder
                OR dateupload LIKE $placeholder
                OR detected_objects LIKE $placeholder
                OR classification LIKE $placeholder
                OR pose LIKE $placeholder
                OR gesture LIKE $placeholder
                OR datecreated LIKE $placeholder
            ";
            $queryParams[$placeholder] = "%$term%";
        }

        // Combine search placeholders into the query
        $query = "SELECT * FROM files WHERE " . implode(' OR ', $placeholders) . " ORDER BY dateupload DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($queryParams);
    } else {
        // Default query if no search term is provided
        $stmt = $pdo->query("SELECT * FROM files ORDER BY dateupload DESC");
    }

    $aiSearchResults = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch results
} catch (PDOException $e) {
    die("Error fetching AI search results: " . $e->getMessage());
}




// Fetch distinct file types from the database
$fileTypes = [];
try {
    $fileTypeQuery = $pdo->query("SELECT DISTINCT filetype FROM files WHERE filetype IS NOT NULL");
    $fileTypes = $fileTypeQuery->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error fetching file types: " . $e->getMessage());
}

// Fetch duplicates
$duplicates = [];
try {
    $duplicatesQuery = $pdo->query("
        SELECT 
            f1.id, f1.filename, f1.filepath, f1.filetype, f1.dateupload
        FROM files AS f1
        INNER JOIN files AS f2 ON f1.filehash = f2.filehash AND f1.id != f2.id
        GROUP BY f1.id
    ");
    $duplicates = $duplicatesQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching duplicates: " . $e->getMessage());
}

$duplicateCount = count($duplicates);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    

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
        .thumbnail img {
            max-width: 50px;
            max-height: 50px;
            border-radius: 4px;
            cursor: pointer;
        }
        .shortened-path {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
        z-index: 1050; /* Ensures the modal is above the header */
    }
    #file-preview-content {
        max-width: 80%;
        max-height: 80%;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 8px;
        padding: 10px;
        position: relative;
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
    .dataTables_filter
        {
            display:none;
        }
    
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><?php echo $pageTitle; ?></h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <!-- AI Search Form -->
                <form method="POST" action="">
                    <div class="input-group mb-3">
                        <input type="text" name="searchTerm" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm ?? ''); ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>


     


<div class="d-flex justify-content-between align-items-center mb-3">
    <!-- Filters (left side) -->
    <div id="filter-container" class="d-flex align-items-center">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="filter-filetype" id="filterAll" value="all" checked>
            <label class="form-check-label" for="filterAll">All</label>
        </div>
        <?php foreach ($fileTypes as $type): ?>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="filter-filetype" id="filter-<?php echo htmlspecialchars($type); ?>" value="<?php echo htmlspecialchars($type); ?>">
            <label class="form-check-label" for="filter-<?php echo htmlspecialchars($type); ?>">
                <?php echo strtoupper(htmlspecialchars($type)); ?>
            </label>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Buttons (right side) -->
    <div class="d-flex align-items-center">
        <div class="btn-group me-3">
            <button id="list-view-btn" class="btn btn-outline-primary"><i class="fas fa-list"></i></button>
            <button id="grid-view-btn" class="btn btn-outline-secondary"><i class="fas fa-th-large"></i></button>
        </div>
        <button id="toggle-duplicates" class="btn btn-secondary">
        Show Duplicates (<?php echo $duplicateCount; ?>)
        </button>
    </div>
</div>


                </div>
            </div>
            <div id="list-view" class="table-responsive">
    <table id="fileTable" class="table table-hover table-striped">
        <thead>
            <tr>
                <th>Thumbnail</th>
                <th>File Name</th>
                <th>Type</th>
                <th>Path</th>
                <th>Date Uploaded</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="table-body">
            <?php foreach ($aiSearchResults as $file): ?>
            <tr data-type="<?php echo htmlspecialchars($file['filetype']); ?>">
                <td class="thumbnail">
                    <img src="<?php echo convertFilePathToURL($file['filepath']); ?>" alt="Thumbnail" onclick="openPreview('<?php echo convertFilePathToURL($file['filepath']); ?>', '<?php echo $file['filetype']; ?>')">
                </td>
                <td><?php echo htmlspecialchars($file['filename']); ?></td>
                <td><?php echo htmlspecialchars($file['filetype']); ?></td>
                <td class="shortened-path"><?php echo htmlspecialchars($file['filepath']); ?></td>
                <td><?php echo htmlspecialchars($file['dateupload']); ?></td>
                <td>
    <div class="dropdown">
        <button class="btn btn-sm btn-danger dropdown-toggle" type="button" id="dropdownActions-<?php echo htmlspecialchars($file['filename']); ?>" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-cogs"></i> Actions
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownActions-<?php echo htmlspecialchars($file['filename']); ?>">
            <li>
                <a class="dropdown-item" href="javascript:void(0);" onclick="publishMedia('<?php echo htmlspecialchars($file['filename']); ?>', '<?php echo htmlspecialchars($file['filepath']); ?>')">
                    <i class="fas fa-cloud-upload-alt"></i> Publish
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="javascript:void(0);" onclick="renameMedia('<?php echo htmlspecialchars($file['filepath']); ?>', '<?php echo htmlspecialchars($file['filename']); ?>')">
                    <i class="fas fa-i-cursor"></i> Rename
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="javascript:void(0);" onclick="copyMedia('<?php echo htmlspecialchars($file['filepath']); ?>')">
                    <i class="fas fa-copy"></i> Copy
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="javascript:void(0);" onclick="downloadMedia('<?php echo htmlspecialchars($file['filepath']); ?>')">
                    <i class="fas fa-download"></i> Download
                </a>
            </li>
            <li>
                <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteMedia('<?php echo htmlspecialchars($file['filepath'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($file['filename'], ENT_QUOTES, 'UTF-8'); ?>')">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </li>
        </ul>
    </div>
</td>



            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="grid-view" class="grid-view d-none">
    <?php foreach ($aiSearchResults as $file): ?>
    <div class="grid-item" data-type="<?php echo htmlspecialchars($file['filetype']); ?>">
        <img src="<?php echo convertFilePathToURL($file['filepath']); ?>" alt="Thumbnail"
             onclick="openPreview('<?php echo convertFilePathToURL($file['filepath']); ?>', '<?php echo $file['filetype']; ?>')">
        <div class="file-info">
            <div><?php echo htmlspecialchars($file['filename']); ?></div>
            <div><?php echo htmlspecialchars($file['filetype']); ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>




    </section>
</main>

<!-- Preview Modal -->
<div id="file-preview-overlay">
    <div id="file-preview-content"></div>
    <span id="file-preview-close" onclick="closePreview()">×</span>
</div>

<!-- JavaScript -->
<script>

    $(document).ready(function () {
        const table = $('#fileTable').DataTable({
            paging: true,
            searching: true,
            responsive: true,
            lengthChange: true,
            pageLength: 10,
            order: [[4, 'desc']]
        });

        const tableBody = $('#table-body');
        const listView = $('#list-view');
        const gridView = $('#grid-view');

     // Toggle List/Grid view
    $('#list-view-btn').on('click', function () {
        listView.show();
        gridView.addClass('d-none');
        $(this).addClass('btn-primary').removeClass('btn-outline-secondary');
        $('#grid-view-btn').addClass('btn-outline-secondary').removeClass('btn-primary');
    });


    $('#grid-view-btn').on('click', function () {
        listView.hide();
        gridView.removeClass('d-none');
        $(this).addClass('btn-primary').removeClass('btn-outline-secondary');
        $('#list-view-btn').addClass('btn-outline-secondary').removeClass('btn-primary');
    });
      

          // Handle the change event of the radio buttons
    $('input[name="filter-filetype"]').on('change', function () {
        const selectedType = $(this).val();

        if (selectedType === 'all') {
            // Show all rows
            $('tr, .grid-item').show();
        } else {
            // Hide all rows and only show those that match the selected type
            $('tr, .grid-item').hide();
            $(`tr[data-type="${selectedType}"], .grid-item[data-type="${selectedType}"]`).show();
        }
    });

        // Toggle duplicates
        let showingDuplicates = false;
        $('#toggle-duplicates').on('click', function () {
            const btn = $(this);
            btn.text(showingDuplicates ? `Show Duplicates (${<?php echo $duplicateCount; ?>})` : 'Hide Duplicates');
            showingDuplicates = !showingDuplicates;

            if (showingDuplicates) {
                tableBody.html('');
                <?php foreach ($duplicates as $file): ?>
                tableBody.append(`
                    <tr data-type="<?php echo htmlspecialchars($file['filetype']); ?>">
                        <td class="thumbnail">
                            <img src="<?php echo convertFilePathToURL($file['filepath']); ?>" alt="Thumbnail">
                        </td>
                        <td><?php echo htmlspecialchars($file['filename']); ?></td>
                        <td><?php echo htmlspecialchars($file['filetype']); ?></td>
                        <td class="shortened-path"><?php echo htmlspecialchars($file['filepath']); ?></td>
                        <td><?php echo htmlspecialchars($file['dateupload']); ?></td>
                        <td>
                            <button class="btn btn-danger btn-sm" onclick="deleteMedia('<?php echo $file['filepath']; ?>', '<?php echo $file['filename']; ?>')">Delete</button>
                        </td>
                    </tr>
                `);
                <?php endforeach; ?>
            } else {
                location.reload();
            }
        });
    });

    function openPreview(fileUrl, fileType) {
    const overlay = document.getElementById('file-preview-overlay');
    const content = document.getElementById('file-preview-content');
    content.innerHTML = '';

    if (fileType.match(/(jpg|jpeg|png|gif)$/i)) {
        const img = document.createElement('img');
        img.src = fileUrl;
        img.style.width = '100%';
        img.style.maxHeight = '80vh';
        img.style.objectFit = 'contain';
        content.appendChild(img);
    } else if (fileType.match(/(mp4|avi|mov|mkv)$/i)) {
        const video = document.createElement('video');
        video.src = fileUrl;
        video.controls = true;
        video.style.width = '100%';
        video.style.maxHeight = '80vh';
        content.appendChild(video);
    }

    overlay.style.display = 'flex';
}

function closePreview() {
    document.getElementById('file-preview-overlay').style.display = 'none';
}






// Publish Media
// Function to publish a file
function publishMedia(fileName, filePath) {
    if (confirm(`Are you sure you want to publish "${fileName}"?`)) {
        $.ajax({
            url: './actions/publish.php',
            type: 'POST',
            data: { filepath: filePath.trim(), filename: fileName.trim() },
            success: function (response) {
                try {
                    const jsonResponse = JSON.parse(response);
                    if (jsonResponse.status === 'success') {
                        alert('File published successfully!');
                        location.reload();
                    } else {
                        alert('Error publishing file: ' + jsonResponse.message);
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    alert('Unexpected error occurred.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('An error occurred while publishing the file.');
            }
        });
    }
}

function deleteMedia(filePath, fileName) {
    if (confirm(`Are you sure you want to delete "${fileName}"? This action cannot be undone.`)) {
        $.ajax({
            url: './actions/deleteMedia.php',
            type: 'POST',
            data: JSON.stringify({ filepath: filePath.trim(), fileName: fileName.trim() }),
            contentType: 'application/json',
            success: function (response) {
                const jsonResponse = JSON.parse(response);
                if (jsonResponse.status === 'success') {
                    alert('File deleted successfully!');
                    location.reload();
                } else {
                    alert('Error deleting file: ' + jsonResponse.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('An error occurred while deleting the file.');
            }
        });
    }
}

function renameMedia(filePath, fileName) {
    const newName = prompt('Enter the new name for the file:', fileName);
    if (newName) {
        $.ajax({
            url: './actions/rename_file.php',
            type: 'POST',
            data: JSON.stringify({ filePath: filePath.trim(), newName: newName.trim() }),
            contentType: 'application/json',
            success: function (response) {
                const jsonResponse = JSON.parse(response);
                if (jsonResponse.success) {
                    alert('File renamed successfully!');
                    location.reload();
                } else {
                    alert('Error renaming file: ' + jsonResponse.error);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('An error occurred while renaming the file.');
            }
        });
    }
}


// Copy Media
function copyMedia(filePath) {
    console.log("Attempting to copy:", filePath);

    fetch('copy_file.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ filePath: filePath }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Response from copyMedia:", data);

        if (data.success) {
            alert("File copied successfully!");
            // Optionally, reload the page to show the copied file
            location.reload();
        } else {
            alert(`Error copying file: ${data.error}`);
        }
    })
    .catch(error => {
        console.error("Error during copyMedia:", error);
        alert('An error occurred while copying the file. Check the console for details.');
    });
}

// Download Media
function downloadMedia(filePath) {
    console.log("Attempting to download:", filePath);

    const downloadUrl = `download_file.php?file=${encodeURIComponent(filePath)}`;
    window.location.href = downloadUrl;
}

</script>
<script src="assets/js/main.js"></script>

</body>
</html>
