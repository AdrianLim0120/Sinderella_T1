<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['cust_id'])) {
    echo json_encode(['success'=>false, 'error'=>'Not logged in']);
    exit;
}
require_once '../db_connect.php';

header('Content-Type: application/json');

$main_booking_id = intval($_GET['booking_id']);
// $stmt = $conn->prepare("SELECT recurring_id, booking_id1, booking_id2, booking_id3, booking_id4 FROM booking_recurring WHERE booking_id1=? OR booking_id2=? OR booking_id3=? OR booking_id4=? LIMIT 1");
// $stmt->bind_param("iiii", $main_booking_id, $main_booking_id, $main_booking_id, $main_booking_id);
// $stmt->execute();
// $stmt->bind_result($recurring_id, $b1, $b2, $b3, $b4);
// if ($stmt->fetch()) {
//     $booking_ids = [$b1, $b2, $b3, $b4];
// } else {
//     echo json_encode(['success'=>false]);
//     exit;
// }
// $stmt->close();

// Find recurring_id for this booking
$stmt = $conn->prepare("SELECT recurring_id FROM booking_recurring WHERE booking_id = ? LIMIT 1");
$stmt->bind_param("i", $main_booking_id);
$stmt->execute();
$stmt->bind_result($recurring_id);
if (!$stmt->fetch()) {
    echo json_encode(['success'=>false, 'error'=>'Not a recurring booking']);
    exit;
}
$stmt->close();

// Get all booking_ids for this recurring_id
$stmt = $conn->prepare("SELECT booking_id FROM booking_recurring WHERE recurring_id = ?");
$stmt->bind_param("i", $recurring_id);
$stmt->execute();
$stmt->bind_result($bid);
$booking_ids = [];
while ($stmt->fetch()) {
    $booking_ids[] = $bid;
}
$stmt->close();

$bookings = [];
foreach ($booking_ids as $bid) {
    $stmt = $conn->prepare("SELECT booking_id, booking_date, booking_from_time, booking_to_time, sind_id, service_id, full_address FROM bookings WHERE booking_id=?");
    $stmt->bind_param("i", $bid);
    $stmt->execute();
    $stmt->bind_result($booking_id, $booking_date, $booking_from_time, $booking_to_time, $sind_id, $service_id, $full_address);
    if ($stmt->fetch()) {
        $stmt->close();
        // Get add-ons as comma-separated string
        $stmt2 = $conn->prepare("SELECT GROUP_CONCAT(ao_id) FROM booking_addons WHERE booking_id=?");
        $stmt2->bind_param("i", $booking_id);
        $stmt2->execute();
        $stmt2->bind_result($addons);
        $stmt2->fetch();
        $stmt2->close();
        $bookings[] = [
            'booking_id' => $booking_id,
            'booking_date' => $booking_date,
            'booking_from_time' => $booking_from_time,
            'booking_to_time' => $booking_to_time,
            'sind_id' => $sind_id,
            'service_id' => $service_id,
            'full_address' => $full_address,
            'addons' => $addons ?: ''
        ];
    } else {
        $stmt->close();
    }
}
echo json_encode(['success'=>true, 'bookings'=>$bookings]);