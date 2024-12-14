<?php
require 'head.php';
require 'config.php';

$pageTitle = 'Media Publishing';

// Function to convert file paths to publicly accessible URLs
function convertFilePathToURL($filePath) {
    $baseDirectory = '/Volumes/creative/greyhoundhub';
    $baseURL = 'http://172.16.152.45:8000/creative/greyhoundhub';

    $relativePath = str_replace($baseDirectory, '', $filePath);
    $relativePath = ltrim($relativePath, '/');
    return htmlspecialchars(str_replace(' ', '%20', $baseURL . '/' . $relativePath));
}

// Function to generate small preview for files
function getFilePreview($filetype, $fileURL) {
    if (preg_match('/image/', $filetype)) {
        return "<img src=\"$fileURL\" alt=\"Image preview\" style=\"width: 50px; height: 50px; object-fit: cover; border-radius: 5px;\">";
    } elseif (preg_match('/video/', $filetype)) {
        return "<i class=\"fas fa-video\" style=\"font-size: 24px; color: #007bff;\"></i>";
    } elseif (preg_match('/audio/', $filetype)) {
        return "<i class=\"fas fa-music\" style=\"font-size: 24px; color: #34a853;\"></i>";
    } else {
        return "<i class=\"fas fa-file\" style=\"font-size: 24px; color: #6c757d;\"></i>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Media Publishing</title>
    <style>
        /* Add styles for comment modal */
        .comment-modal .comment-item {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        .comment-modal .comment-item:last-child {
            border-bottom: none;
        }
        .comment-modal .comment-delete {
            color: #dc3545;
            cursor: pointer;
        }
        button.datatable-sorter:before,button.datatable-sorter:after {
    display: none !important;
}


        
    </style>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<style>
 .file-path-wrapper {
    max-width: 250px; /* Adjust to desired width */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-path-wrapper:hover {
    overflow: visible;
    white-space: normal;
    position: absolute;
    background: #f9f9f9;
    border: 1px solid #ddd;
    z-index: 1000;
    padding: 5px;
    max-width: 400px; /* Optional: Set a max-width for hover */
    word-wrap: break-word;
}

</style>
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

    <!-- Media Publishing Table -->
    <div class="table-responsive">
        <table class="datatable table table-hover table-striped" id="fileTable">
            <thead>
                <tr>
                    <!-- <th><input type="checkbox" id="selectAllCheckbox"></th> -->
                     <th></th>
                    <th>File</th>
                    <th>File Name</th>
                    <th>File Path</th>
                    <th>Published By</th>
                    <th>Date Published</th>
                    <th>Views</th>
                    <th>Comments</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
$query = "
    SELECT pf.id AS published_id, f.filename, f.filepath, f.filetype, pf.published_at, pf.status, au.name AS published_by,
           0 AS views,  -- Placeholder since user_app_logs is dropped
           (SELECT COUNT(*) FROM comments WHERE published_file_id = pf.id) AS comments
    FROM published_files pf
    INNER JOIN files f ON pf.file_id = f.id
    LEFT JOIN admin_users au ON pf.published_by = au.employee_id
    ORDER BY pf.published_at DESC";

$result = $conn->query($query);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $fileURL = convertFilePathToURL($row['filepath']);
            $preview = getFilePreview($row['filetype'], $fileURL);

            echo "<tr>";
            echo '<td><input type="checkbox" class="rowCheckbox" value="' . htmlspecialchars($row['published_id']) . '"></td>';
            echo "<td class=\"file-preview\">$preview</td>";
            echo "<td>
                    <a href='javascript:void(0);' class='file-link' onclick='openPreviewModal(\"$fileURL\", \"" . htmlspecialchars($row['filetype']) . "\", \"" . htmlspecialchars($row['filename']) . "\")'>
                        " . htmlspecialchars($row['filename']) . "
                    </a>
                  </td>";
            echo "<td>
                    <div class='file-path-wrapper'>
                        <span class='file-path' title='" . htmlspecialchars($fileURL) . "'>" . htmlspecialchars($fileURL) . "</span>
                    </div>
                  </td>";
            echo "<td>" . htmlspecialchars($row['published_by'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($row['published_at']) . "</td>";
            echo "<td>" . htmlspecialchars($row['views']) . "</td>"; // Views is set to 0 as a placeholder
            echo "<td>
                    <a href='javascript:void(0);' onclick='openCommentsModal(" . htmlspecialchars($row['published_id']) . ")'>" . htmlspecialchars($row['comments']) . " comments</a>
                  </td>";
            echo "<td>" . htmlspecialchars(ucfirst($row['status'])) . "</td>";
            echo "<td>
                    <div class='dropdown'>
                        <button class='btn btn-sm dropdown-toggle' style='background-color: #ac1e37; color: white;' type='button' data-bs-toggle='dropdown'>
                            Actions
                        </button>
                        <div class='dropdown-menu'>
                            <a class='dropdown-item' href=\"$fileURL\" download>Download</a>
                            <a class='dropdown-item text-danger' href='javascript:void(0);' onclick='deletePublishedMedia(" . htmlspecialchars($row['published_id']) . ")'>Unpublish</a>
                        </div>
                    </div>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='10'>No published files found.</td></tr>";
    }
} else {
    echo "<tr><td colspan='10'>Error fetching data: " . htmlspecialchars($conn->error) . "</td></tr>";
}
?>

            </tbody>
        </table>
    </div>
</main>

<!-- Comments Modal -->
<div class="modal fade comment-modal" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentsModalLabel">Comments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="commentsContainer"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function openCommentsModal(publishedId) {
        $.ajax({
            url: 'fetch_comments.php', // Backend to fetch comments
            type: 'GET',
            data: { published_id: publishedId },
            success: function (response) {
                const commentsContainer = $('#commentsContainer');
                commentsContainer.empty();

                const comments = JSON.parse(response);
                if (comments.length > 0) {
                    comments.forEach((comment) => {
                        commentsContainer.append(`
                            <div class="comment-item">
                                <p>${comment.comment}</p>
                                <span class="comment-delete" onclick="deleteComment(${comment.id}, ${publishedId})">Delete</span>
                            </div>
                        `);
                    });
                } else {
                    commentsContainer.append('<p>No comments available.</p>');
                }

                const modal = new bootstrap.Modal(document.getElementById('commentsModal'));
                modal.show();
            },
            error: function () {
                alert('Failed to load comments.');
            },
        });
    }

    function deletePublishedMedia(publishedId) {
    if (confirm("Are you sure you want to unpublish this media?")) {
        $.ajax({
            url: 'unpublish_file.php', // The backend script to handle unpublishing
            type: 'POST',
            data: { published_id: publishedId },
            success: function (response) {
                console.log("Response from server:", response); // Debug response
                try {
                    var jsonResponse = JSON.parse(response);
                    if (jsonResponse.status === 'success') {
                        alert('File unpublished successfully.');
                        location.reload(); // Reload to reflect changes
                    } else {
                        alert('Error: ' + jsonResponse.message);
                    }
                } catch (e) {
                    console.error("Error parsing response:", e);
                    alert("Error: Failed to parse response. Check the console for details.");
                }
            },
            error: function (xhr, status, error) {
                console.error("Unpublishing failed:", status, error);
                alert('Error: Could not unpublish the file. Check console for details.');
            }
        });
    }
}


    function deleteComment(commentId, publishedId) {
        if (confirm('Are you sure you want to delete this comment?')) {
            $.ajax({
                url: 'delete_comment.php',
                type: 'POST',
                data: { comment_id: commentId },
                success: function (response) {
                    alert(response.message || 'Comment deleted successfully.');
                    openCommentsModal(publishedId); // Reload comments
                },
                error: function () {
                    alert('Failed to delete comment.');
                },
            });
        }
    }
</script>
<script src="assets/js/main.js"></script>

<!-- <?php include 'footer.php'; ?> -->
</body>
</html>
