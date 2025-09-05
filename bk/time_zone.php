<?php
// Set the default timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Database connection
require_once '../db_connect.php';

// Get the current time in the specified timezone
$current_time = date('Y-m-d H:i:s');

// Display the current time and timezone information
echo "Current Time: " . $current_time . "<br>";
echo "Timezone: " . date_default_timezone_get() . "<br>";

// Example query to get the schedule date in the specified timezone
$sind_id = 1; // Example Sinderella ID
$query = "SELECT DATE_FORMAT(CONVERT_TZ(schedule_date, '+00:00', '+08:00'), '%Y-%m-%d %H:%i:%s') AS schedule_date FROM schedules WHERE sind_id = '$sind_id'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Schedule Date: " . $row['schedule_date'] . "<br>";
    }
} else {
    echo "No schedules found for Sinderella ID: $sind_id";
}

// $conn->close();
?>