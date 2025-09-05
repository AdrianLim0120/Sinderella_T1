<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address_id'], $_POST['cust_id'])) {
    $address_id = $_POST['delete_address_id'];
    $cust_id = $_POST['cust_id'];

    // Database connection
    require_once '../db_connect.php'; //
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Delete address
    $stmt = $conn->prepare("DELETE FROM cust_addresses WHERE cust_address_id = ? AND cust_id = ?");
    $stmt->bind_param("ii", $address_id, $cust_id);
    $stmt->execute();
    $stmt->close();
    // $conn->close();

    // Redirect to the customer's edit page
    header("Location: edit_customer.php?cust_id=" . $cust_id);
    exit();
} else {
    // Fallback in case of incorrect usage
    header("Location: view_customers.php");
    exit();
}
?>
