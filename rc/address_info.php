<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $postcode = $_POST['postcode'];
        $area = $_POST['area'];
        $state = $_POST['state'];

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

        // Check if area and state are filled
        if (empty($area) || empty($state)) {
            echo "<script>document.getElementById('error-message').innerText = 'Invalid postcode. Area and state not found.';</script>";
            exit();
        }

        // Format the input to proper styling
        $address = ucwords(strtolower($address));
        $area = ucwords(strtolower($area));
        $state = ucwords(strtolower($state));

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

        // Update customer information
        // $stmt = $conn->prepare("UPDATE customers SET cust_address = ?, cust_postcode = ?, cust_area = ?, cust_state = ? WHERE cust_phno = ?");
        $stmt = $conn->prepare("INSERT INTO cust_addresses (cust_id, cust_address, cust_postcode, cust_area, cust_state) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE cust_address = ?, cust_postcode = ?, cust_area = ?, cust_state = ?");
        if (!$stmt) {
            echo "<script>document.getElementById('error-message').innerText = 'Database error: " . $conn->error . "';</script>";
            exit();
        }
        $stmt->bind_param("issssssss", $cust_id, $address, $postcode, $area, $state, $address, $postcode, $area, $state);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            // Redirect to login page
            echo "<script>alert(\"Account created successfully.\\nYou may now log in to your account.\"); window.location.href = '../login_cust.php';</script>";
            exit();
        } else {
            echo "<script>document.getElementById('error-message').innerText = 'Failed to update address information.';</script>";
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
    <title>Address Information - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/loginstyles.css">
    <script src="../includes/js/address_info.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <img src="../img/sinderella_logo.png" alt="Sinderella">
            <p>Do you need a helping hand in cleaning?</p>
        </div>
        <div class="login-right">
            <form id="addressForm" action="address_info.php" method="POST">
                <h2>Address Information</h2>
                <label for="address">Address:</label>
                <textarea id="address" name="address" required></textarea>
                <label for="postcode">Postcode:</label>
                <input type="text" id="postcode" name="postcode" required>
                <label for="area">Area:</label>
                <input type="text" id="area" name="area" readonly>
                <label for="state">State:</label>
                <input type="text" id="state" name="state" readonly>
                <!-- <input type="hidden" id="phone" name="phone" value="<?php echo htmlspecialchars($_GET['phone']); ?>"> -->
                <input type="hidden" id="phone" name="phone" value="<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>">
                <button type="submit">Submit</button>
                <p id="error-message"></p>
            </form>
        </div>
    </div>
</body>
</html>