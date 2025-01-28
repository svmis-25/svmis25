<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php'; // Include your database config file

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);

$passenger_id = isset($data['passenger_id']) ? intval($data['passenger_id']) : null;
$luggage_code = isset($data['luggage_code']) ? trim($data['luggage_code']) : '';
$description = isset($data['description']) ? trim($data['description']) : '';
$size = isset($data['size']) ? trim($data['size']) : '';
$status = isset($data['status']) ? trim($data['status']) : 'Pending Verification'; // Default status

// Check for required fields
$requiredFields = ['passenger_id', 'luggage_code', 'description', 'size'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing required field: $field."
        ]);
        exit;
    }
}

// Prepare the SQL statement
$sql = "INSERT INTO passengers_luggage (passenger_id, luggage_code, description, size, status)
        VALUES (?, ?, ?, ?, ?)";

if ($stmt = $conn->prepare($sql)) {
    // Bind the parameters
    $stmt->bind_param("issss", $passenger_id, $luggage_code, $description, $size, $status);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Luggage registered successfully."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to register luggage. Error: " . $stmt->error
        ]);
    }

    // Close the statement
    $stmt->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare the SQL statement."
    ]);
}

// Close the database connection
$conn->close();
?>
