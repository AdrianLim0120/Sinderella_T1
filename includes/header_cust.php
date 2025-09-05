<?php
// session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$cust_id = $_SESSION['cust_id'];
$stmt = $conn->prepare("SELECT cust_name, last_login_date FROM customers WHERE cust_id = ?");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($cust_name, $last_login_date);
$stmt->fetch();
$stmt->close();
// $conn->close();

// Set the time zone to Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

// Format date in Malaysian time zone with English day name
$locale = 'en_US';
$dateFormatter = new IntlDateFormatter($locale, IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Kuala_Lumpur');
$dateFormatter->setPattern('dd-MM-yyyy (EEEE)');
$date_malaysia = $dateFormatter->format(new DateTime());

?>
<div class="header">
    <div class="header-left">
        <h2>Welcome, <?php echo htmlspecialchars($cust_name); ?></h2>
    </div>
    <div class="header-right">
        <p>Date: <?php echo $date_malaysia; ?></p>
        <a href="../logout.php"><img src="../img/icon_logout.png" alt="Logout" title="Logout"></a>
    </div>
</div>