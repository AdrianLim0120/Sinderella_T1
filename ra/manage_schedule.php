<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur'); 

if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';
$conn->query("SET time_zone = '+08:00'");
// $sind_id = $_SESSION['sind_id'];
$sind_id = $_GET['sind_id'];

// Retrieve available times for the current and next month
$currentMonth = date('Y-m-01');
$nextMonth = date('Y-m-01', strtotime('+1 month'));
$nextMonthEnd = date('Y-m-t', strtotime('+1 month'));

$stmt = $conn->prepare("SELECT schedule_id, available_date, available_from1, available_from2 FROM sind_available_time WHERE sind_id = ? AND available_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $sind_id, $currentMonth, $nextMonthEnd);
$stmt->execute();
$stmt->bind_result($schedule_id, $available_date, $available_from1, $available_from2);

$availableTimes = [];
while ($stmt->fetch()) {
    $availableTimes[$available_date] = [
        'schedule_id' => $schedule_id,
        'available_from1' => $available_from1,
        'available_from2' => $available_from2
    ];
}
$stmt->close();
// $conn->close();

$stmt = $conn->prepare("SELECT sind_name FROM sinderellas WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($sind_name);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <script src="../includes/js/manage_schedule.js" defer></script>
    <style>
        .calendar {
            width: 100%;
            border-collapse: collapse;
        }
        .calendar th, .calendar td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        .calendar th {
            background-color: #f2f2f2;
            /*cursor: pointer; /* Add cursor pointer for days of the week */
        }
        .calendar td {
            /*cursor: pointer; /* Add cursor pointer for dates */
        }
        .calendar .available {
            background-color: #dff0d8;
        }
        .calendar .unavailable {
            background-color: #f2dede;
        }

        .time-inputs {
            display: flex;
            flex-direction: column;
            gap: 5px
            margin-top: 5px;
        }
        .time-input {
            width: 90%;
            padding: 5px;
            font-size: 14px;
        }
        .calendar .available {
            background-color: #dff0d8;
        }

        .day-form-container {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .day-form-container h3 {
            margin-bottom: 10px;
        }
        .day-form-container .time-input {
            margin-right: 10px;
            padding: 5px;
            font-size: 14px;
        }
        .day-form-container button {
            margin-top: 10px;
            padding: 5px 10px;
            font-size: 14px;
            cursor: pointer;
        }

        .month-year {
            background-color: #0c213b;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Manage Schedule</h2>
                <p>Sinderella: <strong><?php echo htmlspecialchars($sind_name); ?></strong></p>
                <div id="dayBasedTableContainer"></div><br>
                <div id="calendarContainer"></div>
                <div id="scheduleFormContainer" style="display: none;">
                    <h3 id="scheduleFormTitle"></h3>
                    <form id="scheduleForm">
                        <label for="availableFrom1">Start Time 1:</label>
                        <input type="time" id="availableFrom1" name="available_from1" step="1800">
                        <label for="availableFrom2">Start Time 2:</label>
                        <input type="time" id="availableFrom2" name="available_from2" step="1800">
                        <button type="submit">Save</button>
                        <button type="button" id="clearTimeButton">Clear Time</button>
                        <button type="button" id="cancelButton">Cancel</button>
                        <p id="errorMessage" style="color: red;"></p>
                    </form>
                </div>
                <button type="button" onclick="window.history.back();">
                    Back
                </button>
            </div>
        </div>
    </div>

    <script>
        const availableTimes = <?php echo json_encode($availableTimes); ?>;
        const currentMonth = new Date('<?php echo $currentMonth; ?>');
        const nextMonth = new Date('<?php echo $nextMonth; ?>');
        const sind_id = <?php echo json_encode($sind_id); ?>;
        window.isAdmin = true;
        window.sindName = <?php echo json_encode($sind_name ?? ''); ?>;
    </script>
</body>
</html>