<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: recurring_booking.php");
    exit();
}

// Get booking data from POST (not session)
$address = $_POST['address'];
$cust_address_id = $_POST['cust_address_id'] ?? '0';
$service_id = $_POST['service'];
$block_count = isset($_POST['block_count']) ? intval($_POST['block_count']) : 2;
$block_count = max(2, $block_count);

$blocks = [];
for ($i = 1; $i <= $block_count; $i++) {
    $addonsRaw = $_POST["addons_$i"] ?? '';
    $addons = array_unique(array_filter(explode(',', $addonsRaw)));
    $blocks[$i] = [
        'date' => $_POST["date_$i"] ?? '',
        'sinderella' => $_POST["sinderella_$i"] ?? '',
        'time' => $_POST["time_$i"] ?? '',
        'addons' => $addons
    ];
}

// Fetch service details
require_once '../db_connect.php';
$stmt = $conn->prepare("
    SELECT s.service_name, s.service_duration, p.total_price
    FROM services s
    LEFT JOIN pricings p ON s.service_id = p.service_id AND p.service_type = 'r'
    WHERE s.service_id = ?
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($service_name, $service_duration, $service_price);
$stmt->fetch();
$stmt->close();

function formatTime($time) {
    $date = new DateTime($time);
    return $date->format('h:i A');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Recurring Booking - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
</head>
<body>
<div class="main-container">
    <?php include '../includes/menu/menu_cust.php'; ?>
    <div class="content-container">
        <?php include '../includes/header_cust.php'; ?>
        <div class="profile-container">
            <h2>Recurring Booking Confirmation</h2>
            <strong>Address:</strong> <?php echo htmlspecialchars($address); ?><br><br>
            <strong>Address iD:</strong> <?php echo htmlspecialchars($cust_address_id); ?><br><br>

            <table border="1" cellpadding="8" style="border-collapse:collapse; text-align:center; width:100%;">
                <tr>
                    <th>Booking</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Sinderella</th>
                    <th>Service</th>
                    <th>Add-ons</th>
                    <th>Price (RM)</th>
                </tr>
                <?php
                $grand_total = 0;
                $end_time_arr = [];
                for ($i = 1; $i <= $block_count; $i++):
                    $block = $blocks[$i];
                    // Service price and duration
                    $service_price_each = $service_price;
                    $service_duration_each = $service_duration;
                    // Add-ons
                    $addon_names = [];
                    $addon_total = 0;
                    $addon_duration = 0;
                    if (!empty($block['addons'])) {
                        foreach ($block['addons'] as $addon_id) {
                            if (!$addon_id) continue; // skip empty
                            $stmt = $conn->prepare("SELECT ao_desc, ao_price_recurring, ao_duration FROM addon WHERE ao_id = ?");
                            $stmt->bind_param("i", $addon_id);
                            $stmt->execute();
                            $stmt->bind_result($ao_desc, $ao_price, $ao_dur);
                            if ($stmt->fetch() && $ao_desc !== null && $ao_price !== null) {
                                $addon_names[] = htmlspecialchars($ao_desc) . " (RM " . number_format((float)$ao_price,2) . ")";
                                $addon_total += (float)$ao_price;
                                $addon_duration += (float)$ao_dur;
                            }
                            $stmt->close();
                        }
                    }
                    // Calculate end time
                    $start_time_obj = new DateTime($block['time']);
                    $end_time_obj = clone $start_time_obj;
                    $total_duration = $service_duration_each + $addon_duration;
                    $end_time_obj->modify("+$total_duration hours");
                    $end_time = $end_time_obj->format('H:i:s');
                    $end_time_arr[$i] = $end_time;
                    // Sinderella name
                    $sind_id = $block['sinderella'];
                    $stmt = $conn->prepare("SELECT sind_name FROM sinderellas WHERE sind_id = ?");
                    $stmt->bind_param("i", $sind_id);
                    $stmt->execute();
                    $stmt->bind_result($sind_name);
                    $stmt->fetch();
                    $stmt->close();
                    // Total for this booking
                    $booking_total = $service_price_each + $addon_total;
                    $grand_total += $booking_total;
                ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><?php echo htmlspecialchars($block['date']); ?></td>
                    <td>
                        <?php
                        echo htmlspecialchars(formatTime($block['time'])) . " - " . htmlspecialchars(formatTime($end_time));
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($sind_name); ?></td>
                    <td><?php echo htmlspecialchars($service_name) . " (RM " . number_format($service_price_each,2) . ")"; ?></td>
                    <td>
                        <?php
                        if (!empty($addon_names)) {
                            echo implode('<br>', $addon_names);
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td><?php echo number_format($booking_total, 2); ?></td>
                </tr>
                <?php endfor; ?>
                <tr>
                    <td colspan="6" style="text-align:right;"><strong>Total:</strong></td>
                    <td><strong><?php echo number_format($grand_total, 2); ?></strong></td>
                </tr>
            </table>
            <br>
            <form method="POST" action="process_recurring_booking.php">
                <input type="hidden" name="address" value="<?php echo htmlspecialchars($address); ?>">
                <input type="hidden" name="cust_address_id" value="<?php echo htmlspecialchars($cust_address_id); ?>">
                <input type="hidden" name="service" value="<?php echo htmlspecialchars($service_id); ?>">
                <input type="hidden" name="block_count" value="<?php echo $block_count; ?>">
                <?php for ($i = 1; $i <= $block_count; $i++): ?>
                    <input type="hidden" name="date_<?php echo $i; ?>" value="<?php echo htmlspecialchars($blocks[$i]['date']); ?>">
                    <input type="hidden" name="sinderella_<?php echo $i; ?>" value="<?php echo htmlspecialchars($blocks[$i]['sinderella']); ?>">
                    <input type="hidden" name="time_<?php echo $i; ?>" value="<?php echo htmlspecialchars($blocks[$i]['time']); ?>">
                    <input type="hidden" name="end_time_<?php echo $i; ?>" value="<?php echo htmlspecialchars($end_time_arr[$i]); ?>">
                    <input type="hidden" name="addons_<?php echo $i; ?>" value="<?php echo htmlspecialchars(implode(',', $blocks[$i]['addons'])); ?>">
                <?php endfor; ?>
                <label>
                    <input type="checkbox" name="agree" required>
                    By proceeding, I agree with the <a href="cancellation_policy.php" target="_blank">cancellation policy</a>.
                </label>
                <br>
                <button type="submit">Proceed to Payment</button>
            </form>
            <form method="POST" action="recurring_booking.php" style="margin-top:10px;">
                <input type="hidden" name="address" value="<?php echo htmlspecialchars($address); ?>">
                <input type="hidden" name="service" value="<?php echo htmlspecialchars($service_id); ?>">
                <input type="hidden" name="block_count" value="<?php echo $block_count; ?>">
                <?php for ($i = 1; $i <= $block_count; $i++): ?>
                    <input type="hidden" name="date_<?php echo $i; ?>" value="<?php echo htmlspecialchars($blocks[$i]['date']); ?>">
                    <input type="hidden" name="sinderella_<?php echo $i; ?>" value="<?php echo htmlspecialchars($blocks[$i]['sinderella']); ?>">
                    <input type="hidden" name="time_<?php echo $i; ?>" value="<?php echo htmlspecialchars($blocks[$i]['time']); ?>">
                    <input type="hidden" name="addons_<?php echo $i; ?>" value="<?php echo htmlspecialchars(implode(',', $blocks[$i]['addons'])); ?>">
                <?php endfor; ?>
                <!-- <button type="submit">Back to Edit</button> -->
            </form>
        </div>
    </div>
</div>
</body>
</html>