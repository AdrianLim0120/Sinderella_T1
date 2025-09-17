<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/loginstyles.css">
    <script src="../includes/js/forgot_pwd.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <img src="../img/sinderella_logo.png" alt="Sinderella">
            <p>Love cleaning? Love flexibility & freedom?</p>
            <p style="font-size:1rem;"><a href="../login_sind.php">< Back to Login Page</a></p>
        </div>
        <div class="login-right">
            <form id="forgotPwdForm" action="forgot_pwd.php" method="POST">
                <h2>Reset Password</h2>
                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone" placeholder="Exp: 0123456789" required
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? '') ?>">

                <button type="button" id="getCodeButton">Send code via WhatsApp</button>
                <label for="verification_code">Verification Code:</label>
                <input type="text" id="verification_code" name="verification_code" required
                        value="<?php echo htmlspecialchars($_POST['verification_code'] ?? '') ?>">
                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" required>
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <label><input type="checkbox" id="showPasswordAll" onclick="toggleAllPasswords()"> Show Password</label>
                <button type="submit">Change Password</button>
                <p id="error-message"></p>
            </form>
        </div>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $phone = $_POST['phone'];
        $verification_code = $_POST['verification_code'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Database connection
        require_once '../db_connect.php'; //

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Sanitize phone number
        $phone = preg_replace('/[\s-]/', '', $phone);

        // Check if phone number is numeric
        if (!ctype_digit($phone)) {
            echo "<script>document.getElementById('error-message').innerText = 'Phone number must be numeric only.';</script>";
            exit();
        }

        // Check if user exists
        $stmt = $conn->prepare("SELECT sind_id FROM sinderellas WHERE sind_phno = ?");
        if (!$stmt) {
            echo "<script>document.getElementById('error-message').innerText = 'Database error: " . $conn->error . "';</script>";
            exit();
        }
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            echo "<script>document.getElementById('error-message').innerText = 'User does not exist.';</script>";
            // $stmt->close();
            // $conn->close();
            exit();
        }

        $stmt->close();

        // Check if passwords match
        if ($password !== $confirm_password) {
            echo "<script>document.getElementById('error-message').innerText = 'Password and confirm password must match.';</script>";
            exit();
        }

        // Check if verification code is valid
        $stmt = $conn->prepare("SELECT ver_code FROM verification_codes WHERE user_phno = ? AND ver_code = ? AND expires_at > NOW() AND used = 0");
        if (!$stmt) {
            echo "<script>document.getElementById('error-message').innerText = 'Database error: " . $conn->error . "';</script>";
            exit();
        }
        $stmt->bind_param("si", $phone, $verification_code);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            echo "<script>document.getElementById('error-message').innerText = 'Invalid or expired verification code.';</script>";
            // $stmt->close();
            // $conn->close();
            exit();
        }

        // Mark verification code as used
        $stmt->close();
        $stmt = $conn->prepare("UPDATE verification_codes SET used = 1 WHERE user_phno = ? AND ver_code = ?");
        if (!$stmt) {
            echo "<script>document.getElementById('error-message').innerText = 'Database error: " . $conn->error . "';</script>";
            exit();
        }
        $stmt->bind_param("si", $phone, $verification_code);
        $stmt->execute();
        $stmt->close();

        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update sinderella's password in the database
        $stmt = $conn->prepare("UPDATE sinderellas SET sind_pwd = ? WHERE sind_phno = ?");
        if (!$stmt) {
            echo "<script>document.getElementById('error-message').innerText = 'Database error: " . $conn->error . "';</script>";
            exit();
        }
        $stmt->bind_param("ss", $hashed_password, $phone);
        $stmt->execute();
        $stmt->close();
        // $conn->close();

        echo "<script>alert('Password changed successfully.'); window.location.href = '../login_sind.php';</script>";
    }
    ?>
</body>
</html>