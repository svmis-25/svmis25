<?php
header('Content-Type: application/json');

require_once __DIR__ . '/vendor/autoload.php'; // Load Composer's autoloader

// require config file
require_once __DIR__ . '/../config.php';

// Load .env file using vlucas/phpdotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$baseURL = $_ENV['BASE_URL'];

// Get the JSON input
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];

// Validate input
if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

// Check if the email exists
$stmt = $conn->prepare("SELECT id FROM drivers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email not found']);
    exit;
}

// Generate a unique token
$token = bin2hex(random_bytes(50));

// Store the token in the database
$stmt = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $token);
$stmt->execute();

// Send the password reset email
$resetLink = "$baseURL/app_script/reset_password.php?token=$token";
$subject = "Password Reset Request";
$message = "To reset your password, please click the following link: $resetLink";
$headers = "From: no-reply@svmis.com\r\n";

if (mail($email, $subject, $message, $headers)) {
    echo json_encode(['status' => 'success', 'message' => 'Password reset email sent']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send email']);
}

$stmt->close();
$conn->close();
?>
