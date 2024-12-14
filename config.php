<?php
// Database connection parameters
$servername = "localhost"; // or your server name
$username = "root"; // your database username
$password = "capstone2425"; // your database password
$dbname = "greyhound_creative"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
    