<?php
session_start();
require_once '../db_connect.php';

$booking_id = intval($_POST['booking_id'] ?? 0);
$sind_id = intval($_POST['sind_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$rejected_this_month = intval($_POST['rejected_this_month'] ?? 0);

if (!$booking_id || !$sind_id || !$reason) {
    echo json_encode(['success'=>false, 'message'=>'Missing data.']);
    exit;
}

// Insert into sind_rejected_hist
$stmt = $conn->prepare("INSERT INTO sind_rejected_hist (sind_id, booking_id, reason, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $sind_id, $booking_id, $reason);
$stmt->execute();
$stmt->close();

// Update booking status
$stmt = $conn->prepare("UPDATE bookings SET booking_status = 'rejected' WHERE booking_id = ? AND sind_id = ?");
$stmt->bind_param("ii", $booking_id, $sind_id);
$stmt->execute();
$stmt->close();

// If rejected_this_month + 1 >= 3, add to sind_id_label if not already present
if (($rejected_this_month + 1) >= 3) {
    // Check if already exists
    $check = $conn->prepare("SELECT COUNT(*) FROM sind_id_label WHERE sind_id = ? AND slbl_id = 1");
    $check->bind_param("i", $sind_id);
    $check->execute();
    $check->bind_result($exists);
    $check->fetch();
    $check->close();

    if (!$exists) {
        $insert = $conn->prepare("INSERT INTO sind_id_label (sind_id, slbl_id) VALUES (?, 1)");
        $insert->bind_param("i", $sind_id);
        $insert->execute();
        $insert->close();
    }
}

echo json_encode(['success'=>true, 'message'=>'Booking rejected and reason recorded.']);