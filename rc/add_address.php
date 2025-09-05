<?php
    session_start();
    if (!isset($_SESSION['cust_id'])) {
        header("Location: ../login_cust.php");
        exit();
    }

    $cust_id = $_SESSION['cust_id'];
    $error_message = '';
    $address = $postcode = $area = $state = $housetype = $fm_num = $pet = $phone = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $postcode = $_POST['postcode'];
        $area = $_POST['area'];
        $state = $_POST['state'];
        $housetype = trim($_POST['housetype']);
        $fm_num = trim($_POST['fm_num']);
        $pet = trim($_POST['pet']);

        // Database connection
        require_once '../db_connect.php'; //

        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error); // Log the error
            // echo "<script>document.getElementById('error-message').innerText = 'Unable to connect to the database. Please try again later.';</script>";
            $error_message = 'Unable to connect to the database. Please try again later.';
            // exit();
            // die("Connection failed: " . $conn->connect_error);
        }

        // Validate postcode
        else if (!ctype_digit($postcode) || strlen($postcode) != 5) {
            // echo "<script>document.getElementById('error-message').innerText = 'Invalid postcode.';</script>";
            $error_message = 'Invalid postcode.';
            // exit();
        }

        // Check if area and state are filled
        else if (empty($area) || empty($state)) {
            // echo "<script>document.getElementById('error-message').innerText = 'Invalid postcode. Area and state not found.';</script>";
            $error_message = 'Invalid postcode. Area and state not found.';
            // exit();
        }

        // Validate number of family members
        else if (!ctype_digit($fm_num) || intval($fm_num) < 1) {
            // echo "<script>document.getElementById('error-message').innerText = 'Number of family members must be a positive number.';</script>";
            $error_message = 'Number of family members must be a positive number.';
            // exit();
        }

        else {

        // Format the input to proper styling
        $address = ucwords(strtolower($address));
        $area = ucwords(strtolower($area));
        $state = ucwords(strtolower($state));
        $housetype = ucwords(strtolower($housetype));
        $pet = $pet !== '' ? $pet : 'N/A';

        /*
        // Retrieve cust_id based on cust_phno
        $stmt = $conn->prepare("SELECT cust_id FROM customers WHERE cust_phno = ?");
        if (!$stmt) {
            echo "<script>document.getElementById('error-message').innerText = 'Database error: " . $conn->error . "';</script>";
            exit();
        }
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            echo "<script>document.getElementById('error-message').innerText = 'Phone number not found.';</script>";
            $stmt->close();
            // $conn->close();
            exit();
        }
        $row = $result->fetch_assoc();
        $cust_id = $row['cust_id'];
        $stmt->close();
        */

        // Update customer information
        // $stmt = $conn->prepare("UPDATE customers SET cust_address = ?, cust_postcode = ?, cust_area = ?, cust_state = ? WHERE cust_phno = ?");
        
        $stmt = $conn->prepare("INSERT INTO cust_addresses (cust_id, cust_address, cust_postcode, cust_area, cust_state, cust_housetype, cust_fm_num, cust_pet) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo "<script>document.getElementById('error-message').innerText = 'Database error: " . $conn->error . "';</script>";
            exit();
        }
        $stmt->bind_param("isssssss", $cust_id, $address, $postcode, $area, $state, $housetype, $fm_num, $pet);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            header("Location: manage_profile.php");
            exit();
        } else {
            echo "<script>document.getElementById('error-message').innerText = 'Failed to update address information.';</script>";
        }
        $stmt->close();
        // $conn->close();
        }
    }
    ?>
    
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Address Information - Sinderella</title>
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
            <form id="addressForm" action="add_address.php" method="POST">
                <h2>Address Information</h2>
                <p id="error-message"></p>

                <label for="address">Address:</label>
                <textarea id="address" name="address" required></textarea>

                <label for="postcode">Postcode:</label>
                <input type="text" id="postcode" name="postcode" required>

                <label for="area">Area:</label>
                <input type="text" id="area" name="area" readonly>

                <label for="state">State:</label>
                <input type="text" id="state" name="state" readonly>

                <label for="housetype">House Type:</label>
                <input type="text" id="housetype" name="housetype" placeholder="3-Storey Terrace House, Bungalow, Condominium etc" required>

                <label for="fm_num">Number of Family Members:</label>
                <input type="number" id="fm_num" name="fm_num" min="1" required>

                <label for="pet">Pet:</label>
                <input type="text" id="pet" name="pet" placeholder="1xcat, 1xdog etc">

                <input type="hidden" id="phone" name="phone" value="<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>">
                <button type="submit">Submit</button>
            </form>
            </form>
        </div>
    </div>
</body>
</html>