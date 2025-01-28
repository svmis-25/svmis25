<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php'; // Ensure this path is correct

// Retrieve and decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Retrieve and sanitize inputs
$passenger_id = isset($data['passenger_id']) ? intval($data['passenger_id']) : 0;
$topup_code = isset($data['topup_code']) ? trim($data['topup_code']) : '';

if ($passenger_id <= 0 || empty($topup_code)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input parameters.'
    ]);
    exit;
}

// Check if the top-up code exists and is valid
$sql = "SELECT * FROM topup_codes WHERE code = ? AND used = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $topup_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired top-up code.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$topup_code_data = $result->fetch_assoc();
$amount = $topup_code_data['amount'];

// Mark the top-up code as used
$update_code_sql = "UPDATE topup_codes SET used = 1 WHERE code = ?";
$update_stmt = $conn->prepare($update_code_sql);
$update_stmt->bind_param("s", $topup_code);
$update_stmt->execute();
$update_stmt->close();

// Add the amount to the passenger's wallet balance
$update_balance_sql = "INSERT INTO passenger_wallet (passenger_id, amount, created_at) VALUES (?, ?, NOW())
                        ON DUPLICATE KEY UPDATE amount = amount + VALUES(amount)";
$update_balance_stmt = $conn->prepare($update_balance_sql);
$update_balance_stmt->bind_param("id", $passenger_id, $amount);
$update_balance_stmt->execute();
$update_balance_stmt->close();

// Add the top-up details to the passenger's top-up records
$record_topup_sql = "INSERT INTO passenger_topup (passenger_id, topup_code, amount, created_at) VALUES (?, ?, ?, NOW())";
$record_topup_stmt = $conn->prepare($record_topup_sql);
$record_topup_stmt->bind_param("isd", $passenger_id, $topup_code, $amount);
$record_topup_stmt->execute();
$record_topup_stmt->close();

echo json_encode([
    'status' => 'success',
    'message' => 'Wallet topped up successfully!'
]);

$conn->close();
?>