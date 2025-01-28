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

// Check if the passenger exists
$sql = "SELECT * FROM passengers WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $passenger = $result->fetch_assoc();
    // Verify the password
    if (password_verify($password, $passenger['password'])) {
        // Generate a token
        $token = generateToken();
        $passenger_id = $passenger['id'];
        
        // Save the token in the database
        $sql = "INSERT INTO passenger_tokens (passenger_id, token) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $passenger_id, $token);
        $stmt->execute();

        // Update the date_login field
        $sql = "UPDATE passengers SET date_login = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $passenger_id);
        $stmt->execute();
        
        // Login successful
        echo json_encode([
            "status" => "success", 
            "message" => "Login successful", 
            "token" => $token,  // Return the token to the client
            "passenger_id" => $passenger['id'], 
            "firstname" => $passenger['firstname'], 
            "lastname" => $passenger['lastname'],
            "role_id" => $passenger['role_id']
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
