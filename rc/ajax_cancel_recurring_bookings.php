<?php
require_once '../db_connect.php';
$main_booking_id = intval($_GET['booking_id']);
$stmt = $conn->prepare("SELECT booking_id1, booking_id2, booking_id3, booking_id4 FROM booking_recurring WHERE booking_id1=? OR booking_id2=? OR booking_id3=? OR booking_id4=? LIMIT 1");
$stmt->bind_param("iiii", $main_booking_id, $main_booking_id, $main_booking_id, $main_booking_id);
$stmt->execute();
$stmt->bind_result($b1, $b2, $b3, $b4);
if ($stmt->fetch()) {
    $booking_ids = [$b1, $b2, $b3, $b4];
    foreach ($booking_ids as $bid) {
        $stmt2 = $conn->prepare("UPDATE bookings SET booking_status='cancel' WHERE booking_id=?");
        $stmt2->bind_param("i", $bid);
        $stmt2->execute();
        $stmt2->close();
        $stmt3 = $conn->prepare("INSERT INTO booking_cancellation (booking_id, cancellation_reason) VALUES (?, ?)");
        $reason = "Auto-cancelled due to failed recurring booking check";
        $stmt3->bind_param("is", $bid, $reason);
        $stmt3->execute();
        $stmt3->close();
    }
}
$stmt->close();
echo json_encode(['success'=>true]);