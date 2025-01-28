<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Retrieve passenger_id from GET request
$passenger_id = $_GET['passenger_id'];

// Validate if passenger_id is provided
if (!$passenger_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Passenger id not found."
    ]);
    exit;
}

// Prepare SQL query to fetch top-up history for the passenger
$sql = "SELECT * FROM passenger_topup WHERE passenger_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $passenger_id);
$stmt->execute();
$result = $stmt->get_result();

$topups = [];
while ($row = $result->fetch_assoc()) {
    // Format the date and time from the created_at field
    $createdAt = new DateTime($row['created_at']);
    $formattedDate = $createdAt->format('F j, Y'); // Example: August 28, 2024
    $formattedTime = $createdAt->format('g:i A'); // Example: 11:24 AM
    $dayOfWeek = $createdAt->format('D'); // Example: Mon, Tue, Wed

    // Check if the date is today
    $today = (new DateTime())->format('Y-m-d');
    $rowDate = $createdAt->format('Y-m-d');
    $dateSuffix = ($today === $rowDate) ? '(Today)' : '('.$dayOfWeek.')';

    // Add formatted date and time to the result
    $row['created_at'] = $formattedDate . ' ' . $dateSuffix . ' ' . $formattedTime;

    // Append the row to the topups array
    $topups[] = $row;
}

// Check if top-up history exists
if (empty($topups)) {
    echo json_encode([
        "status" => "error",
        "message" => "No top-up history found."
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "topups" => $topups
    ]);
}

$conn->close();
?>