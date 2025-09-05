<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Get and validate input
if (!isset($_GET['address_id']) || !isset($_GET['cust_id'])) {
    header("Location: view_customers.php");
    exit();
}

$cust_id = $_GET['cust_id'];
$address_id = $_GET['address_id'];

// Fetch address info
require_once '../db_connect.php';

$stmt = $conn->prepare("SELECT cust_address, cust_postcode, cust_area, cust_state, cust_housetype, cust_fm_num, cust_pet FROM cust_addresses WHERE cust_address_id = ? AND cust_id = ?");
$stmt->bind_param("ii", $address_id, $cust_id);
$stmt->execute();
$stmt->bind_result($cust_address, $cust_postcode, $cust_area, $cust_state, $cust_housetype, $cust_fm_num, $cust_pet);
if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: edit_customer.php?cust_id=$cust_id");
    exit();
}
$stmt->close();
// $conn->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $address = $_POST['address'];
    $postcode = $_POST['postcode'];
    $area = $_POST['area'];
    $state = $_POST['state'];
    $housetype = trim($_POST['housetype']);
    $fm_num = trim($_POST['fm_num']);
    $pet = trim($_POST['pet']);
    if ($pet === '') {
        $pet = 'N/A';
    }

    // Reconnect to update
    require_once '../db_connect.php'; //
    if ($conn->connect_error) {
        $error_message = "Connection failed: " . $conn->connect_error;
    } elseif (!ctype_digit($postcode) || strlen($postcode) != 5) {
        $error_message = 'Invalid postcode.';
    } elseif (empty($area) || empty($state)) {
        $error_message = 'Invalid postcode. Area and state not found.';
    } elseif (!ctype_digit($fm_num) || intval($fm_num) < 1) {
        $error_message = 'Number of family members must be a positive number.';
    } else {
        $address = ucwords(strtolower($address));
        $area = ucwords(strtolower($area));
        $state = ucwords(strtolower($state));
        $housetype = ucwords(strtolower($housetype));
        $pet = $pet !== '' ? $pet : 'N/A';

        $stmt = $conn->prepare("UPDATE cust_addresses SET cust_address = ?, cust_postcode = ?, cust_area = ?, cust_state = ?, cust_housetype = ?, cust_fm_num = ?, cust_pet = ? WHERE cust_address_id = ? AND cust_id = ?");
        if (!$stmt) {
            $error_message = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("ssssssssi", $address, $postcode, $area, $state, $housetype, $fm_num, $pet, $address_id, $cust_id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                header("Location: edit_customer.php?cust_id=$cust_id&success=1");
                exit();
            } else {
                $error_message = 'Failed to update address.';
            }
            $stmt->close();
        }
    }
    // Repopulate form fields with POST data
    $cust_address = $address;
    $cust_postcode = $postcode;
    $cust_area = $area;
    $cust_state = $state;
    $cust_housetype = $housetype;
    $cust_fm_num = $fm_num;
    $cust_pet = $pet;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Customer Address - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/loginstyles.css">
    <script src="../includes/js/address_info.js" defer></script>
</head>
<body>
<div class="login-container">
    <div class="login-left">
        <img src="../img/sinderella_logo.png" alt="Sinderella">
        <p style="font-size:1rem;"><a href="edit_customer.php?cust_id=<?php echo $cust_id; ?>">&lt; Back to Customer</a></p>
    </div>
    <div class="login-right">
        <form id="addressForm" action="update_cust_address.php?cust_id=<?php echo $cust_id; ?>&address_id=<?php echo $address_id; ?>" method="POST">
            <h2>Update Address</h2>

            <label for="address">Address:</label>
            <textarea id="address" name="address" required><?php echo htmlspecialchars($cust_address); ?></textarea>

            <label for="postcode">Postcode:</label>
            <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($cust_postcode); ?>" required>

            <label for="area">Area:</label>
            <input type="text" id="area" name="area" value="<?php echo htmlspecialchars($cust_area); ?>" readonly>

            <label for="state">State:</label>
            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($cust_state); ?>" readonly>

            <label for="housetype">House Type:</label>
            <input type="text" id="housetype" name="housetype" value="<?php echo htmlspecialchars($cust_housetype); ?>" placeholder="3-Storey Terrace House, Bungalow, Condominium etc" required>

            <label for="fm_num">Number of Family Members:</label>
            <input type="number" id="fm_num" name="fm_num" value="<?php echo htmlspecialchars($cust_fm_num); ?>" min="1" required>

            <label for="pet">Pets:</label>
            <input type="text" id="pet" name="pet" value="<?php echo htmlspecialchars($cust_pet); ?>" placeholder="1xcat, 1xdog etc">
            
            <button type="submit">Save</button>
            <p id="error-message"></p>
        </form>
    </div>
</div>
</body>
</html>
