<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Get parameters from URL
$driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : '';
$trip_id = isset($_GET['trip_id']) ? $_GET['trip_id'] : '';

// Check if driver_id is provided and sanitize it
if (empty($driver_id)) {
    echo json_encode(["status" => "error", "message" => "Driver ID is required"]);
    exit();
}

// Sanitize and validate `driver_id` and `trip_id` (ensure they are integers)
$driver_id = intval($driver_id);  // Sanitize to ensure it's an integer
$trip_id = intval($trip_id);      // Sanitize to ensure it's an integer

// Check if the trip_id is valid
if ($trip_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid Trip ID"]);
    exit();
}

// Get the current timestamp for arrival_time
$new_arrival_time = date("Y-m-d H:i:s");

// Prepare SQL to update the arrival time for the driver
$update_sql = "UPDATE transactions SET is_complete = 1, is_active = 0, arrival_time = ? WHERE trip_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $new_arrival_time, $trip_id);

if ($update_stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Trip ended successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update trip status"]);
}

$update_stmt->close();
$conn->close();

// // Enable error reporting and logging
// error_reporting(E_ALL);
// ini_set('display_errors', 0); // Disable error display
// ini_set('log_errors', 1); // Enable error logging
// ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

// header('Content-Type: application/json');
// require_once __DIR__ . '/../config.php';

// // Get parameters from URL
// $driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : '';
// $trip_id = isset($_GET['trip_id']) ? $_GET['trip_id'] : '';

// // Check if driver_id is provided
// if (empty($driver_id)) {
//     echo json_encode(["status" => "error", "message" => "Driver ID is required"]);
//     exit();
// }

// // Get the current timestamp for arrival_time
// $new_arrival_time = date("Y-m-d H:i:s");

// // Prepare SQL to update the arrival time for the driver
// $update_sql = "UPDATE transactions SET is_complete = 1, is_active = 0, arrival_time = ? WHERE trip_id = ?";
// $update_stmt = $conn->prepare($update_sql);
// $update_stmt->bind_param("si", $new_arrival_time, $trip_id);

// if ($update_stmt->execute()) {
//     echo json_encode(["status" => "success", "message" => "Trip ended successfully!"]);
// } else {
//     echo json_encode(["status" => "error", "message" => "Failed to update trip status"]);
// }

// $update_stmt->close();
// $conn->close();
?>
