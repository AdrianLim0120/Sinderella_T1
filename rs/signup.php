<?php
    $error_message = ""; // Initialize error message variable
    $error_message = $_GET['error'] ?? "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = ucwords(strtolower(trim($_POST['name'])));
        $phone = $_POST['phone'];
        $verification_code = $_POST['verification_code'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Database connection
        require_once '../db_connect.php'; //

        if ($conn->connect_error) {
            $error_message = "Connection failed: " . $conn->connect_error;
        } else {
            // Sanitize phone number
            $phone = preg_replace('/[\s-]/', '', $phone);

            // Check if phone number is numeric
            if (!ctype_digit($phone)) {
                $error_message = "Phone number must be numeric only.";
            } else {
                // Check if phone number exists in sind_downline table
                $stmt = $conn->prepare("SELECT dwln_phno FROM sind_downline WHERE dwln_phno = ?");
                if (!$stmt) {
                    $error_message = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param("s", $phone);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows == 0) {
                        $error_message = "Phone number is not registered by an introducer. <br>Please contact your introducer or customer service.";
                    } else {
                        $stmt->close();

                        // Check if phone number is already used by an active Sinderella
                        $stmt = $conn->prepare("SELECT sind_id FROM sinderellas WHERE sind_phno = ? AND (sind_status = 'active' OR sind_status = 'pending')");
                        if (!$stmt) {
                            $error_message = "Database error: " . $conn->error;
                        } else {
                            $stmt->bind_param("s", $phone);
                            $stmt->execute();
                            $stmt->store_result();

                            if ($stmt->num_rows > 0) {
                                $error_message = "Phone number is already in use by an active Sinderella.";
                            } else {
                                $stmt->close();

                                // Check if passwords match
                                if ($password !== $confirm_password) {
                                    $error_message = "Password and confirm password must match.";
                                } else {
                                    // Check if verification code is valid
                                    $stmt = $conn->prepare("SELECT ver_code FROM verification_codes WHERE user_phno = ? AND ver_code = ? AND expires_at > NOW() AND used = 0");
                                    if (!$stmt) {
                                        $error_message = "Database error: " . $conn->error;
                                    } else {
                                        $stmt->bind_param("si", $phone, $verification_code);
                                        $stmt->execute();
                                        $stmt->store_result();

                                        if ($stmt->num_rows == 0) {
                                            $error_message = "Invalid or expired verification code.";
                                        } else {
                                            // Mark verification code as used
                                            $stmt->close();
                                            $stmt = $conn->prepare("UPDATE verification_codes SET used = 1 WHERE user_phno = ? AND ver_code = ?");
                                            if (!$stmt) {
                                                $error_message = "Database error: " . $conn->error;
                                            } else {
                                                $stmt->bind_param("si", $phone, $verification_code);
                                                $stmt->execute();
                                                $stmt->close();

                                                // Hash the password
                                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                                                // Insert new sinderella into the database
                                                $stmt = $conn->prepare("INSERT INTO sinderellas (sind_name, sind_phno, sind_pwd, sind_status) VALUES (?, ?, ?, 'pending')");
                                                if (!$stmt) {
                                                    $error_message = "Database error: " . $conn->error;
                                                } else {
                                                    $stmt->bind_param("sss", $name, $phone, $hashed_password);
                                                    $stmt->execute();
                                                    $stmt->close();
                                                    // $conn->close();

                                                    // Redirect to address information page
                                                    header("Location: select_upline.php?phone=$phone");
                                                    exit();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
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
            <p>Love cleaning? Love flexibility & freedom?</p>
            <p style="font-size:1rem;"><a href="../index.php">< Back to Home</a></p>
        </div>
        <div class="login-right">
            <form id="signupForm" action="signup.php" method="POST">
                <h2>Sinderella Sign Up</h2>
                <p id="error-message"><?php if (!empty($error_message)) echo $error_message; ?></p>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" placeholder="Exp: Tan Xiao Hua" required
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">

                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone" placeholder="Exp: 0123456789" required
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    
                <button type="button" id="getCodeButton">Send code via WhatsApp</button>
                <label for="verification_code">Verification Code:</label>
                <input type="text" id="verification_code" name="verification_code" required
                    value="<?php echo htmlspecialchars($_POST['verification_code'] ?? ''); ?>">
                    
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                
                <label><input type="checkbox" id="showPasswordAll" onclick="toggleAllPasswords()"> Show Password</label>

                <label>
                    <input type="checkbox" name="agree" required>
                    By proceeding, I agree with the <a href="terms_and_conditions.php" target="_blank">Terms and Conditions</a>.
                </label>
                <button type="submit">Sign Up</button>
            </form>
        </div>
    </div>
</body>
</html>