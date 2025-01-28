<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$data = json_decode(file_get_contents("php://input"), true);

// Get parameters from the request
$transaction_no = isset($data['transaction_no']) ? $data['transaction_no'] : '';

if (empty($transaction_no)) {
    echo json_encode(["status" => "error", "message" => "Transaction number is required"]);
    exit();
}

// Get the current timestamp for arrival_time
$arrival_time = date("Y-m-d H:i:s");

// SQL to update the transaction as complete
$sql = "UPDATE transactions SET is_complete = 1, is_active = 0, arrival_time = ? WHERE transaction_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $arrival_time, $transaction_no);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Transaction completed successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to complete the transaction"]);
}

$stmt->close();
$conn->close();
?>
