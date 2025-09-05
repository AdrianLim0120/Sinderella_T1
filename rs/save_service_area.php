<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}
require_once '../db_connect.php';
$sind_id = $_SESSION['sind_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $states = $_POST['states'] ?? [];
    $areas = $_POST['areas'] ?? [];

    // 1. Delete all service areas for states that are no longer present
    if (!empty($states)) {
        $in = implode(',', array_fill(0, count($states), '?'));
        $types = str_repeat('s', count($states));
        $params = $states;
        array_unshift($params, $sind_id);
        $types = 'i' . $types;

        $stmt = $conn->prepare("DELETE FROM sind_service_area WHERE sind_id = ? AND state NOT IN ($in)");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    } else {
        // If no states left, delete all
        $stmt = $conn->prepare("DELETE FROM sind_service_area WHERE sind_id = ?");
        $stmt->bind_param("i", $sind_id);
        $stmt->execute();
        $stmt->close();
    }

    // 2. For each state, delete all and re-insert checked areas
    foreach ($states as $state) {
        $stmt = $conn->prepare("DELETE FROM sind_service_area WHERE sind_id = ? AND state = ?");
        $stmt->bind_param("is", $sind_id, $state);
        $stmt->execute();
        $stmt->close();

        if (!empty($areas[$state])) {
            $stmt = $conn->prepare("INSERT INTO sind_service_area (sind_id, area, state) VALUES (?, ?, ?)");
            foreach ($areas[$state] as $area) {
                $stmt->bind_param("iss", $sind_id, $area, $state);
                $stmt->execute();
            }
            $stmt->close();
        }
    }

    // header("Location: manage_profile.php?success=1");
    header("Location: service_area.php?success=1");
    exit();
}
?>