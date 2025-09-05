<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

$cust_id = $_SESSION['cust_id'];

// Database connection
require_once '../db_connect.php';

// Get address_id from query string
if (!isset($_GET['address_id'])) {
    header("Location: manage_profile.php");
    exit();
}

$address_id = $_GET['address_id'];

// Retrieve address details
$stmt = $conn->prepare("SELECT cust_address, cust_postcode, cust_area, cust_state, cust_housetype, cust_fm_num, cust_pet FROM cust_addresses WHERE cust_address_id = ? AND cust_id = ?");
$stmt->bind_param("ii", $address_id, $cust_id);
$stmt->execute();
$stmt->bind_result($cust_address, $cust_postcode, $cust_area, $cust_state, $cust_housetype, $cust_fm_num, $cust_pet);
if (!$stmt->fetch()) {
    $stmt->close();
    // $conn->close();
    header("Location: manage_profile.php");
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

    // Database connection
    require_once '../db_connect.php'; //
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Validate postcode
    if (!ctype_digit($postcode) || strlen($postcode) != 5) {
        echo "<script>document.getElementById('error-message').innerText = 'Invalid postcode.';</script>";
        exit();
    }

    if (empty($area) || empty($state)) {
        echo "<script>document.getElementById('error-message').innerText = 'Invalid postcode. Area and state not found.';</script>";
        exit();
    }

    // Format inputs
    $address = ucwords(strtolower($address));
    $area = ucwords(strtolower($area));
    $state = ucwords(strtolower($state));

    // Update the address
    $stmt = $conn->prepare("UPDATE cust_addresses SET cust_address = ?, cust_postcode = ?, cust_area = ?, cust_state = ?, cust_housetype = ?, cust_fm_num = ?, cust_pet = ? WHERE cust_address_id = ? AND cust_id = ?");
    if (!$stmt) {
        echo "<script>document.getElementById('error-message').innerText = 'Database error: {$conn->error}';</script>";
        exit();
    }
    $stmt->bind_param("ssssssssi", $address, $postcode, $area, $state, $housetype, $fm_num, $pet, $address_id, $cust_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        header("Location: manage_profile.php?success=1");
        exit();
    } else {
        echo "<script>document.getElementById('error-message').innerText = 'Failed to update address.';</script>";
    }
    $stmt->close();
    // $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Address - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/loginstyles.css">
    <script src="../includes/js/address_info.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <img src="../img/sinderella_logo.png" alt="Sinderella">
            <p style="font-size:1rem;"><a href="manage_profile.php">< Back to Profile</a></p>
        </div>
        <div class="login-right">
            <form id="addressForm" action="update_address.php?address_id=<?php echo $address_id; ?>" method="POST">
                <h2>Update Address</h2>
                <p id="error-message"></p>
                
                <label for="address">Address:</label>
                <textarea id="address" name="address" required><?php echo htmlspecialchars($cust_address); ?></textarea>

                <label for="postcode">Postcode:</label>
                <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($cust_postcode); ?>" required>

                <label for="area">Area:</label>
                <input type="text" id="area" name="area" value="<?php echo htmlspecialchars($cust_area); ?>" readonly>

                <label for="state">State:</label>
                <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($cust_state); ?>" readonly>

                <label for="housetype">House Type:</label>
                <input type="text" id="housetype" name="housetype" value="<?php echo htmlspecialchars($cust_housetype); ?>" required>

                <label for="fm_num">Number of Family Members:</label>
                <input type="number" id="fm_num" name="fm_num" value="<?php echo htmlspecialchars($cust_fm_num); ?>" min="1" required>

                <label for="pet">Pets:</label>
                <input type="text" id="pet" name="pet" value="<?php echo htmlspecialchars($cust_pet); ?>" placeholder="e.g. Dog, Cat, None">

                <button type="submit">Save</button>
            </form>
        </div>
    </div>
</body>
</html>
