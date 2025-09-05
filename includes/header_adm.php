<?php
// session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$current_adm_id = $_SESSION['adm_id'];
$stmt = $conn->prepare("SELECT adm_name, last_login_date FROM admins WHERE adm_id = ?");
$stmt->bind_param("i", $current_adm_id);
$stmt->execute();
$stmt->bind_result($current_adm_name, $last_login_date);
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
        <h2>Welcome, <?php echo htmlspecialchars($current_adm_name); ?></h2>
    </div>
    <div class="header-right">
        <p>Date: <?php echo $date_malaysia; ?></p>
        <a href="../logout.php"><img src="../img/icon_logout.png" alt="Logout" title="Logout"></a>
    </div>
</div>