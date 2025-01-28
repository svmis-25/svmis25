<?php  
// Connect to the database
require_once __DIR__ . '/../config.php';

// Fetch companies from the database using prepared statement
$sql = "SELECT id, company_name FROM ref_companies";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    die("Failed to prepare the SQL statement: " . $conn->error);
}

$stmt->execute();

$result = $stmt->get_result();

$companies = array();

while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}

// Convert the array to JSON
header('Content-Type: application/json');
echo json_encode($companies);

// Close the statement and connection
$stmt->close();
$conn->close();
?>

