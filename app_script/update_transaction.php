<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$data = json_decode(file_get_contents("php://input"));

// Get parameters from URL
$driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : '';
$passenger_id = isset($_GET['passenger_id']) ? $_GET['passenger_id'] : '';
$transaction_no = isset($_GET['transaction_no']) ? $_GET['transaction_no'] : '';

$sql = "UPDATE transactions SET driver_id = ? WHERE transaction_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $driver_id, $transaction_no);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Transaction updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update transaction"]);
}

$stmt->close();
$conn->close();
?>
