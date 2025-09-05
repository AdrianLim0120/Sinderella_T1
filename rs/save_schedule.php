<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$sind_id = $_POST['sind_id'] ?? null;
$date = $_POST['date'] ?? null;
$day = $_POST['day'] ?? null;

$from1 = isset($_POST['available_from1']) ? trim($_POST['available_from1']) : '';
$from2 = isset($_POST['available_from2']) ? trim($_POST['available_from2']) : '';

// Database connection
require_once '../db_connect.php';
$conn->query("SET time_zone = '+08:00'");
if (isset($_SESSION['adm_id'])) {
    // Admin: use sind_id from POST
    $sind_id = $_POST['sind_id'] ?? null;
} elseif (isset($_SESSION['sind_id'])) {
    // Sinderella: use their own session id
    $sind_id = $_SESSION['sind_id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$available_from1 = isset($_POST['available_from1']) && $_POST['available_from1'] !== '' ? $_POST['available_from1'] : null;
$available_from2 = isset($_POST['available_from2']) && $_POST['available_from2'] !== '' ? $_POST['available_from2'] : null;

$af1 = ($available_from1 === '' || $available_from1 === null) ? null : $available_from1;
$af2 = ($available_from2 === '' || $available_from2 === null) ? null : $available_from2;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['date'])) {
        $date = $_POST['date'];

        // If both times are empty/null, delete the row
        // if (empty($available_from1) && empty($available_from2)) {
        //     $stmt = $conn->prepare("DELETE FROM sind_available_time WHERE sind_id = ? AND available_date = ?");
        //     $stmt->bind_param("is", $sind_id, $date);
        //     if ($stmt->execute()) {
        //         echo json_encode(['success' => true, 'deleted' => true]);
        //     } else {
        //         echo json_encode(['success' => false, 'message' => 'Failed to delete schedule', 'error' => $stmt->error]);
        //     }
        //     $stmt->close();
        //     // $conn->close();
        //     exit();
        // }

        $stmt = $conn->prepare("SELECT schedule_id FROM sind_available_time WHERE sind_id = ? AND available_date = ?");
        $stmt->bind_param("is", $sind_id, $date);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE sind_available_time SET available_from1 = ?, available_from2 = ? WHERE sind_id = ? AND available_date = ?");
            $stmt->bind_param("ssis", $available_from1, $available_from2, $sind_id, $date);
        } else {
            $stmt = $conn->prepare("INSERT INTO sind_available_time (sind_id, available_date, available_from1, available_from2) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE available_from1 = VALUES(available_from1), available_from2 = VALUES(available_from2)");
            $stmt->bind_param("isss", $sind_id, $date, $available_from1, $available_from2);
        }
    } elseif (isset($_POST['day'])) {
        $day = $_POST['day'];

        // If both times are empty/null, delete the row
        if (empty($available_from1) && empty($available_from2)) {
            $stmt = $conn->prepare("DELETE FROM sind_available_day WHERE sind_id = ? AND day_of_week = ?");
            $stmt->bind_param("is", $sind_id, $day);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'deleted' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete schedule', 'error' => $stmt->error]);
            }
            $stmt->close();
            // $conn->close();
            exit();
        }

        // If only one time is empty, set it to NULL for update/insert
        $stmt = $conn->prepare("SELECT day_id FROM sind_available_day WHERE sind_id = ? AND day_of_week = ?");
        $stmt->bind_param("is", $sind_id, $day);
        $stmt->execute();
        $stmt->store_result();
        // if ($stmt->num_rows > 0) {
        //     $stmt->close();
        //     $stmt = $conn->prepare("UPDATE sind_available_day SET available_from1 = ?, available_from2 = ? WHERE sind_id = ? AND day_of_week = ?");
        //     // Use "s" for string, but pass NULL if value is null
        //     $stmt->bind_param(
        //         "ssis",
        //         $available_from1, $available_from2, $sind_id, $day
        //     );
        // } else {
        //     $stmt->close();
        //     $stmt = $conn->prepare("INSERT INTO sind_available_day (sind_id, day_of_week, available_from1, available_from2) VALUES (?, ?, ?, ?)");
        //     $stmt->bind_param(
        //         "isss",
        //         $sind_id, $day, $available_from1, $available_from2
        //     );
        // }
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $query = "UPDATE sind_available_day SET available_from1 = ?, available_from2 = ? WHERE sind_id = ? AND day_of_week = ?";
            $stmt = $conn->prepare($query);

            // Use variables for binding
            $af1 = ($available_from1 === '' || $available_from1 === null) ? null : $available_from1;
            $af2 = ($available_from2 === '' || $available_from2 === null) ? null : $available_from2;

            $stmt->bind_param("ssis", $af1, $af2, $sind_id, $day);
        } else {
            $stmt->close();
            $query = "INSERT INTO sind_available_day (sind_id, day_of_week, available_from1, available_from2) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);

            $af1 = ($available_from1 === '' || $available_from1 === null) ? null : $available_from1;
            $af2 = ($available_from2 === '' || $available_from2 === null) ? null : $available_from2;

            $stmt->bind_param("isss", $sind_id, $day, $af1, $af2);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save schedule', 'error' => $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
// $conn->close();