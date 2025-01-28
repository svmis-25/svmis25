<?php
require_once __DIR__ . '/vendor/autoload.php'; // Load Composer's autoloader

include 'session_check.php'; // Include session check

// Load .env file using vlucas/phpdotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Retrieve database credentials from environment variables
$servername = $_ENV['DB_SERVER'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Uncomment this if you want to verify successful connection during development
// echo "Connection successfully established!";
?>


