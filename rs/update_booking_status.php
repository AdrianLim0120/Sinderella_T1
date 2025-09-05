<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['sind_id'])) {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}

$booking_id = intval($_POST['booking_id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!in_array($status, ['confirm', 'rejected'])) {
    echo json_encode(['success'=>false, 'message'=>'Invalid status']);
    exit;
}

$stmt = $conn->prepare("UPDATE bookings SET booking_status=? WHERE booking_id=? AND sind_id=?");
$stmt->bind_param("sii", $status, $booking_id, $_SESSION['sind_id']);
if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'message'=>'Booking status updated.']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Failed to update status.']);
}
$stmt->close();