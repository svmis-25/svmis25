<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config.php';
require 'vendor/autoload.php';

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\RoundBlockSizeMode;

if (isset($_GET['id'])) {
    $luggageId = $_GET['id'];

    // Fetch the luggage details from the database
    $sql = "SELECT luggage_code FROM passengers_luggage WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $luggageId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $luggageCode = $row['luggage_code'];

        // Generate the QR code with only the luggage_code
        $qrCode = new QrCode(
            data: "$luggageCode",  // Only include luggage_code
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
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

        // Return the QR code as a JSON response
        echo json_encode(['success' => true, 'qr_code' => $base64QrCode]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Luggage not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>