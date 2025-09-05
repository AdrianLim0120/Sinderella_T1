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

$service_id = $_POST['service_id'];
$password = $_POST['password'];

// Verify admin password
$adm_id = $_SESSION['adm_id'];
$stmt = $conn->prepare("SELECT adm_pwd FROM admins WHERE adm_id = ?");
$stmt->bind_param("i", $adm_id);
$stmt->execute();
$stmt->bind_result($adm_pwd);
$stmt->fetch();
$stmt->close();

if (password_verify($password, $adm_pwd)) {
    // Update service status to 'inactive'
    $update_stmt = $conn->prepare("UPDATE service_pricing SET service_status = 'inactive' WHERE service_id = ?");
    $update_stmt->bind_param("i", $service_id);
    if ($update_stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }
    $update_stmt->close();
} else {
    echo 'error';
}

// $conn->close();
?>