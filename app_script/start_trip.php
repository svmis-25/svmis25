<?php
// // Enable error reporting and logging
// error_reporting(E_ALL);
// ini_set('display_errors', 0); // Disable error display
// ini_set('log_errors', 1); // Enable error logging
// ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

// header('Content-Type: application/json');
// require_once __DIR__ . '/../config.php';

// // Get parameters from URL
// $driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : '';

// // Check if driver_id is provided
// if (empty($driver_id)) {
//     echo json_encode(["status" => "error", "message" => "Driver ID is required"]);
//     exit();
// }

// // Check if trip ID is already set for this driver
// $check_sql = "SELECT trip_id FROM transactions WHERE driver_id = ? LIMIT 1";
// $check_stmt = $conn->prepare($check_sql);
// $check_stmt->bind_param("i", $driver_id);
// $check_stmt->execute();
// $check_stmt->bind_result($trip_id);
// $check_stmt->fetch();
// $check_stmt->close();

// // If trip_id is null, proceed to start the trip
// if (is_null($trip_id)) {
//     $new_departure_time = date("Y-m-d H:i:s");

//     // Set the default timezone to Manila
//     date_default_timezone_set('Asia/Manila');

//     // Function to generate unique trip_id
//     function generateUniqueTripId($conn, $driver_id) {
//         do {
//             $currentDate = date('mdY'); // MMDDYYYY format
//             $randomDigits = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT); // Random 4-digit number
//             $trip_id = $driver_id . "-" . $currentDate . $randomDigits;

//             // Check for uniqueness in the database
//             $sql = "SELECT COUNT(*) FROM transactions WHERE trip_id = ?";
//             $stmt = $conn->prepare($sql);
//             $stmt->bind_param("s", $trip_id);
//             $stmt->execute();
//             $stmt->bind_result($count);
//             $stmt->fetch();
//             $stmt->close();
//         } while ($count > 0); // Repeat if trip_id already exists

//         return $trip_id; // Return unique trip_id
//     }

//     // Generate unique trip_id
//     $trip_id = generateUniqueTripId($conn, $driver_id);

//     // Prepare SQL to update the departure time for the driver
//     $update_sql = "UPDATE transactions SET departure_time = ?, trip_id = ? WHERE driver_id = ?";
//     $update_stmt = $conn->prepare($update_sql);
//     $update_stmt->bind_param("ssi", $new_departure_time, $trip_id, $driver_id);

//     if ($update_stmt->execute()) {
//         echo json_encode([
//             "status" => "success", 
//             "message" => "Trip status updated successfully!",
//             "trip_id" => $trip_id
//         ]);
//     } else {
//         echo json_encode(["status" => "error", "message" => "Failed to update trip status"]);
//     }
//     $update_stmt->close();
//     $conn->close();
//     exit();
// }

// // If trip_id is not null, return an error message
// $departure_time_obj = new DateTime($departure_time);

// // Format the date and time
// $formattedDate = $departure_time_obj->format('F j, Y');
// $formattedTime = $departure_time_obj->format('g:i A');

// // Get the day of the week
// $dayOfWeek = $departure_time_obj->format('D');

// // Check if the date is today
// $today = (new DateTime())->format('Y-m-d');
// $rowDate = $departure_time_obj->format('Y-m-d');
// $dateSuffix = ($today === $rowDate) ? '(Today)' : '('.$dayOfWeek.')';

// // Construct the final string
// $departure_time_formatted = $formattedDate . ' ' . $dateSuffix . ' ' . $formattedTime;

// echo json_encode([
//     "status" => "error", 
//     "message" => "Trip already started at $departure_time_formatted",
//     "trip_id" => $trip_id
// ]);
// exit();

// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Get parameters from URL
$driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : '';

// Check if driver_id is provided
if (empty($driver_id)) {
    echo json_encode(["status" => "error", "message" => "Driver ID is required"]);
    exit();
}

// Check if there is an active trip for this driver
$check_sql = "SELECT trip_id, departure_time FROM transactions WHERE driver_id = ? AND is_complete = 0 LIMIT 1";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $driver_id);
$check_stmt->execute();
$check_stmt->bind_result($trip_id, $departure_time);
$check_stmt->fetch();
$check_stmt->close();

// If trip_id is null, proceed to start the trip
if (is_null($trip_id)) {
    $new_departure_time = date("Y-m-d H:i:s");

    // Set the default timezone to Manila
    date_default_timezone_set('Asia/Manila');

    // Function to generate unique trip_id
    function generateUniqueTripId($conn, $driver_id) {
        do {
            $currentDate = date('mdY'); // MMDDYYYY format
            $randomDigits = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT); // Random 4-digit number
            $trip_id = $driver_id . "-" . $currentDate . $randomDigits;

            // Check for uniqueness in the database
            $sql = "SELECT COUNT(*) FROM transactions WHERE trip_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $trip_id);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
        } while ($count > 0); // Repeat if trip_id already exists

        return $trip_id; // Return unique trip_id
    }

    // Generate unique trip_id
    $trip_id = generateUniqueTripId($conn, $driver_id);

    // Prepare SQL to update the existing trip's departure time and trip_id
    $update_sql = "UPDATE transactions SET departure_time = ?, trip_id = ? WHERE driver_id = ? AND is_complete = 0";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $new_departure_time, $trip_id, $driver_id);

    if ($update_stmt->execute()) {
        echo json_encode([
            "status" => "success", 
            "message" => "Trip started successfully!",
            "trip_id" => $trip_id
        ]);

        // Update the driver's status to 'On Trip'
        $update_driver_sql = "UPDATE drivers SET status = 'On Trip' WHERE id = ?";
        $update_driver_stmt = $conn->prepare($update_driver_sql);
        $update_driver_stmt->bind_param("i", $driver_id);
        $update_driver_stmt->execute();
        $update_driver_stmt->close();

        // Update the vehicle's status to 'On Trip' --- Not working
        $update_vehicle_sql = "UPDATE vans SET status = 'On Trip' WHERE driver_id = ?";
        $update_vehicle_stmt = $conn->prepare($update_vehicle_sql);
        $update_vehicle_stmt->bind_param("i", $driver_id);
        $update_vehicle_stmt->execute();
        $update_vehicle_stmt->close();

        // Update the luggage Trip ID -- Not working
        $update_luggage_sql = "UPDATE passengers_luggage SET trip_id = ? SET status = 'In-transit' WHERE driver_id = ? AND trip_id = 0";
        $update_luggage_stmt = $conn->prepare($update_luggage_sql);
        $update_luggage_stmt->bind_param("si", $trip_id, $driver_id);
        $update_luggage_stmt->execute();
        $update_luggage_stmt->close();

    } else {
        echo json_encode(["status" => "error", "message" => "Failed to start trip"]);
    }
    $update_stmt->close();
} else {
    // If trip_id is not null, return an error message with formatted departure time
    $departure_time_obj = new DateTime($departure_time);

    // Format the date and time
    $formattedDate = $departure_time_obj->format('F j, Y');
    $formattedTime = $departure_time_obj->format('g:i A');

    // Get the day of the week
    $dayOfWeek = $departure_time_obj->format('D');

    // Check if the date is today
    $today = (new DateTime())->format('Y-m-d');
    $rowDate = $departure_time_obj->format('Y-m-d');
    $dateSuffix = ($today === $rowDate) ? '(Today)' : '('.$dayOfWeek.')';

    // Construct the final string
    $departure_time_formatted = $formattedDate . ' ' . $dateSuffix . ' ' . $formattedTime;

    echo json_encode([
        "status" => "error", 
        "message" => "Trip already started at $departure_time_formatted",
        "trip_id" => $trip_id
    ]);
}

$conn->close();
?>
