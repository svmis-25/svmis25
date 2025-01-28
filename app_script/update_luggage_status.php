<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check for required fields
$requiredFields = ['driver_id', 'luggage_code', 'status'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing required field: $field."
        ]);
        exit;
    }
}

// Sanitize and validate inputs
$driver_id = intval($data['driver_id']);
$luggage_code = trim($data['luggage_code']);
$status = trim($data['status']);

// Prepare the update SQL statement
$sql = "UPDATE passengers_luggage SET driver_id = ?, status = ? WHERE luggage_code = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Log and return a SQL error if the statement preparation fails
    error_log("SQL Error: " . $conn->error);
    echo json_encode([
        "status" => "error",
        "message" => "Database error. Failed to prepare statement."
    ]);
    exit;
}

$stmt->bind_param("iss", $driver_id, $status, $luggage_code);

// Execute the statement
if ($stmt->execute()) {
    $response = [
        "status" => "success",
        "message" => "Luggage status updated successfully."
    ];
} else {
    // Log the error message for SQL execution failure
    error_log("SQL Error: " . $stmt->error);
    $response = [
        "status" => "error",
        "message" => "Failed to update status."
    ];
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>