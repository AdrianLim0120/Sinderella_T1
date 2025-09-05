<?php
require_once '../db_connect.php';

$booking_id = $_GET['booking_id'];
$sind_id = $_GET['sind_id'];
$booking_date = $_GET['booking_date'];
$from_time = $_GET['from_time'];
$to_time = $_GET['to_time'];

$available = false;
$message = "";

// Check sind_available_time first
$stmt = $conn->prepare("SELECT available_from1, available_from2 FROM sind_available_time WHERE sind_id=? AND available_date=? LIMIT 1");
$stmt->bind_param("is", $sind_id, $booking_date);
$stmt->execute();
$stmt->bind_result($af1, $af2);
if ($stmt->fetch()) {
    if (($af1 === $from_time || $af1 === null) && ($af2 === $to_time || $af2 === null)) {
        $available = true;
    } else {
        $message = "Sinderella's schedule has changed for this date. The booking will be cancelled.";
    }
} else {
    // Check sind_available_day
    $day_of_week = date('l', strtotime($booking_date));
    $stmt->close();
    $stmt = $conn->prepare("SELECT available_from1, available_from2 FROM sind_available_day WHERE sind_id=? AND day_of_week=? LIMIT 1");
    $stmt->bind_param("is", $sind_id, $day_of_week);
    $stmt->execute();
    $stmt->bind_result($af1, $af2);
    if ($stmt->fetch()) {
        if (($af1 === $from_time || $af1 === null) && ($af2 === $to_time || $af2 === null)) {
            $available = true;
        } else {
            $message = "Sinderella's schedule has changed for this day. The booking will be cancelled.";
        }
    } else {
        $message = "Sinderella's availability could not be verified. The booking will be cancelled.";
    }
}
$stmt->close();
// $conn->close();

echo json_encode(['available' => $available, 'message' => $message]);