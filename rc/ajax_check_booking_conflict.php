<?php
require_once '../db_connect.php';

$booking_id = $_GET['booking_id'];
$sind_id = $_GET['sind_id'];
$booking_date = $_GET['booking_date'];
$from_time = $_GET['from_time'];
$to_time = $_GET['to_time'];

// Check for time conflict with confirmed bookings (exclude current booking)
$stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE sind_id=? AND booking_date=? AND booking_status='confirm' AND booking_id<>? AND ((booking_from_time < ? AND booking_to_time > ?) OR (booking_from_time < ? AND booking_to_time > ?) OR (booking_from_time >= ? AND booking_to_time <= ?)) LIMIT 1");
$stmt->bind_param("issssssss", $sind_id, $booking_date, $booking_id, $to_time, $to_time, $from_time, $from_time, $from_time, $to_time);
$stmt->execute();
$stmt->store_result();
echo json_encode(['conflict' => $stmt->num_rows > 0]);
$stmt->close();
// $conn->close();