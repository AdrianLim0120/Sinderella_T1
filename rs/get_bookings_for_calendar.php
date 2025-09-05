<?php
session_start();
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'] ?? 0;
if (!$sind_id) {
    echo json_encode([]);
    exit;
}

$currentMonth = date('Y-m-01');
$nextMonthEnd = date('Y-m-t', strtotime('+1 month'));

$query = "SELECT booking_date FROM bookings WHERE sind_id = ? AND booking_status NOT IN ('pending','cancel') AND booking_date BETWEEN ? AND ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $sind_id, $currentMonth, $nextMonthEnd);
$stmt->execute();
$stmt->bind_result($booking_date);

$bookings = [];
while ($stmt->fetch()) {
    $bookings[$booking_date] = true;
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode($bookings);