<?php
// Start session to store temporary messages
session_start();
// Require config file
require_once __DIR__ . '/config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index");
    exit;
}

// Include composer autoloader
require 'vendor/autoload.php';

// use Endroid\QrCode\QrCode;
// use Endroid\QrCode\Writer\PngWriter;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\RoundBlockSizeMode;

/*=============================================
  Function to fetch user data and transactions
=============================================*/

if (isset($_GET['id'])){
    $id = intval($_GET['id']);

    // Fetch the existing data for the passenger
    $sql = "SELECT * FROM passengers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1){
        $passenger = $result->fetch_assoc();
        $passenger_name = $passenger['firstname'] . " " . $passenger['middlename'] . " " . $passenger['lastname'] . " " . $passenger['qualifier'];
        $passenger_contact = $passenger['contact'];
    } else {
        echo "Passenger not found!";
        exit;
    }

    // Check if no active transaction is found
    $noTransactionMessage = ""; // Initialize an empty string for the message

    // Fetch the latest transaction for the passenger where driver_id = 0, active = 1, and is_complete = 0
    $sql = "SELECT * FROM transactions WHERE passenger_id = ? AND is_active = 1 AND is_complete = 0 ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $transaction = $result->fetch_assoc();
        // Use the transaction details
        $passenger_id = $transaction['passenger_id'];
        $transactionNo = $transaction['transaction_no'];
        $origin = $transaction['origin'];
        $destination = $transaction['destination'];
        $qr_code = generateQRCode($transactionNo, $passenger_id, $origin, $destination);
    } else {
        // echo "No active transaction found for the selected passenger.";
        // exit;
        $noTransactionMessage = "No active transaction found for the selected passenger.";
    }

    $stmt->close();
} else {
    // Redirect to index after form submission
    header("Location: passengerManagement");
    exit;
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print QR Code</title>
    <!-- Favicon tab icon-->
    <link rel="icon" href="assets/images/banner.png" type="image/x-icon">

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            text-align: center;
        }

        .qr-text {
            font-size: 20px;
            font-weight: bold;
            color: green;
        }

        .qr-description {
            font-size: 14px;
        }

        .qr-image {
            margin-top: 5px;
        }

        .print-btn {
            margin-top: 20px;
            padding: 10px 20px;
        }

        .print-btn:hover {
            background-color: #45a049;
        }
        .no-transaction-message {
            color: red;
            font-weight: bold;
        }
        .no-transaction-title {
            margin-top: 50px;
            font-weight: bold;
        }
        .transaction-title {
            margin-top: 50px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- <h1>QR Code</h1> -->
    <!-- <p class="qr-text">QR Code for Booking</p>
    <p class="qr-description">Please have the driver scan this QR code using the Driver App to match him to the booking.</p>
    <img id="qrCodeImage" src="data:image/png;base64,<?= $qr_code ?>" alt="QR Code" class="qr-image">
    <br> -->
    <!-- Print Button -->
    <!-- <button class="print-btn btn btn-success" onclick="printQRCode()">Print QR Code</button> -->
    <div class="container">
    <!-- Display message if no active transaction -->
        <?php if ($noTransactionMessage): ?>
            <h1 class="no-transaction-title">No Transaction Found</h1>
            <?php if (isset($passenger)): ?>
                <p class="text-muted">Passenger: <?= $passenger_name ?>/ Contact: <?= $passenger_contact ?></p>
            <?php endif; ?>
            <p class="no-transaction-message"><?= $noTransactionMessage ?></p>
            <br>
            <img src="assets/images/result-not-found.gif" alt="No Transaction" style="width: 300px; height: 300px;">
            <br>
            <a href="passengerManagement" class="btn btn-primary">Back to Passenger Management</a>
        <?php else: ?>
            <h1 class="transaction-title">Transaction</h1>
            <p class="qr-text">QR Code for Booking</p>
            <p class="qr-description">Please have the driver scan this QR code using the Driver App to match him to the booking.</p>
            <?php if (isset($passenger)): ?>
                <p class="text-muted">Passenger: <?= $passenger_name ?>/ Contact: <?= $passenger_contact ?></p>
            <?php endif; ?>
            <img id="qrCodeImage" src="data:image/png;base64,<?= $qr_code ?>" alt="QR Code" class="qr-image">
            <br>
            <!-- Print Button -->
            <button class="print-btn btn btn-success" onclick="printQRCode()">Print QR Code</button>
        <?php endif; ?>
    </div>

    <script>
        // Function to print the QR code
        function printQRCode() {
            var qrCodeImage = document.getElementById("qrCodeImage"); // Get the QR code image
            var printWindow = window.open('', '', 'height=400,width=600'); // Open a new window for printing

            printWindow.document.write('<html><head><title>Print QR Code</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; text-align: center; }');
            printWindow.document.write('.qr-text { font-size: 20px; font-weight: bold; color: green; }');
            printWindow.document.write('.qr-description { font-size: 14px; }');
            printWindow.document.write('.qr-image { margin-top: 20px; }');
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<p class="qr-text">QR Code for Booking</p>');
            printWindow.document.write('<p class="qr-description">Please have the driver scan this QR code using the Driver App to match him to the booking.</p>');
            printWindow.document.write('<img class="qr-image" src="' + qrCodeImage.src + '" />'); // Add the QR code image to the print window
            printWindow.document.write('</body></html>');
            printWindow.document.close(); // Close the document to finish writing
            printWindow.print(); // Open the print dialog
        }
    </script>
</body>
</html>
