<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get the current page name
?>
    <ul class="sidebar-nav" id="sidebar-nav">

<aside id="sidebar" class="sidebar">
    <!-- New Button with Dropdown -->
    <div class="new-button-container">
    <button class="new-btn" onclick="toggleNewMenu()">
        <i class="bi bi-plus"></i> <!-- Plus Icon -->
        <span>New</span>
    </button>
    <div id="newMenu" class="new-menu">
        <a href="#" data-action="file-upload">
            <i class="bi bi-file-earmark-arrow-up"></i> File Upload
        </a>
        <a href="#" data-action="folder-upload">
            <i class="bi bi-folder-plus"></i> Folder Upload
        </a>
        <a href="#" data-action="new-document">
            <i class="bi bi-file-earmark-text"></i> New Document
        </a>
        <a href="#" data-action="new-spreadsheet">
            <i class="bi bi-file-earmark-spreadsheet"></i> New Spreadsheet
        </a>
        <a href="#" data-action="new-presentation">
            <i class="bi bi-file-earmark-ppt"></i> New Presentation
        </a>
    </div>
</div>


        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'home.php' ? 'active' : 'collapsed' ?>" href="home.php">
                <i class="bi bi-house-door"></i> <!-- Home Icon -->
                <span>Home</span>
            </a>
        </li><!-- End Home Nav -->

        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'recent.php' ? 'active' : 'collapsed' ?>" href="recent.php">
                <i class="bi bi-clock-history"></i> <!-- Recent Icon -->
                <span>Recent</span>
            </a>
        </li><!-- End Recent Nav -->

        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'media.php' ? 'active' : 'collapsed' ?>" href="media.php">
                <i class="bi bi-camera-video"></i> <!-- Media Icon -->
                <span>Media</span>
            </a>
        </li><!-- End Media Page Nav -->

        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : 'collapsed' ?>" href="index.php">
                <i class="bi bi-grid"></i> <!-- Dashboard Icon -->
                <span>Dashboard</span>
            </a>
        </li><!-- End Dashboard Nav -->

        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'starred.php' ? 'active' : 'collapsed' ?>" href="starred.php">
                <i class="bi bi-star"></i> <!-- Starred Icon -->
                <span>Starred</span>
            </a>
        </li><!-- End Starred Nav -->

        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'trash.php' ? 'active' : 'collapsed' ?>" href="trash.php">
                <i class="bi bi-trash"></i> <!-- Trash Icon -->
                <span>Trash</span>
            </a>
        </li><!-- End Trash Nav -->

    </ul>
</aside><!-- End Sidebar-->


<!-- Make sure jQuery is included in your project -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Function to toggle the visibility of the dropdown menu
    function toggleNewMenu() {
        var menu = $("#newMenu");
        menu.toggle();
    }

    // Attach toggle function to the "New" button click
    $(".new-btn").on("click", function(event) {
        event.stopPropagation(); // Prevent the event from propagating to window click
        toggleNewMenu();
    });

    // Close the dropdown menu when clicking outside of it
    $(window).on("click", function(event) {
        if (!$(event.target).closest('.new-button-container').length) {
            $("#newMenu").hide();
        }
    });

    // Placeholder function for file upload using a hidden input element
    function openFileUpload() {
        // Create a hidden file input element
        let fileInput = $("<input>", {
            type: "file",
            style: "display:none"
        });

        // Trigger the file selection dialog
        fileInput.on("change", function(event) {
            let file = event.target.files[0];
            if (file) {
                alert("Selected file: " + file.name);
                // Here you would handle the file upload, e.g., send it to a server
            }
        });

        // Append to body, trigger click, and remove
        $("body").append(fileInput);
        fileInput.click();
        fileInput.remove();
    }

    // Placeholder function for folder upload
    function openFolderUpload() {
        // Create a hidden folder input element
        let folderInput = $("<input>", {
            type: "file",
            style: "display:none",
            webkitdirectory: true // Enables folder selection in supported browsers
        });

        // Trigger the folder selection dialog
        folderInput.on("change", function(event) {
            let files = event.target.files;
            if (files.length > 0) {
                alert("Selected folder with " + files.length + " files.");
                // Here you would handle the folder upload, e.g., send the files to a server
            }
        });

        // Append to body, trigger click, and remove
        $("body").append(folderInput);
        folderInput.click();
        folderInput.remove();
    }

    // Function to create a new document
    function createNewDocument() {
        alert("Creating a new document...");
        // Redirect to document creation page or initialize new document
        window.location.href = "create-document.php"; // Update the URL as needed
    }

    // Function to create a new spreadsheet
    function createNewSpreadsheet() {
        alert("Creating a new spreadsheet...");
        // Redirect to spreadsheet creation page or initialize new spreadsheet
        window.location.href = "create-spreadsheet.php"; // Update the URL as needed
    }

    // Function to create a new presentation
    function createNewPresentation() {
        alert("Creating a new presentation...");
        // Redirect to presentation creation page or initialize new presentation
        window.location.href = "create-presentation.php"; // Update the URL as needed
    }

    // Event listeners for dropdown items
    $("#newMenu").on("click", "a[data-action='file-upload']", openFileUpload);
    $("#newMenu").on("click", "a[data-action='folder-upload']", openFolderUpload);
    $("#newMenu").on("click", "a[data-action='new-document']", createNewDocument);
    $("#newMenu").on("click", "a[data-action='new-spreadsheet']", createNewSpreadsheet);
    $("#newMenu").on("click", "a[data-action='new-presentation']", createNewPresentation);
});
</script>
