<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$cust_id = $_SESSION['cust_id'];

// Fetch bookings
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : '';
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : '';

$query = "SELECT b.booking_id, b.booking_date, b.booking_from_time, b.booking_to_time, b.full_address, 
            s.sind_name, sv.service_name, b.booking_type, b.booking_status
          FROM bookings b
          JOIN sinderellas s ON b.sind_id = s.sind_id
          JOIN services sv ON b.service_id = sv.service_id
          WHERE b.cust_id = ?";

if ($search_date) {
    $query .= " AND DATE(b.booking_date) = '" . $conn->real_escape_string($search_date) . "'";
}

// if ($search_status) {
//     $query .= " AND b.booking_status = '" . $conn->real_escape_string($search_status) . "'";
// }

if ($search_status == 'paid') {
    $query .= " AND (b.booking_status = 'paid' OR b.booking_status = 'rejected')";
} elseif ($search_status) {
    $query .= " AND b.booking_status = '" . $conn->real_escape_string($search_status) . "'";
}

$query .= " ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$result = $stmt->get_result();

function formatDate($date) {
    $date = new DateTime($date);
    return $date->format('Y-m-d (l)');
}

function formatTime($time) {
    $date = new DateTime($time);
    return $date->format('h:i A');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Customer - Sinderella</title>
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
        #bookingTable td {
            text-align: center;
        }
        #bookingTable tbody tr {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_cust.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_cust.php'; ?>
            <div class="profile-container">
                <h2>My Bookings</h2>
                <form method="GET" action="" class="search-container">
                    <div>
                        <label for="search_date">Date:</label>
                        <input type="date" id="search_date" name="search_date" value="<?php echo htmlspecialchars($search_date); ?>">
                    </div>
                    <div>
                        <label for="search_status">Status:</label>
                        <select id="search_status" name="search_status">
                            <option value="">All</option>
                            <option value="pending" <?php if ($search_status == 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="paid" <?php if ($search_status == 'paid') echo 'selected'; ?>>Paid</option>
                            <option value="confirm" <?php if ($search_status == 'confirm') echo 'selected'; ?>>Confirm</option>
                            <option value="done" <?php if ($search_status == 'done') echo 'selected'; ?>>Done</option>
                            <option value="cancel" <?php if ($search_status == 'cancel') echo 'selected'; ?>>Cancelled</option>
                            <option value="rated" <?php if ($search_status == 'rated') echo 'selected'; ?>>Rated</option>
                        </select>
                    </div>
                    <div class="button-container">
                        <button type="submit">Search</button>
                        <button type="button" id="clear-button" onclick="clearSearch()">Clear Search</button>
                    </div>
                </form>
                <table id="bookingTable" class="display">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Area</th>
                            <th>Sinderella</th>
                            <th>Service</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr onclick="viewBookingDetails(<?php echo $row['booking_id']; ?>)">
                                <td><?php echo htmlspecialchars(formatDate($row['booking_date'])); ?></td>
                                <td><?php echo htmlspecialchars(formatTime($row['booking_from_time'])) . ' - ' . htmlspecialchars(formatTime($row['booking_to_time'])); ?></td>
                                <?php
                                    $full_address = $row['full_address'];

                                    if (empty($full_address)) {
                                        $area = 'N/A';
                                        $state = 'N/A';
                                    } else {
                                        $parts = array_map('trim', explode(',', $full_address));
                                        $count = count($parts);

                                        // Safely get last two parts (area and state)
                                        $area = $count >= 2 ? $parts[$count - 2] : 'N/A';
                                        $state = $count >= 1 ? $parts[$count - 1] : 'N/A';
                                    }
                                ?>
                                <td><?php echo htmlspecialchars("$area, $state"); ?></td>
                                <td><?php echo htmlspecialchars($row['sind_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($row['booking_type'])); ?></td>
                                <!-- <td><?php echo htmlspecialchars($row['booking_status']); ?></td> -->
                                <td>
                                    <?php if ($row['booking_status'] == 'rated'): ?>
                                        <?php
                                        // Fetch the rating for this booking
                                        $rating_stmt = $conn->prepare("SELECT rate FROM booking_ratings WHERE booking_id = ?");
                                        $rating_stmt->bind_param("i", $row['booking_id']);
                                        $rating_stmt->execute();
                                        $rating_stmt->bind_result($rate);
                                        $rating_stmt->fetch();
                                        $rating_stmt->close();
                                        if (isset($rate)) {
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rate ? '⭐' : '☆';
                                            }
                                        } else {
                                            echo 'Rated';
                                        }
                                        ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(ucfirst($row['booking_status'])); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#bookingTable').DataTable({
                "dom": '<"top"l>rt<"bottom"ip><"clear">',
                "order": [[0, "desc"]],
                "searching": false
            });
        });

        function clearSearch() {
            document.getElementById('search_date').value = '';
            document.getElementById('search_status').value = '';
            window.location.href = window.location.pathname;
        }

        function viewBookingDetails(bookingId) {
            window.location.href = 'view_booking_details.php?booking_id=' + bookingId;
        }
    </script>
</body>
</html>

<?php
// $stmt->close();
// // $conn->close();
?>