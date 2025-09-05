<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    echo 'error';
    exit();
}

// Database connection
require_once '../db_connect.php'; //
if ($conn->connect_error) {
    echo 'error';
    exit();
}

$new_fee = $_POST['new_fee'];

// Update registration fee
$update_stmt = $conn->prepare("UPDATE master_number SET master_amount = ? WHERE master_desc = 'Sind Registration Fee'");
$update_stmt->bind_param("d", $new_fee);
if ($update_stmt->execute()) {
    echo 'success';
} else {
    echo 'error';
}
$update_stmt->close();

// $conn->close();
?>