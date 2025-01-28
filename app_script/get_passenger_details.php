<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$passenger_id = $_GET['passenger_id'];

$sql = "SELECT * FROM passengers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $passenger_id);
$stmt->execute();

$result = $stmt->get_result();
$passenger = [];
while ($row = $result->fetch_assoc()) {
    // Construct the image URL
    $row['image_url'] = isset($row['image_filename']) ? 'images/profile/passenger/' . $row['image_filename'] : 'images/profile/passenger/placeholder.jpg';
    $passenger[] = $row;
}

// Check if no passengers were found
if (empty($passenger)) {
    $response = [
        "status" => "error",
        "message" => "No passenger available."
    ];
} else {
    $response = [
        "status" => "success",
        "passenger" => $passenger
    ];
}

echo json_encode($response);
$conn->close();
?>
