<?php
require "config.php";
require 'login-check.php';

// Check if the user is logged in
$employee_id = $_SESSION['employee_id'] ?? null;

// Redirect to login if user is not logged in
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

// Fetch user details from the new database structure
$sql = "SELECT employee_id, name FROM admin_users WHERE employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// If no user details are found, redirect to login page
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Fetch the latest 20 login activities by users
$logs = [];
$log_sql = "SELECT admin_users.name AS user_name, admin_activity_logs.action, admin_activity_logs.details, admin_activity_logs.timestamp 
            FROM admin_activity_logs 
            JOIN admin_users ON admin_activity_logs.admin_id = admin_users.employee_id 
            WHERE admin_activity_logs.action = 'LOGIN' 
            ORDER BY admin_activity_logs.timestamp DESC 
            LIMIT 20";
$log_stmt = $conn->prepare($log_sql);
$log_stmt->execute();
$log_result = $log_stmt->get_result();
$logs = $log_result->fetch_all(MYSQLI_ASSOC);
$log_stmt->close();

// Convert timestamps to Philippine Time and adjust for 4-hour discrepancy
date_default_timezone_set('Asia/Manila');
foreach ($logs as &$log) {
    $log['timestamp'] = date('Y-m-d H:i:s', strtotime($log['timestamp']) - 4 * 3600);
}
?>

<!DOCTYPE html>
<html lang="en">
<title>Activity Logs</title>

<?php require 'head.php'; ?>

<body>
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<main id="main" class="main">
    <div class="activity-logs-container">
        <div class="card activity-logs-card">
            <div class="card-header text-center">
                <h2>User Login Activity</h2>
                <p>Recent login actions by system users</p>
            </div>

            <div class="card-body">
                <?php if (!empty($logs)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User Name</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $index => $log): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                                        <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No login activity available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require 'footer.php'; ?>

<a href="#" class="back-to-top"><i class="bi bi-arrow-up-short"></i></a>

</body>
</html>