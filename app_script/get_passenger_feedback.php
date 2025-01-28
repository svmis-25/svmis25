<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$passenger_id = isset($_GET['passenger_id']) ? $_GET['passenger_id'] : '';

$sql = "SELECT * FROM passengers_feedback WHERE passenger_id = ? LIMIT 1"; // Fetch only the latest feedback
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $passenger_id);
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

echo json_encode($response);
$conn->close();
?>