<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cust_name = ucwords(strtolower(trim($_POST['cust_name'])));
    $cust_phno = $_POST['cust_phno'];
    // $cust_address = ucwords(strtolower(trim($_POST['cust_address'])));
    // $cust_postcode = $_POST['cust_postcode'];
    // $cust_area = $_POST['cust_area'];
    // $cust_state = $_POST['cust_state'];
    $cust_emer_name = ucwords(strtolower(trim($_POST['cust_emer_name'])));
    $cust_emer_phno = $_POST['cust_emer_phno'];
    $cust_pwd = bin2hex(random_bytes(4)); // Generate a random password
    $cust_status = 'active';

    // Check if phone number is already used by another active customer
    $stmt = $conn->prepare("SELECT COUNT(*) FROM customers WHERE cust_phno = ? AND cust_status = 'active'");
    $stmt->bind_param("s", $cust_phno);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $error_message = 'The phone number is already used by another active customer.';
    } else {
        // 1. Insert into `customers`
        $stmt = $conn->prepare("INSERT INTO customers (cust_name, cust_phno, cust_pwd, cust_status, cust_emer_name, cust_emer_phno) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $cust_name, $cust_phno, $cust_pwd, $cust_status, $cust_emer_name, $cust_emer_phno);
        if (!$stmt->execute()) {
            $error_message = 'Error creating customer: ' . $stmt->error;
            $stmt->close();
        } else {
            $new_cust_id = $stmt->insert_id;
            // $stmt->close();

            // // 2. Insert address into `cust_addresses`
            // $stmt = $conn->prepare("INSERT INTO cust_addresses (cust_id, cust_address, cust_postcode, cust_area, cust_state) VALUES (?, ?, ?, ?, ?)");
            // $stmt->bind_param("issss", $new_cust_id, $cust_address, $cust_postcode, $cust_area, $cust_state);
            // if (!$stmt->execute()) {
            //     $error_message = 'Error saving address: ' . $stmt->error;
            // }
            // $stmt->close();

            if (empty($error_message)) {
                // header("Location: view_customers.php");
                header("Location: edit_customer.php?cust_id=" . $new_cust_id);
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
    <title>Add Customer - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .profile-container label {
            display: block;
            margin-top: 10px;
        }
        .profile-container input[type="text"],
        .profile-container input[type="number"],
        .profile-container select,
        .profile-container textarea {
            width: calc(50% - 10px);
            padding: 5px;
            margin-right: 10px;
        }
        .profile-container button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .profile-container button:hover {
            background-color: #0056b3;
        }
        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
        .invalid-postcode {
            color: red;
            margin-top: 5px;
        }
    </style>
    <script>
        // const postcodeData = <?php echo file_get_contents('../data/postcode.json'); ?>;

        // function updateAreaAndState() {
        //     const postcode = document.getElementById('cust_postcode').value;
        //     let area = '';
        //     let state = '';
        //     let validPostcode = false;

        //     postcodeData.state.forEach(stateObj => {
        //         stateObj.city.forEach(cityObj => {
        //             if (cityObj.postcode.includes(postcode)) {
        //                 area = cityObj.name;
        //                 state = stateObj.name;
        //                 validPostcode = true;
        //             }
        //         });
        //     });

        //     // if (validPostcode) {
        //     //     document.getElementById('cust_area').value = area;
        //     //     document.getElementById('cust_state').value = state;
        //     //     document.getElementById('invalid-postcode').style.display = 'none';
        //     // } else {
        //     //     document.getElementById('cust_area').value = '';
        //     //     document.getElementById('cust_state').value = '';
        //     //     document.getElementById('invalid-postcode').style.display = 'block';
        //     // }
        // }

        // function validateForm() {
        //     const area = document.getElementById('cust_area').value;
        //     const state = document.getElementById('cust_state').value;
        //     if (!area || !state) {
        //         alert('Please enter a valid postcode to populate the area and state fields.');
        //         return false;
        //     }
        //     return true;
        // }

        // function showError(message) {
        //     alert(message);
        // }

        // <?php if (!empty($error_message)): ?>
        //     showError('<?php echo $error_message; ?>');
        // <?php endif; ?>
    </script>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Add Customer</h2>
                <!-- <form method="POST" action="" onsubmit="return validateForm()"> -->
                <form method="POST" action="">
                    <label for="cust_name">Name</label>
                    <input type="text" id="cust_name" name="cust_name" value="<?php echo htmlspecialchars($_POST['cust_name'] ?? ''); ?>" required>

                    <label for="cust_phno">Phone Number</label>
                    <input type="text" id="cust_phno" name="cust_phno" value="<?php echo htmlspecialchars($_POST['cust_phno'] ?? ''); ?>" required>

                    <label for="cust_emer_name">Emergency Contact Name</label>
                    <input type="text" id="cust_emer_name" name="cust_emer_name" value="<?php echo htmlspecialchars($_POST['cust_emer_name'] ?? ''); ?>" required>

                    <label for="cust_emer_phno">Emergency Contact Number</label>
                    <input type="text" id="cust_emer_phno" name="cust_emer_phno" value="<?php echo htmlspecialchars($_POST['cust_emer_phno'] ?? ''); ?>" required>

                    <!-- <label for="cust_address">Address</label>
                    <textarea id="cust_address" name="cust_address" required><?php echo htmlspecialchars($_POST['cust_address'] ?? ''); ?></textarea>

                    <label for="cust_postcode">Postcode</label>
                    <input type="text" id="cust_postcode" name="cust_postcode" value="<?php echo htmlspecialchars($_POST['cust_postcode'] ?? ''); ?>" required oninput="updateAreaAndState()">
                    <div id="invalid-postcode" class="invalid-postcode" style="display: none;">Invalid postcode. Please enter a valid postcode.</div>

                    <label for="cust_area">Area</label>
                    <input type="text" id="cust_area" name="cust_area" value="<?php echo htmlspecialchars($_POST['cust_area'] ?? ''); ?>" readonly required>

                    <label for="cust_state">State</label>
                    <input type="text" id="cust_state" name="cust_state" value="<?php echo htmlspecialchars($_POST['cust_state'] ?? ''); ?>" readonly required> -->

                    <div class="button-container">
                        <button type="submit">Add Customer</button>
                        <button type="button" onclick="window.location.href='view_customers.php'">Back</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function showError(message) {
            alert(message);
        }
        <?php if (!empty($error_message)): ?>
            showError('<?php echo addslashes($error_message); ?>');
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// // $conn->close();
?>