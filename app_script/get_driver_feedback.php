<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Get the driver_id from the URL
$driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : '';

// Sanitize and validate the driver_id
$driver_id = intval($driver_id); // Sanitize to ensure it's an integer

// Check if the driver_id is a valid positive integer
if ($driver_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid driver ID"]);
    exit();
}

// Prepare SQL to fetch feedback for the driver
$sql = "SELECT * FROM drivers_feedback WHERE driver_id = ? LIMIT 1"; // Fetch only the latest feedback
$stmt = $conn->prepare($sql);

// Bind parameters and execute query
if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $feedback = $result->fetch_assoc(); // Fetch a single row

    // Check if feedback is available
    if ($feedback) {
        $response = [
            "status" => "success",
            "feedback" => $feedback
        ];
    } else {
        $response = [
            "status" => "error",
            "message" => "No feedback available."
        ];
    }

    $stmt->close(); // Close the statement
} else {
    $response = [
        "status" => "error",
        "message" => "Database error: " . $conn->error
    ];
}

$conn->close(); // Close the database connection

// Return the response
echo json_encode($response);

// // Enable error reporting and logging
// error_reporting(E_ALL);
// ini_set('display_errors', 0); // Disable error display
// ini_set('log_errors', 1); // Enable error logging
// ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

// header('Content-Type: application/json');
// require_once __DIR__ . '/../config.php';

// $driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : '';

// $driver_id = intval($driver_id); // Sanitize to ensure it's an integer

// // Check if the driver_id is a valid positive integer
// if ($driver_id <= 0) {
//     echo json_encode(["status" => "error", "message" => "Invalid driver ID"]);
//     exit();
// }

// $sql = "SELECT * FROM drivers_feedback WHERE driver_id = ? LIMIT 1"; // Fetch only the latest feedback
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("i", $driver_id);
// $stmt->execute();

// $result = $stmt->get_result();
// $feedback = $result->fetch_assoc(); // Fetch a single row

// // Check if feedback is available
// if ($feedback) {
//     $response = [
//         "status" => "success",
//         "feedback" => $feedback
//     ];
// } else {
//     $response = [
//         "status" => "error",
//         "message" => "No feedback available."
//     ];
// }

// echo json_encode($response);
// $conn->close();
?>