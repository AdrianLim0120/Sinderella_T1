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
    $sind_name = ucwords(strtolower(trim($_POST['sind_name'])));
    $sind_phno = $_POST['sind_phno'];
    $sind_address = ucwords(strtolower(trim($_POST['sind_address'])));
    $sind_postcode = $_POST['sind_postcode'];
    $sind_area = $_POST['sind_area'];
    $sind_state = $_POST['sind_state'];
    $sind_ic = $_POST['sind_ic'];
    $sind_pwd = bin2hex(random_bytes(4)); // Generate a random password
    $sind_status = 'pending';

    // Check if phone number is already used
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sinderellas WHERE sind_phno = ?");
    $stmt->bind_param("s", $sind_phno);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $error_message = 'The phone number is already used by another Sinderella.';
    } else {
        // Handle file uploads
        $sind_ic_photo = $_FILES['sind_ic_photo']['name'];
        $sind_profile_photo = $_FILES['sind_profile_photo']['name'];

        $sind_upline_id = 0;
        $stmt = $conn->prepare("INSERT INTO sinderellas (sind_name, sind_phno, sind_pwd, sind_address, sind_postcode, sind_area, sind_state, sind_icno, sind_status, sind_upline_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssi", $sind_name, $sind_phno, $sind_pwd, $sind_address, $sind_postcode, $sind_area, $sind_state, $sind_ic, $sind_status, $sind_upline_id);
        $stmt->execute();
        $sind_id = $stmt->insert_id;
        $stmt->close();

        // Save IC photo
        $target_dir_ic = "../img/ic_photo/";
        $target_file_ic = $target_dir_ic . str_pad($sind_id, 4, '0', STR_PAD_LEFT) . ".jpg";
        if (!move_uploaded_file($_FILES["sind_ic_photo"]["tmp_name"], $target_file_ic)) {
            $error_message = 'Failed to upload IC photo.';
        }

        // Save profile photo
        $target_dir_profile = "../img/profile_photo/";
        $target_file_profile = $target_dir_profile . str_pad($sind_id, 4, '0', STR_PAD_LEFT) . ".jpg";
        if (!move_uploaded_file($_FILES["sind_profile_photo"]["tmp_name"], $target_file_profile)) {
            $error_message = 'Failed to upload profile photo.';
        }

        // Update database with photo paths
        $stmt = $conn->prepare("UPDATE sinderellas SET sind_icphoto_path = ?, sind_profile_path = ? WHERE sind_id = ?");
        $stmt->bind_param("ssi", $target_file_ic, $target_file_profile, $sind_id);
        $stmt->execute();
        $stmt->close();

        if (empty($error_message)) {
            header("Location: view_sinderellas.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Sinderella - Admin - Sinderella</title>
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
        const postcodeData = <?php echo file_get_contents('../data/postcode.json'); ?>;

        function updateAreaAndState() {
            const postcode = document.getElementById('sind_postcode').value;
            let area = '';
            let state = '';
            let validPostcode = false;

            postcodeData.state.forEach(stateObj => {
                stateObj.city.forEach(cityObj => {
                    if (cityObj.postcode.includes(postcode)) {
                        area = cityObj.name;
                        state = stateObj.name;
                        validPostcode = true;
                    }
                });
            });

            if (validPostcode) {
                document.getElementById('sind_area').value = area;
                document.getElementById('sind_state').value = state;
                document.getElementById('invalid-postcode').style.display = 'none';
            } else {
                document.getElementById('sind_area').value = '';
                document.getElementById('sind_state').value = '';
                document.getElementById('invalid-postcode').style.display = 'block';
            }
        }

        function validateForm() {
            const area = document.getElementById('sind_area').value;
            const state = document.getElementById('sind_state').value;
            if (!area || !state) {
                alert('Please enter a valid postcode to populate the area and state fields.');
                return false;
            }
            return true;
        }

        function showError(message) {
            alert(message);
        }

        <?php if (!empty($error_message)): ?>
            showError('<?php echo $error_message; ?>');
        <?php endif; ?>
    </script>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Add Sinderella</h2>
                <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateForm()">
                    <label for="sind_name">Name</label>
                    <input type="text" id="sind_name" name="sind_name" value="<?php echo htmlspecialchars($_POST['sind_name'] ?? ''); ?>" required>

                    <label for="sind_phno">Phone Number</label>
                    <input type="text" id="sind_phno" name="sind_phno" value="<?php echo htmlspecialchars($_POST['sind_phno'] ?? ''); ?>" required>

                    <label for="sind_address">Address</label>
                    <textarea id="sind_address" name="sind_address" required><?php echo htmlspecialchars($_POST['sind_address'] ?? ''); ?></textarea>

                    <label for="sind_postcode">Postcode</label>
                    <input type="text" id="sind_postcode" name="sind_postcode" value="<?php echo htmlspecialchars($_POST['sind_postcode'] ?? ''); ?>" required oninput="updateAreaAndState()">
                    <div id="invalid-postcode" class="invalid-postcode" style="display: none;">Invalid postcode. Please enter a valid postcode.</div>

                    <label for="sind_area">Area</label>
                    <input type="text" id="sind_area" name="sind_area" value="<?php echo htmlspecialchars($_POST['sind_area'] ?? ''); ?>" readonly required>

                    <label for="sind_state">State</label>
                    <input type="text" id="sind_state" name="sind_state" value="<?php echo htmlspecialchars($_POST['sind_state'] ?? ''); ?>" readonly required>

                    <label for="sind_ic">IC Number</label>
                    <input type="text" id="sind_ic" name="sind_ic" value="<?php echo htmlspecialchars($_POST['sind_ic'] ?? ''); ?>" required>

                    <label for="sind_ic_photo">IC Photo</label>
                    <input type="file" id="sind_ic_photo" name="sind_ic_photo" required>

                    <label for="sind_profile_photo">Profile Photo</label>
                    <input type="file" id="sind_profile_photo" name="sind_profile_photo" required>

                    <div class="button-container">
                        <button type="submit">Add Sinderella</button>
                        <button type="button" onclick="window.location.href='view_sinderellas.php'">Back</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// // $conn->close();
?>