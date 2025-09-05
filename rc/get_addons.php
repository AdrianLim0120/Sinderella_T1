<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

$booking_type = $_GET['booking_type'] ?? 'a'; 
$service_id = $_GET['service_id'];

// Database connection
require_once '../db_connect.php'; //
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Fetch add-ons for the selected service
// $query = "SELECT ao_id, ao_desc, ao_price, ao_price_recurring, ao_duration, ao_status FROM addon WHERE service_id = ? AND ao_status = 'active'";
$query = "SELECT ao_id, ao_desc, 
    CASE 
        WHEN ? = 'r' THEN ao_price_recurring 
        ELSE ao_price 
    END AS ao_price, 
    ao_price_recurring, ao_price, ao_duration, ao_status 
    FROM addon 
    WHERE service_id = ? AND ao_status = 'active'";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['error' => 'Failed to prepare statement']);
    exit();
}
$stmt->bind_param("si", $booking_type, $service_id);
$stmt->execute();
$result = $stmt->get_result();

$addons = [];
while ($row = $result->fetch_assoc()) {
    $addons[] = $row;
}

$stmt->close();
// $conn->close();

header('Content-Type: application/json');
echo json_encode($addons);
?>