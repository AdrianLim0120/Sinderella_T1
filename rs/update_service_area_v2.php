<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];

// Group areas by state and ID
$grouped_service_areas = [];
$stmt = $conn->prepare("SELECT service_area_id, area, state FROM sind_service_area WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($service_area_id, $area, $state);
while ($stmt->fetch()) {
    $grouped_service_areas[$service_area_id]['state'] = $state;
    $grouped_service_areas[$service_area_id]['areas'][] = $area;
}
$stmt->close();
// $conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Service Area - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
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
            margin-top: 3px;
            margin-bottom: 3px;
        }
        .service-area-block select,
        .service-area-block .area-checkboxes {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
        }
        #delete-button {
            position: absolute;
            top: 1px;
            right: 2%;
            background-color: red;
            color: white;
            border: none;
            border-radius: 5px;
            width: 25px;
            height: 25px;
            cursor: pointer;
            padding: unset;
        }
        .area-checkboxes {
            display: flex;
            flex-wrap: wrap;
        }
        .area-checkboxes div {
            flex: 1 1 30%;
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .area-checkboxes div label {
            margin-left: 5px;
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
                <form action="save_service_area.php" method="post">
                    <div id="serviceAreasContainer">
                        <?php foreach ($grouped_service_areas as $id => $data): ?>
                        <div class="service-area-block" data-service-area-id="<?= $id ?>">
                            <button type="button" class="delete-button"><b>X</b></button>
                            <label>State:</label>
                            <select name="service_areas[<?= $id ?>][state]" class="state-select" data-state="<?= $data['state'] ?>" required>
                                <option value="">Select State</option>
                            </select>
                            <label>Areas:</label>
                            <div class="area-checkboxes" data-area='<?= json_encode($data['areas']) ?>'></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="deleted_service_areas" id="deletedServiceAreas">
                    <button type="button" id="addServiceAreaButton">+ Add Service Area</button>
                    <button type="submit">Save</button>
                </form>
                <script src="../includes/js/update_service_area.js"></script>
            </div>
        </div>
    </div>
</body>
</html>
