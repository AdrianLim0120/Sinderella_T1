<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];

// Retrieve current service areas
$service_areas = [];
$stmt = $conn->prepare("SELECT service_area_id, area, state FROM sind_service_area WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($service_area_id, $area, $state);
while ($stmt->fetch()) {
    $service_areas[] = [
        'service_area_id' => $service_area_id,
        'area' => $area,
        'state' => $state
    ];
}
$stmt->close();
// $conn->close();

$service_areas = [];
$stmt = $conn->prepare("SELECT area, state FROM sind_service_area WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($area, $state);
while ($stmt->fetch()) {
    if (!isset($service_areas[$state])) $service_areas[$state] = [];
    $service_areas[$state][] = $area;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Service Area - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1') {
                alert('Service areas updated successfully!');
            }
        });

        var selectedAreas = <?php echo json_encode($service_areas); ?>;

    </script>
    <script src="../includes/js/update_service_area.js" defer></script>
    <style>
        .service-area-block {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            position: relative;
        }
        .service-area-block label {
            display: block;
            margin-top: 10px;
        }
        .service-area-block select {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
        }
        .profile-container .delete-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: red;
            color: white;
            border: none;
            /* border-radius: 25%; */
            width: 25px;
            height: 25px;
            cursor: pointer;
            margin-top: 0;
        }
        .profile-container .delete-button:hover {
            background-color: darkred;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_sind.php'; ?>
            <div class="profile-container">
                <h2>Update Service Area</h2>
                <form id="updateServiceAreaForm" action="save_service_area.php" method="POST">
                    <input type="hidden" name="deleted_service_areas" id="deletedServiceAreas">
                    <div id="serviceAreasContainer"></div>
                    <button type="button" id="addServiceAreaButton">Add Service Area</button>
                    <button type="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>