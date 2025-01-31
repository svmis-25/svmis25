<?php 
ob_start();
session_start();
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path
global $active; 
// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index");
    exit;
} elseif ($_SESSION["userrole"] != 1){
  header("Location: errors/403");
  exit;
}

$current_user_id = $_SESSION["userID"];
$current_user_role_id = $_SESSION["userrole"];

$active = "modules";
// require config file
require_once __DIR__ . '/config.php';

// Include composer autoloader
require 'vendor/autoload.php';

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\RoundBlockSizeMode;

/*========================================
  Function to generate transaction number
=========================================*/
function generateTransactionNo() {
    // Set the timezone to Manila
    date_default_timezone_set('Asia/Manila');
    
    // Get the current date and time in Manila
    $currentDateTime = new DateTime();
    
    // Format the timestamp as "YYYYMMDDHHMMSS"
    $timestamp = $currentDateTime->format('YmdHis');
    
    // Generate random digits (from 1000 to 9999) for uniqueness
    $randomDigits = mt_rand(1000, 9999);
    
    // Combine the timestamp and random digits
    $transactionNo = $timestamp . $randomDigits;
    
    return $transactionNo;
}

function generateQRCode($transactionNo, $passenger_id, $origin, $destination) {
    // Create QR code
    $qrCode = new QrCode(
        data: "$transactionNo-$passenger_id-$origin-$destination",
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::Low,
        size: 250,
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin,
        foregroundColor: new Color(0, 0, 0), // Black color for QR code blocks
        backgroundColor: new Color(255, 255, 255) // White background
    );

    // Specify the path to the logo image (make sure the file exists)
    $logoPath = __DIR__ . '/assets/images/banner.png'; // Replace with your actual logo file path

    // Create the logo instance if the file exists
    $logo = null;
    if (file_exists($logoPath)) {
        $logo = new Logo(
            path: $logoPath,
            resizeToWidth: 50, // Resize the logo to fit the QR code
            punchoutBackground: true // This removes the background color of the logo
        );
    }

    // Create the writer instance
    $writer = new PngWriter();

    // Generate the QR code with the logo
    $result = $writer->write($qrCode, $logo);

    // Convert the QR code to a base64 string
    $base64QrCode = base64_encode($result->getString());
    return $base64QrCode;
}

/*===============================
  Function to sanitize user input
=================================*/

function sanitizeInput($input){
    $input = trim($input); //Remove whitespaces from the beginning and end of input
    $input = strip_tags($input); // Remove tags
    $input = stripslashes($input); // Remove backslashes
    $input = htmlspecialchars($input); // Convert special characters to HTML entities to prevent XSS attacks

    return $input;
}

/*===============================
  Function to fetch user data
=================================*/

if (isset($_GET['id'])){
    $id = intval($_GET['id']);

    // Fetch the existing data for the user
    $sql = "SELECT firstname, middlename, lastname, qualifier, contact, email, role_id FROM passengers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1){
        $user = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "Passenger not found!";
        header("Location: passengerManagement");
        exit;
    }

    $stmt->close();
} else {
    // echo "Invalid parameters!";
    // Redirect to index after form submission
    header("Location: passengerManagement");
    exit;
}

/*===============================
  Function to create transaction
=================================*/

if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['submitBtn'])) {
    $driver_id = ""; // Initialize with an empty string
    $passenger_id = intval($_POST['id']);
    $transactionNo = generateTransactionNo();
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $estimated_time_of_arrival = $_POST['eta'];
    $distance = $_POST['distance'];
    // $qr_code = generateQRCode($transactionNo, $passenger_id, $origin, $destination);
    $qr_code = $transactionNo . "-" . $passenger_id . "-" . $origin . "-" . $destination;

    // Validate inputs
    $origin = sanitizeInput($origin);
    $destination = sanitizeInput($destination);
    $estimated_time_of_arrival = sanitizeInput($estimated_time_of_arrival);
    $distance = sanitizeInput($distance);

    if (empty($origin) || empty($destination) || empty($estimated_time_of_arrival) || empty($distance)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        exit;
    }

    // Insert the transaction data into the database
    $sql = "INSERT INTO transactions (passenger_id, origin, destination, estimated_time_of_arrival, distance, qr_code, driver_id, transaction_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssis", $passenger_id, $origin, $destination, $estimated_time_of_arrival, $distance, $qr_code, $driver_id, $transactionNo);

    if ($stmt->execute()) {
        $_SESSION['success-transaction'] = "Transaction created successfully!";
    } else {
        echo "Failed to create transaction. Please try again.: $stmt->error";
    }

    // display the QR code
    $qrCode = generateQRCode($transactionNo, $passenger_id, $origin, $destination);
    $_SESSION['qr_code'] = $qrCode;
    
    $stmt->close();
    // $conn->close();
    // header("Location: passengerManagement");
    // exit;

}

// Check if there's a success message in session and display it
if (isset($_SESSION['qr_code'])) {
    $qr_code = $_SESSION['qr_code'];
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#qrCodeModal').modal('show');
        });
    </script>";
    unset($_SESSION['qr_code']); // Clear session data
}

// Close the database connection
$conn->close();
ob_end_flush();

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$google_maps_api_key = $_ENV['GOOGLE_MAPS_JAVASCRIPT_API_KEY']; // Fetch from .env
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distance and ETA Calculator</title>
    <!-- Favicon tab icon-->
    <link rel="icon" href="assets/images/banner.png" type="image/x-icon">

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($google_maps_api_key); ?>&libraries=places"></script>
    <style>
      main > .container {
        padding: 10px 15px 0;
        margin-left: 10px;
      }

      .errors {
        color: red;
        font-size: 0.875em; /* 14px/16=0.875em */
      }

      .footer {
        background-color: #f5f5f5;
      }

      .footer > .container {
        padding-right: 15px;
        padding-left: 15px;
      }

      .hidden {
        display: none;
      }

      code {
        font-size: 80%;
      }
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }
      .more-info {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
      }
    </style>
</head>
<body lass="d-flex flex-column h-100">
<header>
   <?php include "partials/nav.php"; ?>

   <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item active" aria-current="page"><h3><?php echo $active; ?></h3></li>
    </ol>
  </nav>
</header>
<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Make Transaction</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-primary mb-5">
      <div class="card-header" style="color: white; background-color: #007BFF;">
        <strong>Add Booking for Passenger</strong>
      </div>
      <div class="card-body">
        <form id="distanceForm" action="" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="origin">Origin:</label>
                        <input type="text" id="origin" name="origin" placeholder="Enter origin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="destination">Destination:</label>
                        <input type="text" id="destination" name="destination" placeholder="Enter destination" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="distance">Distance (KM):</label>
                        <input type="text" id="distance" name="distance" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="eta">Estimated Time of Arrival:</label>
                        <input type="text" id="eta" name="eta" class="form-control" readonly>
                    </div>
                </div>
            </div>
            <div class="row">
              <div class="col-md-12 d-flex justify-content-between">
                  <button type="button" class="btn btn-primary" onclick="calculateDistance()">Calculate Distance and ETA</button>
                  <input type="hidden" name="id" value="<?php echo isset($id) ? htmlspecialchars($id) : '' ?>"><br>
                  <button type="submit" class="btn btn-success" id="submitBtn" name="submitBtn" style="display: none;">Submit</button>
                  <!-- <button type="button" class="btn btn-primary" id="qrCodeButton" data-toggle="modal" data-target="#qrCodeModal">Generate QR Code</button> -->
              </div>
            </div>
        </form>
    </div>
      <!-- /.card-body -->
    </div>
    <!-- /.card -->
  </div>
  <!-- /.container-fluid -->
</main>

<?php include "partials/footer.php"; ?>

<!-- Modal to display the QR code -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" role="dialog" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="qrCodeModalLabel">QR Code</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <p class="text-center">
            <span style="font-size: 20px; font-weight: bold; color: green;">Ready to Scan</span><br>
            <span style="font-size: 14px;">Please have the driver scan this QR code using the Driver App to match him to the booking.</span>
        </p>
      <?php if (!empty($qr_code)): ?>
        <img id="qrCodeImage" src="data:image/png;base64,<?php echo $qr_code; ?>" alt="QR Code" />
      <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <!-- Print Button -->
        <button type="button" class="btn btn-primary" onclick="printQRCode()">Print QR Code</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS and any other script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let autocompleteOrigin, autocompleteDestination;

function initAutocomplete() {
    // Restrict Autocomplete for Origin and Destination to the Philippines
    const options = {
        componentRestrictions: { country: 'PH' }, // Restrict to PH
    };

    // Initialize Autocomplete for the origin field
    autocompleteOrigin = new google.maps.places.Autocomplete(
        document.getElementById('origin'),
        options
    );

    // Initialize Autocomplete for the destination field
    autocompleteDestination = new google.maps.places.Autocomplete(
        document.getElementById('destination'),
        options
    );
}

function calculateDistance() {
    const origin = document.getElementById('origin').value;
    const destination = document.getElementById('destination').value;
    const submitButton = document.getElementById('submitBtn');

    if (!origin || !destination) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Input',
            text: 'Please enter both origin and destination.',
        });
        return;
    }

    // Show the loader using SweetAlert2
    Swal.fire({
        title: 'Processing...',
        text: 'Calculating distance and ETA. Please wait.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    // Send data to the server for processing
    fetch('compute_diseta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ origin, destination }),
    })
        .then((response) => response.json())
        .then((data) => {
            Swal.close(); // Close the loader
            if (data.status === 'OK') {
                document.getElementById('distance').value = data.distance;
                document.getElementById('eta').value = data.duration;
                submitButton.style.display = 'block';

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Distance and ETA calculated successfully!',
                });
            } else {
                // Display the specific error message from the server
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Unknown error occurred. Please try again.',
                });
            }
        })
        .catch((error) => {
            Swal.close(); // Close the loader
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Unexpected Error',
                text: 'An unexpected error occurred. Please check your internet connection and try again.',
            });
        });
}


// Initialize Google Places Autocomplete when the page loads
window.onload = initAutocomplete;
</script>
<script>
  $(document).ready(function() {
    // Listen for the modal close event
    $('#qrCodeModal').on('hidden.bs.modal', function() {
      // Redirect to the passengerManagement page
      window.location.href = 'passengerManagement'; // Adjust the URL as needed
    });
  });
</script>
<script>
// Function to print the QR code with modal style
function printQRCode() {
  var qrCodeImage = document.getElementById("qrCodeImage"); // Get the QR code image
  
  // Check if the QR code image exists before trying to access its src
  if (!qrCodeImage) {
    alert("QR code is not available to print.");
    return;
  }

  var qrCodeSrc = qrCodeImage.src; // Get the base64-encoded source of the QR code
  var printWindow = window.open('', '', 'height=400,width=600'); // Open a new window for printing
  
  // Wait for the print window to open and write content
  printWindow.document.write('<html><head><title>Print QR Code</title>');
  
  // Adding styles to match the modal's appearance
  printWindow.document.write('<style>');
  printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; text-align: center; }');
  printWindow.document.write('.qr-text { font-size: 20px; font-weight: bold; color: green; }');
  printWindow.document.write('.qr-description { font-size: 14px; }');
  printWindow.document.write('.qr-image { margin-top: 20px; }');
  printWindow.document.write('</style>');

  printWindow.document.write('</head><body>');
  printWindow.document.write('<p class="qr-text">QR Code for Booking</p>');
  printWindow.document.write('<p class="qr-description">Please have the driver scan this QR code using the Driver App to match him to the booking.</p>');
  
  // Add the QR code image using the base64 source
  printWindow.document.write('<img class="qr-image" src="' + qrCodeSrc + '" />');
  printWindow.document.write('</body></html>');
  printWindow.document.close(); // Close the document to finish writing

  // Trigger the print dialog
  printWindow.print();
}
</script>

</body>
</html>
