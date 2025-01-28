<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['driver_id']) || !isset($data['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields."
    ]);
    exit;
}

// Sanitize and validate inputs
$driver_id = intval($data['driver_id']);
$password = password_hash($data['password'], PASSWORD_BCRYPT);

// Prepare the update SQL statement
$sql = "UPDATE drivers SET password = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

// Bind parameters
$stmt->bind_param("si", $password, $driver_id);

// Execute the statement
if ($stmt->execute()) {
    $response = [
        "status" => "success",
        "message" => "Password updated successfully."
    ];
} else {
    $response = [
        "status" => "error",
        "message" => "Failed to update password."
    ];
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>