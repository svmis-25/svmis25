<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid input data"]);
    exit;
}

$driver_id = ""; // Initialize with an empty string
$passenger_id = $data->passenger_id ?? null;
$transaction_no = $data->transaction_no ?? null;
$origin = $data->origin ?? null;
$destination = $data->destination ?? null;
$estimated_time_of_arrival = $data->estimated_time_of_arrival ?? null;
$distance = $data->distance ?? null;
$qr_code = $data->qr_code ?? null;

if (!$passenger_id || !$transaction_no || !$origin || !$destination || !$estimated_time_of_arrival || !$qr_code) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$sql = "INSERT INTO transactions (passenger_id, origin, destination, estimated_time_of_arrival, distance, qr_code, driver_id, transaction_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssis", $passenger_id, $origin, $destination, $estimated_time_of_arrival, $distance, $qr_code, $driver_id, $transaction_no);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Transaction created successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to create transaction"]);
}

$stmt->close();
$conn->close();
?>
