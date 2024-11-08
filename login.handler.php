<?php
session_start(); // Start the session

// Check if POST request contains employee_id and password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['employee_id']) && isset($_POST['password'])) {
    $employee_id = $_POST['employee_id'];
    $password = $_POST['password'];

    // Prepare the payload for the API request
    $payload = json_encode([
        'employee_id' => $employee_id,
        'password' => $password
    ]);

    // Send POST request to the login API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://172.16.51.98:8080/greyhoundhub/login");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Set timeout for 5 seconds

    // Get the response from the API
    $response = curl_exec($ch);

    // Check if cURL request failed
    if ($response === false) {
        curl_close($ch);
        // Redirect back to the login page with an error message if the server is down
        header('Location: login.php?error=Server unreachable. Please try again.');
        exit();
    }

    curl_close($ch);

    // Decode the response
    $response_data = json_decode($response, true);

    // Check if the response contains a token
    if (isset($response_data['token'])) {
        // Store token and employee ID in session
        $_SESSION['authToken'] = $response_data['token'];
        $_SESSION['employeeId'] = $response_data['id'];

        // Redirect to the index page after successful login
        header('Location: index.php');
        exit();
    } else {
        // Redirect back to login with an error if credentials are invalid
        header('Location: login.php?error=Invalid credentials. Please try again.');
        exit();
    }
} else {
    // Redirect back to login if the form is not submitted properly
    header('Location: login.php?error=Please provide valid credentials');
    exit();
}
