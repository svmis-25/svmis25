<?php
// require config file
require_once __DIR__ . '/../config.php';

// Function to check if the token is valid
function isValidToken($conn, $token) {
    $sql = "SELECT * FROM driver_tokens WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Get the token from the request headers
$headers = apache_request_headers();
$token = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (isValidToken($conn, $token)) {
    // Token is valid, proceed with the request
    echo json_encode(["status" => "success", "message" => "Token is valid"]);
} else {
    // Invalid token
    echo json_encode(["status" => "error", "message" => "Invalid token"]);
}

$conn->close();
?>
