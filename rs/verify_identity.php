<?php
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ic_number = $_POST['ic_number'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // --- Extract DOB and Gender from IC number in PHP ---
    $dob = '';
    $gender = '';
    if (ctype_digit($ic_number) && strlen($ic_number) == 12) {
        $year = intval(substr($ic_number, 0, 2));
        $month = intval(substr($ic_number, 2, 2));
        $day = intval(substr($ic_number, 4, 2));
        $currentYear = intval(date('y'));
        $fullYear = ($year > $currentYear ? 1900 + $year : 2000 + $year);

        // Validate month and day
        if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && checkdate($month, $day, $fullYear)) {
            $dob = sprintf('%04d-%02d-%02d', $fullYear, $month, $day);
        } else {
            $error_message = 'Invalid IC number: Date is not valid.';
        }

        // Gender (12th digit)
        $gender_digit = intval(substr($ic_number, 11, 1));
        $gender = ($gender_digit % 2 === 0) ? 'female' : 'male';
    } else {
        $error_message = 'IC number must be a 12-digit numeric value.';
    }

    // Validate file uploads
    if (!$error_message) {
        if (
            !isset($_FILES['ic_photo']) || $_FILES['ic_photo']['error'] !== UPLOAD_ERR_OK ||
            !isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK
        ) {
            $error_message = 'Both IC photo and profile photo are required.';
        } else {
            // Validate IC photo is an image
            $ic_mime = mime_content_type($_FILES["ic_photo"]["tmp_name"]);
            if (strpos($ic_mime, 'image/') !== 0) {
                $error_message = 'IC photo must be an image file.';
            }
            // Validate profile photo is an image
            $profile_mime = mime_content_type($_FILES["profile_photo"]["tmp_name"]);
            if (strpos($profile_mime, 'image/') !== 0) {
                $error_message = 'Profile photo must be an image file.';
            }
        }
    }

    if (!$error_message) {
        // Database connection
        require_once '../db_connect.php';
        if ($conn->connect_error) {
            $error_message = "Connection failed: " . $conn->connect_error;
        } else {
            // Check if user exists
            $stmt = $conn->prepare("SELECT sind_id FROM sinderellas WHERE sind_phno = ? AND (sind_icno IS NULL OR sind_icno='')");
            if (!$stmt) {
                $error_message = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param("s", $phone);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows == 0) {
                    $error_message = 'User does not exist.';
                } else {
                    $stmt->bind_result($sind_id);
                    $stmt->fetch();
                    $stmt->close();

                    // Handle IC photo upload
                    $target_dir_ic = "../img/ic_photo/";
                    $target_file_ic = $target_dir_ic . str_pad($sind_id, 4, '0', STR_PAD_LEFT) . ".jpg";
                    if (!move_uploaded_file($_FILES["ic_photo"]["tmp_name"], $target_file_ic)) {
                        $error_message = 'Failed to upload IC photo.';
                    } else {
                        // Handle profile photo upload
                        $target_dir_profile = "../img/profile_photo/";
                        $target_file_profile = $target_dir_profile . str_pad($sind_id, 4, '0', STR_PAD_LEFT) . ".jpg";
                        if (!move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file_profile)) {
                            $error_message = 'Failed to upload profile photo.';
                        } else {
                            // Update database with IC number, dob, gender, and photo paths
                            $stmt = $conn->prepare("UPDATE sinderellas SET sind_icno = ?, sind_dob = ?, sind_gender = ?, sind_icphoto_path = ?, sind_profile_path = ? WHERE sind_id = ?");
                            if (!$stmt) {
                                $error_message = "Database error: " . $conn->error;
                            } else {
                                $stmt->bind_param("sssssi", $ic_number, $dob, $gender, $target_file_ic, $target_file_profile, $sind_id);
                                $stmt->execute();
                                $stmt->close();
                                // echo "<script>alert('Identity submitted successfully.\\nYou may now log in to your account.'); window.location.href = '../login_sind.php';</script>";
                                echo "<script>window.location.href = 'personal_info.php?phone=" . urlencode($phone) . "';</script>";
                                exit();
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
    <title>Verify Identity - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/loginstyles.css">
    <script src="../includes/js/verify_identity.js" defer></script>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <img src="../img/sinderella_logo.png" alt="Sinderella">
            <p>Love cleaning? Love flexibility & freedom?</p>
        </div>
        <div class="login-right">
            <form id="verifyIdentityForm" action="verify_identity.php" method="POST" enctype="multipart/form-data">
                <h2>Verify Identity</h2>
                
                <p id="error-message" style="color:red;"><?php echo htmlspecialchars($error_message); ?></p>
                
                <label for="ic_number">IC Number:</label>
                <input type="text" id="ic_number" name="ic_number" placeholder="Enter 12-digit IC number" required>
                
                <label for="ic_photo">Upload IC Photo:</label>
                <input type="file" id="ic_photo" name="ic_photo" accept="image/*" required>
                
                <label for="profile_photo">Upload Profile Photo:</label>
                <input type="file" id="profile_photo" name="profile_photo" accept="image/*" required>
                
                <label for="dob">Date of Birth:</label>
                <input type="date" id="dob" name="dob" readonly>

                <label for="age">Age:</label>
                <input type="text" id="age" name="age" readonly>

                <label for="gender">Gender:</label>
                <input type="text" id="gender" name="gender" readonly>

                <!-- <input type="hidden" id="phone" name="phone" value="<?php echo htmlspecialchars($_GET['phone'] ?? ''); ?>"> -->
                <input type="hidden" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? $_GET['phone'] ?? ''); ?>">
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>