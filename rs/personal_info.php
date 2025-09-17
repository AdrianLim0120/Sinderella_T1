<?php
$error_message = '';
$success_message = '';
$phone = $_GET['phone'] ?? $_POST['phone'] ?? '';
$id_type = $_POST['id_type'] ?? 'NI';
$ic_number = $_POST['ic_number'] ?? '';
$dob = $_POST['dob'] ?? '';
$gender = $_POST['gender'] ?? ($_POST['gender_select'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../db_connect.php';

    $stmt = $conn->prepare("SELECT sind_id FROM sinderellas WHERE sind_phno=? AND sind_status NOT IN ('inactive')");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 0) {
        $error_message = "User does not exist.";
        header("Location: signup.php?error=" . urlencode("User does not exist.") . "&phone=" . urlencode($phone));
        exit();
    }
    $stmt->close();

// --- Handle IC number, DOB, gender, and photo uploads ---
if ($id_type === 'NI') {
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
            $error_field = 'ic_number';
        }

        // Gender (12th digit)
        $gender_digit = intval(substr($ic_number, 11, 1));
        $gender = ($gender_digit % 2 === 0) ? 'female' : 'male';
    } else {
        $error_message = 'IC number must be a 12-digit numeric value.';
        $error_field = 'ic_number';
    }
} elseif ($id_type === 'PP') {
    if (!$ic_number) {
        $error_message = 'Passport number is required.';
        $error_field = 'ic_number';
    } else {
        $ic_number = strtoupper($ic_number);
        $dob = $_POST['dob'] ?? '';
        $gender = $_POST['gender_select'] ?? '';
        if (!$dob) {
            $error_message = 'Please select your date of birth.';
            $error_field = 'dob';
        }
        if (!$gender) {
            $error_message = 'Please select your gender.';
            $error_field = 'gender';
        }
    }
}

// Handle IC photo and profile photo uploads
if (!$error_message) {
    $ic_photo_path = '';
    $profile_photo_path = '';
    $stmt = $conn->prepare("SELECT sind_id FROM sinderellas WHERE sind_phno=?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->bind_result($sind_id);
    $stmt->fetch();
    $stmt->close();

    if ($sind_id) {
        // IC photo
        if (isset($_FILES['ic_photo']) && $_FILES['ic_photo']['error'] === UPLOAD_ERR_OK) {
            $ic_mime = mime_content_type($_FILES["ic_photo"]["tmp_name"]);
            if (strpos($ic_mime, 'image/') !== 0) {
                $error_message = 'IC photo must be an image file.';
                $error_field = 'ic_photo';
            } else {
                $target_dir_ic = "../img/ic_photo/";
                $target_file_ic = $target_dir_ic . str_pad($sind_id, 4, '0', STR_PAD_LEFT) . ".jpg";
                if (!move_uploaded_file($_FILES["ic_photo"]["tmp_name"], $target_file_ic)) {
                    $error_message = 'Failed to upload IC photo.';
                    $error_field = 'ic_photo';
                } else {
                    $ic_photo_path = $target_file_ic;
                }
            }
        }
        // Profile photo
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $profile_mime = mime_content_type($_FILES["profile_photo"]["tmp_name"]);
            if (strpos($profile_mime, 'image/') !== 0) {
                $error_message = 'Profile photo must be an image file.';
                $error_field = 'profile_photo';
            } else {
                $target_dir_profile = "../img/profile_photo/";
                $target_file_profile = $target_dir_profile . str_pad($sind_id, 4, '0', STR_PAD_LEFT) . ".jpg";
                if (!move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file_profile)) {
                    $error_message = 'Failed to upload profile photo.';
                    $error_field = 'profile_photo';
                } else {
                    $profile_photo_path = $target_file_profile;
                }
            }
        }
    }
}

$address = ucwords(strtolower(trim($_POST['address'] ?? '')));
$postcode = trim($_POST['postcode'] ?? '');
$area = ucwords(strtolower(trim($_POST['area'] ?? '')));
$state = ucwords(strtolower(trim($_POST['state'] ?? '')));

if (!$address || !$postcode || !$area || !$state) {
    $error_message = "Please fill in all address fields.";
} elseif (!ctype_digit($postcode) || strlen($postcode) != 5) {
    $error_message = "Invalid postcode.";
}

    if (!$error_message) {

        function capitalizeWords($str) {
            return ucwords(strtolower($str));
        }
        $emer_name = capitalizeWords(trim($_POST['emer_name'] ?? ''));
        $emer_phno = preg_replace('/[\s-]/', '', trim($_POST['emer_phno'] ?? ''));
        $race = $_POST['race'] ?? '';
        $race_other = capitalizeWords(trim($_POST['race_other'] ?? ''));
        $marital_status = $_POST['marital_status'] ?? '';
        $marital_status_other = capitalizeWords(trim($_POST['marital_status_other'] ?? ''));
        $no_kids = $_POST['no_kids'] ?? null;
        $spouse_name = capitalizeWords(trim($_POST['spouse_name'] ?? ''));
        $spouse_ic_no = preg_replace('/[\s-]/', '', trim($_POST['spouse_ic_no'] ?? ''));
        $spouse_phno = preg_replace('/[\s-]/', '', trim($_POST['spouse_phno'] ?? ''));
        $spouse_occupation = capitalizeWords(trim($_POST['spouse_occupation'] ?? ''));
        $bank_name = strtoupper(trim($_POST['bank_name'] ?? ''));
        $bank_acc_no = preg_replace('/[\s-]/', '', trim($_POST['bank_acc_no'] ?? ''));

        // Use 'others' value if selected
        if ($race === 'others') {
            if (!$race_other) {
                $ic_error_message = "Please specify your race.";
                $error_field = 'race_other';
                $error_message = $ic_error_message;
            }
            $race = $race_other;
        }
        if ($marital_status === 'others') {
            if (!$marital_status_other) {
                $ic_error_message = "Please specify your marital status.";
                $error_field = 'marital_status_other';
                $error_message = $ic_error_message;
            }
            $marital_status = $marital_status_other;
        }

        $current_year = date('Y');
        foreach ($_POST['child_name'] as $i => $child_name) {
            $child_born_year_raw = $_POST['child_born_year'][$i] ?? '';
            $child_born_year = intval($child_born_year_raw);
            if (trim($child_name) || $child_born_year_raw !== '') {
                if (!ctype_digit($child_born_year_raw) || $child_born_year < 1900 || $child_born_year > $current_year) {
                    $fm_error_message = "Child born year must be between 1900 and $current_year.";
                    $error_field = 'child_born_year_' . $i;
                    $error_message = $fm_error_message;
                    break;
                }
            }
        }

        if (!$error_message) {

            // Validate required fields
            if (!$emer_name || !$emer_phno || !$race || !$marital_status || !$bank_name || !$bank_acc_no) {
                $error_message = "Please fill in all required fields.";
            }
            // Validate phone and account number are numeric
            elseif (!ctype_digit($emer_phno)) {
                $ic_error_message = "Emergency contact phone must be numeric only.";
                $error_field = 'emer_phno';
                $error_message = $ic_error_message;
            }
            elseif ($spouse_phno && !ctype_digit($spouse_phno)) {
                $fm_error_message = "Spouse mobile number must be numeric only.";
                $error_field = 'spouse_phno';
                $error_message = $fm_error_message;
            }
            elseif (!ctype_digit($bank_acc_no)) {
                $bank_error_message = "Bank account number must be numeric only.";
                $error_field = 'bank_acc_no';
                $error_message = $bank_error_message;
            }
            elseif (!empty($spouse_ic_no) && !ctype_digit($spouse_ic_no)) {
                $fm_error_message = "Spouse IC number must be numeric only.";
                $error_field = 'spouse_ic_no';
                $error_message = $fm_error_message;
            }
            // else {
            if (!$error_message) {
                // Update sinderellas table
                // $stmt = $conn->prepare("UPDATE sinderellas SET sind_emer_name=?, sind_emer_phno=?, sind_race=?, sind_marital_status=?, sind_no_kids=?, sind_spouse_name=?, sind_spouse_ic_no=?, sind_spouse_phno=?, sind_spouse_occupation=?, sind_bank_name=?, sind_bank_acc_no=? WHERE sind_phno=?");
                // $stmt->bind_param("ssssisssssss", $emer_name, $emer_phno, $race, $marital_status, $no_kids, $spouse_name, $spouse_ic_no, $spouse_phno, $spouse_occupation, $bank_name, $bank_acc_no, $phone);
                $stmt = $conn->prepare("UPDATE sinderellas SET 
                    sind_id_type=?, sind_icno=?, sind_dob=?, sind_gender=?, sind_icphoto_path=?, sind_profile_path=?, sind_address=?, sind_postcode=?, sind_area=?, sind_state=?, 
                    sind_emer_name=?, sind_emer_phno=?, sind_race=?, sind_marital_status=?, sind_no_kids=?, 
                    sind_spouse_name=?, sind_spouse_ic_no=?, sind_spouse_phno=?, sind_spouse_occupation=?, 
                    sind_bank_name=?, sind_bank_acc_no=? WHERE sind_phno=? AND sind_status NOT IN ('inactive')");
                $stmt->bind_param(
                    "ssssssssssssssssssssss",
                    $id_type, $ic_number, $dob, $gender, $ic_photo_path, $profile_photo_path, $address, $postcode, $area, $state,
                    $emer_name, $emer_phno, $race, $marital_status, $no_kids,
                    $spouse_name, $spouse_ic_no, $spouse_phno, $spouse_occupation,
                    $bank_name, $bank_acc_no, $phone
                );
                $stmt->execute();
                $stmt->close();

                // Handle children (delete old, insert new)
                $stmt = $conn->prepare("SELECT sind_id FROM sinderellas WHERE sind_phno=?");
                $stmt->bind_param("s", $phone);
                $stmt->execute();
                $stmt->bind_result($sind_id);
                $stmt->fetch();
                $stmt->close();

                if ($sind_id) {
                    // Remove old children
                    $conn->query("DELETE FROM sind_child WHERE sind_id = $sind_id");
                    // Insert new children
                    if (!empty($_POST['child_name'])) {
                        foreach ($_POST['child_name'] as $i => $child_name) {
                            $child_name = capitalizeWords(trim($child_name));
                            $child_born_year_raw = $_POST['child_born_year'][$i] ?? '';
                            $child_born_year = intval($child_born_year_raw);
                            $child_occupation = capitalizeWords(trim($_POST['child_occupation'][$i] ?? ''));
                            // Only insert if both name and year are present and valid
                            if ($child_name && ctype_digit($child_born_year_raw) && $child_born_year >= 1900 && $child_born_year <= $current_year) {
                                $stmt = $conn->prepare("INSERT INTO sind_child (sind_id, child_name, child_born_year, child_occupation) VALUES (?, ?, ?, ?)");
                                $stmt->bind_param("isis", $sind_id, $child_name, $child_born_year, $child_occupation);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                }

                $success_message = "Profile completed successfully. You may now log in.";
                echo "<script>alert('Profile completed successfully. You may now log in.'); window.location.href = '../login_sind.php';</script>";
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
    <title>Complete Profile - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/loginstyles.css">
    <script src="../includes/js/verify_identity.js" defer></script>
    <script src="../includes/js/address_info.js" defer></script>
    <style>
        .children-table td, .children-table th { padding: 4px 8px; }
        .children-table input { 
            width: 90%; 
            border: none;
            text-align: center;
            margin: 0;
        }
        .add-row-btn { margin: 5px 0; }
        .radio-group { margin-bottom: 10px; }
        .radio-group label { margin-right: 18px; }
        .other-input { display: none;}
        label { font-weight: bold; }
        .children-table, .children-table th, .children-table td {
            border: 1px solid;
            border-collapse: collapse;
        }
        #rmv-btn {
            background-color: #f44336;
            margin: 0;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 10px;
        }
        .form-col {
            flex: 1;
            min-width: 200px;
        }
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        input[type="text"], 
        input[type="number"], 
        input[type="date"], 
        input[type="file"] {
            width: 95%;
        }
        #marital_status_other, 
        #race_other {
            width: 30%;
        }

        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 1em;
            box-sizing: border-box;
        }
    </style>
    <script>
    function addChildRow() {
        const table = document.getElementById('children-table');
        const row = table.insertRow(-1);
        row.innerHTML = `
            <td><input type="text" name="child_name[]" placeholder="Exp: Tan Xiao Hua"></td>
            <td><input type="number" name="child_born_year[]" placeholder="Exp: 2000"></td>
            <td><input type="text" name="child_occupation[]" placeholder="Exp: Student"></td>
            <td><button type="button" id="rmv-btn" onclick="this.closest('tr').remove()">Remove</button></td>
        `;
    }
    function toggleOtherInput(name, value) {
        document.getElementById(name + '_other').style.display = (value === 'others') ? 'inline' : 'none';
    }
    </script>
</head>
<body>
<div class="login-container">
    <div class="login-right">
        <form id="verifyIdentityForm" method="POST" action="personal_info.php?phone=<?php echo htmlspecialchars($phone); ?>" autocomplete="off" enctype="multipart/form-data">
            <h2>Complete Your Profile</h2>
            <p id="error-message" style="color:red;"></p>
            <?php if ($error_message): ?>
                <p style="color:red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">

            <!-- <label>Emergency Contact Name: <span style="color:red">*</span></label>
            <input type="text" name="emer_name" required
                value="<?php echo htmlspecialchars($emer_name ?? ''); ?>">

            <label>Emergency Contact Phone: <span style="color:red">*</span></label>
            <input type="text" name="emer_phno" required
                value="<?php echo htmlspecialchars($emer_phno ?? ''); ?>"> -->

            <h3>Address Information</h3>
            <label for="address">Full Address: <span style="color:red">*</span></label>
            <textarea id="address" name="address" required><?php echo htmlspecialchars($address ?? ''); ?></textarea>

            <div class="form-row">
                <div class="form-col">
                    <label for="postcode">Postcode: <span style="color:red">*</span></label>
                    <input type="text" id="postcode" name="postcode" required value="<?php echo htmlspecialchars($postcode ?? ''); ?>">
                </div>
                <div class="form-col">
                    <label for="area">Area:</label>
                    <input type="text" id="area" name="area" readonly value="<?php echo htmlspecialchars($area ?? ''); ?>">
                </div>
                <div class="form-col">
                    <label for="state">State:</label>
                    <input type="text" id="state" name="state" readonly value="<?php echo htmlspecialchars($state ?? ''); ?>">
                </div>
            </div>
            <hr>
            <p id="ic-error-message" style="color:red;"></p>
            <?php if (!empty($ic_error_message)): ?>
                <p style="color:red;"><?php echo htmlspecialchars($ic_error_message); ?></p>
            <?php endif; ?>
            <h3>Verify Identity</h3>
            <div class="form-row">
                <label>ID Type: <span style="color:red">*</span></label>
                <input type="radio" name="id_type" id="id_type_ic" value="NI" checked <?php echo (($id_type ?? 'NI') === 'NI') ? 'checked' : ''; ?>> Malaysia IC
                <input type="radio" name="id_type" id="id_type_passport" value="PP" <?php echo (($id_type ?? '') === 'PP') ? 'checked' : ''; ?>> Passport
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label for="ic_number" id="id_number_label">IC Number: <span style="color:red">*</span></label>
                    <input type="text" id="ic_number" name="ic_number" placeholder="Enter 12-digit IC number" required value="<?php echo htmlspecialchars($ic_number ?? ''); ?>">
                </div>
                <div class="form-col">
                    <label for="dob" id="dob_label">Date of Birth:</label>
                    <input type="date" id="dob" name="dob" readonly required value="<?php echo htmlspecialchars($dob ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label for="ic_photo" id="id_photo_label">Upload IC Photo: <span style="color:red">*</span></label>
                    <input type="file" id="ic_photo" name="ic_photo" accept="image/*" <?php if (empty($ic_photo_path)) echo 'required'; ?>>
                </div>
                <div class="form-col">
                    <label for="profile_photo">Upload Profile Photo: <span style="color:red">*</span></label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/*" <?php if (empty($profile_photo_path)) echo 'required'; ?>>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label for="age">Age:</label>
                    <input type="text" id="age" name="age" readonly value="<?php echo htmlspecialchars($age ?? ''); ?>">
                </div>
                <div class="form-col" id="gender_col">
                    <label for="gender" id="gender_label">Gender:</label>
                    <input type="text" id="gender" name="gender" readonly value="<?php echo htmlspecialchars($gender ?? ''); ?>">
                    <select id="gender_select" name="gender_select" style="display:none;" required>
                        <option value="" disabled selected>Select your gender</option>
                        <option value="female" <?php if(($gender ?? '')=='female') echo 'selected'; ?>>Female</option>
                        <option value="male" <?php if(($gender ?? '')=='male') echo 'selected'; ?>>Male</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>Emergency Contact Name: <span style="color:red">*</span></label>
                    <input type="text" name="emer_name" required value="<?php echo htmlspecialchars($emer_name ?? ''); ?>">
                </div>
                <div class="form-col">
                    <label>Emergency Contact Phone: <span style="color:red">*</span></label>
                    <input type="text" name="emer_phno" required value="<?php echo htmlspecialchars($emer_phno ?? ''); ?>">
                </div>
            </div>

            <label>Race: <span style="color:red">*</span></label>
            <div class="radio-group">
                <input type="radio" name="race" value="malay" onclick="toggleOtherInput('race', this.value)" required
                    <?php if (($race ?? '') === 'malay') echo 'checked'; ?>> Malay
                <input type="radio" name="race" value="chinese" onclick="toggleOtherInput('race', this.value)"
                    <?php if (($race ?? '') === 'chinese') echo 'checked'; ?>> Chinese
                <input type="radio" name="race" value="indian" onclick="toggleOtherInput('race', this.value)"
                    <?php if (($race ?? '') === 'indian') echo 'checked'; ?>> Indian
                <input type="radio" name="race" value="others" onclick="toggleOtherInput('race', this.value)"
                    <?php if (($race ?? '') === 'others' || (!in_array(($race ?? ''), ['malay','chinese','indian','others']) && !empty($race))) echo 'checked'; ?>> Others: 
                <input type="text" id="race_other" name="race_other" class="other-input" placeholder="Please specify your race"
                    value="<?php echo htmlspecialchars($race_other ?? ''); ?>">
            </div>
            
            <label>Marital Status: <span style="color:red">*</span></label>
            <div class="radio-group">
                <input type="radio" name="marital_status" value="single" onclick="toggleOtherInput('marital_status', this.value)" required
                    <?php if (($marital_status ?? '') === 'single') echo 'checked'; ?>> Single
                <input type="radio" name="marital_status" value="married" onclick="toggleOtherInput('marital_status', this.value)"
                    <?php if (($marital_status ?? '') === 'married') echo 'checked'; ?>> Married
                <input type="radio" name="marital_status" value="divorced" onclick="toggleOtherInput('marital_status', this.value)"
                    <?php if (($marital_status ?? '') === 'divorced') echo 'checked'; ?>> Divorced
                <input type="radio" name="marital_status" value="widow" onclick="toggleOtherInput('marital_status', this.value)"
                    <?php if (($marital_status ?? '') === 'widow') echo 'checked'; ?>> Widow
                <input type="radio" name="marital_status" value="others" onclick="toggleOtherInput('marital_status', this.value)"
                    <?php if (($marital_status ?? '') === 'others' || (!in_array(($marital_status ?? ''), ['single','married','divorced','widow','others']) && !empty($marital_status))) echo 'checked'; ?>> Others: 
                <input type="text" id="marital_status_other" name="marital_status_other" class="other-input" placeholder="Please specify your marital status"
                    value="<?php echo htmlspecialchars($marital_status_other ?? ''); ?>">
            </div>

            <hr>
            <?php if (!empty($fm_error_message)): ?>
                <p id="fm-error-message" style="color:red;"><?php echo htmlspecialchars($fm_error_message); ?></p>
            <?php endif; ?>
            <h3>Family Details (optional)</h3>
            <div class="form-row">
                <div class="form-col">
                    <label>Spouse Name:</label>
                    <input type="text" name="spouse_name"
                        value="<?php echo htmlspecialchars($spouse_name ?? ''); ?>">
                </div>
                <div class="form-col">
                    <label>Spouse NRIC:</label>
                    <input type="text" name="spouse_ic_no"
                        value="<?php echo htmlspecialchars($spouse_ic_no ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label>Spouse Mobile No.:</label>
                    <input type="text" name="spouse_phno"
                        value="<?php echo htmlspecialchars($spouse_phno ?? ''); ?>">
                </div>
                <div class="form-col">
                    <label>Spouse Occupation:</label>
                    <input type="text" name="spouse_occupation"
                        value="<?php echo htmlspecialchars($spouse_occupation ?? ''); ?>">
                </div>
            </div>

            <label>No. of Kids (if any):</label>
            <input type="number" name="no_kids" min="0"
                value="<?php echo htmlspecialchars($no_kids ?? ''); ?>">

            <?php
            $child_names = $_POST['child_name'] ?? [];
            $child_years = $_POST['child_born_year'] ?? [];
            $child_occs  = $_POST['child_occupation'] ?? [];
            $child_count = max(count($child_names), 1); // At least 1 row
            ?>

            <label>Children</label>
            <table id="children-table" class="children-table">
                <tr>
                    <th>Name</th>
                    <th>Born Year</th>
                    <th>Occupation</th>
                    <th></th>
                </tr>
                <?php
                for ($i = 0; $i < $child_count; $i++):
                    $name = htmlspecialchars($child_names[$i] ?? '');
                    $year = htmlspecialchars($child_years[$i] ?? '');
                    $occ  = htmlspecialchars($child_occs[$i] ?? '');
                ?>
                <tr>
                    <td><input type="text" name="child_name[]" placeholder="Exp: Tan Xiao Hua" value="<?php echo $name; ?>"></td>
                    <td><input type="number" min="1900" max="<?php echo date('Y'); ?>" name="child_born_year[]" placeholder="Exp: 2020" value="<?php echo $year; ?>"></td>
                    <td><input type="text" name="child_occupation[]" placeholder="Exp: Student" value="<?php echo $occ; ?>"></td>
                    <td><?php if ($i > 0): ?><button type="button" id="rmv-btn" onclick="this.closest('tr').remove()">Remove</button><?php endif; ?></td>
                </tr>
                <?php endfor; ?>
            </table>
            <br>
            <button type="button" class="add-row-btn" onclick="addChildRow()">Add Child</button>
            <hr>
            <?php if (!empty($bank_error_message)): ?>
                <p id="bank-error-message" style="color:red;"><?php echo htmlspecialchars($bank_error_message); ?></p>
            <?php endif; ?>
            <h3>Bank Details</h3>
            <div class="form-row">
                <div class="form-col">
                    <label>Bank Name: <span style="color:red">*</span></label>
                    <input type="text" name="bank_name" required
                        value="<?php echo htmlspecialchars($bank_name ?? ''); ?>">
                </div>
                <div class="form-col">
                    <label>Account Number: <span style="color:red">*</span></label>
                    <input type="text" name="bank_acc_no" required
                        value="<?php echo htmlspecialchars($bank_acc_no ?? ''); ?>">
                </div>
            </div>

            <button type="submit">Submit</button>
        </form>
    </div>
</div>
<script>
    // Show "other" input if already selected on reload
    // window.onload = function() {
    //     var race = document.querySelector('input[name="race"]:checked');
    //     if (race && race.value === 'others') document.getElementById('race_other').style.display = 'block';
    //     var ms = document.querySelector('input[name="marital_status"]:checked');
    //     if (ms && ms.value === 'others') document.getElementById('marital_status_other').style.display = 'block';
    // };

// document.getElementById('ic_number').addEventListener('input', function() {
//     validateAndFillIC(this.value);
// });
function validateAndFillIC(icNumber) {
    const errorMessage = document.getElementById('ic-error-message');
    const dobInput = document.getElementById('dob');
    const ageInput = document.getElementById('age');
    const genderInput = document.getElementById('gender');
    errorMessage.innerText = '';
    dobInput.value = '';
    ageInput.value = '';
    genderInput.value = '';

    if (!/^\d{12}$/.test(icNumber)) {
        errorMessage.innerText = 'IC number must be a 12-digit numeric value.';
        return false;
    }
    let year = parseInt(icNumber.substring(0, 2), 10);
    let month = parseInt(icNumber.substring(2, 4), 10);
    let day = parseInt(icNumber.substring(4, 6), 10);
    const currentYear = new Date().getFullYear() % 100;
    const fullYear = (year > currentYear ? 1900 + year : 2000 + year);
    const dobDate = new Date(fullYear, month - 1, day);
    if (month < 1 || month > 12) {
        errorMessage.innerText = 'Invalid IC number: Month is not valid.';
        return false;
    }
    if (day < 1 || day > 31) {
        errorMessage.innerText = 'Invalid IC number: Day is not valid.';
        return false;
    }
    if (
        dobDate.getFullYear() !== fullYear ||
        dobDate.getMonth() !== month - 1 ||
        dobDate.getDate() !== day
    ) {
        errorMessage.innerText = 'Invalid IC number: Date is not valid.';
        return false;
    }
    dobInput.value = dobDate.toISOString().slice(0, 10);
    let age = new Date().getFullYear() - fullYear;
    ageInput.value = age;
    const genderDigit = parseInt(icNumber.charAt(11), 10);
    genderInput.value = (genderDigit % 2 === 0) ? 'Female' : 'Male';
    return true;
}

document.getElementById('postcode').addEventListener('input', function() {
    const postcode = this.value;
    const errorMessage = document.getElementById('error-message');

    // Clear previous error message
    if (errorMessage) errorMessage.innerText = '';

    // Validate postcode
    if (!/^\d{5}$/.test(postcode)) {
        if (errorMessage) errorMessage.innerText = 'Postcode must be a 5-digit number.';
        document.getElementById('area').value = '';
        document.getElementById('state').value = '';
        return;
    }

    // Fetch area and state from postcode.json
    fetch('../data/postcode.json')
        .then(response => response.json())
        .then(data => {
            let found = false;
            data.state.forEach(state => {
                state.city.forEach(city => {
                    if (city.postcode && city.postcode.includes(postcode)) {
                        document.getElementById('area').value = city.name;
                        document.getElementById('state').value = state.name;
                        found = true;
                    }
                });
            });

            if (!found) {
                if (errorMessage) errorMessage.innerText = 'Invalid postcode. Area and state not found.';
                document.getElementById('area').value = '';
                document.getElementById('state').value = '';
            }
        })
        .catch(error => {
            if (errorMessage) errorMessage.innerText = 'Error fetching postcode data.';
        });
});

function toggleIdType(type) {
    console.log('toggleIdType called with:', type);
    document.getElementById('ic-error-message').innerText = '';
    const idNumberLabel = document.getElementById('id_number_label');
    const idPhotoLabel = document.getElementById('id_photo_label');
    const icNumber = document.getElementById('ic_number');
    const dobLabel = document.getElementById('dob_label');
    const dob = document.getElementById('dob');
    const age = document.getElementById('age');
    const genderInput = document.getElementById('gender');
    const genderSelect = document.getElementById('gender_select');
    const genderLabel = document.getElementById('gender_label');
    // const genderInput = document.getElementById('gender');

    if (type === 'PP') {
        idNumberLabel.innerHTML = 'Passport Number: <span style="color:red">*</span>';
        idPhotoLabel.innerHTML = 'Upload Passport Photo: <span style="color:red">*</span>';

        icNumber.placeholder = "Enter Passport Number";
        // icNumber.value = '';
        icNumber.removeEventListener('input', icInputHandler);

        dobLabel.innerHTML = 'Date of Birth: <span style="color:red">*</span>';
        dob.readOnly = false;
        // dob.value = '';
        // age.value = '';

        genderInput.style.display = 'none';
        genderLabel.innerHTML = 'Gender: <span style="color:red">*</span>';
        genderSelect.style.display = '';
        // genderSelect.value = '';
        genderSelect.required = true;

    } else if (type === 'NI') {
        idNumberLabel.innerHTML = 'IC Number: <span style="color:red">*</span>';
        idPhotoLabel.innerHTML = 'Upload IC Photo: <span style="color:red">*</span>';
        
        icNumber.placeholder = "Enter 12-digit IC number";
        // icNumber.value = '';
        icNumber.removeEventListener('input', icInputHandler);
        icNumber.addEventListener('input', icInputHandler);

        dobLabel.innerHTML = 'Date of Birth:';
        dob.readOnly = true;
        // dob.value = '';
        // age.value = '';

        genderLabel.innerHTML = 'Gender:';
        genderInput.style.display = '';
        genderLabel.style.display = '';
        genderSelect.style.display = 'none';
        // genderInput.value = '';
        genderSelect.required = false;
    }
}

// Handler for IC input
function icInputHandler() {
    validateAndFillIC(this.value);
}

// Attach handler on load if Malaysia IC is selected
window.onload = function() {
    var race = document.querySelector('input[name="race"]:checked');
    if (race && race.value === 'others') document.getElementById('race_other').style.display = 'block';
    var ms = document.querySelector('input[name="marital_status"]:checked');
    if (ms && ms.value === 'others') document.getElementById('marital_status_other').style.display = 'block';

    // Set up IC/Passport toggle
    if(document.getElementById('id_type_ic').checked) {
        // document.getElementById('ic_number').addEventListener('input', icInputHandler);
        toggleIdType('NI');
    } else {
        // document.getElementById('ic_number').removeEventListener('input', icInputHandler);
        toggleIdType('PP');
    }
    document.getElementById('id_type_ic').onclick = function() { toggleIdType('NI'); };
    document.getElementById('id_type_passport').onclick = function() { toggleIdType('PP'); };

    // Error field focus and highlight
    <?php if (!empty($error_field)): ?>
    var field = document.getElementsByName('<?php echo $error_field; ?>')[0];
    if (field) {
        field.focus();
        field.scrollIntoView({behavior: "smooth", block: "center"});
        field.style.borderColor = "red";
        field.style.boxShadow = "0 0 5px red";
        field.addEventListener('input', function() {
            field.style.borderColor = "";
            field.style.boxShadow = "";
        });
    }
    <?php endif; ?>
};

// Update age when DOB changes (for passport)
document.getElementById('dob').addEventListener('change', function() {
    if(document.getElementById('id_type_passport').checked) {
        let dobVal = this.value;
        if(dobVal) {
            let dobDate = new Date(dobVal);
            let today = new Date();
            let age = today.getFullYear() - dobDate.getFullYear();
            if (
                today.getMonth() < dobDate.getMonth() ||
                (today.getMonth() === dobDate.getMonth() && today.getDate() < dobDate.getDate())
            ) {
                age--;
            }
            document.getElementById('age').value = age;
        } else {
            document.getElementById('age').value = '';
        }
    }
});

// document.getElementById('id_type_ic').addEventListener('click', function() { toggleIdType('NI'); });
// document.getElementById('id_type_passport').addEventListener('click', function() { toggleIdType('PP'); });

// For gender select (passport)
document.getElementById('gender_select').addEventListener('change', function() {
    document.getElementById('gender').value = this.value ? this.value.charAt(0).toUpperCase() + this.value.slice(1) : '';
});
</script>
</body>
</html>