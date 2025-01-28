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

$driver_id = $data['driver_id'] ?? null;
$type_of_emergency = $data['type_of_emergency'] ?? null;
$details_of_emergency = $data['details_of_emergency'] ?? null;
// $report_date = date('Y-m-d H:i:s');

// Validate input
if ($driver_id === null || $type_of_emergency === null || $details_of_emergency === null) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}

// Check if the driver_id exists in the drivers table
$sqlCheck = "SELECT COUNT(*) FROM drivers WHERE id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $driver_id);
$stmtCheck->execute();
$stmtCheck->bind_result($count);
$stmtCheck->fetch();
$stmtCheck->close();

if ($count > 0) {
    // Insert the incident report into the incidents table
    $sqlInsert = "INSERT INTO incidents (type_of_emergency, details_of_emergency, driver_id) VALUES (?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("sss", $type_of_emergency, $details_of_emergency, $driver_id);
    
    if ($stmtInsert->execute()) {
        $response = [
            "status" => "success",
            "message" => "Incident report submitted successfully."
        ];
    } else {
        $response = [
            "status" => "error",
            "message" => "Failed to submit Incident report: " . $stmtInsert->error
        ];
    }

    $stmtInsert->close();

} else {
    $response = ['status' => 'error', 'message' => 'Driver ID does not exist.'];
}

// Return the response
echo json_encode($response);

// Close the database connection
$conn->close();
?>
