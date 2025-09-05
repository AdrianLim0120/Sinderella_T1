<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_address_id'])) {
    $cust_id = $_SESSION['cust_id'];
    $address_id = $_POST['delete_address_id'];

    require_once '../db_connect.php'; //
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("DELETE FROM cust_addresses WHERE cust_address_id = ? AND cust_id = ?");
    $stmt->bind_param("ii", $address_id, $cust_id);
    $stmt->execute();
    $stmt->close();
    // $conn->close();
}

header("Location: manage_profile.php");
exit();
?>
