<?php
header('Content-Type: application/json');
// Connect to the database
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = $data['email'];
    $lastname = $data['lastname'];
    $firstname = $data['firstname'];
    $middlename = !empty($data['middlename']) ? $data['middlename'] : '';
    $qualifier = !empty($data['qualifier']) ? $data['qualifier'] : '';
    $contact = $data['contact'];
    $address = $data['address'];
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    
    if (empty($email) || empty($lastname) || empty($firstname) || empty($contact) || empty($address) || empty($password)) {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'All required fields must be filled out.']);
        exit();
    }

    $sql = "INSERT INTO passengers (email, lastname, firstname, middlename, qualifier, contact, address, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ssssssss', $email, $lastname, $firstname, $middlename, $qualifier, $contact, $address, $password);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Signup successful']);
        } else {
            echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Invalid request method.']);
}
?>
