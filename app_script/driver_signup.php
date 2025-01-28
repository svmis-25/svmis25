<?php
header('Content-Type: application/json');
// Connect to the database
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Sanitize inputs to prevent XSS
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $lastname = htmlspecialchars($data['lastname'], ENT_QUOTES, 'UTF-8');
    $firstname = htmlspecialchars($data['firstname'], ENT_QUOTES, 'UTF-8');
    $middlename = !empty($data['middlename']) ? htmlspecialchars($data['middlename'], ENT_QUOTES, 'UTF-8') : '';
    $qualifier = !empty($data['qualifier']) ? htmlspecialchars($data['qualifier'], ENT_QUOTES, 'UTF-8') : '';
    $contact = htmlspecialchars($data['contact'], ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($data['address'], ENT_QUOTES, 'UTF-8');
    $company_id = $data['company_id'];
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    
    // Validate required fields
    if (empty($email) || empty($lastname) || empty($firstname) || empty($contact) || empty($address) || empty($company_id) || empty($password)) {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'All required fields must be filled out.']);
        exit();
    }

    // SQL to insert data
    $sql = "INSERT INTO drivers (email, lastname, firstname, middlename, qualifier, contact, address, company_id, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters to the statement
        $stmt->bind_param('sssssssss', $email, $lastname, $firstname, $middlename, $qualifier, $contact, $address, $company_id, $password);
        
        // Execute and return response
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

// header('Content-Type: application/json');
// // Connect to the database
// require_once __DIR__ . '/../config.php';

// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     $data = json_decode(file_get_contents('php://input'), true);
    
//     $email = $data['email'];
//     $lastname = $data['lastname'];
//     $firstname = $data['firstname'];
//     $middlename = !empty($data['middlename']) ? $data['middlename'] : '';
//     $qualifier = !empty($data['qualifier']) ? $data['qualifier'] : '';
//     $contact = $data['contact'];
//     $address = $data['address'];
//     $company_id = $data['company_id'];
//     $password = password_hash($data['password'], PASSWORD_BCRYPT);
    
//     if (empty($email) || empty($lastname) || empty($firstname) || empty($contact) || empty($address) || empty($company_id) || empty($password)) {
//         echo json_encode(['success' => false, 'status' => 'error', 'message' => 'All required fields must be filled out.']);
//         exit();
//     }

//     $sql = "INSERT INTO drivers (email, lastname, firstname, middlename, qualifier, contact, address, company_id, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
//     if ($stmt = $conn->prepare($sql)) {
//         $stmt->bind_param('sssssssss', $email, $lastname, $firstname, $middlename, $qualifier, $contact, $address, $company_id, $password);
        
//         if ($stmt->execute()) {
//             echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Signup successful']);
//         } else {
//             echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
//         }
        
//         $stmt->close();
//     } else {
//         echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Database error: ' . $conn->error]);
//     }

//     $conn->close();
// } else {
//     echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Invalid request method.']);
// }
?>
