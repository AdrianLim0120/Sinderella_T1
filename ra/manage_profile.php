<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$adm_id = $_SESSION['adm_id'];
$stmt = $conn->prepare("SELECT adm_name, adm_phno, adm_role FROM admins WHERE adm_id = ?");
$stmt->bind_param("i", $adm_id);
$stmt->execute();
$stmt->bind_result($adm_name, $adm_phno, $adm_role);
$stmt->fetch();
$stmt->close();
// $conn->close();

$adm_phno_formatted = preg_replace("/(\d{3})(\d{3})(\d{4})/", "$1-$2 $3", $adm_phno);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Manage Profile</h2>
                <table>
                    <tr>
                        <td><strong>Name</strong></td>
                        <td>: <?php echo htmlspecialchars($adm_name); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Phone Number</strong></td>
                        <td>: <?php echo htmlspecialchars($adm_phno_formatted); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Role</strong></td>
                        <td>: <?php echo htmlspecialchars($adm_role); ?></td>
                    </tr>
                </table>
                <button onclick="location.href='reset_pwd.php'">Reset Password</button>
            </div>
        </div>
    </div>
</body>
</html>