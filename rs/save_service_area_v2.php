<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];

// Delete service areas
if (!empty($_POST['deleted_service_areas'])) {
    $ids = explode(",", $_POST['deleted_service_areas']);
    foreach ($ids as $id) {
        $id = intval($id);
        $stmt = $conn->prepare("DELETE FROM sind_service_area WHERE service_area_id = ? AND sind_id = ?");
        $stmt->bind_param("ii", $id, $sind_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle existing service areas (update)
if (!empty($_POST['service_areas'])) {
    foreach ($_POST['service_areas'] as $id => $data) {
        if (!isset($data['state']) || !isset($data['areas']) || empty($data['state']) || empty($data['areas'])) {
            continue;
        }

        $state = $data['state'];
        $areas = $data['areas'];

        // Remove old rows for this service_area_id
        if (is_numeric($id)) {
            $stmt = $conn->prepare("DELETE FROM sind_service_area WHERE service_area_id = ? AND sind_id = ?");
            $stmt->bind_param("ii", $id, $sind_id);
            $stmt->execute();
            $stmt->close();
        }

        // Re-insert updated area rows
        foreach ($areas as $area) {
            $stmt = $conn->prepare("INSERT INTO sind_service_area (sind_id, area, state) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $sind_id, $area, $state);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Handle new service areas
if (!empty($_POST['new_service_areas'])) {
    foreach ($_POST['new_service_areas'] as $block) {
        if (!isset($block['state']) || !isset($block['areas']) || empty($block['state']) || empty($block['areas'])) {
            continue;
        }

        $state = $block['state'];
        $areas = $block['areas'];

        foreach ($areas as $area) {
            $stmt = $conn->prepare("INSERT INTO sind_service_area (sind_id, area, state) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $sind_id, $area, $state);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// $conn->close();
header("Location: manage_profile.php");
exit();
?>
