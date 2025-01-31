<?php
require_once __DIR__ . '/config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM passengers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $passenger = $result->fetch_assoc();
    echo json_encode($passenger);
}
?>