<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: add_booking.php");
    exit();
}

// Retrieve booking details from the form submission
// $selected_address = $_POST['full_address_1'];
// $booking_date = $_POST['booking_date_1'];
// $sinderella_id = $_POST['sinderella'];
// $start_time = $_POST['start_time_1'];
// $service_id = $_POST['service_1'];
// $addons = isset($_POST['addons_1']) ? $_POST['addons_1'] : [];
// $selected_address = $_POST['full_address_1'];

$selected_address = $_POST['full_address_1'] ?? $_POST['full_address'] ?? '';
$booking_date = $_POST['booking_date_1'] ?? $_POST['booking_date'] ?? '';
$sinderella_id = $_POST['sinderella'] ?? '';
$start_time = $_POST['start_time_1'] ?? $_POST['start_time'] ?? '';
$end_time = $_POST['end_time_1'] ?? $_POST['end_time'] ?? '';
$service_id = $_POST['service_1'] ?? $_POST['service'] ?? '';
$addons = $_POST['addons_1'] ?? $_POST['addons'] ?? [];
$cust_address_id = $_POST['cust_address_id_1'] ?? $_POST['cust_address_id'] ?? '';
if (!is_array($addons)) $addons = [];

// Database connection
require_once '../db_connect.php';

// Fetch service details
$stmt = $conn->prepare("
    SELECT s.service_name, s.service_duration, p.total_price
    FROM services s
    LEFT JOIN pricings p ON s.service_id = p.service_id AND p.service_type = 'a'
    WHERE s.service_id = ?
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$stmt->bind_result($service_name, $service_duration, $service_price);
$stmt->fetch();
$stmt->close();

// Fetch add-on details
$addon_details = [];
$total_addon_price = 0;
$total_addon_duration = 0;
if (!empty($addons)) {
    $addon_ids = implode(',', array_fill(0, count($addons), '?'));
    $stmt = $conn->prepare("SELECT ao_desc, ao_price, ao_duration FROM addon WHERE ao_id IN ($addon_ids)");
    $stmt->bind_param(str_repeat('i', count($addons)), ...$addons);
    $stmt->execute();
    $stmt->bind_result($ao_desc, $ao_price, $ao_duration);
    while ($stmt->fetch()) {
        $addon_details[] = ['desc' => $ao_desc, 'price' => $ao_price, 'duration' => $ao_duration];
        $total_addon_price += $ao_price;
        $total_addon_duration += $ao_duration;
    }
    $stmt->close();
}

// Calculate end time
$start_time_obj = new DateTime($start_time);
$end_time_obj = clone $start_time_obj;
$total_duration = $service_duration + $total_addon_duration;
$end_time_obj->modify("+$total_duration hours");
$end_time = $end_time_obj->format('H:i');

// Fetch Sinderella details
$stmt = $conn->prepare("SELECT sind_name FROM sinderellas WHERE sind_id = ?");
$stmt->bind_param("i", $sinderella_id);
$stmt->execute();
$stmt->bind_result($sinderella_name);
$stmt->fetch();
$stmt->close();

// $conn->close();

function formatTime($time) {
    $date = new DateTime($time);
    return $date->format('h:i A');
}

function formatDate($date) {
    $date = new DateTime($date);
    return $date->format('Y-m-d (l)');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Booking - Customer - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .confirmation-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .confirmation-container h2 {
            margin-top: 0;
        }
        .confirmation-container label {
            display: block;
            margin-top: 10px;
        }
        .confirmation-container input[type="checkbox"] {
            margin-right: 10px;
        }
        .confirmation-container button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .confirmation-container button:hover {
            background-color: #0056b3;
        }

        .confirmation-container td{
            padding: 8px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_cust.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_cust.php'; ?>
            <div class="confirmation-container">
                <h2>Booking Confirmation</h2>
                <table>
                    <tr>
                        <td><strong>Address:</strong></td>
                        <td><?php echo htmlspecialchars($selected_address); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Booking Date:</strong></td>
                        <td><?php echo htmlspecialchars(formatDate($booking_date)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Booking Time:</strong></td>
                        <td><?php echo htmlspecialchars(formatTime($start_time)); ?> - <?php echo htmlspecialchars(formatTime($end_time)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Sinderella:</strong></td>
                        <td><?php echo htmlspecialchars($sinderella_name); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Service:</strong></td>
                        <td><?php echo htmlspecialchars($service_name); ?> (RM <?php echo number_format($service_price, 2); ?>)</td>
                    </tr>
                    <tr>
                        <td><strong>Add-ons:</strong></td>
                        <td><?php foreach ($addon_details as $addon): ?>
                            <ul>
                            <li> <?php echo htmlspecialchars($addon['desc']); ?> (RM <?php echo number_format($addon['price'], 2); ?>)<br/> </li>
                            </ul>
                        <?php endforeach; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td>RM <?php echo number_format($service_price + $total_addon_price, 2); ?></td>
                    </tr>
                </table>

                <!-- <p><strong>Address:</strong> <?php echo htmlspecialchars($selected_address); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($booking_date); ?></p>
                <p><strong>Time:</strong> <?php echo htmlspecialchars(formatTime($start_time)); ?> - <?php echo htmlspecialchars(formatTime($end_time)); ?></p>
                <p><strong>Sinderella:</strong> <?php echo htmlspecialchars($sinderella_name); ?></p>
                <p><strong>Service:</strong> <?php echo htmlspecialchars($service_name); ?> (RM <?php echo number_format($service_price, 2); ?>)</p>
                <p><strong>Add-ons:</strong></p>
                <ul>
                    <?php foreach ($addon_details as $addon): ?>
                        <li><?php echo htmlspecialchars($addon['desc']); ?> (RM <?php echo number_format($addon['price'], 2); ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Total:</strong> RM <?php echo number_format($service_price + $total_addon_price, 2); ?></p>-->
                
                <form id="confirmationForm" method="POST" action="process_booking.php">
                    <input type="hidden" name="booking_date" value="<?php echo htmlspecialchars($booking_date); ?>">
                    <input type="hidden" name="sinderella" value="<?php echo htmlspecialchars($sinderella_id); ?>">
                    <input type="hidden" name="start_time" value="<?php echo htmlspecialchars($start_time); ?>">
                    <input type="hidden" name="end_time" value="<?php echo htmlspecialchars($end_time); ?>">
                    <input type="hidden" name="service" value="<?php echo htmlspecialchars($service_id); ?>">
                    <input type="hidden" name="full_address" value="<?php echo htmlspecialchars($selected_address); ?>">
                    <input type="hidden" name="cust_address_id" value="<?php echo htmlspecialchars($cust_address_id); ?>">
                    <?php foreach ($addons as $addon_id): ?>
                        <input type="hidden" name="addons[]" value="<?php echo htmlspecialchars($addon_id); ?>">
                    <?php endforeach; ?>
                    <label>
                        <input type="checkbox" name="agree" required>
                        By proceeding, I agree with the <a href="cancellation_policy.php" target="_blank">cancellation policy</a>.
                    </label>
                    <button type="submit">Proceed to Payment</button>
                </form>
                <!-- <button onclick="editServiceSelection()">Edit Service Selection</button> -->
            </div>
        </div>
    </div>

    <script>
        function editServiceSelection() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'add_booking.php';

            const bookingDateInput = document.createElement('input');
            bookingDateInput.type = 'hidden';
            bookingDateInput.name = 'booking_date';
            bookingDateInput.value = '<?php echo htmlspecialchars($booking_date); ?>';
            form.appendChild(bookingDateInput);

            const sinderellaInput = document.createElement('input');
            sinderellaInput.type = 'hidden';
            sinderellaInput.name = 'sinderella';
            sinderellaInput.value = '<?php echo htmlspecialchars($sinderella_id); ?>';
            form.appendChild(sinderellaInput);

            const startTimeInput = document.createElement('input');
            startTimeInput.type = 'hidden';
            startTimeInput.name = 'start_time';
            startTimeInput.value = '<?php echo htmlspecialchars($start_time); ?>';
            form.appendChild(startTimeInput);

            const serviceInput = document.createElement('input');
            serviceInput.type = 'hidden';
            serviceInput.name = 'service';
            serviceInput.value = '<?php echo htmlspecialchars($service_id); ?>';
            form.appendChild(serviceInput);

            <?php foreach ($addons as $addon_id): ?>
                const addonInput = document.createElement('input');
                addonInput.type = 'hidden';
                addonInput.name = 'addons[]';
                addonInput.value = '<?php echo htmlspecialchars($addon_id); ?>';
                form.appendChild(addonInput);
            <?php endforeach; ?>

            const addressInput = document.createElement('input');
            addressInput.type = 'hidden';
            addressInput.name = 'address';
            addressInput.value = '<?php echo htmlspecialchars($selected_address); ?>';
            form.appendChild(addressInput);
            <input type="hidden" name="full_address" value="<?php echo htmlspecialchars($selected_address); ?>">

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>