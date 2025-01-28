<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Retrieve passenger_id and luggage_code from GET request
$passenger_id = isset($_GET['passenger_id']) ? $_GET['passenger_id'] : null;
$luggage_code = isset($_GET['luggage_code']) ? $_GET['luggage_code'] : null;

// Validate inputs
if (!$passenger_id) {
    echo json_encode(["status" => "error", "message" => "Missing passenger_id."]);
    exit;
}

if (!$luggage_code) {
    echo json_encode(["status" => "error", "message" => "Missing luggage_code."]);
    exit;
}

// Sanitize the input to avoid SQL injection
$passenger_id = intval($passenger_id);  // Ensure passenger_id is an integer
$luggage_code = htmlspecialchars($luggage_code, ENT_QUOTES, 'UTF-8'); // Sanitize luggage_code

// SQL query with proper joins to fetch required fields
$sql = "
    SELECT 
        passengers.firstname AS passenger_firstname,
        passengers.lastname AS passenger_lastname,
        passengers.middlename AS passenger_middlename,
        passengers.qualifier AS passenger_qualifier,
        passengers.image_filename AS image_filename,
        passengers_luggage.passenger_id AS luggage_passengerId,
        passengers_luggage.luggage_code AS luggage_code,
        passengers_luggage.description AS description,
        passengers_luggage.size AS size,
        passengers_luggage.status AS status,
        passengers_luggage.created_at AS created_at
    FROM passengers_luggage
    LEFT JOIN passengers ON passengers_luggage.passenger_id = passengers.id
    WHERE passengers_luggage.passenger_id = ? AND passengers_luggage.luggage_code = ?
";

// Prepare the SQL statement
$stmt = $conn->prepare($sql);

// Check if the prepared statement was successful
if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "Database error: Failed to prepare query."]);
    exit;
}

// Bind the parameters
$stmt->bind_param("is", $passenger_id, $luggage_code);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Initialize an array to hold the transactions
$transactions = [];

while ($row = $result->fetch_assoc()) {
    // Format the created_at timestamp
    $createdAt = new DateTime($row['created_at']);
    $formattedDate = $createdAt->format('F j, Y'); // Example: August 28, 2024
    $formattedTime = $createdAt->format('g:i A'); // Example: 11:24 AM
    $dayOfWeek = $createdAt->format('D'); // Example: Mon, Tue, Wed

    // Check if the date is today
    $today = (new DateTime())->format('Y-m-d');
    $rowDate = $createdAt->format('Y-m-d');
    $dateSuffix = ($today === $rowDate) ? '(Today)' : '('.$dayOfWeek.')';

    // Add formatted date and time to the row
    $row['created_at'] = $formattedDate . ' ' . $dateSuffix . ' ' . $formattedTime;

    // Construct the image URL with a placeholder fallback
    $row['image_url'] = isset($row['image_filename']) ? 'images/profile/passenger/' . $row['image_filename'] : 'images/profile/passenger/placeholder.jpg';

    // Add the row to the transactions array
    $transactions[] = $row;
}

// Check if no transactions were found
if (empty($transactions)) {
    echo json_encode(["status" => "error", "message" => "No transactions available for this passenger."]);
} else {
    echo json_encode(["status" => "success", "luggage" => $transactions]);
}

// Close the prepared statement and database connection
$stmt->close();
$conn->close();

// // Enable error reporting and logging
// error_reporting(E_ALL);
// ini_set('display_errors', 0); // Disable error display
// ini_set('log_errors', 1); // Enable error logging
// ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

// header('Content-Type: application/json');
// require_once __DIR__ . '/../config.php';

// // Retrieve passenger_id and luggage_code from GET request
// $passenger_id = isset($_GET['passenger_id']) ? $_GET['passenger_id'] : null;
// $luggage_code = isset($_GET['luggage_code']) ? $_GET['luggage_code'] : null;

// if (!$passenger_id) {
//     echo json_encode(["status" => "error", "message" => "Missing passenger_id."]);
//     exit;
// }

// if (!$luggage_code) {
//     echo json_encode(["status" => "error", "message" => "Missing luggage_code."]);
//     exit;
// }

// // Updated SQL query to include proper table joins and fetch required fields
// $sql = "
//     SELECT 
//         passengers.firstname AS passenger_firstname,
//         passengers.lastname AS passenger_lastname,
//         passengers.middlename AS passenger_middlename,
//         passengers.qualifier AS passenger_qualifier,
//         passengers.image_filename AS image_filename,
//         passengers_luggage.passenger_id AS luggage_passengerId,
//         passengers_luggage.luggage_code AS luggage_code,
//         passengers_luggage.description AS description,
//         passengers_luggage.size AS size,
//         passengers_luggage.status AS status,
//         passengers_luggage.created_at AS created_at
//     FROM passengers_luggage
//     LEFT JOIN passengers ON passengers_luggage.passenger_id = passengers.id
//     WHERE passengers_luggage.passenger_id = ? AND passengers_luggage.luggage_code = ?
// ";

// // Prepare and execute the SQL query
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("is", $passenger_id, $luggage_code); // Use "i" for integer (passenger_id) and "s" for string (transaction_no)
// $stmt->execute();
// $result = $stmt->get_result();

// // Initialize array to hold transaction data
// $transactions = [];

// while ($row = $result->fetch_assoc()) {
//     // Format the date and time from the created_at field
//     $createdAt = new DateTime($row['created_at']);
//     $formattedDate = $createdAt->format('F j, Y'); // Example: August 28, 2024
//     $formattedTime = $createdAt->format('g:i A'); // Example: 11:24 AM
//     $dayOfWeek = $createdAt->format('D'); // Example: Mon, Tue, Wed

//     // Check if the date is today
//     $today = (new DateTime())->format('Y-m-d');
//     $rowDate = $createdAt->format('Y-m-d');
//     $dateSuffix = ($today === $rowDate) ? '(Today)' : '('.$dayOfWeek.')';

//     // Add formatted date and time to the result
//     $row['created_at'] = $formattedDate . ' ' . $dateSuffix . ' ' . $formattedTime;

//     // Construct the image URL
//     $row['image_url'] = isset($row['image_filename']) ? 'images/profile/passenger/' . $row['image_filename'] : 'images/profile/passenger/placeholder.jpg';

//     // Append the row to the transactions array
//     $transactions[] = $row;
// }

// // Check if no transactions were found
// if (empty($transactions)) {
//     echo json_encode(["status" => "error", "message" => "No transactions available for this passenger." . $stmt->error]);
// } else {
//     echo json_encode(["status" => "success", "luggage" => $transactions]);
// }

// // Close the database connection
// $conn->close();
?>
