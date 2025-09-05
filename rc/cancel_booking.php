<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    header("Location: my_booking.php");
    exit();
}

$booking_id = intval($_GET['booking_id']);
$reason_code = isset($_GET['reason']) ? $_GET['reason'] : 'user';

// Map reason codes to user-friendly messages
$reason_map = [
    'user' => 'Cancelled by customer',
    'past' => 'Booking date is in the past',
    'conflict' => 'Sinderella is already booked for this time slot',
    'schedule' => "Sinderella's schedule has changed",
];

$cancellation_reason = isset($reason_map[$reason_code]) ? $reason_map[$reason_code] : 'Cancelled';

// Database connection
require_once '../db_connect.php';

// Update booking status
$stmt = $conn->prepare("UPDATE bookings SET booking_status='cancel' WHERE booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->close();

// Insert into booking_cancellation
$stmt = $conn->prepare("INSERT INTO booking_cancellation (booking_id, cancellation_reason) VALUES (?, ?)");
$stmt->bind_param("is", $booking_id, $cancellation_reason);
$stmt->execute();
$stmt->close();

header("Location: my_booking.php?cancel=success");
exit();