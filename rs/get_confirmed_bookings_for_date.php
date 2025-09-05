<?php
session_start();
require_once '../db_connect.php';

$sind_id = $_GET['sind_id'] ?? '';
$date = $_GET['date'] ?? '';

if (!$sind_id || !$date) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT booking_from_time FROM bookings WHERE sind_id = ? AND booking_date = ? AND booking_status NOT IN ('pending','cancel', 'rejected')");
$stmt->bind_param("is", $sind_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($bookings);