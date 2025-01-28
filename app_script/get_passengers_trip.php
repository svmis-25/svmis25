<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$trip_id = isset($_GET['trip_id']) ? $_GET['trip_id'] : '';

$sql = "
    SELECT 
        transactions.id AS transaction_id,
        transactions.passenger_id AS passenger_id,
        transactions.origin AS origin,
        transactions.destination AS destination,
        transactions.estimated_time_of_arrival AS estimated_time_of_arrival,
        transactions.qr_code AS qr_code,
        transactions.driver_id AS driver_id,
        transactions.created_at AS transaction_created_at,
        transactions.transaction_no AS transaction_no,
        transactions.distance AS distance,
        transactions.departure_time AS departure_time,
        transactions.trip_id AS trip_id,
        passengers.firstname AS passenger_firstname, 
        passengers.lastname AS passenger_lastname, 
        passengers.middlename AS passenger_middlename,
        passengers.contact AS passenger_contact,
        passengers.email AS passenger_email,
        passengers.address AS passenger_address,
        passengers.created_at AS passenger_created_at,
        passengers.image_filename AS image_filename
    FROM transactions
    LEFT JOIN passengers ON transactions.passenger_id = passengers.id
    WHERE transactions.trip_id = ? AND transactions.is_active = 0
    ORDER BY transactions.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $trip_id);
$stmt->execute();

$result = $stmt->get_result();
$passengers = [];
while ($row = $result->fetch_assoc()) {

    // Construct the image URL
    $row['image_url'] = isset($row['image_filename']) ? 'images/profile/passenger/' . $row['image_filename'] : 'images/profile/passenger/placeholder.jpg';

    $createdAt = new DateTime($row['transaction_created_at']);
    $formattedDate = $createdAt->format('F j, Y');
    $formattedTime = $createdAt->format('g:i A');
    $dayOfWeek = $createdAt->format('D');
    $today = (new DateTime())->format('Y-m-d');
    $rowDate = $createdAt->format('Y-m-d');
    $dateSuffix = ($today === $rowDate) ? '(Today)' : '('.$dayOfWeek.')';
    $row['transaction_created_at'] = $formattedDate . ' ' . $dateSuffix . ' ' . $formattedTime;
    
    // Append the row to the passengers array
    $passengers[] = $row;
}

// Check if no transactions were found
if (empty($passengers)) {
    $response = [
        "status" => "error",
        "message" => "No passengers available."
    ];
} else {
    $response = [
        "status" => "success",
        "count" => count($passengers),
        "passengers" => $passengers
    ];
}

echo json_encode($response);
$conn->close();
?>
