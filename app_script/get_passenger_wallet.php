<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Get the passenger_id from the URL (ensure it's set)
$passenger_id = isset($_GET['passenger_id']) ? $_GET['passenger_id'] : '';

// Validate and sanitize passenger_id
$passenger_id = intval($passenger_id); // Sanitize to ensure it's an integer

// Check if passenger_id is valid
if ($passenger_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid passenger ID"]);
    exit();
}

// Prepare and execute the query
$sql = "SELECT * FROM passenger_wallet WHERE passenger_id = ?";
$stmt = $conn->prepare($sql);

// Check if statement preparation was successful
if ($stmt) {
    $stmt->bind_param("i", $passenger_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $passenger = $result->fetch_assoc();

    // Check if a passenger record was found
    if ($passenger) {
        $response = [
            "status" => "success",
            "balance" => $passenger['amount'] // Assuming 'amount' is the column holding the balance
        ];
    } else {
        $response = [
            "status" => "error",
            "message" => "You have zero balance as of this date."
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

$conn->close(); // Close the connection

// Return the response
echo json_encode($response);

// header('Content-Type: application/json');
// require_once __DIR__ . '/../config.php';

// $passenger_id = $_GET['passenger_id'];

// // Prepare and execute the query
// $sql = "SELECT * FROM passenger_wallet WHERE passenger_id = ?";
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("i", $passenger_id);
// $stmt->execute();

// $result = $stmt->get_result();
// $passenger = $result->fetch_assoc();

// // Check if a passenger record was found
// if ($passenger) {
//     $response = [
//         "status" => "success",
//         "balance" => $passenger['amount'] // Assuming 'amount' is the column holding the balance
//     ];
// } else {
//     $response = [
//         "status" => "error",
//         "message" => "You have zero balance as of this date."
//     ];
// }

// echo json_encode($response);
// $conn->close();
?>
