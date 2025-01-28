<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php'; // This should include the database connection

$response = ['status' => 'error', 'message' => 'Unknown error occurred'];

// Log incoming request data
error_log('Debug: Request Method = ' . $_SERVER['REQUEST_METHOD']);
error_log('Debug: Driver ID = ' . (isset($_GET['driver_id']) ? $_GET['driver_id'] : 'Not set'));

// Check if driver ID is provided
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['driver_id'])) {
    $driverId = intval($_GET['driver_id']); // Sanitize driver ID
    $targetDir = __DIR__ . '/../app_script/images/profile/driver/';
    
    // Fetch driver details from the database
    global $conn; // Assuming $conn is the database connection variable in config.php

    $sql = "SELECT firstname, lastname FROM drivers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $driverId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response['message'] = 'Driver not found.';
        error_log('Debug: Driver not found with ID = ' . $driverId);
        echo json_encode($response);
        exit();
    }

    $driver = $result->fetch_assoc();
    $firstname = $driver['firstname'];
    $lastname = $driver['lastname'];

    // Create a custom filename
    $fileName = $driverId . '-' . $firstname . $lastname . '.' . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $targetFile = $targetDir . $fileName;

    // Ensure the directory exists
    if (!file_exists($targetDir)) {
        if (mkdir($targetDir, 0777, true)) {
            error_log('Debug: Directory created successfully: ' . $targetDir);
        } else {
            error_log('Debug: Failed to create directory: ' . $targetDir);
        }
    }

    // Move the uploaded file
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
        // Update the drivers record with the new image filename
        $updateSql = "UPDATE drivers SET image_filename = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('si', $fileName, $driverId);
        
        if ($updateStmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'File uploaded and database updated successfully.';
            $response['file'] = $targetFile;
        } else {
            $response['message'] = 'File uploaded, but database update failed.';
            error_log('Debug: Database update failed for driver ID = ' . $driverId);
        }
    } else {
        $response['message'] = 'Error moving uploaded file.';
        $response['file_tmp_name'] = $_FILES['profile_picture']['tmp_name']; // Debug info
        $response['target_file'] = $targetFile; // Debug info
        error_log('Debug: Error moving file from ' . $_FILES['profile_picture']['tmp_name'] . ' to ' . $targetFile);
    }
} else {
    $response['message'] = 'Invalid request. Driver ID missing.';
    error_log('Debug: Invalid request. Driver ID missing.');
}

echo json_encode($response);
?>
