<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['sind_id'])) {
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}

$booking_id = intval($_POST['booking_id']);
$action = $_POST['action'];
$imageData = $_POST['image'];

if (!in_array($action, ['checkin', 'checkout'])) {
    echo json_encode(['success'=>false, 'message'=>'Invalid action']);
    exit;
}

// Decode base64 image
if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
    $imageData = substr($imageData, strpos($imageData, ',') + 1);
    $type = strtolower($type[1]);
    if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
        echo json_encode(['success'=>false, 'message'=>'Invalid image type']);
        exit;
    }
    $imageData = base64_decode($imageData);
    if ($imageData === false) {
        echo json_encode(['success'=>false, 'message'=>'Base64 decode failed']);
        exit;
    }
} else {
    echo json_encode(['success'=>false, 'message'=>'Invalid image data']);
    exit;
}

// Prepare file path
$filename = str_pad($booking_id, 5, '0', STR_PAD_LEFT) . '.jpg';
if ($action == 'checkin') {
    $dir = '../img/checkin_photo/';
    $column = 'checkin_photo_path';
    $timecol = 'checkin_time';
} else {
    $dir = '../img/checkout_photo/';
    $column = 'checkout_photo_path';
    $timecol = 'checkout_time';
}
if (!is_dir($dir)) mkdir($dir, 0777, true);
$filepath = $dir . $filename;

// Save image
file_put_contents($filepath, $imageData);

// Insert/update DB
if ($action == 'checkin') {
    // Insert or update checkin
    $stmt = $conn->prepare("INSERT INTO booking_checkinout (booking_id, checkin_photo_path, checkin_time) VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE checkin_photo_path=VALUES(checkin_photo_path), checkin_time=VALUES(checkin_time)");
    $stmt->bind_param("is", $booking_id, $filepath);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'Check-in successful!']);
} else {
    // Update checkout, set booking status to done
    $stmt = $conn->prepare("UPDATE booking_checkinout SET checkout_photo_path=?, checkout_time=NOW() WHERE booking_id=?");
    $stmt->bind_param("si", $filepath, $booking_id);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("UPDATE bookings SET booking_status='done' WHERE booking_id=?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->close();
    // echo json_encode(['success'=>true, 'message'=>'Checkout successful!']);
    // temporarily removed for testing (rating link generation)

    // Generate token
    $token = bin2hex(random_bytes(16));
    $expires_at = date('Y-m-d H:i:s', time() + 86400); // 24 hours from now

    // Remove old tokens for this booking
    $conn->query("DELETE FROM booking_rating_links WHERE booking_id = $booking_id");

    // Store new token
    $stmt = $conn->prepare("INSERT INTO booking_rating_links (booking_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $booking_id, $token, $expires_at);
    $stmt->execute();
    $stmt->close();

    // Build the link // need to adjust when changing env
    $rate_link = "http://sinderellauat.free.nf/rc/rate_booking.php?token=$token";
    // $created_at = date('Y-m-d H:i:s');
    // $expired_at = $expires_at;

    echo json_encode([
        'success'=>true,
        'message'=>"Checkout Successful!\n\nLink for customer to rate:\n$rate_link",
        'rate_link' => $rate_link
        // 'created_at' => $created_at,
        // 'expired_at' => $expired_at
    ]);
}