<?php
require_once 'db_connect.php'; //
if ($conn->connect_error) {
    echo '<span style="color:red;">Database connection error.</span>';
    exit;
}

$area = isset($_POST['area']) ? $conn->real_escape_string($_POST['area']) : '';
if (!$area) {
    echo '<span style="color:red;">Invalid area.</span>';
    exit;
}

// Query sind_service_area for Sinderellas in the area
$sql = "SELECT COUNT(*) as total FROM sind_service_area WHERE area = '$area'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

if ($row['total'] > 0) {
    echo '<span style="color:green;">Great news! There are <b>' . $row['total'] . '</b> Sinderella(s) serving your area. <br><a href="login_cust.php" style="color:green;text-decoration:underline;">Sign up now to book a service!</a></span>';
} else {
    echo '<span style="color:red;">Sorry, there is currently no Sinderella serving your area.</span>';
}
// $conn->close();
?>