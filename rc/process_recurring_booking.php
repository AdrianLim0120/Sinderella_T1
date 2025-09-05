<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['agree'])) {
    header("Location: recurring_booking.php");
    exit();
}
require_once '../db_connect.php';

$cust_id = $_SESSION['cust_id'];
$address = $_POST['address'];
$cust_address_id = $_POST['cust_address_id'] ?? 0;
$service_id = $_POST['service'];
$booking_ids = [];
$total_amount = 0;

$block_count = isset($_POST['block_count']) ? intval($_POST['block_count']) : 2;
$block_count = max(2, $block_count);

for ($i=1; $i<=$block_count; $i++) {
    $date = $_POST["date_$i"];
    $sinderella_id = $_POST["sinderella_$i"];
    $start_time = $_POST["time_$i"];
    $addons = array_filter(explode(',', $_POST["addons_$i"] ?? ''));
    // Fetch service price (recurring)
    $stmt = $conn->prepare("SELECT total_price, service_duration FROM pricings p JOIN services s ON p.service_id = s.service_id WHERE p.service_id=? AND p.service_type='r'");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->bind_result($service_price, $service_duration);
    $stmt->fetch();
    $stmt->close();
    $total_amount += $service_price;

    $addon_duration = 0;
    foreach ($addons as $addon_id) {
        $stmt = $conn->prepare("SELECT ao_duration FROM addon WHERE ao_id=?");
        $stmt->bind_param("i", $addon_id);
        $stmt->execute();
        $stmt->bind_result($ao_dur);
        if ($stmt->fetch()) {
            $addon_duration += (float)$ao_dur;
        }
        $stmt->close();
    }
    $total_duration = $service_duration + $addon_duration;

    $end_time = $_POST["end_time_$i"];
    if (preg_match('/^\d{1,2}:\d{2}$/', $end_time)) {
        $end_time .= ':00';
    }

    // Insert booking
    error_log("cust_address_id: " . $cust_address_id);
    $stmt = $conn->prepare("INSERT INTO bookings (cust_id, sind_id, booking_date, booking_from_time, booking_to_time, service_id, full_address, cust_address_id, booked_at, booking_status, booking_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', 'r')");
    $stmt->bind_param("iisssisi", $cust_id, $sinderella_id, $date, $start_time, $end_time, $service_id, $address, $cust_address_id);
    $stmt->execute();
    $booking_id = $stmt->insert_id;
    $stmt->close();
    $booking_ids[] = $booking_id;

    // Insert add-ons
    foreach ($addons as $addon_id) {
        $stmt = $conn->prepare("INSERT INTO booking_addons (booking_id, ao_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $booking_id, $addon_id);
        $stmt->execute();
        $stmt->close();

        // Fetch add-on price
        $stmt = $conn->prepare("SELECT ao_price_recurring FROM addon WHERE ao_id=?");
        $stmt->bind_param("i", $addon_id);
        $stmt->execute();
        $stmt->bind_result($ao_price);
        $stmt->fetch();
        $stmt->close();
        $total_amount += $ao_price;
    }
}

// Get next recurring_id
$recurring_id = 1;
$result = $conn->query("SELECT MAX(recurring_id) FROM booking_recurring");
if ($row = $result->fetch_row()) {
    $recurring_id = (int)$row[0] + 1;
}

// Insert into booking_recurring (one row per booking, same recurring_id)
foreach ($booking_ids as $booking_id) {
    $stmt = $conn->prepare("INSERT INTO booking_recurring (recurring_id, cust_id, booking_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $recurring_id, $cust_id, $booking_id);
    $stmt->execute();
    $stmt->close();
}

// === Payment Integration (ToyyibPay) ===
$amount_in_sen = (int) round($total_amount * 100);
$bill_data = [
    'userSecretKey' => 'p8bt5ekz-a7xz-xtwn-xo7u-yprm4n2gv7gn',
    'categoryCode' => 'pyoc7tn6',
    'billName' => 'Sinderella Recurring Booking',
    'billDescription' => 'Recurring Booking IDs: ' . implode(', ', $booking_ids),
    'billPriceSetting' => 1,
    'billPayorInfo' => 1,
    'billAmount' => $amount_in_sen,
    'billReturnUrl' => 'http://sinderellauat.free.nf/rc/payment_success.php',
    'billCallbackUrl' => 'http://sinderellauat.free.nf/rc/payment_callback.php',
    'billExternalReferenceNo' => implode('-', $booking_ids),
    'billTo' => 'Customer ' . $cust_id,
    'billEmail' => 'test@example.com',
    'billPhone' => '0100000000'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://dev.toyyibpay.com/index.php/api/createBill');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($bill_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$resp_data = json_decode($response, true);
$bill_code = $resp_data[0]['BillCode'] ?? null;

if ($bill_code) {
    // Save to DB (for each booking)
    foreach ($booking_ids as $booking_id) {
        $stmt = $conn->prepare("INSERT INTO booking_payments (booking_id, bill_code, payment_amount, payment_status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("isd", $booking_id, $bill_code, $total_amount);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: https://dev.toyyibpay.com/$bill_code");
    exit();
} else {
    echo "Failed to create bill. Response: " . htmlspecialchars($response);
    exit();
}
?>