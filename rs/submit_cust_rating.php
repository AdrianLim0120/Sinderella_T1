<?php
require_once '../db_connect.php';

$booking_id = intval($_POST['booking_id']);
$sind_id = intval($_POST['sind_id']);
$cust_id = intval($_POST['cust_id']);
// $rate = intval($_POST['rate']);
// $comment = trim($_POST['comment']);
$cmt_ppl = trim($_POST['cmt_ppl'] ?? '');
$cmt_hse = trim($_POST['cmt_hse'] ?? '');

// if ($rate < 1 || $rate > 5) {
//     echo json_encode(['success' => false, 'message' => 'Invalid rating.']);
//     exit;
// }

// $stmt = $conn->prepare("INSERT INTO cust_ratings (booking_id, sind_id, cust_id, rate, comment) VALUES (?, ?, ?, ?, ?)");
// $stmt->bind_param("iiiis", $booking_id, $sind_id, $cust_id, $rate, $comment);
// if ($stmt->execute()) {
//     echo json_encode(['success' => true, 'message' => 'Customer rated successfully!']);
// } else {
//     echo json_encode(['success' => false, 'message' => 'Failed to submit rating.']);
// }
// $stmt->close();
$stmt = $conn->prepare("SELECT COUNT(*) FROM cust_ratings WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already submitted a comment for this booking.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO cust_ratings (booking_id, sind_id, cust_id, cmt_ppl, cmt_hse) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiss", $booking_id, $sind_id, $cust_id, $cmt_ppl, $cmt_hse);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Comment submitted successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit comment.']);
}
$stmt->close();
?>