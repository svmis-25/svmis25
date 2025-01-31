<?php
require_once __DIR__ . '/config.php';

if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql = "SELECT id, CONCAT(firstname, ' ', middlename, ' ', lastname, ' ', qualifier) AS name FROM passengers WHERE firstname LIKE ? OR middlename LIKE ? OR lastname LIKE ? OR qualifier LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$search%";
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $passengers = [];
    while ($row = $result->fetch_assoc()) {
        $passengers[] = $row;
    }
    echo json_encode($passengers);
}
?>