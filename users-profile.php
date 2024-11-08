<?php
session_start();
include 'config.php';

// Check if the user is logged in
$employee_id = $_SESSION['employee_id'] ?? null;

// Redirect to login if user is not logged in
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

// Fetch user details from the database based on the new structure
$sql = "SELECT employee_id, name, department, position, email_address FROM user WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Profile</title>
    <link href="assets/img/logoo.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Google Fonts and Vendor CSS Files -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Nunito|Poppins" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">


</head>

<body>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<main id="main" class="main">
    <div class="profile-card-container">
        <div class="card profile-card">
            <!-- Header with profile picture and name -->
            <div class="profile-header">
                <!-- <div class="profile-img">
                    <img src="assets/img/default-profile.png" alt="Profile Picture">
                </div> -->
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <h4><?php echo htmlspecialchars($user['position']); ?></h4>
            </div>

            <!-- Profile details section -->
            <div class="profile-body">
                <div class="about-section">
                </div>

                <div class="profile-content">
                    <h5>Profile Details</h5>
                    <div class="row">
                        <div class="col-4 col-md-3 label">Employee ID</div>
                        <div class="col-8 col-md-9"><?php echo htmlspecialchars($user['employee_id']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 col-md-3 label">Full Name</div>
                        <div class="col-8 col-md-9"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 col-md-3 label">Department</div>
                        <div class="col-8 col-md-9"><?php echo htmlspecialchars($user['department']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 col-md-3 label">Position</div>
                        <div class="col-8 col-md-9"><?php echo htmlspecialchars($user['position']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-4 col-md-3 label">Email</div>
                        <div class="col-8 col-md-9"><?php echo htmlspecialchars($user['email_address']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<footer id="footer" class="footer">
    <div class="copyright">
        &copy; Copyright <strong><span>NiceAdmin</span></strong>. All Rights Reserved
    </div>
    <div class="credits">
        Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div>
</footer>

<a href="#" class="back-to-top"><i class="bi bi-arrow-up-short"></i></a>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
