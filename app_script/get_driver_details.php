<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : '';

$driver_id = intval($driver_id); // Sanitize to ensure it's an integer

$sql = "SELECT * FROM drivers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();

$result = $stmt->get_result();
$driver = [];
while ($row = $result->fetch_assoc()) {
    // Construct the image URL
    $row['image_url'] = isset($row['image_filename']) ? 'images/profile/driver/' . $row['image_filename'] : 'images/profile/driver/placeholder.jpg';
    $driver[] = $row;
}

// Check if no passengers were found
if (empty($driver)) {
    $response = [
        "status" => "error",
        "message" => "No driver available."
    ];
} else {
    $response = [
        "status" => "success",
        "driver" => $driver
    ];
}

echo json_encode($response);
$conn->close();
?>
