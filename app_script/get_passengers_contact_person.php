<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$passenger_id = $_GET['passenger_id'];

$sql = "SELECT * FROM passengers_contact_person WHERE passenger_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $passenger_id);
$stmt->execute();

$result = $stmt->get_result();
$contact_person = [];
while ($row = $result->fetch_assoc()) {
    $contact_person[] = $row;
}

// Check if no transactions were found
if (empty($contact_person)) {
    $response = [
        "status" => "error",
        "message" => "No contact person available."
    ];
} else {
    $response = [
        "status" => "success",
        "contact_person" => $contact_person
    ];
}

echo json_encode($response);
$conn->close();
?>
