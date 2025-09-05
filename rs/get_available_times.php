<?php
// session_start();
// if (!isset($_SESSION['sind_id'])) {
//     echo json_encode([]);
//     exit();
// }

// Database connection
require_once '../db_connect.php'; //
if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

// $sind_id = $_SESSION['sind_id'];
$sind_id = isset($_GET['sind_id']) ? intval($_GET['sind_id']) : (isset($_SESSION['sind_id']) ? $_SESSION['sind_id'] : 0);
if (!$sind_id) {
    echo json_encode(['dateBased' => [], 'dayBased' => []]);
    exit;
}

// Retrieve available times for the current and next month
$currentMonth = date('Y-m-01');
$nextMonth = date('Y-m-01', strtotime('+1 month'));
$nextMonthEnd = date('Y-m-t', strtotime('+1 month'));

// Retrieve date-based available times
$stmt = $conn->prepare("SELECT schedule_id, available_date, available_from1, available_from2 FROM sind_available_time WHERE sind_id = ? AND available_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $sind_id, $currentMonth, $nextMonthEnd);
$stmt->execute();
$stmt->bind_result($schedule_id, $available_date, $available_from1, $available_from2);

$dateBasedTimes = [];
while ($stmt->fetch()) {
    $dateBasedTimes[$available_date] = [
        'schedule_id' => $schedule_id,
        'available_from1' => $available_from1,
        'available_from2' => $available_from2
    ];
}
$stmt->close();

// Retrieve day-based available times
$stmt = $conn->prepare("SELECT day_of_week, available_from1, available_from2 FROM sind_available_day WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($day_of_week, $available_from1, $available_from2);

$dayBasedTimes = [];
while ($stmt->fetch()) {
    $dayBasedTimes[$day_of_week] = [
        'available_from1' => $available_from1,
        'available_from2' => $available_from2
    ];
}
$stmt->close();
// $conn->close();

// Return the data as JSON
echo json_encode(['dateBased' => $dateBasedTimes, 'dayBased' => $dayBasedTimes]);
?>