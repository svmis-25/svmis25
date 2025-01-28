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
$requiredFields = ['driver_id', 'company_id', 'firstname', 'lastname', 'email', 'contact', 'address', 'age'];
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
$company_id = intval($data['company_id']);
$firstname = $conn->real_escape_string($data['firstname']);
$middlename = isset($data['middlename']) ? $conn->real_escape_string($data['middlename']) : "";
$lastname = $conn->real_escape_string($data['lastname']);
$qualifier = isset($data['qualifier']) ? $conn->real_escape_string($data['qualifier']) : "";
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$contact = $conn->real_escape_string($data['contact']);
$address = $conn->real_escape_string($data['address']);
$age = filter_var($data['age'], FILTER_VALIDATE_INT);

// Validate age
if ($age === false || $age < 0 || $age > 120) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid age. Please enter a valid age."
    ]);
    exit;
}

// Prepare the update SQL statement
$sql = "UPDATE drivers SET firstname = ?, middlename = ?, lastname = ?, qualifier = ?, email = ?, contact = ?, address = ?, company_id = ?, age = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssiii", $firstname, $middlename, $lastname, $qualifier, $email, $contact, $address, $company_id, $age, $driver_id);

// Execute the statement
if ($stmt->execute()) {
    $response = [
        "status" => "success",
        "message" => "Details updated successfully."
    ];
} else {
    $response = [
        "status" => "error",
        "message" => "Failed to update details."
    ];
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
