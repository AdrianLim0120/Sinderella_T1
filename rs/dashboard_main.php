<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}
require_once '../db_connect.php';

// Get today's date in Malaysia timezone
date_default_timezone_set('Asia/Kuala_Lumpur');
$today = date('Y-m-d');
$sind_id = $_SESSION['sind_id'];

// Pending bookings (status == 'paid')
$stmt_pending = $conn->prepare("SELECT booking_id, booking_date, booking_from_time, booking_to_time, cust_id, full_address FROM bookings WHERE sind_id = ? AND booking_status = 'paid' ORDER BY booking_date, booking_from_time");
$stmt_pending->bind_param("i", $sind_id);
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();

// Today's bookings
$stmt_today = $conn->prepare("SELECT booking_id, booking_date, booking_from_time, booking_to_time, cust_id, full_address FROM bookings WHERE sind_id = ? AND booking_date = ? AND booking_status = 'confirm' ORDER BY booking_from_time");
$stmt_today->bind_param("is", $sind_id, $today);
$stmt_today->execute();
$result_today = $stmt_today->get_result();

// Helper to get customer name
function getCustomerName($conn, $cust_id) {
    $stmt = $conn->prepare("SELECT cust_name FROM customers WHERE cust_id = ?");
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $stmt->bind_result($cust_name);
    $stmt->fetch();
    $stmt->close();
    return $cust_name ?? '';
}

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
    <title>Sinderella Dashboard</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .dashboard-section { margin-bottom: 40px; }
        .dashboard-table { width: 100%; border-collapse: collapse; }
        .dashboard-table th, .dashboard-table td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        .dashboard-table th { background: #f2f2f2; }
        .dashboard-table tbody tr { cursor: pointer; }
        .dashboard-table tbody tr:hover { background: #e0f7fa; }
        h2 { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="main-container">
    <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
        <?php include '../includes/header_sind.php'; ?>
            <div class="profile-container">
            <div class="dashboard-section">
                <h2>Bookings to Confirm</h2>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Full Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_pending->fetch_assoc()): ?>
                            <tr onclick="viewBookingDetails(<?php echo $row['booking_id']; ?>)">
                                <td><?php echo htmlspecialchars(formatDate($row['booking_date'])); ?></td>
                                <td><?php echo htmlspecialchars(formatTime($row['booking_from_time'])) . ' - ' . htmlspecialchars(formatTime($row['booking_to_time'])); ?></td>
                                <td><?php echo htmlspecialchars(getCustomerName($conn, $row['cust_id'])); ?></td>
                                <td><?php echo htmlspecialchars($row['full_address']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($result_pending->num_rows == 0): ?>
                            <tr><td colspan="4">No pending bookings.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="dashboard-section">
                <h2>Today's Bookings - <?php echo htmlspecialchars(formatDate($today)); ?></h2>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Full Address</th>
                            <!-- <th>Status</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_today->fetch_assoc()): ?>
                            <tr onclick="viewBookingDetails(<?php echo $row['booking_id']; ?>)">
                                <td><?php echo htmlspecialchars(formatDate($row['booking_date'])); ?></td>
                                <td><?php echo htmlspecialchars(formatTime($row['booking_from_time'])) . ' - ' . htmlspecialchars(formatTime($row['booking_to_time'])); ?></td>
                                <td><?php echo htmlspecialchars(getCustomerName($conn, $row['cust_id'])); ?></td>
                                <td><?php echo htmlspecialchars($row['full_address']); ?></td>
                                <!-- <td><?php echo htmlspecialchars(ucfirst($row['booking_status'])); ?></td> -->
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($result_today->num_rows == 0): ?>
                            <tr><td colspan="4">No bookings for today.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        if (performance.navigation.type === 2) {
            location.reload();
        }

        function viewBookingDetails(bookingId) {
            window.location.href = 'view_booking_details.php?booking_id=' + bookingId;
        }
    </script>
<script>
window.addEventListener("pageshow", function(event) {
    if (!sessionStorage.getItem("reloaded")) {
        sessionStorage.setItem("reloaded", "true");
        window.location.reload();
    } else {
        sessionStorage.removeItem("reloaded");
    }
});
</script>
</body>
</html>
<?php
$stmt_pending->close();
$stmt_today->close();
?>