<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];

// Retrieve current address information
$stmt = $conn->prepare("SELECT sind_address, sind_postcode, sind_area, sind_state FROM sinderellas WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($sind_address, $sind_postcode, $sind_area, $sind_state);
$stmt->fetch();
$stmt->close();
// $conn->close();
?>
<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

        // Update sinderella's information
        $stmt = $conn->prepare("UPDATE sinderellas SET sind_address = ?, sind_postcode = ?, sind_area = ?, sind_state = ? WHERE sind_id = ?");
        if (!$stmt) {
            echo "<script>document.getElementById('error-message').innerText = 'Database error: " . $conn->error . "';</script>";
            exit();
        }
        $stmt->bind_param("ssssi", $address, $postcode, $area, $state, $sind_id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            // Redirect to manage profile page
            header("Location: manage_profile.php?success=1");
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
    <title>Update Address - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/loginstyles.css">
    <script src="../includes/js/address_info.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <img src="../img/sinderella_logo.png" alt="Sinderella">
            <p>Love cleaning? Love flexibility & freedom?</p>
            <p style="font-size:1rem;"><a href="manage_profile.php">< Back to Profile</a></p>
        </div>
        <div class="login-right">
            <form id="addressForm" action="update_address.php" method="POST">
                <h2>Update Address</h2>
                <label for="address">Address:</label>
                <textarea id="address" name="address" required><?php echo htmlspecialchars($sind_address); ?></textarea>
                <label for="postcode">Postcode:</label>
                <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($sind_postcode); ?>" required>
                <label for="area">Area:</label>
                <input type="text" id="area" name="area" value="<?php echo htmlspecialchars($sind_area); ?>" readonly>
                <label for="state">State:</label>
                <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($sind_state); ?>" readonly>
                <button type="submit">Save</button>
                <p id="error-message"></p>
            </form>
        </div>
    </div>
</body>
</html>