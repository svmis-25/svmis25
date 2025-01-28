<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$passenger_id = $_GET['passenger_id'];

// Update the SQL query to join the drivers table with full table names
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
        drivers.firstname AS driver_name, 
        drivers.lastname AS driver_lastname, 
        passengers.firstname As passenger_firstname,
        passengers.lastname As passenger_lastname,
        passengers.role_id As passenger_roleId
    FROM transactions
    LEFT JOIN drivers ON transactions.driver_id = drivers.id
    LEFT JOIN passengers ON transactions.passenger_id = passengers.id
    WHERE transactions.passenger_id = ? AND transactions.is_active = 0
    ORDER BY transactions.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $passenger_id);
$stmt->execute();

$result = $stmt->get_result();
$transactions = [];

while ($row = $result->fetch_assoc()) {
    // Format created_at
    $createdAt = new DateTime($row['created_at']);
    $formattedDate = $createdAt->format('F j, Y'); // e.g., August 28, 2024
    $formattedTime = $createdAt->format('g:i A'); // e.g., 11:24 AM

    // Get day of the week
    $dayOfWeek = $createdAt->format('D'); // e.g., Mon, Tue, Wed

    // Check if the date is today
    $today = (new DateTime())->format('Y-m-d');
    $rowDate = $createdAt->format('Y-m-d');
    $dateSuffix = ($today === $rowDate) ? '(Today)' : '('.$dayOfWeek.')';

    $row['created_at'] = $formattedDate . ' ' . $dateSuffix . ' ' . $formattedTime;

    $transactions[] = $row;
}

// Check if no transactions were found
if (empty($transactions)) {
    echo json_encode(["status" => "error", "message" => "No transactions available."]);
} else {
    echo json_encode(["status" => "success", "transactions" => $transactions]);
}

$conn->close();
?>
