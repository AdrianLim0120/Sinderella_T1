<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete service areas
        if (!empty($_POST['deleted_service_areas'])) {
            $deletedServiceAreas = explode(',', $_POST['deleted_service_areas']);
            foreach ($deletedServiceAreas as $service_area_id) {
                if (is_numeric($service_area_id)) {
                    $stmt = $conn->prepare("DELETE FROM sind_service_area WHERE service_area_id = ?");
                    $stmt->bind_param("i", $service_area_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // Process existing service areas
        if (isset($_POST['service_areas'])) {
            foreach ($_POST['service_areas'] as $service_area_id => $service_area) {
                if (!empty($service_area['state']) && isset($service_area['areas'])) {
                    // First delete existing areas for this service area
                    $stmt = $conn->prepare("DELETE FROM sind_service_area WHERE service_area_id = ?");
                    $stmt->bind_param("i", $service_area_id);
                    $stmt->execute();
                    $stmt->close();

                    // Insert new areas
                    foreach ($service_area['areas'] as $area) {
                        $stmt = $conn->prepare("INSERT INTO sind_service_area (sind_id, area, state) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $sind_id, $area, $service_area['state']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        // Process new service areas
        if (isset($_POST['new_service_areas'])) {
            foreach ($_POST['new_service_areas'] as $new_service_area) {
                if (!empty($new_service_area['state']) && isset($new_service_area['areas'])) {
                    foreach ($new_service_area['areas'] as $area) {
                        $stmt = $conn->prepare("INSERT INTO sind_service_area (sind_id, area, state) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $sind_id, $area, $new_service_area['state']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        // Commit transaction
        $conn->commit();
        header("Location: update_service_area.php?success=1");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error updating service areas: " . $e->getMessage());
        header("Location: update_service_area.php?error=1");
        exit();
    }
}

// $conn->close();
?>