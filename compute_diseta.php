<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents('php://input'), true);
    $origin = urlencode($data['origin']);
    $destination = urlencode($data['destination']);

    $apiKey = $_ENV['GOOGLE_DESTANCE_MATRIX_API_KEY']; // Fetch from .env

    // Distance Matrix API endpoint
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=$origin&destinations=$destination&key=$apiKey";

    // Fetch data from the API
    $response = file_get_contents($url);
    $responseData = json_decode($response, true);

    if ($responseData['status'] === 'OK') {
        $distance = $responseData['rows'][0]['elements'][0]['distance']['text'];
        $duration = $responseData['rows'][0]['elements'][0]['duration']['text'];

        echo json_encode([
            'status' => 'OK',
            'distance' => $distance,
            'duration' => $duration
        ]);
    } else {
        echo json_encode([
            'status' => 'ERROR',
            'message' => 'Unable to fetch data from Google API'
        ]);
    }
}