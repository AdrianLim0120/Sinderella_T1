<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}
require_once '../db_connect.php';

// Set timezone and get today's date and max date
date_default_timezone_set('Asia/Kuala_Lumpur');
$today = date('Y-m-d');
$max_date = date('Y-m-t', strtotime('+1 month'));

// Handle search
$from_date = isset($_GET['from_date']) && $_GET['from_date'] ? $_GET['from_date'] : $today;
$to_date = isset($_GET['to_date']) && $_GET['to_date'] ? $_GET['to_date'] : $from_date;
$search_area = trim($_GET['search_area'] ?? '');
$search_sinderella = trim($_GET['search_sinderella'] ?? '');
$search_status = trim($_GET['search_status'] ?? '');

// Fetch all active Sinderellas
$sind_query = "SELECT sind_id, sind_name FROM sinderellas WHERE sind_status='active' AND acc_approved='approve'";
if ($search_sinderella) {
    $sind_query .= " AND sind_name LIKE '%" . $conn->real_escape_string($search_sinderella) . "%'";
}
$sind_query .= " ORDER BY sind_name ASC";
$sind_result = $conn->query($sind_query);
$sinderellas = [];
while ($row = $sind_result->fetch_assoc()) {
    $sinderellas[$row['sind_id']] = $row['sind_name'];
}

// Fetch all service areas for Sinderellas
$area_query = "SELECT s.sind_id, sa.area, sa.state FROM sinderellas s JOIN sind_service_area sa ON s.sind_id = sa.sind_id";
$area_result = $conn->query($area_query);
$sind_areas = [];
while ($row = $area_result->fetch_assoc()) {
    $sind_areas[$row['sind_id']][] = $row['area'] . ', ' . $row['state'];
}

// Build date range
$date_list = [];
$start = new DateTime($from_date);
$end = new DateTime($to_date);
while ($start <= $end) {
    $date_list[] = $start->format('Y-m-d');
    $start->modify('+1 day');
}

// Prepare results
$results = [];
foreach ($sinderellas as $sind_id => $sind_name) {
    foreach ($date_list as $date) {
        // Area filter
        $area_match = true;
        if ($search_area) {
            $area_match = false;
            if (isset($sind_areas[$sind_id])) {
                foreach ($sind_areas[$sind_id] as $area) {
                    if (stripos($area, $search_area) !== false) {
                        $area_match = true;
                        break;
                    }
                }
            }
        }
        if (!$area_match) continue;

        // 1. Try date-based available time
        $stmt = $conn->prepare("SELECT available_from1, available_from2 FROM sind_available_time WHERE sind_id = ? AND available_date = ?");
        $stmt->bind_param("is", $sind_id, $date);
        $stmt->execute();
        $stmt->bind_result($from1, $from2);
        $has_date = $stmt->fetch();
        $stmt->close();

        // 2. If not found, try day-based
        if (!$has_date) {
            $day_of_week = date('l', strtotime($date));
            $stmt = $conn->prepare("SELECT available_from1, available_from2 FROM sind_available_day WHERE sind_id = ? AND day_of_week = ?");
            $stmt->bind_param("is", $sind_id, $day_of_week);
            $stmt->execute();
            $stmt->bind_result($from1, $from2);
            $has_day = $stmt->fetch();
            $stmt->close();
            if (!$has_day) {
                $from1 = $from2 = null;
            }
        }

        // If both are null, not available
        if (!$from1 && !$from2) continue;

        // Check bookings for this sinderella, date, and each time slot
        $slots = [];
        foreach (['from1' => $from1, 'from2' => $from2] as $slot_key => $slot_time) {
            if (!$slot_time || $slot_time == '00:00:00') continue;
            $booking_stmt = $conn->prepare("SELECT booking_status FROM bookings WHERE sind_id = ? AND booking_date = ? AND booking_from_time = ? AND booking_status NOT IN ('pending', 'cancel')");
            $booking_stmt->bind_param("iss", $sind_id, $date, $slot_time);
            $booking_stmt->execute();
            $booking_stmt->bind_result($booking_status);
            if ($booking_stmt->fetch()) {
                $slots[] = [
                    'time' => date('h:i A', strtotime($slot_time)),
                    'status' => ucfirst($booking_status),
                    'is_booked' => true
                ];
            } else {
                $slots[] = [
                    'time' => date('h:i A', strtotime($slot_time)),
                    'status' => 'Available',
                    'is_booked' => false
                ];
            }
            $booking_stmt->close();
        }
        if (empty($slots)) continue;

        // Status filter
        if ($search_status) {
            $all_booked = array_reduce($slots, function($carry, $slot) {
                return $carry && $slot['is_booked'];
            }, true);
            $all_available = array_reduce($slots, function($carry, $slot) {
                return $carry && !$slot['is_booked'];
            }, true);
            if ($search_status == 'booked' && !$all_booked) continue;
            if ($search_status == 'available' && !$all_available) continue;
        }

        // Get all bookings for this sinderella and date (except pending/cancel)
        $booking_times = [];
        $booking_stmt = $conn->prepare("SELECT booking_from_time FROM bookings WHERE sind_id = ? AND booking_date = ? AND booking_status NOT IN ('pending', 'cancel')");
        $booking_stmt->bind_param("is", $sind_id, $date);
        $booking_stmt->execute();
        $booking_stmt->bind_result($booking_from_time);
        while ($booking_stmt->fetch()) {
            $booking_times[] = date('h:i A', strtotime($booking_from_time));
        }
        $booking_stmt->close();

        $results[] = [
            'sind_id' => $sind_id,
            'sind_name' => $sind_name,
            'date' => $date,
            'area' => isset($sind_areas[$sind_id]) ? implode('<br>', $sind_areas[$sind_id]) : '',
            'slots' => $slots,
            'booking_times' => $booking_times
        ];
    }
}

// Sort by date
usort($results, function($a, $b) {
    return strcmp($a['date'], $b['date']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Sinderella Available Times</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <style>
        .search-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .search-container div {
            min-width: 200px;
        }
        .search-container button {
            margin-top: 0;
        }
        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        #clear-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        #clear-button:hover {
            background-color: #d32f2f;
        }
        .dataTables_wrapper .dataTables_length {
            float: left;
        }
        .dataTables_wrapper .dataTables_filter {
            display: none;
        }
        .dataTables_wrapper .dataTables_info {
            float: left;
            margin-top: 10px;
        }
        .dataTables_wrapper .dataTables_paginate {
            float: right;
            margin-top: 10px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
        }
        #availableTable td {
            text-align: center;
        }
        #availableTable tbody tr {
            cursor: pointer;
        }
        .status-booked {
            /* background-color: #ffcccc !important; */
            color: #b71c1c;
            font-weight: bold;
        }
        .status-available {
            /* background-color: #c8e6c9 !important; */
            color: #256029;
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
            margin: 2px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>View Sinderella Available Times</h2>
                <form method="GET" action="" class="search-container">
                    <div>
                        <label for="from_date">From Date:</label>
                        <input type="date" id="from_date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" max="<?php echo $max_date; ?>">
                    </div>
                    <div>
                        <label for="to_date">To Date:</label>
                        <input type="date" id="to_date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" min="<?php echo $today; ?>" max="<?php echo $max_date; ?>">
                    </div>
                    <div>
                        <label for="search_area">Area/State:</label>
                        <input type="text" id="search_area" name="search_area" value="<?php echo htmlspecialchars($search_area); ?>">
                    </div>
                    <div>
                        <label for="search_sinderella">Sinderella Name:</label>
                        <input type="text" id="search_sinderella" name="search_sinderella" value="<?php echo htmlspecialchars($search_sinderella); ?>">
                    </div>
                    <div>
                        <label for="search_status">Status:</label>
                        <select id="search_status" name="search_status">
                            <option value="">All</option>
                            <option value="booked" <?php if ($search_status == 'booked') echo 'selected'; ?>>Booked</option>
                            <option value="available" <?php if ($search_status == 'available') echo 'selected'; ?>>Available</option>
                        </select>
                    </div>
                    <div class="button-container">
                        <button type="submit">Search</button>
                        <button type="button" id="clear-button" onclick="clearSearch()">Clear Search</button>
                    </div>
                </form>
                <table id="availableTable" class="display">
                    <thead>
                        <tr>
                            <th>Sinderella</th>
                            <th>Date</th>
                            <th>Area</th>
                            <th>Available Time(s)</th>
                            <th>Booked Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr onclick="window.location.href='manage_schedule.php?sind_id=<?php echo $row['sind_id']; ?>'">
                                <td><?php echo htmlspecialchars($row['sind_name']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d (l)', strtotime($row['date']))); ?></td>
                                <td><?php echo $row['area']; ?></td>
                                <td>
                                    <?php
                                    $times = [];
                                    foreach ($row['slots'] as $slot) {
                                        $times[] = $slot['time'];
                                    }
                                    echo implode(', ', $times);
                                    ?>
                                </td>
                                <!-- <td class="<?php
                                    $all_booked = array_reduce($row['slots'], function($carry, $slot) {
                                        return $carry && $slot['is_booked'];
                                    }, true);
                                    $all_available = array_reduce($row['slots'], function($carry, $slot) {
                                        return $carry && !$slot['is_booked'];
                                    }, true);
                                    if ($all_booked) echo 'status-booked';
                                    elseif ($all_available) echo 'status-available';
                                ?>">
                                    <?php
                                    if ($all_booked) {
                                        // Show all booking statuses if multiple slots
                                        $statuses = [];
                                        foreach ($row['slots'] as $slot) {
                                            $statuses[] = '<span class="status-badge" style="background-color: #ffcccc; color: #b71c1c;">' . htmlspecialchars($slot['status'] . ' Booking') . '</span>';
                                        }
                                        echo implode(', ', array_unique($statuses));
                                    } elseif ($all_available) {
                                        echo '<span class="status-badge" style="background-color: #c8e6c9; color: #256029;">Available</span>';
                                    } else {
                                        // Mixed
                                        $statuses = [];
                                        foreach ($row['slots'] as $slot) {
                                            if ($slot['is_booked']) {
                                                $statuses[] = '<span class="status-badge" style="background-color: #ffcccc; color: #b71c1c;">' . htmlspecialchars($slot['status'] . ' Booking') . '</span>';
                                            } else {
                                                $statuses[] = '<span class="status-badge" style="background-color: #c8e6c9; color: #256029;">Available</span>';
                                            }
                                        }
                                        echo implode(', ', $statuses);
                                    }
                                    ?> -->
                                    <td>
                                        <?php
                                        if (!empty($row['booking_times'])) {
                                            // Show all booking times as red badges, separated by commas
                                            $badges = [];
                                            foreach ($row['booking_times'] as $bt) {
                                                $badges[] = '<span class="status-badge" style="background-color: #ffcccc; color: #b71c1c;">' . htmlspecialchars($bt) . '</span>';
                                            }
                                            echo implode(', ', $badges);
                                        } else {
                                            // No bookings, show Available
                                            echo '<span class="status-badge" style="background-color: #c8e6c9; color: #256029;">Available</span>';
                                        }
                                        ?>
                                    </td>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#availableTable').DataTable({
                "dom": '<"top"l>rt<"bottom"ip><"clear">',
                "order": [[1, "asc"]],
                "searching": false
            });
        });

        function clearSearch() {
            const today = "<?php echo $today; ?>";
            const maxDate = "<?php echo $max_date; ?>";
            document.getElementById('from_date').value = today;
            document.getElementById('to_date').value = maxDate;
            document.getElementById('search_area').value = '';
            document.getElementById('search_sinderella').value = '';
            document.getElementById('search_status').value = '';
            window.location.href = window.location.pathname + '?from_date=' + today + '&to_date=' + today;
        }
    </script>
</body>
</html>