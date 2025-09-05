<?php
/*session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kuala_Lumpur'); 
if (!isset($_SESSION['sind_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
require_once '../db_connect.php'; //
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$conn->query("SET time_zone = '+08:00'");
$sind_id = $_SESSION['sind_id'];

if (isset($_POST['date'])) {
    $date = $_POST['date'];
    // Check if a record already exists for this date
    $stmt = $conn->prepare("SELECT schedule_id FROM sind_available_time WHERE sind_id = ? AND available_date = ?");
    $stmt->bind_param("is", $sind_id, $date);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        // Update existing record to mark as unavailable
        $stmt = $conn->prepare("UPDATE sind_available_time SET available_from = NULL, available_to = NULL WHERE sind_id = ? AND available_date = ?");
        $stmt->bind_param("is", $sind_id, $date);
    } else {
        // Insert new record to mark as unavailable
        $stmt = $conn->prepare("INSERT INTO sind_available_time (sind_id, available_date, available_from, available_to) VALUES (?, ?, NULL, NULL)");
        $stmt->bind_param("is", $sind_id, $date);
    }
} elseif (isset($_POST['day'])) {
    $day = $_POST['day'];
    // Check if a record already exists for this day
    $stmt = $conn->prepare("SELECT day_id FROM sind_available_day WHERE sind_id = ? AND day_of_week = ?");
    $stmt->bind_param("is", $sind_id, $day);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        // Delete existing record
        $stmt = $conn->prepare("DELETE FROM sind_available_day WHERE sind_id = ? AND day_of_week = ?");
        $stmt->bind_param("is", $sind_id, $day);
    } else {
        echo json_encode(['success' => false, 'message' => 'No record found to delete']);
        exit();
    }
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to clear schedule', 'error' => $stmt->error]);
}

$stmt->close();
// $conn->close();*/

session_start();
date_default_timezone_set('Asia/Kuala_Lumpur'); 

if (!isset($_SESSION['sind_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../db_connect.php'; //
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$conn->query("SET time_zone = '+08:00'");
$sind_id = $_SESSION['sind_id'];

if (isset($_POST['date'])) {
    $date = $_POST['date'];
    $stmt = $conn->prepare("UPDATE sind_available_time SET available_from1 = NULL, available_from2 = NULL WHERE sind_id = ? AND available_date = ?");
    $stmt->bind_param("is", $sind_id, $date);
} elseif (isset($_POST['day'])) {
    $day = $_POST['day'];
    $stmt = $conn->prepare("DELETE FROM sind_available_day WHERE sind_id = ? AND day_of_week = ?");
    $stmt->bind_param("is", $sind_id, $day);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to clear schedule']);
}

$stmt->close();
// $conn->close();
?>