<?php
session_start();
require_once '../db_connect.php';

$booking_id = intval($_POST['booking_id']);
$sind_id = intval($_POST['sind_id']);
$cust_id = intval($_POST['cust_id']);
$rate = intval($_POST['rate']);
$comment = trim($_POST['comment']);

if ($rate < 1 || $rate > 5) {
    echo json_encode(['success'=>false, 'message'=>'Invalid rating.']);
    exit;
}

// Prevent duplicate rating for the same booking
$stmt = $conn->prepare("SELECT rating_id FROM booking_ratings WHERE booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success'=>false, 'message'=>'You have already rated this booking.']);
    exit;
}
$stmt->close();

// Insert rating
$stmt = $conn->prepare("INSERT INTO booking_ratings (booking_id, sind_id, cust_id, rate, comment) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiss", $booking_id, $sind_id, $cust_id, $rate, $comment);
$stmt->execute();
$stmt->close();

// Update booking status to 'rated'
$stmt = $conn->prepare("UPDATE bookings SET booking_status='rated' WHERE booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success'=>true, 'message'=>'Thank you for your rating!']);