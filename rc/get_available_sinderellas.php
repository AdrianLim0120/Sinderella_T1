<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

$date = $_GET['date'] ?? '';
$area = $_GET['area'] ?? '';
$state = $_GET['state'] ?? '';

if (!$date || !$area || !$state) {
    echo json_encode(['error' => 'Invalid input parameters']);
    exit();
}

require_once '../db_connect.php';
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Retrieve previous Sinderellas 
$prevSindQuery = "
    SELECT DISTINCT sind_id FROM bookings
    WHERE cust_id = ? AND booking_status IN ('confirm', 'done', 'rated')
";
$prevStmt = $conn->prepare($prevSindQuery);
$prevStmt->bind_param("i", $_SESSION['cust_id']);
$prevStmt->execute();
$prevResult = $prevStmt->get_result();
$previousSinderellas = [];
while ($row = $prevResult->fetch_assoc()) {
    $previousSinderellas[] = $row['sind_id'];
}
$prevStmt->close();

$query = "
    SELECT DISTINCT 
        s.sind_id, 
        s.sind_name, 
        CONCAT('../profile_photo/', s.sind_profile_path) AS sind_profile_full_path,
        CASE 
            WHEN sat.available_from1 IS NOT NULL AND sat.available_from1 != '00:00:00' THEN sat.available_from1
            WHEN sat.available_from1 IS NULL AND sad.available_from1 IS NOT NULL AND sad.available_from1 != '00:00:00' AND sat.schedule_id IS NULL THEN sad.available_from1
            ELSE NULL
        END AS available_from1,
        CASE 
            WHEN sat.available_from2 IS NOT NULL AND sat.available_from2 != '00:00:00' THEN sat.available_from2
            WHEN sat.available_from2 IS NULL AND sad.available_from2 IS NOT NULL AND sad.available_from2 != '00:00:00' AND sat.schedule_id IS NULL THEN sad.available_from2
            ELSE NULL
        END AS available_from2
    FROM sinderellas s
    JOIN sind_service_area sa ON s.sind_id = sa.sind_id
    LEFT JOIN sind_available_time sat 
        ON s.sind_id = sat.sind_id AND sat.available_date = ?
    LEFT JOIN sind_available_day sad 
        ON s.sind_id = sad.sind_id AND sad.day_of_week = DAYNAME(?)
    WHERE s.sind_status = 'active' AND s.acc_approved = 'approve'
    AND sa.area = ? AND sa.state = ?
    AND (
        sat.schedule_id IS NOT NULL OR sad.day_id IS NOT NULL
    )
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $date, $date, $area, $state);
$stmt->execute();
$result = $stmt->get_result();

$sinderellas = [];
while ($row = $result->fetch_assoc()) {

    $row['is_previous'] = in_array($row['sind_id'], $previousSinderellas);

    $available_times = [];
    foreach (['available_from1', 'available_from2'] as $slot_key) {
        $slot_time = $row[$slot_key];

        if ($slot_time) {
            $booking_query = "
                SELECT 1 FROM bookings 
                WHERE sind_id = ? 
                AND booking_status IN ('confirm', 'paid')
                AND DATE(booking_date) = ?
                AND booking_from_time <= ?
                AND booking_to_time > ?
                LIMIT 1
            ";
            $booking_stmt = $conn->prepare($booking_query);
            $booking_stmt->bind_param("isss", $row['sind_id'], $date, $slot_time, $slot_time);
            $booking_stmt->execute();
            $booking_stmt->store_result();

            if ($booking_stmt->num_rows == 0) {
                $available_times[] = $slot_time;
            }

            $booking_stmt->close();
        }
    }

    if (!empty($available_times)) {
        $row['available_times'] = $available_times;

        $rating_stmt = $conn->prepare("SELECT AVG(rate) as avg_rating, COUNT(*) as review_count FROM booking_ratings WHERE sind_id = ?");
        $rating_stmt->bind_param("i", $row['sind_id']);
        $rating_stmt->execute();
        $rating_stmt->bind_result($avg_rating, $review_count);
        $rating_stmt->fetch();
        $rating_stmt->close();

        $row['avg_rating'] = $avg_rating ? round($avg_rating, 1) : null;
        $row['review_count'] = $review_count;

        $sinderellas[] = $row;
    }
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode($sinderellas);
?>
