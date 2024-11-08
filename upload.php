<?php
session_start(); // Ensure session start at the top with no whitespace above

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "capstone2425";
$dbname = "greyhoundhub";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the base directory path
$base_directory = '/Volumes/creative/categorizesample';
$current_directory = isset($_GET['dir']) ? urldecode($_GET['dir']) : $base_directory;

// Handle search term
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filteredItems = [];
$dbResults = [];

// Only search if there is a search term
if ($searchTerm !== '') {
    // Function to recursively search the directory for files matching the search term
    function searchDirectory($directory, $searchTerm) {
        $results = [];
        if (!is_dir($directory)) return $results; // Return empty if the directory does not exist
        $items = scandir($directory);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $itemPath = $directory . '/' . $item;
            if (is_dir($itemPath)) {
                $results = array_merge($results, searchDirectory($itemPath, $searchTerm));
            } else {
                if (stripos($item, $searchTerm) !== false) {
                    $results[] = ['path' => $itemPath, 'type' => 'File', 'name' => $item];
                }
            }
        }
        return $results;
    }

    // Directory search results
    $filteredItems = searchDirectory($base_directory, $searchTerm);

    // Database search results
    $stmt = $conn->prepare("SELECT * FROM files WHERE filename LIKE ?");
    $likeTerm = '%' . $searchTerm . '%';
    $stmt->bind_param("s", $likeTerm);
    $stmt->execute();
    $dbResults = $stmt->get_result();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Directory Listing</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 id="pageTitle">Directory Listing</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
            <li class="breadcrumb-item active">My Folder</li>
        </ol>
    </div>

    <!-- Search Bar -->
    <form method="GET" action="">
        <input class="datatable-input" placeholder="Search..." type="search" name="search" title="Search within table" aria-controls="fileTable" value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit">Search</button>
    </form>

    <!-- Only show file list if search term is provided -->
    <?php if ($searchTerm !== ''): ?>
        <div class="container">
            <section class="section">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <table class="datatable table table-hover list-view" id="fileTable">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>File/Folder Name</th>
                                        <th>Type</th>
                                        <th>Owner</th>
                                        <th>Filepath</th>
                                        <th>Location</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Display filtered directory items
                                    foreach ($filteredItems as $item) {
                                        $itemPath = $item['path'];
                                        $file_extension = pathinfo($item['name'], PATHINFO_EXTENSION);
                                        echo '<tr>';
                                        echo '<td><input type="checkbox" class="rowCheckbox"></td>';
                                        echo '<td>' . htmlspecialchars($item['name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($file_extension) . '</td>';
                                        echo '<td>Unknown</td>';
                                        echo '<td>' . htmlspecialchars($itemPath) . '</td>';
                                        echo '<td>Creative</td>';
                                        echo '<td><button class="btn btn-info">Preview</button></td>';
                                        echo '</tr>';
                                    }

                                    // Display results from the database table
                                    if ($dbResults) {
                                        while ($row = $dbResults->fetch_assoc()) {
                                            echo '<tr>';
                                            echo '<td><input type="checkbox" class="rowCheckbox"></td>';
                                            echo '<td>' . htmlspecialchars($row['filename']) . '</td>';
                                            echo '<td>' . htmlspecialchars($row['filetype']) . '</td>';
                                            echo '<td>Database</td>';
                                            echo '<td>' . htmlspecialchars($row['filepath']) . '</td>';
                                            echo '<td>Creative</td>';
                                            echo '<td><button class="btn btn-info">Preview</button></td>';
                                            echo '</tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <?php if (empty($filteredItems) && ($dbResults->num_rows === 0)): ?>
                                <p>No files or folders match your search criteria.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    <?php endif; ?>

    <!-- Filter and Action Buttons -->
    <div class="filter-buttons d-flex gap-2 mb-4 mt-4">
        <div class="dropdown">
            <button class="btn btn-outline-secondary type-dropdown" type="button" data-bs-toggle="dropdown">
                Type
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Photos & Images</a></li>
                <li><a class="dropdown-item" href="#">Audio</a></li>
                <li><a class="dropdown-item" href="#">Video</a></li>
                <li><a class="dropdown-item" href="#">Folder</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <button class="btn btn-outline-secondary people-dropdown" type="button" data-bs-toggle="dropdown">
                People
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Foundation University</a></li>
                <li><a class="dropdown-item" href="#">Anyone with the link</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <button class="btn btn-outline-secondary modified-dropdown" type="button" data-bs-toggle="dropdown">
                Modified
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Today</a></li>
                <li><a class="dropdown-item" href="#">Last Week</a></li>
                <li><a class="dropdown-item" href="#">Last Month</a></li>
                <li><a class="dropdown-item" href="#">This year</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <button class="btn btn-outline-secondary location-dropdown" type="button" data-bs-toggle="dropdown">
                Location
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Creative</a></li>
            </ul>
        </div>
    </div>

    <!-- Action Buttons for Folder and Upload -->
    <div class="button-container mb-4">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFolderModal">Add New Folder</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">Upload File</button>
    </div>

    <!-- Modals for Adding Folder and Uploading Files -->
    <div class="modal fade" id="addFolderModal" tabindex="-1" aria-labelledby="addFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFolderModalLabel">New Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addFolderForm" method="POST" action="add_folder.php">
                        <label for="folderName" class="form-label">Folder Name</label>
                        <input type="text" class="form-control" id="folderName" name="folderName" required>
                        <input type="hidden" name="currentDir" value="<?php echo htmlspecialchars($current_directory); ?>">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="addFolderForm" class="btn btn-primary">Create Folder</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm" action="" method="POST" enctype="multipart/form-data">
                        <label for="fileToUpload" class="form-label">Select Files</label>
                        <input type="file" class="form-control" id="fileToUpload" name="file[]" multiple required>
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <input type="hidden" name="currentDir" value="<?php echo htmlspecialchars($current_directory); ?>">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="uploadForm" class="btn btn-primary">Upload Files</button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Vendor JS Files -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>

</body>
</html>
