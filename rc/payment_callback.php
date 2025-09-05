<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data = $_POST;
$booking_id = $data['refno'];
$status = $data['status_id']; // 1 = success

require_once '../db_connect.php'; //

if ($status == 1) {
    $conn->query("UPDATE bookings SET booking_status ='confirm' WHERE booking_id=$booking_id");
    $conn->query("UPDATE booking_payments SET payment_status='confirmed' WHERE booking_id=$booking_id");
} else {
    // $conn->query("UPDATE bookings SET payment_status='failed' WHERE booking_id=$booking_id");
    $conn->query("UPDATE booking_payments SET payment_status='failed' WHERE booking_id=$booking_id");
}

// $conn->close();
http_response_code(200);
