<?php
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = ucwords(strtolower(trim($_POST['name'])));
    $phone = $_POST['phone'];
    $phone = preg_replace('/[\s-]/', '', $phone);
    $verification_code = $_POST['verification_code'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $emer_name = isset($_POST['emer_name']) ? ucwords(strtolower(trim($_POST['emer_name']))) : '';
    $emer_phone = isset($_POST['emer_phone']) ? $_POST['emer_phone'] : '';
    $emer_phone = preg_replace('/[\s-]/', '', $emer_phone);

    require_once '../db_connect.php';

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


    // Check if phone number is numeric
    if (!ctype_digit($phone)) {
        $error_message = 'Phone number must be numeric only.';
    }

    // Check if emer phone number is numeric
    if (!$error_message && !ctype_digit($emer_phone)) {
        $error_message = 'Emergency contact phone number must be numeric only.';
    }

    // Check if phone number and emer phone number are different
    if (!$error_message && $phone === $emer_phone) {
        $error_message = 'Phone number and emergency contact phone number must be different.';
    }

    // Check if phone number is already used by an active customer
    if (!$error_message) {
        $stmt = $conn->prepare("SELECT cust_id FROM customers WHERE cust_phno = ? AND cust_status = 'active'");
        if (!$stmt) {
            $error_message = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error_message = 'Phone number is already in use by an active customer.';
            }
            $stmt->close();
        }
    }

    // Check if passwords match
    if (!$error_message && $password !== $confirm_password) {
        $error_message = 'Password and confirm password must match.';
    }

    // Check if verification code is valid
    if (!$error_message) {
        $stmt = $conn->prepare("SELECT ver_code FROM verification_codes WHERE user_phno = ? AND ver_code = ? AND expires_at > NOW() AND used = 0");
        if (!$stmt) {
            $error_message = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("si", $phone, $verification_code);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 0) {
                $error_message = 'Invalid or expired verification code.';
            }
            $stmt->close();
        }
    }

    // Only proceed if no error
    if (!$error_message) {
        // Mark verification code as used
        $stmt = $conn->prepare("UPDATE verification_codes SET used = 1 WHERE user_phno = ? AND ver_code = ?");
        if (!$stmt) {
            $error_message = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param("si", $phone, $verification_code);
            $stmt->execute();
            $stmt->close();

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new customer into the database
            $stmt = $conn->prepare("INSERT INTO customers (cust_name, cust_phno, cust_pwd, cust_status, cust_emer_name, cust_emer_phno) VALUES (?, ?, ?, 'active', ?, ?)");
            if (!$stmt) {
                $error_message = 'Database error: ' . $conn->error;
            } else {
                $stmt->bind_param("sssss", $name, $phone, $hashed_password, $emer_name, $emer_phone);
                $stmt->execute();
                $stmt->close();

                echo "<script>alert(\"Account created successfully.\\nYou may now log in to your account.\"); window.location.href = '../login_cust.php';</script>";
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/loginstyles.css">
    <script src="../includes/js/signup.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <img src="../img/sinderella_logo.png" alt="Sinderella">
            <p>Do you need a helping hand in cleaning?</p>
            <p style="font-size:1rem;"><a href="../index.php">< Back to Home</a></p>
        </div>
        <div class="login-right">
            <form id="signupForm" action="signup.php" method="POST">
                <h2>Customer Sign Up</h2>
                <p id="error-message" style="color:red;"><?php echo htmlspecialchars($error_message); ?></p><br>

                <label for="name">Name:</label>
                <input type="text" id="name" name="name" placeholder="Exp: Tan Xiao Hua" required
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">

                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone" placeholder="Exp: 0123456789" required
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">

                <button type="button" id="getCodeButton">Get Code</button>
                <label for="verification_code">Verification Code:</label>
                <input type="text" id="verification_code" name="verification_code" required
                    value="<?php echo htmlspecialchars($_POST['verification_code'] ?? ''); ?>">

                <label for="emer_name">Emergency Contact Name:</label>
                <input type="text" id="emer_name" name="emer_name" placeholder="Exp: Tan Xiao Hua" required
                    value="<?php echo htmlspecialchars($_POST['emer_name'] ?? ''); ?>">

                <label for="emer_phone">Emergency Contact Phone Number:</label>
                <input type="text" id="emer_phone" name="emer_phone" placeholder="Exp: 0123456789" required
                    value="<?php echo htmlspecialchars($_POST['emer_phone'] ?? ''); ?>">

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                
                <button type="submit">Sign Up</button>
                <!-- <p id="error-message"></p> -->

                <p><a href="../login_cust.php">Already have an account? Sign In</a></p>
            </form>
        </div>
    </div>
</body>
</html>