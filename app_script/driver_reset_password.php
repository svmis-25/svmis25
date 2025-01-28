<?php
header('Content-Type: application/json');

// require config file
require_once __DIR__ . '/../config.php';

// Get the token from the query string
// $token = $_GET['token'];
// Get the token from the query string and sanitize input
$token = htmlspecialchars($_GET['token'], ENT_QUOTES, 'UTF-8');

// Define token expiration time (e.g., 1 hour)
$expiration_time = 60 * 60; // 1 hour in seconds

// Validate the token
$stmt = $conn->prepare("SELECT email, created_at FROM password_resets WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

// Fetch the token data
$stmt->bind_result($email, $created_at);
$stmt->fetch();

// Check if the token has expired
$current_time = time();
$token_created_time = strtotime($created_at);
$time_difference = $current_time - $token_created_time;

if ($time_difference > $expiration_time) {
    // Token has expired
    echo json_encode(['status' => 'error', 'message' => 'Token has expired']);
    // Optionally, delete the expired token
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    exit;
}

// Allow the user to enter a new password and update the database
$data = json_decode(file_get_contents("php://input"), true);
$new_password = $data['password'];

if (empty($new_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password is required']);
    exit;
}

if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/', $new_password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long and include at least one letter, one number, and one special character.']);
    exit;
}

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

// Update the user's password
$stmt = $conn->prepare("UPDATE drivers SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed_password, $email);
$stmt->execute();

// Delete the token
$stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();

echo json_encode(['status' => 'success', 'message' => 'Password has been reset']);

$stmt->close();
$conn->close();
?>
