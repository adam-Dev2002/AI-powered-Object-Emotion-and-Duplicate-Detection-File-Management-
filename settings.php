
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - File Management</title>

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

    <!-- Custom CSS for Settings Page -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
<?php
// Include the header and sidebar
include 'header.php';
include 'sidebar.php';
?>

<!-- Main Content -->
<main id="main" class="main">
    <div class="pagetitle">
        <h1>File Management Settings</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Settings</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section settings">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Manage File Settings</h5>

                        <form action="settings.php" method="post">
                            <div class="row mb-3">
                                <label for="storageLimit" class="col-sm-3 col-form-label">Storage Limit (in GB)</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="storageLimit" name="storageLimit" value="100">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="fileTypes" class="col-sm-3 col-form-label">Allowed File Types</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="fileTypes" name="fileTypes" value="jpg, png, pdf">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="maxFileSize" class="col-sm-3 col-form-label">Max File Size (in MB)</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="maxFileSize" name="maxFileSize" value="10">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="fileRetention" class="col-sm-3 col-form-label">File Retention Period (in days)</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="fileRetention" name="fileRetention" value="30">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="uploadDirectory" class="col-sm-3 col-form-label">Upload Directory</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="uploadDirectory" name="uploadDirectory" value="/uploads">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-9 offset-sm-3">
                                <button type="submit" style="background-color: maroon; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 5px; cursor: pointer;">Save Settings</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </section>
</main><!-- End Main Content -->

<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
</a>

<!-- Vendor JS Files -->
<script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/chart.js/chart.umd.js"></script>
<script src="assets/vendor/echarts/echarts.min.js"></script>
<script src="assets/vendor/quill/quill.js"></script>
<script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="assets/vendor/tinymce/tinymce.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>

<!-- Template Main JS File -->
<script src="assets/js/main.js"></script>

</body>

</html>
