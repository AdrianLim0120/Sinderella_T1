<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}
require_once '../db_connect.php';

$sind_id = isset($_GET['sind_id']) ? intval($_GET['sind_id']) : 0;
$error_message = '';
$success_message = '';

$active_tab = 'personal';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_family'])) {
        $active_tab = 'family';
    } elseif (isset($_POST['save_bank'])) {
        $active_tab = 'bank';
    } elseif (isset($_POST['save_personal'])) {
        $active_tab = 'personal';
    } elseif (isset($_POST['save_service_area'])) {
        $active_tab = 'service_area';
    }
} elseif (isset($_GET['active_tab'])) {
    $active_tab = $_GET['active_tab'];
}
if (isset($_GET['success']) && $_GET['success'] == 1 && $active_tab == 'service_area') {
    $success_message = "Service area updated successfully.";
}

// Fetch main profile info
$stmt = $conn->prepare("SELECT sind_name, sind_phno, sind_address, sind_postcode, sind_area, sind_state, sind_icno, sind_dob, sind_gender, sind_emer_name, sind_emer_phno, sind_race, sind_marital_status, sind_no_kids, sind_spouse_name, sind_spouse_phno, sind_spouse_ic_no, sind_spouse_occupation, sind_status, sind_icphoto_path, sind_profile_path, sind_upline_id, acc_approved, sind_bank_name, sind_bank_acc_no FROM sinderellas WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($sind_name, $sind_phno, $sind_address, $sind_postcode, $sind_area, $sind_state, $sind_icno, $sind_dob, $sind_gender, $sind_emer_name, $sind_emer_phno, $sind_race, $sind_marital_status, $no_kids, $spouse_name, $spouse_phno, $spouse_ic_no, $spouse_occupation, $sind_status, $sind_ic_photo, $sind_profile_photo, $sind_upline_id, $acc_approved, $bank_name, $bank_acc_no);
$stmt->fetch();
$stmt->close();

// Upline name
$upline_name = "N/A";
if ($sind_upline_id === null || $sind_upline_id === '' || $sind_upline_id === false) {
    $upline_name = "N/A";
} elseif ($sind_upline_id == 0) {
    $upline_name = "Sinderella";
} else {
    $stmt = $conn->prepare("SELECT sind_name FROM sinderellas WHERE sind_id = ?");
    $stmt->bind_param("i", $sind_upline_id);
    $stmt->execute();
    $stmt->bind_result($found_upline_name);
    if ($stmt->fetch()) {
        $upline_name = $found_upline_name;
    }
    $stmt->close();
}

// Ratings
$stmt = $conn->prepare("SELECT AVG(rate) as avg_rating, COUNT(*) as review_count FROM booking_ratings WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($avg_rating, $review_count);
$stmt->fetch();
$stmt->close();
$avg_rating = $avg_rating ? round($avg_rating, 1) : null;

// Children
$children = [];
$stmt = $conn->prepare("SELECT child_name, child_born_year, child_occupation FROM sind_child WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($child_name, $child_born_year, $child_occupation);
while ($stmt->fetch()) {
    $children[] = [
        'name' => $child_name,
        'born_year' => $child_born_year,
        'occupation' => $child_occupation
    ];
}
$stmt->close();

// Labels
$labels = $conn->query("SELECT slbl_id, slbl_name FROM sind_label WHERE slbl_status = 'Active'");
$selected_labels = [];
$stmt = $conn->prepare("SELECT slbl_id FROM sind_id_label WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($slbl_id);
while ($stmt->fetch()) {
    $selected_labels[] = $slbl_id;
}
$stmt->close();

// Service Areas
$service_areas = [];
$stmt = $conn->prepare("SELECT area, state FROM sind_service_area WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($area, $state);
while ($stmt->fetch()) {
    if (!isset($service_areas[$state])) $service_areas[$state] = [];
    $service_areas[$state][] = $area;
}
$stmt->close();

// Fetch all rejected history rows first
$rejected_rows = [];
$stmt = $conn->prepare("SELECT booking_id, reason, created_at FROM sind_rejected_hist WHERE sind_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($booking_id, $rej_reason, $rej_created_at);
while ($stmt->fetch()) {
    $rejected_rows[] = [
        'booking_id' => $booking_id,
        'reason' => $rej_reason,
        'created_at' => $rej_created_at
    ];
}
$stmt->close();

$rejected_this_month = 0;
$total_rejected = 0;
$rejected_details = [];
foreach ($rejected_rows as $row) {
    $total_rejected++;
    $rej_month = date('Y-m', strtotime($row['created_at']));
    if ($rej_month == date('Y-m')) $rejected_this_month++;
    // Now safely run the second query
    $booking_date = $booking_from = $booking_to = '';
    $stmt2 = $conn->prepare("SELECT booking_date, booking_from_time, booking_to_time FROM bookings WHERE booking_id = ?");
    $stmt2->bind_param("i", $row['booking_id']);
    $stmt2->execute();
    $stmt2->bind_result($booking_date, $booking_from, $booking_to);
    $stmt2->fetch();
    $stmt2->close();
    $rejected_details[] = [
        'booking_date' => $booking_date,
        'booking_from' => $booking_from,
        'booking_to' => $booking_to,
        'reason' => $row['reason'],
        'created_at' => $row['created_at']
    ];
}

// Handle form submissions
$current_year = date('Y');

// --- Personal Details Update ---
if (isset($_POST['save_personal'])) {
    function capitalizeWords($str) { return ucwords(strtolower($str)); }
    $sind_name_new = capitalizeWords(trim($_POST['sind_name'] ?? ''));
    $sind_phno_new = preg_replace('/[\s-]/', '', trim($_POST['sind_phno'] ?? ''));
    $sind_address_new = capitalizeWords(trim($_POST['sind_address'] ?? ''));
    $sind_postcode_new = trim($_POST['sind_postcode'] ?? '');
    $sind_area_new = capitalizeWords(trim($_POST['sind_area'] ?? ''));
    $sind_state_new = capitalizeWords(trim($_POST['sind_state'] ?? ''));
    $sind_icno_new = trim($_POST['sind_icno'] ?? '');
    $sind_emer_name_new = capitalizeWords(trim($_POST['sind_emer_name'] ?? ''));
    $sind_emer_phno_new = preg_replace('/[\s-]/', '', trim($_POST['sind_emer_phno'] ?? ''));
    $race = $_POST['race'] ?? '';
    $race_other = capitalizeWords(trim($_POST['race_other'] ?? ''));
    $sind_status_new = $_POST['sind_status'] ?? $sind_status;
    $selected_labels = isset($_POST['sind_labels']) ? $_POST['sind_labels'] : [];
    $selected_upline_id = isset($_POST['sind_upline_id']) ? intval($_POST['sind_upline_id']) : $sind_upline_id;

    // IC number to DOB and gender
    $dob = '';
    $gender = '';
    if (ctype_digit($sind_icno_new) && strlen($sind_icno_new) == 12) {
        $year = intval(substr($sind_icno_new, 0, 2));
        $month = intval(substr($sind_icno_new, 2, 2));
        $day = intval(substr($sind_icno_new, 4, 2));
        $currentYear = intval(date('y'));
        $fullYear = ($year > $currentYear ? 1900 + $year : 2000 + $year);
        if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && checkdate($month, $day, $fullYear)) {
            $dob = sprintf('%04d-%02d-%02d', $fullYear, $month, $day);
        } else {
            $error_message = 'Invalid IC number: Date is not valid.';
        }
        $gender_digit = intval(substr($sind_icno_new, 11, 1));
        $gender = ($gender_digit % 2 === 0) ? 'female' : 'male';
    } else {
        $error_message = 'IC number must be a 12-digit numeric value.';
    }

    // Use 'others' value if selected
    if ($race === 'others') {
        if (!$race_other) $error_message = "Please specify your race.";
        $race = capitalizeWords($race_other);
    } else {
        // $race = capitalizeWords($race_other);
    }

    // Validate required fields
    if (!$sind_name_new || !$sind_phno_new || !$sind_address_new || !$sind_postcode_new || !$sind_area_new || !$sind_state_new || !$sind_icno_new || !$dob || !$gender || !$race || !$sind_emer_name_new || !$sind_emer_phno_new) {
        $error_message = "Please fill in all required fields.";
    } elseif (!ctype_digit($sind_phno_new)) {
        $error_message = "Phone number must be numeric only.";
    } elseif (!ctype_digit($sind_emer_phno_new)) {
        $error_message = "Emergency contact phone must be numeric only.";
    }

    // Handle photo uploads (optional, similar to your current logic)
    $target_dir_ic = "../img/ic_photo/";
    $target_file_ic = $target_dir_ic . str_pad($sind_id, 4, '0', STR_PAD_LEFT) . ".jpg";
    if (isset($_FILES['sind_ic_photo']) && $_FILES['sind_ic_photo']['error'] === UPLOAD_ERR_OK) {
        if (!move_uploaded_file($_FILES['sind_ic_photo']['tmp_name'], $target_file_ic)){
            $error_message .= 'Failed to upload IC photo. ';
        }
    }
    $target_dir_profile = "../img/profile_photo/";
    $target_file_profile = $target_dir_profile . str_pad($sind_id, 4, '0', STR_PAD_LEFT) . ".jpg";
    if (isset($_FILES['sind_profile_photo']) && $_FILES['sind_profile_photo']['error'] === UPLOAD_ERR_OK) {
        if (!move_uploaded_file($_FILES['sind_profile_photo']['tmp_name'], $target_file_profile)){
            $error_message .= 'Failed to upload profile photo. ';
        }
    }

    if (!$error_message) {
        $stmt = $conn->prepare("UPDATE sinderellas SET sind_name=?, sind_phno=?, sind_address=?, sind_postcode=?, sind_area=?, sind_state=?, sind_icno=?, sind_dob=?, sind_gender=?, sind_emer_name=?, sind_emer_phno=?, sind_race=?, sind_status=?, sind_icphoto_path=?, sind_profile_path=?, sind_upline_id=? WHERE sind_id=?");
        $stmt->bind_param("ssssssssssssssssi", $sind_name_new, $sind_phno_new, $sind_address_new, $sind_postcode_new, $sind_area_new, $sind_state_new, $sind_icno_new, $dob, $gender, $sind_emer_name_new, $sind_emer_phno_new, $race, $sind_status_new, $target_file_ic, $target_file_profile, $selected_upline_id, $sind_id);
        $stmt->execute();
        $stmt->close();

        // Update labels
        $stmt = $conn->prepare("DELETE FROM sind_id_label WHERE sind_id = ?");
        $stmt->bind_param("i", $sind_id);
        $stmt->execute();
        $stmt->close();
        foreach ($selected_labels as $label_id) {
            $stmt = $conn->prepare("INSERT INTO sind_id_label (sind_id, slbl_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $sind_id, $label_id);
            $stmt->execute();
            $stmt->close();
        }
        $success_message = "Personal details updated successfully.";
        // Update variables for form restoration
        $sind_name = $sind_name_new;
        $sind_phno = $sind_phno_new;
        $sind_address = $sind_address_new;
        $sind_postcode = $sind_postcode_new;
        $sind_area = $sind_area_new;
        $sind_state = $sind_state_new;
        $sind_icno = $sind_icno_new;
        $sind_dob = $dob;
        $sind_gender = $gender;
        $sind_emer_name = $sind_emer_name_new;
        $sind_emer_phno = $sind_emer_phno_new;
        $sind_race = $race;
        $sind_status = $sind_status_new;
        $sind_ic_photo = $target_file_ic;
        $sind_profile_photo = $target_file_profile;
        $sind_upline_id = $selected_upline_id;
    }
}

// --- Family Details Update ---
if (isset($_POST['save_family'])) {
    function capitalizeWords($str) { return ucwords(strtolower($str)); }
    $marital_status = $_POST['marital_status'] ?? '';
    $marital_status_other = capitalizeWords(trim($_POST['marital_status_other'] ?? ''));
    $spouse_name_new = capitalizeWords(trim($_POST['spouse_name'] ?? ''));
    $spouse_ic_no_new = preg_replace('/[\s-]/', '', trim($_POST['spouse_ic_no'] ?? ''));
    $spouse_phno_new = preg_replace('/[\s-]/', '', trim($_POST['spouse_phno'] ?? ''));
    $spouse_occupation_new = capitalizeWords(trim($_POST['spouse_occupation'] ?? ''));
    $no_kids_new = $_POST['no_kids'] ?? null;

    // Use 'others' value if selected
    if ($marital_status === 'others') {
        if (!$marital_status_other) $error_message = "Please specify your marital status.";
        $marital_status = capitalizeWords($marital_status_other);
    } else {
        // $marital_status = capitalizeWords($marital_status);
    }

    // Validate spouse phone and ic
    if ($spouse_phno_new && !ctype_digit($spouse_phno_new)) {
        $error_message = "Spouse mobile number must be numeric only.";
    } elseif (!empty($spouse_ic_no_new) && !ctype_digit($spouse_ic_no_new)) {
        $error_message = "Spouse IC number must be numeric only.";
    } elseif ($no_kids_new !== null && $no_kids_new !== '' && (!is_numeric($no_kids_new) || $no_kids_new < 0)) {
        $error_message = "Number of kids must be zero or a positive number.";
    }

    // Children year validation
    $child_names = $_POST['child_name'] ?? [];
    $child_years = $_POST['child_born_year'] ?? [];
    $child_occs  = $_POST['child_occupation'] ?? [];
    foreach ($child_names as $i => $child_name) {
        $child_born_year_raw = $child_years[$i] ?? '';
        $child_born_year = intval($child_born_year_raw);
        if (trim($child_name) || $child_born_year_raw !== '') {
            if (!ctype_digit($child_born_year_raw) || $child_born_year < 1900 || $child_born_year > $current_year) {
                $error_message = "Child born year must be between 1900 and $current_year.";
                break;
            }
        }
    }

    if (!$error_message) {
        $stmt = $conn->prepare("UPDATE sinderellas SET sind_marital_status=?, sind_spouse_name=?, sind_spouse_ic_no=?, sind_spouse_phno=?, sind_spouse_occupation=?, sind_no_kids=? WHERE sind_id=?");
        $stmt->bind_param("ssssssi", $marital_status, $spouse_name_new, $spouse_ic_no_new, $spouse_phno_new, $spouse_occupation_new, $no_kids_new, $sind_id);
        $stmt->execute();
        $stmt->close();

        // Remove old children
        $conn->query("DELETE FROM sind_child WHERE sind_id = $sind_id");
        // Insert new children
        if (!empty($child_names)) {
            foreach ($child_names as $i => $child_name) {
                $child_name = capitalizeWords(trim($child_name));
                $child_born_year_raw = $child_years[$i] ?? '';
                $child_born_year = intval($child_born_year_raw);
                $child_occupation = capitalizeWords(trim($child_occs[$i] ?? ''));
                if ($child_name && ctype_digit($child_born_year_raw) && $child_born_year >= 1900 && $child_born_year <= $current_year) {
                    $stmt = $conn->prepare("INSERT INTO sind_child (sind_id, child_name, child_born_year, child_occupation) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isis", $sind_id, $child_name, $child_born_year, $child_occupation);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        $success_message = "Family details updated successfully.";
        $sind_marital_status = $marital_status;
        $spouse_name = $spouse_name_new;
        $spouse_ic_no = $spouse_ic_no_new;
        $spouse_phno = $spouse_phno_new;
        $spouse_occupation = $spouse_occupation_new;
        $no_kids = $no_kids_new;
        // Refresh children for display
        $children = [];
        $stmt = $conn->prepare("SELECT child_name, child_born_year, child_occupation FROM sind_child WHERE sind_id = ?");
        $stmt->bind_param("i", $sind_id);
        $stmt->execute();
        $stmt->bind_result($child_name, $child_born_year, $child_occupation);
        while ($stmt->fetch()) {
            $children[] = [
                'name' => $child_name,
                'born_year' => $child_born_year,
                'occupation' => $child_occupation
            ];
        }
        $stmt->close();
    }
}

// --- Bank Details Update ---
if (isset($_POST['save_bank'])) {
    $bank_name_new = strtoupper(trim($_POST['bank_name'] ?? ''));
    $bank_acc_no_new = preg_replace('/[\s-]/', '', trim($_POST['bank_acc_no'] ?? ''));
    if (!$bank_name_new || !$bank_acc_no_new) {
        $error_message = "Please fill in all required fields.";
    } elseif (!ctype_digit($bank_acc_no_new)) {
        $error_message = "Bank account number must be numeric only.";
    }
    if (!$error_message) {
        $stmt = $conn->prepare("UPDATE sinderellas SET sind_bank_name=?, sind_bank_acc_no=? WHERE sind_id=?");
        $stmt->bind_param("ssi", $bank_name_new, $bank_acc_no_new, $sind_id);
        $stmt->execute();
        $stmt->close();
        $success_message = "Bank details updated successfully.";
        $bank_name = $bank_name_new;
        $bank_acc_no = $bank_acc_no_new;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Sinderella - Admin</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .tab-btns { display: flex; margin-bottom: 20px; }
        .tab-btn { flex: 1; padding: 12px; cursor: pointer; background: #e0e0e0; border: none; font-size: 16px; border-radius: 8px 8px 0 0; margin-right: 2px; }
        .tab-btn.active { background: #1976d2; color: #fff; font-weight: bold; }
        .tab-btn:not(.active):hover { background: #d0d0d0; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .children-table, .children-table th, .children-table td { border: 1px solid #888; border-collapse: collapse; }
        .children-table th, .children-table td { padding: 4px 8px; }
        .children-table input { width: 90%; border: none; text-align: center; margin: 0; }
        .add-row-btn { margin: 5px 0; }
        .radio-group { margin-bottom: 10px; }
        .radio-group label { margin-right: 18px; }
        .other-input { display: none; margin-top: 5px; }
        label { font-weight: bold; }
        #rmv-btn { background-color: #f44336; margin: 0; }
        .other-input { display: none; vertical-align: middle; }
        .profile-photo-container { text-align: center; }
        .service-area-block { margin-bottom: 20px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background: #fafbff; }
        .service-area-block label { display: block; margin-top: 10px; }
        .service-area-block select { width: 100%; padding: 5px; margin-top: 5px; }
        .rejected-table th, .rejected-table td { border: 1px solid #888; padding: 6px 10px; }
        .rejected-table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        input[type=text], textarea, select { width:90%;}
    </style>
    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
        document.getElementById('tab-btn-' + tab).classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
    }

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
        // document.getElementById(name + '_other').style.display = (value === 'others') ? 'inline' : 'none';
        var otherInput = document.getElementById(name + '_other');
        if (value === 'others') {
            otherInput.style.display = 'inline';
        } else {
            otherInput.style.display = 'none';
            otherInput.value = '';
        }
    }
    window.onload = function() {
        showTab('<?php echo $active_tab; ?>');
        var ms = document.querySelector('input[name="marital_status"]:checked');
        // if (ms && ms.value === 'others') document.getElementById('marital_status_other').style.display = 'inline';
        var msOther = document.getElementById('marital_status_other');
        var msOtherVal = msOther ? msOther.value.trim().toLowerCase() : '';
        var standard = ['single','married','divorced','widow','others'];
        if (
            (ms && ms.value === 'others') ||
            (msOther && msOtherVal && !standard.includes(msOtherVal))
        ) {
            msOther.style.display = 'inline';
        } else {
            msOther.style.display = 'none';
            msOther.value = '';
        }

        var race = document.querySelector('input[name="race"]:checked');
        if (race && race.value === 'others') document.getElementById('race_other').style.display = 'inline';
        else document.getElementById('race_other').style.display = 'none';
    };

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
        document.getElementById('sind_area').value = area;
        document.getElementById('sind_state').value = state;
        document.getElementById('invalid-postcode').style.display = validPostcode ? 'none' : 'block';
    }

    function fillDobGenderFromIC(ic) {
        let dobInput = document.getElementById('sind_dob');
        let genderInput = document.getElementById('sind_gender');
        dobInput.value = '';
        genderInput.value = '';
        if (!/^\d{12}$/.test(ic)) return;
        let year = parseInt(ic.substring(0, 2), 10);
        let month = parseInt(ic.substring(2, 4), 10);
        let day = parseInt(ic.substring(4, 6), 10);
        const currentYear = new Date().getFullYear() % 100;
        const fullYear = (year > currentYear ? 1900 + year : 2000 + year);
        const dobDate = new Date(Date.UTC(fullYear, month - 1, day));
        if (
            dobDate.getUTCFullYear() !== fullYear ||
            dobDate.getUTCMonth() !== month - 1 ||
            dobDate.getUTCDate() !== day
        ) return;
        dobInput.value = dobDate.toISOString().slice(0, 10);
        const genderDigit = parseInt(ic.charAt(11), 10);
        genderInput.value = (genderDigit % 2 === 0) ? 'female' : 'male';
    }
    </script>
</head>
<body>
<div class="main-container">
    <?php include '../includes/menu/menu_adm.php'; ?>
    <div class="content-container">
        <?php include '../includes/header_adm.php'; ?>

        <?php if ($error_message): ?>
            <div style="background:#e53935;color:#fff;padding:12px 0;text-align:center;font-weight:bold;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php elseif ($success_message): ?>
            <div style="background:#4caf50;color:#fff;padding:12px 0;text-align:center;font-weight:bold;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            <h2>Edit Sinderella</h2>
            <div class="tab-btns">
                <button class="tab-btn" id="tab-btn-personal" onclick="showTab('personal')">Personal Details</button>
                <button class="tab-btn" id="tab-btn-family" onclick="showTab('family')">Family Details</button>
                <button class="tab-btn" id="tab-btn-bank" onclick="showTab('bank')">Bank Details</button>
                <button class="tab-btn" id="tab-btn-service_area" onclick="showTab('service_area')">Service Area</button>
                <button class="tab-btn" id="tab-btn-rejected" onclick="showTab('rejected')">Rejected History</button>
            </div>

            <!-- Tab 1: Personal Details -->
            <div class="tab-content" id="tab-personal">
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                <div style="display: flex;">
                    <div style="flex: 1;">
                        <table>
                            <tr>
                                <td><label>Name</label></td>
                                <td>: <input type="text" name="sind_name" value="<?php echo htmlspecialchars($sind_name); ?>" required></td>
                            </tr>
                            <tr>
                                <td><label>Phone Number</label></td>
                                <td>: <input type="text" name="sind_phno" value="<?php echo htmlspecialchars($sind_phno); ?>" required></td>
                            </tr>
                            <tr>
                                <td><label>Upline</label></td>
                                <td>: 
                                    <select name="sind_upline_id" id="sind_upline_id" required>
                                        <option value="0" <?php if ($sind_upline_id == 0) echo 'selected'; ?>>Sinderella</option>
                                        <?php
                                        $uplines = ['active' => [], 'pending' => [], 'inactive' => []];
                                        $stmt = $conn->prepare("SELECT sind_id, sind_name, sind_status FROM sinderellas WHERE sind_id != ? ORDER BY sind_status = 'active' DESC, sind_status = 'pending' DESC, sind_status = 'inactive' DESC, sind_name ASC");
                                        $stmt->bind_param("i", $sind_id);
                                        $stmt->execute();
                                        $stmt->bind_result($up_id, $up_name, $up_status);
                                        while ($stmt->fetch()) {
                                            $uplines[$up_status][] = ['sind_id' => $up_id, 'sind_name' => $up_name];
                                        }
                                        $stmt->close();
                                        foreach (['active', 'pending', 'inactive'] as $status) {
                                            if (count($uplines[$status]) > 0) {
                                                echo "<optgroup label='" . ucfirst($status) . "'>";
                                                foreach ($uplines[$status] as $up) {
                                                    echo "<option value='{$up['sind_id']}'" . ($sind_upline_id == $up['sind_id'] ? ' selected' : '') . ">" . htmlspecialchars($up['sind_name']) . "</option>";
                                                }
                                                echo "</optgroup>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label>Address</label></td>
                                <td>: <textarea name="sind_address" required><?php echo htmlspecialchars($sind_address); ?></textarea></td>
                            </tr>
                            <tr>
                                <td><label>Postcode</label></td>
                                <td>: <input type="text" name="sind_postcode" id="sind_postcode" value="<?php echo htmlspecialchars($sind_postcode); ?>" required oninput="updateAreaAndState()"></td>
                                <div id="invalid-postcode" class="invalid-postcode" style="display: none; color: red;">Invalid postcode. Please enter a valid postcode.</div>
                            </tr>
                            <tr>
                                <td><label>Area</label></td>
                                <td>: <input type="text" name="sind_area" id="sind_area" value="<?php echo htmlspecialchars($sind_area); ?>" readonly required></td>
                            </tr>
                            <tr>
                                <td><label>State</label></td>
                                <td>: <input type="text" name="sind_state" id="sind_state" value="<?php echo htmlspecialchars($sind_state); ?>" readonly required></td>
                            </tr>
                            <tr>
                                <td><label>IC Number</label></td>
                                <td>: <input type="text" name="sind_icno" id="sind_icno" value="<?php echo htmlspecialchars($sind_icno); ?>" required oninput="fillDobGenderFromIC(this.value)"></td>
                            </tr>
                            <tr>
                                <td><label>Date of Birth</label></td>
                                <td>: <input type="date" name="sind_dob" id="sind_dob" value="<?php echo htmlspecialchars($sind_dob); ?>" readonly></td>
                            </tr>
                            <tr>
                                <td><label>Gender</label></td>
                                <td>: <input type="text" name="sind_gender" id="sind_gender" value="<?php echo htmlspecialchars($sind_gender); ?>" readonly></td>
                            </tr>
                            <tr>
                                <td><label>Race</label></td>
                                <td>
                                    <div class="radio-group">: 
                                        <input type="radio" name="race" value="malay" onclick="toggleOtherInput('race', this.value)" <?php if (strtolower($sind_race) === 'malay') echo 'checked'; ?>> Malay
                                        <input type="radio" name="race" value="chinese" onclick="toggleOtherInput('race', this.value)" <?php if (strtolower($sind_race) === 'chinese') echo 'checked'; ?>> Chinese
                                        <input type="radio" name="race" value="indian" onclick="toggleOtherInput('race', this.value)" <?php if (strtolower($sind_race) === 'indian') echo 'checked'; ?>> Indian
                                        <input type="radio" name="race" value="others" onclick="toggleOtherInput('race', this.value)" <?php if (!in_array(strtolower($sind_race), ['malay','chinese','indian'])) echo 'checked'; ?>> Others
                                        <input type="text" id="race_other" name="race_other" class="other-input" style="width:180px; margin-left:8px;" placeholder="Please specify your race"
                                            value="<?php echo htmlspecialchars(!in_array(strtolower($sind_race), ['malay','chinese','indian']) ? $sind_race : ''); ?>">
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <br>
                        <table>
                            <tr>
                                <td><label>Emergency Contact Name:</label></td>
                                <td><input type="text" name="sind_emer_name" value="<?php echo htmlspecialchars($sind_emer_name); ?>" required></td>
                            </tr>
                            <tr>
                                <td><label>Emergency Contact Phone:</label></td>
                                <td><input type="text" name="sind_emer_phno" value="<?php echo htmlspecialchars($sind_emer_phno); ?>" required></td>
                            </tr>
                        </table>
                        <br>
                        <label>Labels:</label>
                        <div>
                            <?php if ($labels->num_rows == 0): ?>
                                <span style="color:#888;">No active label</span>
                            <?php endif; ?>
                            <?php while ($label = $labels->fetch_assoc()): ?>
                                <input type="checkbox" name="sind_labels[]" value="<?php echo $label['slbl_id']; ?>" <?php if (in_array($label['slbl_id'], $selected_labels)) echo 'checked'; ?>>
                                <?php echo htmlspecialchars($label['slbl_name']); ?><br>
                            <?php endwhile; ?>
                        </div>
                        <br>
                        <label>Status:</label>
                        <select name="sind_status" required>
                            <option value="active" <?php if ($sind_status == 'active') echo 'selected'; ?>>Active</option>
                            <option value="inactive" <?php if ($sind_status == 'inactive') echo 'selected'; ?>>Inactive</option>
                            <option value="pending" <?php if ($sind_status == 'pending') echo 'selected'; ?>>Pending</option>
                        </select>
                        <br>
                        <button type="submit" name="save_personal">Save Changes</button>
                        <button type="button" onclick="window.location.href='view_sinderellas.php'">Back</button>
                        <br><br>
                        <label>Account Approval:</label>
                        <?php echo ucfirst($acc_approved); ?>
                        <div>
                            <?php if ($acc_approved == 'pending'): ?>
                                <button type="button" id="approve-btn" onclick="updateApproval('approve')">Approve</button>
                                <button type="button" id="reject-btn" onclick="updateApproval('reject')">Reject</button>
                            <?php elseif ($acc_approved == 'approve'): ?>
                                <button type="button" id="reject-btn" onclick="updateApproval('reject')">Reject</button>
                            <?php elseif ($acc_approved == 'reject'): ?>
                                <button type="button" id="approve-btn" onclick="updateApproval('approve')">Approve</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <label>Rating:</label>
                        <span>
                            <?php if ($avg_rating !== null && $review_count > 0): ?>
                                <span style="color:#F09E0B;font-size:16px;cursor:pointer;" onclick="showRatingsPopup(<?php echo $sind_id; ?>)">
                                    &#11088;<?php echo $avg_rating; ?> (<?php echo $review_count; ?> review<?php echo $review_count > 1 ? 's' : ''; ?>)
                                </span>
                            <?php else: ?>
                                <span style="color:#888;font-size:14px;">N/A</span>
                            <?php endif; ?>
                        </span>
                        <br><br><br>
                        <label for="sind_profile_photo">Profile Photo</label><br>
                        <img src="<?php echo htmlspecialchars($sind_profile_photo) . '?v=' . time(); ?>" alt="Profile Photo" style="max-width: 200px;"><br>
                        <input type="file" id="sind_profile_photo" name="sind_profile_photo">
                        <br><br><br>
                        <label for="sind_ic_photo">IC Photo</label><br>
                        <img src="<?php echo htmlspecialchars($sind_ic_photo) . '?v=' . time(); ?>" alt="IC Photo" style="max-width: 200px;"><br>
                        <input type="file" id="sind_ic_photo" name="sind_ic_photo">
                    </div>
                </div>
                </form>
            </div>

            <!-- Tab 2: Family Details -->
            <div class="tab-content" id="tab-family">
                <form method="POST" autocomplete="off">
                <table>
                    <tr>
                        <td><label for="marital_status">Marital Status</label></td>
                        <!-- <td>
                            <div class="radio-group">
                                <input type="radio" name="marital_status" value="single" onclick="toggleOtherInput('marital_status', this.value)" required <?php if (($sind_marital_status ?? '') === 'Single') echo 'checked'; ?>> Single
                                <input type="radio" name="marital_status" value="married" onclick="toggleOtherInput('marital_status', this.value)" <?php if (($sind_marital_status ?? '') === 'Married') echo 'checked'; ?>> Married
                                <input type="radio" name="marital_status" value="divorced" onclick="toggleOtherInput('marital_status', this.value)" <?php if (($sind_marital_status ?? '') === 'Divorced') echo 'checked'; ?>> Divorced
                                <input type="radio" name="marital_status" value="widow" onclick="toggleOtherInput('marital_status', this.value)" <?php if (($sind_marital_status ?? '') === 'Widow') echo 'checked'; ?>> Widow
                                <input type="radio" name="marital_status" value="others" onclick="toggleOtherInput('marital_status', this.value)" <?php if (($sind_marital_status ?? '') === 'others' || (!in_array(($sind_marital_status ?? ''), ['Single','Married','Divorced','Widow','others']) && !empty($sind_marital_status))) echo 'checked'; ?>>
                                    Others
                                    <input type="text" id="marital_status_other" name="marital_status_other" class="other-input" style="width:180px; margin-left:8px;" placeholder="Please specify your marital status"
                                        value="<?php echo htmlspecialchars($marital_status_other ?? ((isset($sind_marital_status) && !in_array($sind_marital_status, ['Single','Married','Divorced','Widow','others'])) ? $sind_marital_status : '')); ?>">
                                </label>
                            </div>
                        </td> -->
                        <td>
                            <div class="radio-group">
                                <input type="radio" name="marital_status" value="single" onclick="toggleOtherInput('marital_status', this.value)" required
                                    <?php if (($sind_marital_status ?? '') === 'single') echo 'checked'; ?>> Single
                                <input type="radio" name="marital_status" value="married" onclick="toggleOtherInput('marital_status', this.value)"
                                    <?php if (($sind_marital_status ?? '') === 'married') echo 'checked'; ?>> Married
                                <input type="radio" name="marital_status" value="divorced" onclick="toggleOtherInput('marital_status', this.value)"
                                    <?php if (($sind_marital_status ?? '') === 'divorced') echo 'checked'; ?>> Divorced
                                <input type="radio" name="marital_status" value="widow" onclick="toggleOtherInput('marital_status', this.value)"
                                    <?php if (($sind_marital_status ?? '') === 'widow') echo 'checked'; ?>> Widow
                                <input type="radio" name="marital_status" value="others" onclick="toggleOtherInput('marital_status', this.value)"
                                    <?php if (($sind_marital_status ?? '') === 'others' || (!in_array(($sind_marital_status ?? ''), ['single','married','divorced','widow','others']) && !empty($sind_marital_status))) echo 'checked'; ?>>
                                    Others: 
                                    <input type="text" id="marital_status_other" name="marital_status_other" class="other-input" style="width:180px; margin-left:8px;" placeholder="Please specify your marital status"
                                        value="<?php echo htmlspecialchars($marital_status_other ?? ((isset($sind_marital_status) && !in_array($sind_marital_status, ['single','married','divorced','widow','others'])) ? $sind_marital_status : '')); ?>">
                                
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="spouse_name">Spouse Name</label></td>
                        <td>: <input type="text" name="spouse_name" value="<?php echo htmlspecialchars($spouse_name); ?>"></td>
                    </tr>
                    <tr>
                        <td><label for="spouse_ic_no">Spouse NRIC</label></td>
                        <td>: <input type="text" name="spouse_ic_no" value="<?php echo htmlspecialchars($spouse_ic_no); ?>"></td>
                    </tr>
                    <tr>
                        <td><label for="spouse_phno">Spouse Mobile No.</label></td>
                        <td>: <input type="text" name="spouse_phno" value="<?php echo htmlspecialchars($spouse_phno); ?>"></td>
                    </tr>
                    <tr>
                        <td><label for="spouse_occupation">Spouse Occupation</label></td>
                        <td>: <input type="text" name="spouse_occupation" value="<?php echo htmlspecialchars($spouse_occupation); ?>"></td>
                    </tr>
                    <tr>
                        <td><label for="no_kids">No. of Kids</label></td>
                        <td>: <input type="number" name="no_kids" min="0" value="<?php echo htmlspecialchars($no_kids ?? ''); ?>"></td>
                    </tr>
                </table>
                <br>
                <label>Children (optional):</label>
                <table id="children-table" class="children-table">
                    <tr>
                        <th>Name</th>
                        <th>Born Year</th>
                        <th>Occupation</th>
                        <th></th>
                    </tr>
                    <?php
                    $child_count = max(count($children), 1);
                    for ($i = 0; $i < $child_count; $i++):
                        $name = htmlspecialchars($children[$i]['name'] ?? '');
                        $year = htmlspecialchars($children[$i]['born_year'] ?? '');
                        $occ  = htmlspecialchars($children[$i]['occupation'] ?? '');
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
                <br>
                <button type="submit" name="save_family">Save Changes</button>
                </form>
            </div>

            <!-- Tab 3: Bank Details -->
            <div class="tab-content" id="tab-bank">
                <form method="POST" autocomplete="off">
                    <table>
                        <tr>
                            <td><label for="bank_name">Bank Name</label></td>
                            <td>: <input type="text" name="bank_name" value="<?php echo htmlspecialchars($bank_name); ?>" required></td>
                        </tr>
                        <tr>
                            <td><label for="bank_acc_no">Account Number</label></td>
                            <td>: <input type="text" name="bank_acc_no" value="<?php echo htmlspecialchars($bank_acc_no); ?>" required></td>
                        </tr>
                    </table>
                    <button type="submit" name="save_bank">Save Changes</button>
                </form>
            </div>

            <!-- Tab 4: Service Area -->
            <div class="tab-content" id="tab-service_area">
                <h3>Service Areas</h3>
                <ul id="serviceAreasList">
                    <?php foreach ($service_areas as $state => $areas): ?>
                        <?php foreach ($areas as $area): ?>
                            <li><?php echo htmlspecialchars($area) . ', ' . htmlspecialchars($state); ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
                <button type="button" onclick="openServiceAreaPopup()">Update Service Area</button>
                <div id="serviceAreaModal" style="display:none; position:fixed; top:5%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; box-shadow:0 2px 10px #0002; z-index:9999; padding:20px; max-width:90vw; max-height:90vh; overflow:auto;">
                    <h2>Update Service Area</h2>
                    <form id="updateServiceAreaForm" action="save_service_area.php?sind_id=<?php echo $sind_id; ?>" method="POST">
                        <input type="hidden" name="from_admin" value="1">
                        <div id="serviceAreasContainer"></div>
                        <button type="button" id="addServiceAreaButton">Add Service Area</button>
                        <button type="submit">Save Changes</button>
                        <button type="button" onclick="closeServiceAreaPopup()">Cancel</button>
                    </form>
                </div>
            </div>

            <!-- Tab 5: Rejected History -->
            <div class="tab-content" id="tab-rejected">
                <h3>Rejected Bookings (This Month): <?php echo $rejected_this_month; ?></h3>
                <h4>Total Rejected Bookings: <?php echo $total_rejected; ?></h4>
                <table class="rejected-table">
                    <thead>
                        <tr>
                            <th>Booking Date</th>
                            <th>Time</th>
                            <th>Reason</th>
                            <th>Rejected At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rejected_details as $rej): ?>
                        <tr>
                            <td style="text-align:center;">
                                <?php
                                    $dateObj = DateTime::createFromFormat('Y-m-d', $rej['booking_date']);
                                    echo $dateObj ? $dateObj->format('Y-m-d (l)') : htmlspecialchars($rej['booking_date']);
                                ?>
                            </td>
                            <!-- <td style="text-align:center;"><?php echo htmlspecialchars($rej['booking_from']) . ' - ' . htmlspecialchars($rej['booking_to']); ?></td> -->
                            <td style="text-align:center;">
                                <?php
                                    $fromObj = DateTime::createFromFormat('H:i:s', $rej['booking_from']);
                                    $toObj = DateTime::createFromFormat('H:i:s', $rej['booking_to']);
                                    $fromStr = $fromObj ? $fromObj->format('h:i A') : htmlspecialchars($rej['booking_from']);
                                    $toStr = $toObj ? $toObj->format('h:i A') : htmlspecialchars($rej['booking_to']);
                                    echo $fromStr . ' - ' . $toStr;
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($rej['reason']); ?></td>
                            <td style="text-align:center;">
                                <?php
                                    $rejAtObj = DateTime::createFromFormat('Y-m-d H:i:s', $rej['created_at']);
                                    echo $rejAtObj ? $rejAtObj->format('Y-m-d h:i:s A') : htmlspecialchars($rej['created_at']);
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="ratingsPopup" style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; box-shadow:0 2px 10px #0002; z-index:9999; padding:20px; max-width:90vw; max-height:80vh; overflow:auto;">
                <h3 style="text-align:center;">Sinderella Ratings</h3>
                <table id="ratingsTable" border="1" cellpadding="5" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>Rated By</th>
                            <th>Rate</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Ratings will be loaded here -->
                    </tbody>
                </table>
                <div style="text-align:center;">
                    <button onclick="closeRatingsPopup()">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function showRatingsPopup(sind_id) {
    $('#ratingsPopup').show();
    $('#ratingsTable tbody').html('<tr><td colspan="3">Loading...</td></tr>');
    $.get('get_sinderella_ratings.php', { sind_id: sind_id }, function(data) {
        $('#ratingsTable tbody').html(data);
    });
}
function closeRatingsPopup() {
    $('#ratingsPopup').hide();
}
function openServiceAreaPopup() {
    document.getElementById('serviceAreaModal').style.display = 'block';
}
function closeServiceAreaPopup() {
    document.getElementById('serviceAreaModal').style.display = 'none';
}
function updateApproval(action) {
    let msg = '';
    if (action === 'approve') {
        msg = 'Are you sure you want to APPROVE this account? \n Kindly check for the STATUS of the account.';
    } else if (action === 'reject') {
        msg = 'Are you sure you want to REJECT this account? \nThis will also set the STATUS to INACTIVE.';
    }
    if (!confirm(msg)) return;
    $.post('update_account_approval.php', {
        sind_id: <?php echo $sind_id; ?>,
        action: action
    }, function(resp) {
        if (resp === 'success') {
            alert('Account approval updated.');
            location.reload();
        } else {
            alert('Failed to update approval: ' + resp);
        }
    });
}

$(document).on('change', '.public-checkbox', function() {
    var rating_id = $(this).data('rating-id');
    var is_public = $(this).is(':checked') ? 1 : 0;
    $.post('update_rating_public.php', { rating_id: rating_id, public: is_public }, function(resp) {
    });
});

window.selectedAreas = <?php echo json_encode($service_areas); ?>;
</script>
<script src="../includes/js/update_service_area.js"></script>
</body>
</html>