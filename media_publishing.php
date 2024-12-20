<?php
require 'head.php';
require 'config.php';

$pageTitle = 'Media Publishing';

// Function to convert file paths to publicly accessible URLs
function convertFilePathToURL($filePath) {
    $baseDirectory = '/Volumes/creative';
    $baseURL = 'http://172.16.152.45:8000/creative';

    // Replace the base directory with the base URL
    $relativePath = str_replace($baseDirectory, '', $filePath);

    // Encode special characters in the URL
    $urlPath = str_replace(' ', '%20', $relativePath);

    return $baseURL . '/' . ltrim($urlPath, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <title>Media Publishing</title>
    <style>
        /* Styles */
        .file-path-wrapper {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            overflow: hidden;
        }

        #file-preview-content {
            max-width: 95vw;
            max-height: 95vh;
        }

        .preview-media {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        #close-preview-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 2rem;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
            z-index: 1100;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .datatable {
            table-layout: auto;
            width: 100%;
        }

        .datatable th, .datatable td {
            white-space: nowrap;
            text-align: left;
        }

        /* Ensure buttons are aligned properly */
.btn-group .btn {
    padding: 6px 12px;
    border-radius: 5px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-group .btn.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.btn-group .btn:not(.active):hover {
    background-color: #e9ecef;
    color: #495057;
}

.d-flex.justify-content-end {
    margin-bottom: 10px;
}

/* Optional: Styling tweaks for a cleaner layout */
.table-responsive {
    margin-top: 10px;
}

.datatable {
    table-layout: auto;
    width: 100%;
}

.datatable th, .datatable td {
    white-space: nowrap;
    text-align: left;
}


/* Style the buttons to be more compact */
.btn-group .btn {
    padding: 8px 12px; /* Adjust padding for icon-only buttons */
    font-size: 18px; /* Increase icon size */
}

.btn-group .btn i {
    margin: 0; /* Remove any margin around the icon */
}

.btn-group .btn.active {
    background-color: #007bff; /* Active button background */
    color: white;
    border-color: #007bff;
}

.btn-group .btn:not(.active):hover {
    background-color: #e9ecef;
    color: #495057;
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

<!-- Buttons for List View and Grid View -->
<div class="d-flex justify-content-end mb-2">
    <div class="btn-group" role="group" aria-label="View Toggle">
        <!-- List View Button with Icon -->
        <button type="button" class="btn btn-outline-primary active" id="listViewBtn" onclick="switchToListView()">
            <i class="fas fa-list"></i> <!-- Font Awesome List Icon -->
        </button>
        <!-- Grid View Button with Icon -->
        <button type="button" class="btn btn-outline-secondary" id="gridViewBtn" onclick="switchToGridView()">
            <i class="fas fa-th-large"></i> <!-- Font Awesome Grid Icon -->
        </button>
    </div>
</div>



<!-- Delete Selected Button (Below View Buttons) -->
<div class="mb-3">
    <button type="button" class="btn btn-danger" id="deleteSelectedBtn" style="display: none;">
        <i class="fas fa-trash"></i> Delete Selected
    </button>
</div>
<div id="listView" class="table-responsive">
    <table class="datatable table table-hover table-striped" id="fileTable">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAllCheckbox" title="Select/Deselect All"></th>
                <th>Thumbnail</th>
                <th>File/Folder Name</th>
                <th>Type</th>
                <th class="file-path-column">File Path</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "
            SELECT p.p_id AS published_id, f.filename, f.filepath, f.filetype
            FROM publish p
            INNER JOIN files f ON CONVERT(p.filepath USING utf8mb4) = CONVERT(f.filepath USING utf8mb4)
            ORDER BY p.p_id DESC;
            ";

            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $filePath = $row['filepath'];
                    $fileName = $row['filename'];
                    $fileType = htmlspecialchars($row['filetype']);
                    $fileURL = convertFilePathToURL($filePath);

                    // Generate thumbnail preview
                    $thumbnail = '';
                    if (preg_match('/(jpg|jpeg|png|gif)$/i', $fileType)) {
                        $thumbnail = "<img src='" . htmlspecialchars($fileURL) . "' alt='Thumbnail' class='thumbnail' style='width: 60px; height: 60px; object-fit: cover;'>";
                    } elseif (preg_match('/(mp4|mov|avi)$/i', $fileType)) {
                        $thumbnail = "<video src='" . htmlspecialchars($fileURL) . "' class='thumbnail' style='width: 60px; height: 60px; object-fit: cover;' muted></video>";
                    } else {
                        $thumbnail = "<span>No Preview</span>";
                    }

                    echo "<tr data-path='" . htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8') . "'>";
                    echo "<td><input type='checkbox' class='rowCheckbox'></td>";
                    echo "<td>{$thumbnail}</td>";
                    echo "<td>
                            <a href='javascript:void(0);' 
                               onclick=\"openModal('" . htmlspecialchars($fileURL) . "', '" . htmlspecialchars($fileType) . "')\" 
                               style='text-decoration: none; color: inherit;'>
                                " . htmlspecialchars($fileName) . "
                            </a>
                          </td>";
                    echo "<td>" . htmlspecialchars($fileType) . "</td>";
                    echo "<td class='file-path-column'>
                            <a href='" . htmlspecialchars($fileURL) . "' target='_blank' class='file-path'>" 
                            . htmlspecialchars(str_replace('http://172.16.152.45:8000/creative/', '', $fileURL)) . "
                            </a>
                          </td>";
                    echo "<td>
                            <div class='dropdown'>
                                <button class='btn btn-sm btn-danger dropdown-toggle' type='button' data-bs-toggle='dropdown'>
                                    <i class='fas fa-cogs'></i> Actions
                                </button>
                                <ul class='dropdown-menu'>
                                    <li><a class='dropdown-item' href='javascript:void(0);'>Action 1</a></li>
                                    <li><a class='dropdown-item' href='javascript:void(0);'>Action 2</a></li>
                                </ul>
                            </div>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No files or folders found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    // Initialize DataTables
    $(document).ready(function () {
        $('#fileTable').DataTable({
            "pageLength": 10,
            "order": [[2, "asc"]], // Sort by File/Folder Name
            "columnDefs": [
                { "orderable": false, "targets": [0, 1, 5] } // Disable sorting on checkboxes, thumbnail, and actions
            ]
        });
    });
    </script>
<!-- SCRIPT FOR BULK DELETE -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const rowCheckboxes = document.querySelectorAll('.rowCheckbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

    // Show/Hide "Delete Selected" Button
    function toggleDeleteButton() {
        const anyChecked = Array.from(rowCheckboxes).some(checkbox => checkbox.checked);
        deleteSelectedBtn.style.display = anyChecked ? 'block' : 'none';
    }

    // Select All Checkbox Logic
    selectAllCheckbox.addEventListener('change', function () {
        const isChecked = selectAllCheckbox.checked;
        rowCheckboxes.forEach(checkbox => (checkbox.checked = isChecked));
        toggleDeleteButton();
    });

    // Individual Checkbox Logic
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!checkbox.checked) {
                selectAllCheckbox.checked = false;
            } else if (Array.from(rowCheckboxes).every(cb => cb.checked)) {
                selectAllCheckbox.checked = true;
            }
            toggleDeleteButton();
        });
    });

    // Delete Selected Files (Database Only)
    deleteSelectedBtn.addEventListener('click', function () {
        const selectedCheckboxes = Array.from(rowCheckboxes).filter(checkbox => checkbox.checked);
        if (selectedCheckboxes.length === 0) {
            alert('No files selected.');
            return;
        }

        const filePaths = selectedCheckboxes.map(checkbox => checkbox.closest('tr').dataset.path);

        if (confirm('Are you sure you want to delete the selected files from the database?')) {
            fetch('delete_files_db.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ filepaths: filePaths })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Selected files deleted from the database successfully.');
                    location.reload(); // Reload to reflect changes
                } else {
                    alert('Error deleting files: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting files.');
            });
        }
    });
});



    </script>

<!-- Grid View -->
<div id="gridView" class="row" style="display: none;">
    <?php
    $result = $conn->query($query); // Reusing the same query for grid view

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $filePath = $row['filepath'];
            $fileName = $row['filename'];
            $fileURL = convertFilePathToURL($filePath);
            $fileType = htmlspecialchars($row['filetype']);

            echo "<div class='col-md-3 mb-4'>";
            echo "<div class='card h-100'>";
            echo "<div class='card-body text-center'>";

            // Add modal trigger for thumbnails
            if (preg_match('/(jpg|jpeg|png|gif)$/i', $fileType)) {
                echo "<a href='javascript:void(0);' onclick=\"openModal('" . htmlspecialchars($fileURL) . "', 'image')\">";
                echo "<img src='" . htmlspecialchars($fileURL) . "' alt='Image' class='img-fluid mb-2' style='height: 150px; object-fit: cover;'>";
                echo "</a>";
            } elseif (preg_match('/(mp4|mov|avi)$/i', $fileType)) {
                echo "<a href='javascript:void(0);' onclick=\"openModal('" . htmlspecialchars($fileURL) . "', 'video')\">";
                echo "<video src='" . htmlspecialchars($fileURL) . "' class='img-fluid mb-2' style='height: 150px; object-fit: cover;' muted></video>";
                echo "</a>";
            } else {
                echo "<p class='text-muted'>No Preview</p>";
            }

            // Add File Name
            echo "<h6 class='card-title'>
                    <a href='javascript:void(0);' 
                       onclick=\"openModal('" . htmlspecialchars($fileURL) . "', '" . htmlspecialchars($fileType) . "')\">
                        " . htmlspecialchars($fileName) . "
                    </a>
                  </h6>";

            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<div class='col-12'><p>No files or folders found.</p></div>";
    }
    ?>
</div>



<!-- JavaScript for List/Grid View Toggle -->
<script>
// Function to Switch to List View
function switchToListView() {
    $("#grid-view").hide();  // Hide grid view
    $("#table-view").show(); // Show table (list) view

    $("#listViewBtn").addClass("btn-primary").removeClass("btn-secondary");
    $("#gridViewBtn").addClass("btn-secondary").removeClass("btn-primary");
}

// Function to Switch to Grid View
function switchToGridView() {
    $("#table-view").hide(); // Hide table (list) view
    $("#grid-view").show();  // Show grid view

    $("#gridViewBtn").addClass("btn-primary").removeClass("btn-secondary");
    $("#listViewBtn").addClass("btn-secondary").removeClass("btn-primary");
}

// Ensure both views have initial visibility
$(document).ready(function () {
    $("#grid-view").hide(); // Initially hide grid view
    $("#table-view").show(); // Initially show list view
});


</script>


<!-- File Preview Modal -->
<div id="file-preview-overlay">
    <button id="close-preview-btn" onclick="closePreview()">&#10005;</button>
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
            img.className = "preview-media";
            content.appendChild(img);
        } else if (fileType.match(/(mp4|mp3|wav|mov)$/i)) {
            const video = document.createElement("video");
            video.src = fileUrl;
            video.className = "preview-media";
            video.controls = true;
            content.appendChild(video);
        } else {
            const text = document.createElement("p");
            text.textContent = "Preview not available.";
            content.appendChild(text);
        }

        overlay.style.display = "flex";
    }

    function closePreview() {
        const overlay = document.getElementById("file-preview-overlay");
        overlay.style.display = "none";
    }
</script>
<script src="assets/js/main.js"></script>

<?php require 'footer.php'; ?>

</body>
</html>