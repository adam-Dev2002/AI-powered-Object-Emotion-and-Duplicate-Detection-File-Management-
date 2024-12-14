<?php
require "config.php";

// Convert MySQLi connection to PDO
$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8";
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Base URL for the file paths
$base_url = 'http://172.16.152.45:8000/creative/categorizesample';

// Get the current PHP file name
$currentPage = basename($_SERVER['PHP_SELF'], ".php");

// Set page title dynamically based on the file name
$pageTitle = ($currentPage === 'ifound') ? 'iFound AI Search' : 'Files';

// Search filter functionality
$aiSearchResults = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['searchTerm'])) {
    $searchTerm = '%' . $_POST['searchTerm'] . '%';

    try {
        // Prepare SQL query to search in tags, description, filename, and filetype
        $stmt = $pdo->prepare("SELECT * FROM files WHERE tags LIKE ? OR description LIKE ? OR filename LIKE ? OR filetype LIKE ?");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $aiSearchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

    <style>
        #file-preview-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        #file-preview-content img,
        #file-preview-content video {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.5);
        }

        .preview-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            color: white;
            cursor: pointer;
            z-index: 1001;
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
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <form method="POST" action="">
                        <div class="input-group mb-3">
                            <input type="text" name="searchTerm" class="form-control" placeholder="Search for objects (e.g., 'car', 'person')" required>
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="fileTable" class="datatable table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>File/Folder Name</th>
                                    <th>Type</th>
                                    <th>Filepath</th>
                                    <th>Date Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($aiSearchResults)) : ?>
                                    <?php foreach ($aiSearchResults as $file) : ?>
                                        <?php 
                                            // Remove the '/Volumes/creative/categorizesample' prefix
                                            $relative_path = str_replace('/Volumes/creative/categorizesample', '', $file['filepath']);
                                            
                                            // Generate the final URL
                                            $file_url = $base_url . $relative_path;
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="javascript:void(0);" onclick="openModal('<?php echo htmlspecialchars($file_url); ?>', '<?php echo htmlspecialchars($file['filetype']); ?>')">
                                                    <?php echo htmlspecialchars($file['filename']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($file['filetype']); ?></td>
                                            <td><?php echo htmlspecialchars($file_url); ?></td>
                                            <td><?php echo htmlspecialchars($file['dateupload']); ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars($file_url); ?>" download>Download</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No results found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- File Preview Overlay -->
    <div id="file-preview-overlay">
        <span class="preview-close-btn" onclick="closePreview()">&times;</span>
        <div id="file-preview-content"></div>
    </div>

    <script>
        function openModal(fileUrl, fileType) {
            const overlay = document.getElementById("file-preview-overlay");
            const content = document.getElementById("file-preview-content");
            content.innerHTML = "";

            if (fileType.match(/(jpg|jpeg|png|gif)$/i)) {
                const img = document.createElement("img");
                img.src = fileUrl;
                content.appendChild(img);
            } else if (fileType.match(/(mp4|mp3|wav|mov)$/i)) {
                const video = document.createElement("video");
                video.src = fileUrl;
                video.controls = true;
                content.appendChild(video);
            } else {
                content.innerHTML = `<p style="color: white;">Preview not available for this file type.</p>`;
            }

            overlay.style.display = "flex";
        }

        function closePreview() {
            const overlay = document.getElementById("file-preview-overlay");
            overlay.style.display = "none";
        }
    </script>
</body>
</html>
