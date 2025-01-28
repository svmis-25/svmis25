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
$rating = $data['rating'] ?? null;
$feedback = $data['feedback'] ?? '';

// Validate input
if ($driver_id === null || $rating === null) {
    echo json_encode(['status' => 'error', 'message' => 'Driver ID and rating are required.']);
    exit;
}

// Check if the driver_id already exists in the feedback table
$sqlCheck = "SELECT COUNT(*) FROM drivers_feedback WHERE driver_id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $driver_id);
$stmtCheck->execute();
$stmtCheck->bind_result($count);
$stmtCheck->fetch();
$stmtCheck->close();

if ($count > 0) {
    // Driver ID exists, perform an update
    $sqlUpdate = "UPDATE drivers_feedback SET rating = ?, feedback = ? WHERE driver_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("dsi", $rating, $feedback, $driver_id);

    if ($stmtUpdate->execute()) {
        $response = [
            "status" => "success",
            "message" => "Feedback updated successfully."
        ];
    } else {
        $response = [
            "status" => "error",
            "message" => "Failed to update feedback: " . $stmtUpdate->error
        ];
    }

    $stmtUpdate->close();
} else {
    // Driver ID does not exist, perform an insert
    $sqlInsert = "INSERT INTO drivers_feedback (driver_id, rating, feedback) VALUES (?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ids", $driver_id, $rating, $feedback);

    if ($stmtInsert->execute()) {
        $response = [
            "status" => "success",
            "message" => "Feedback submitted successfully."
        ];
    } else {
        $response = [
            "status" => "error",
            "message" => "Failed to submit feedback: " . $stmtInsert->error
        ];
    }

    $stmtInsert->close();
}

// Return the response
echo json_encode($response);

// Close the database connection
$conn->close();
?>
