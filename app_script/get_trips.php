<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : '';

$sql = "
    SELECT 
        transactions.passenger_id AS passenger_id,
        transactions.origin AS origin,
        transactions.destination AS destination,
        transactions.estimated_time_of_arrival AS estimated_time_of_arrival,
        transactions.distance AS distance,
        transactions.qr_code AS qr_code,
        transactions.driver_id AS driver_id,
        transactions.created_at AS created_at,
        transactions.transaction_no AS transaction_no,
        transactions.is_complete AS is_complete,
        transactions.departure_time AS departure_time,
        transactions.arrival_time AS arrival_time,
        transactions.trip_id AS trip_id,
        drivers.firstname AS driver_name, 
        drivers.lastname AS driver_lastname, 
        passengers.firstname As passenger_firstname,
        passengers.lastname As passenger_lastname,
        passengers.role_id As passenger_roleId
    FROM transactions
    LEFT JOIN drivers ON transactions.driver_id = drivers.id
    LEFT JOIN passengers ON transactions.passenger_id = passengers.id
    WHERE transactions.driver_id = ? AND transactions.is_complete = 1
    ORDER BY transactions.id ASC
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "SQL prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $driver_id);
if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "message" => "SQL execution failed: " . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$transactions = [];

while ($row = $result->fetch_assoc()) {
    // Format created_at
    $createdAt = new DateTime($row['created_at']);
    $formattedDate = $createdAt->format('F j, Y');
    $dayOfWeek = $createdAt->format('D');
    $today = (new DateTime())->format('Y-m-d');
    $rowDate = $createdAt->format('Y-m-d');
    $dateSuffix = ($today === $rowDate) ? '(Today)' : '('.$dayOfWeek.')';

    $row['created_at'] = $formattedDate . ' ' . $dateSuffix;

    // Format departure_time
    $departureTime = new DateTime($row['departure_time']); // Convert to DateTime
    $formattedDepartureTime = $departureTime->format('g:i A');
    $row['departure_time'] = $formattedDepartureTime;

    // Format arrival_time
    $arrivalTime = new DateTime($row['arrival_time']); // Convert to DateTime
    $formattedArrivalTime = $arrivalTime->format('g:i A');
    $row['arrival_time'] = $formattedArrivalTime;

    $transactions[] = $row;
}

if (empty($transactions)) {
    echo json_encode(["status" => "error", "message" => "No transactions available."]);
} else {
    echo json_encode(["status" => "success", "transactions" => $transactions]);
}

$conn->close();