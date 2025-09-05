<?php
session_start();
if (!isset($_SESSION['adm_id'])) exit('Unauthorized');
require_once '../db_connect.php';

$sind_id = intval($_POST['sind_id']);
$action = $_POST['action'];

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE sinderellas SET acc_approved='approve' WHERE sind_id=?");
    $stmt->bind_param("i", $sind_id);
    $stmt->execute();
    $stmt->close();
    echo 'success';
} elseif ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE sinderellas SET acc_approved='reject', sind_status='inactive' WHERE sind_id=?");
    $stmt->bind_param("i", $sind_id);
    $stmt->execute();
    $stmt->close();
    echo 'success';
} else {
    echo 'Invalid action';
}