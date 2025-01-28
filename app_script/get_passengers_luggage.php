<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Retrieve the passenger ID from the query string
$passenger_id = isset($_GET['passenger_id']) ? intval($_GET['passenger_id']) : null;

$response = ['status' => 'error', 'data' => null, 'message' => ''];

if ($passenger_id === null) {
    $response['message'] = 'Invalid passenger ID';
    echo json_encode($response);
    exit;
}

// Update the SQL query to filter by status
$sql = "SELECT luggage_code, description, status FROM passengers_luggage WHERE passenger_id = ? AND status = 'Pending Verification' OR status = 'Checked-in' ORDER BY id DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $passenger_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $luggageList = [];

        while ($row = $result->fetch_assoc()) {
            $luggageList[] = $row;
        }

        if (empty($luggageList)) {
            $response['message'] = 'No luggage found for the provided passenger ID with pending verification status.';
        } else {
            $response['status'] = 'success';
            $response['data'] = $luggageList;
            $response['message'] = 'Luggage retrieved successfully.';
        }
    } else {
        $response['message'] = 'Execution of SQL statement failed: ' . $stmt->error;
    }
    $stmt->close();
} else {
    $response['message'] = 'Failed to prepare SQL statement: ' . $conn->error;
}

$conn->close();
echo json_encode($response);
?>
