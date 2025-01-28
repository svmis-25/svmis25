<?php
header('Content-Type: application/json');
// require config file
require_once __DIR__ . '/../config.php';

// Generate a unique token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Get the JSON data from the request
$data = json_decode(file_get_contents("php://input"));

$email = $data->email;
$password = $data->password;

// Check if the driver exists
$sql = "SELECT * FROM drivers WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $driver = $result->fetch_assoc();
    // Verify the password
    if (password_verify($password, $driver['password'])) {
        // Generate a token
        $token = generateToken();
        $driver_id = $driver['id'];
        
        // Save the token in the database
        $sql = "INSERT INTO driver_tokens (driver_id, token) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $driver_id, $token);
        $stmt->execute();

        // Update the date_login field
        $sql = "UPDATE drivers SET date_login = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        
        // Login successful
        echo json_encode([
            "status" => "success", 
            "message" => "Login successful", 
            "token" => $token,  // Return the token to the client
            "driver_id" => $driver['id'], 
            "firstname" => $driver['firstname'], 
            "lastname" => $driver['lastname'],
            "role_id" => $driver['role_id']
        ]);
    } else {
        // Invalid password
        echo json_encode(["status" => "error", "message" => "Invalid password"]);
    }
} else {
    // Invalid email
    echo json_encode(["status" => "error", "message" => "Invalid email"]);
}

$conn->close();
?>
