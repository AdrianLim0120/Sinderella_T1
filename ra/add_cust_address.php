<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

if (!isset($_GET['cust_id'])) {
    header("Location: view_customers.php");
    exit();
}

$cust_id = $_GET['cust_id'];
$error_message = "";
$address = $postcode = $area = $state = $housetype = $fm_num = $pet = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = $_POST['address'];
    $postcode = $_POST['postcode'];
    $area = $_POST['area'];
    $state = $_POST['state'];
    $housetype = trim($_POST['housetype']);
    $fm_num = trim($_POST['fm_num']);
    $pet = trim($_POST['pet']);

    require_once '../db_connect.php';
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        $error_message = 'Unable to connect to the database.';
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

        $stmt = $conn->prepare("INSERT INTO cust_addresses (cust_id, cust_address, cust_postcode, cust_area, cust_state, cust_housetype, cust_fm_num, cust_pet) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $error_message = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("isssssss", $cust_id, $address, $postcode, $area, $state, $housetype, $fm_num, $pet);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                header("Location: edit_customer.php?cust_id=" . $cust_id);
                exit();
            } else {
                $error_message = 'Failed to add address.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Address - Admin - Sinderella</title>
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
            <form action="add_cust_address.php?cust_id=<?php echo $cust_id; ?>" method="POST" id="addressForm">
                <h2>Add New Address</h2>
                <label for="address">Address:</label>
                <textarea id="address" name="address" required></textarea>

                <label for="postcode">Postcode:</label>
                <input type="text" id="postcode" name="postcode" required>

                <label for="area">Area:</label>
                <input type="text" id="area" name="area" readonly>

                <label for="state">State:</label>
                <input type="text" id="state" name="state" readonly>

                <label for="housetype">House Type:</label>
                <input type="text" id="housetype" name="housetype" placeholder="3-Storey Terrace House, Bungalow, Condominium etc" value="<?php echo htmlspecialchars($housetype); ?>" required>

                <label for="fm_num">Number of Family Members:</label>
                <input type="number" id="fm_num" name="fm_num" min="1" value="<?php echo htmlspecialchars($fm_num); ?>" required>

                <label for="pet">Pet:</label>
                <input type="text" id="pet" name="pet" placeholder="1xcat, 1xdog etc" value="<?php echo htmlspecialchars($pet); ?>">

                <button type="submit">Add Address</button>
                <p id="error-message"><?php echo $error_message; ?></p>
            </form>
        </div>
    </div>
</body>
</html>
