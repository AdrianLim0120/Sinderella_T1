<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

if (!isset($_GET['booking_id']) || !isset($_GET['addons']) || !isset($_GET['total'])) {
    echo "Invalid request.";
    exit();
}

$booking_id = intval($_GET['booking_id']);
$addons = $_GET['addons'];
$total = floatval($_GET['total']);

require_once '../db_connect.php';

// === STEP 1: Create ToyyibPay Bill ===
$bill_data = [
    'userSecretKey' => 'p8bt5ekz-a7xz-xtwn-xo7u-yprm4n2gv7gn',
    'categoryCode' => 'pyoc7tn6',
    'billName' => 'Sinderella Add-on',
    'billDescription' => 'Add-on for Booking ID #' . $booking_id,
    'billPriceSetting' => 1,
    'billPayorInfo' => 1,
    'billAmount' => (int) round($total * 100),
    'billReturnUrl' => 'http://sinderellauat.free.nf/rc/process_addon.php?booking_id=' . $booking_id . '&addons=' . implode(',', $addons) . '&total=' . $total . '&pay=1',
    'billCallbackUrl' => 'http://sinderellauat.free.nf/rc/payment_callback.php',
    'billExternalReferenceNo' => $booking_id,
    'billTo' => 'Customer ' . $_SESSION['cust_id'],
    'billEmail' => 'test@example.com',
    'billPhone' => '0100000000'
];

if (!isset($_GET['pay'])) {
    // Step 1: Create bill and redirect to ToyyibPay
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
        header("Location: https://dev.toyyibpay.com/$bill_code");
        exit();
    } else {
        echo "Failed to create bill. Response: " . htmlspecialchars($response);
        exit();
    }
} else {
    // Step 2: Payment returned
    if (isset($_GET['status_id']) && $_GET['status_id'] == 1) {
        // Payment successful, insert add-ons
        $addons_arr = explode(',', $_GET['addons']);
        foreach ($addons_arr as $addon_id) {
            $addon_id = intval($addon_id);
            $stmt = $conn->prepare("INSERT INTO booking_addons (booking_id, ao_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $booking_id, $addon_id);
            $stmt->execute();
            $stmt->close();
        }
        echo "<script>
            alert('🎉 Add-on payment successful! Add-ons have been added to your booking.');
            window.location.href = 'view_booking_details.php?booking_id=$booking_id';
        </script>";
    } else {
        echo "<script>
            alert('❌ Payment failed or cancelled. No add-ons were added.');
            window.location.href = 'view_booking_details.php?booking_id=$booking_id';
        </script>";
    }
}
?>