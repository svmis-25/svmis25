<?php
// require config file
require_once __DIR__ . '/../config.php';

// Get the JSON data from the request
$data = json_decode(file_get_contents("php://input"));
$token = $data->token;

// Delete the token from the database
$sql = "DELETE FROM passenger_tokens WHERE token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "Logged out successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to log out"]);
}

$conn->close();
?>
