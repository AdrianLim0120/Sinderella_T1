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

$addon_id = isset($_POST['addon_id']) ? (int)$_POST['addon_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

if ($addon_id && in_array($status, ['active', 'inactive'])) {
    $stmt = $conn->prepare("UPDATE addon SET ao_status = ? WHERE ao_id = ?");
    $stmt->bind_param("si", $status, $addon_id);
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }
    $stmt->close();
} else {
    echo 'error';
}

// $conn->close();
?>