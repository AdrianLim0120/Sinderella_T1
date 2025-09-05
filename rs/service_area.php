<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];

// Retrieve service areas
$service_areas = [];
$stmt = $conn->prepare("SELECT area, state FROM sind_service_area WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($area, $state);
while ($stmt->fetch()) {
    $service_areas[] = ['area' => $area, 'state' => $state];
}
$stmt->close();
// $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Service Area - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_sind.php'; ?>

            <div class="profile-container">
                <h2>Manage Service Area</h2>
                <div style="display: flex;">
                    <div style="flex: 1;">
                        <ul>
                            <?php foreach ($service_areas as $service_area) { ?>
                                <li><?php echo htmlspecialchars($service_area['area']) . ', ' . htmlspecialchars($service_area['state']); ?></li>
                            <?php } ?>
                        </ul>
                        <button onclick="location.href='update_service_area.php'">Update Service Area</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>