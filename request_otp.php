<?php
/*require '../send_whatsapp.php';*/

function generateOTP() {
    return rand(100000, 999999);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'];

    // Sanitize phone number
    $phone = preg_replace('/[\s-]/', '', $phone);

    // Generate OTP
    $otp = generateOTP();

    // Save OTP to the database with an expiration time
    require_once 'db_connect.php'; //
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO verification_codes (user_phno, ver_code, created_at, expires_at, used) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)");
    if (!$stmt) {
        echo "Database error: " . $conn->error;
        exit();
    }
    $stmt->bind_param("si", $phone, $otp);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo $otp; // Return the OTP for testing purposes
    } else {
        echo "Failed to store OTP in the database.";
    }
    $stmt->close();
    // $conn->close();
}
?>