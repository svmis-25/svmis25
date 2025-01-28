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

// Prepare SQL to fetch contact person details
$sql = "SELECT * FROM drivers_contact_person WHERE driver_id = ?";

$stmt = $conn->prepare($sql);

// Check if statement preparation was successful
if ($stmt) {
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $contact_person = [];
    while ($row = $result->fetch_assoc()) {
        $contact_person[] = $row;
    }

    // Check if no contact person was found
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

    $stmt->close(); // Close the statement
} else {
    // Handle SQL preparation error
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

// $driver_id = intval($driver_id);

// $sql = "SELECT * FROM drivers_contact_person WHERE driver_id = ?";

// $stmt = $conn->prepare($sql);
// $stmt->bind_param("i", $driver_id);
// $stmt->execute();

// $result = $stmt->get_result();
// $contact_person = [];
// while ($row = $result->fetch_assoc()) {
//     $contact_person[] = $row;
// }

// // Check if no transactions were found
// if (empty($contact_person)) {
//     $response = [
//         "status" => "error",
//         "message" => "No contact person available."
//     ];
// } else {
//     $response = [
//         "status" => "success",
//         "contact_person" => $contact_person
//     ];
// }

// echo json_encode($response);
// $conn->close();
?>
