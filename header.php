<!-- ======= Header ======= -->
<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start(); // Start session if not already started
}
include 'config.php';

// Check if the user is logged in
$employee_id = $_SESSION['employee_id'] ?? null;

if ($employee_id) {
    // Fetch user details from the database
    $sql = "SELECT employee_id, name, department, position, email_address FROM user WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();
}

// Close connection at the end of the header if not needed further in other includes
$conn->close();
?>

<header id="header" class="header fixed-top d-flex align-items-center">

<div class="d-flex align-items-center justify-content-between">
  <a href="index.php" class="logo d-flex align-items-center">
    <img src="assets/img/logoo.png" alt="" > <!-- Adjust max-width as needed -->
    <span class="d-none d-lg-block">Greyhound Hub</span>
  </a>
  <i class="bi bi-list toggle-sidebar-btn"></i>
</div><!-- End Logo -->

<nav class="header-nav ms-auto">
  <ul class="d-flex align-items-center">
    <!-- Notifications, Messages, and Profile as per your original code -->
    
    <li class="nav-item dropdown pe-3">
      <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
        <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo htmlspecialchars($user['name']); ?></span>
        </a><!-- End Profile Image Icon -->

        <li class="nav-item dropdown">

        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
        <i class="bi bi-bell"></i>
        <span class="badge bg-primary badge-number">4</span>
        </a><!-- End Notification Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
        <li class="dropdown-header">
            You have 4 new notifications
            <a href="#"><span class="badge rounded-pill bg-white p-2 ms-2">View all</span></a>
        </li>
        <li>
            <hr class="dropdown-divider">
        </li>

      <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
        <li class="dropdown-header">
          <h6><?php echo htmlspecialchars($user['name']); ?></h6>
          <span><?php echo htmlspecialchars($user['position']); ?></span>
        </li>
        <li>
          <hr class="dropdown-divider">
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center" href="users-profile.php">
            <i class="bi bi-person"></i>
            <span>My Profile</span>
          </a>
        </li>
        <li>
          <hr class="dropdown-divider">
        </li>
        <li>
          <a class="dropdown-item d-flex align-items-center" href="settings.php">
            <i class="bi bi-gear"></i>
            <span>Account Settings</span>
          </a>
        </li>
        <li>
          <hr class="dropdown-divider">
        </li>
        <li>
        <a class="dropdown-item d-flex align-items-center" href="logout.php">
    <i class="bi bi-box-arrow-right"></i>
    <span>Sign Out</span>
</a>

          </a>
        </li>
      </ul><!-- End Profile Dropdown Items -->
    </li><!-- End Profile Nav -->
  </ul>
</nav><!-- End Icons Navigation -->

</header><!-- End Header -->
