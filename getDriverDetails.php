<?php
require_once __DIR__ . '/config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // Fetch driver details from the database. Join with ref_companies to get company name
    $sql = "SELECT
                drivers.id,
                drivers.firstname,
                drivers.lastname,
                drivers.contact,
                drivers.email,
                drivers.address,
                ref_companies.company_name AS company_name
            FROM drivers
            LEFT JOIN ref_companies ON drivers.company_id = ref_companies.id
            WHERE drivers.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $driver = $result->fetch_assoc();
    echo json_encode($driver);
}
?>