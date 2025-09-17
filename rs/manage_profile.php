<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];

$active_tab = 'personal';
if (isset($_POST['save_family'])) {
    $active_tab = 'family';
} elseif (isset($_POST['save_personal'])) {
    $active_tab = 'personal';
}

// Fetch main profile info
$stmt = $conn->prepare("SELECT sind_name, sind_phno, sind_address, sind_postcode, sind_area, sind_state, sind_profile_path, sind_upline_id, sind_id_type, sind_icno, sind_dob, sind_gender, sind_race, sind_emer_name, sind_emer_phno, sind_marital_status, sind_no_kids, sind_spouse_name, sind_spouse_phno, sind_spouse_ic_no, sind_spouse_occupation, sind_bank_name, sind_bank_acc_no, sind_status, acc_approved FROM sinderellas WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($sind_name, $sind_phno, $sind_address, $sind_postcode, $sind_area, $sind_state, $sind_profile_path, $sind_upline_id, $sind_id_type, $sind_icno, $sind_dob, $sind_gender, $sind_race, $sind_emer_name, $sind_emer_phno, $sind_marital_status, $no_kids, $spouse_name, $spouse_phno, $spouse_ic_no, $spouse_occupation, $bank_name, $bank_acc_no, $sind_status, $acc_approved);
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

// Handle form submissions
$error_message = '';
$success_message = '';
$current_year = date('Y');

// --- Personal Details Update ---
if (isset($_POST['save_personal'])) {
    $emer_name = ucwords(strtolower(trim($_POST['emer_name'] ?? '')));
    $emer_phno = preg_replace('/[\s-]/', '', trim($_POST['emer_phno'] ?? ''));

    if (!$emer_name || !$emer_phno) {
        $error_message = "Please fill in all required fields.";
    } elseif (!ctype_digit($emer_phno)) {
        $error_message = "Emergency contact phone must be numeric only.";
    } else {
        $stmt = $conn->prepare("UPDATE sinderellas SET sind_emer_name=?, sind_emer_phno=? WHERE sind_id=?");
        $stmt->bind_param("ssi", $emer_name, $emer_phno, $sind_id);
        $stmt->execute();
        $stmt->close();
        $success_message = "Emergency contact updated successfully.";
        $sind_emer_name = $emer_name;
        $sind_emer_phno = $emer_phno;
    }
}

// --- Family Details Update ---
if (isset($_POST['save_family'])) {
    function capitalizeWords($str) { return ucwords(strtolower($str)); }
    $marital_status = $_POST['marital_status'] ?? '';
    $marital_status_other = capitalizeWords(trim($_POST['marital_status_other'] ?? ''));
    $spouse_name = capitalizeWords(trim($_POST['spouse_name'] ?? ''));
    $spouse_ic_no = preg_replace('/[\s-]/', '', trim($_POST['spouse_ic_no'] ?? ''));
    $spouse_phno = preg_replace('/[\s-]/', '', trim($_POST['spouse_phno'] ?? ''));
    $spouse_occupation = capitalizeWords(trim($_POST['spouse_occupation'] ?? ''));
    $no_kids = $_POST['no_kids'] ?? null;

    // Use 'others' value if selected
    if ($marital_status === 'others') {
        if (!$marital_status_other) $error_message = "Please specify your marital status.";
        $marital_status = $marital_status_other;
    } else {
        $marital_status = $marital_status;
    }

    // Validate spouse phone and ic
    if ($spouse_phno && !ctype_digit($spouse_phno)) {
        $error_message = "Spouse mobile number must be numeric only.";
    } elseif (!empty($spouse_ic_no) && !ctype_digit($spouse_ic_no)) {
        $error_message = "Spouse IC number must be numeric only.";
    } elseif ($no_kids !== null && $no_kids !== '' && (!is_numeric($no_kids) || $no_kids < 0)) {
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
        $stmt->bind_param("ssssssi", $marital_status, $spouse_name, $spouse_ic_no, $spouse_phno, $spouse_occupation, $no_kids, $sind_id);
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

if ($acc_approved == 'pending' && $sind_status == 'active') {
    $error_message = "Your account is not yet approved. Please wait for admin approval.";
} else if ($acc_approved == 'rejected' && $sind_status == 'active') {
    $error_message = "Your account has been rejected. Please contact support for more details.";
} elseif ($sind_status != 'active' && $sind_status != 'pending') {
    $error_message = "Your account is currently inactive. Please contact support for assistance.";
} elseif ($sind_status == 'pending') {
    $error_message = "Please attempt qualifier test before you start your job.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Profile - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        /* .tab-btns { display: flex; margin-bottom: 20px; } */
        /* .tab-btn.inactive { flex: 1; padding: 12px; cursor: pointer; background: #e0e0e0; border: none; font-size: 16px; border-radius: 8px 8px 0 0; margin-right: 2px; } */
        /* .tab-btn.inactive:hover { background: #d0d0d0; } */
        /* .tab-btn.active { background: #1976d2; color: #fff; font-weight: bold; } */
        .tab-btns {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 24px;
        }
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
    </style>
    <script>

    function showTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.add('inactive'));
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.add('inactive'));
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
        document.getElementById(name + '_other').style.display = (value === 'others') ? 'inline' : 'none';
    }

    window.onload = function() {
        showTab('<?php echo $active_tab; ?>');
        var ms = document.querySelector('input[name="marital_status"]:checked');
        if (ms && ms.value === 'others') document.getElementById('marital_status_other').style.display = 'inline';
    };
    </script>
</head>
<body>
<div class="main-container">
    <?php include '../includes/menu/menu_sind.php'; ?>
    <div class="content-container">
        <?php include '../includes/header_sind.php'; ?>

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
            <h2>Manage Profile</h2>
            <div class="tab-btns">
                <button class="tab-btn" id="tab-btn-personal" onclick="showTab('personal')">Personal Details</button>
                <button class="tab-btn" id="tab-btn-family" onclick="showTab('family')">Family Details</button>
                <!-- <button class="tab-btn" id="tab-btn-bank" onclick="showTab('bank')">Bank Details</button> -->
            </div>

            <!-- Tab 1: Personal Details -->
            <div class="tab-content" id="tab-personal">
                <form method="POST" autocomplete="off">
                <div style="display: flex;">
                    <div style="flex: 1;">
                        <table>
                            <tr>
                                <td><strong>Name</strong></td>
                                <td>: <?php echo htmlspecialchars($sind_name); ?>
                                    <?php if ($avg_rating !== null && $review_count > 0): ?>
                                        <span style="margin-left:8px;color:#F09E0B;font-size:16px;cursor:pointer;" onclick="showRatingsPopup(<?php echo $sind_id; ?>)">
                                            &#11088;<?php echo $avg_rating; ?> (<?php echo $review_count; ?> review<?php echo $review_count > 1 ? 's' : ''; ?>)
                                        </span>
                                    <?php else: ?>
                                        <span style="margin-left:8px;color:#888;font-size:14px;">No ratings</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Phone Number</strong></td>
                                <td>: <?php echo htmlspecialchars(preg_replace("/(\d{3})(\d{3})(\d{4})/", "$1-$2 $3", $sind_phno)); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Upline</strong></td>
                                <td>: <?php echo htmlspecialchars($upline_name); ?></td>
                            </tr>
                        </table>
                        <button type="button" onclick="location.href='reset_pwd.php'">Reset Password</button>
                        <br><br>
                        <table>
                            <tr>
                                <td><strong>Address</strong></td>
                                <td>: <?php echo htmlspecialchars($sind_address); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Postcode</strong></td>
                                <td>: <?php echo htmlspecialchars($sind_postcode); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Area</strong></td>
                                <td>: <?php echo htmlspecialchars($sind_area); ?></td>
                            </tr>
                            <tr>
                                <td><strong>State</strong></td>
                                <td>: <?php echo htmlspecialchars($sind_state); ?></td>
                            </tr>
                        </table>
                        <button type="button" onclick="location.href='update_address.php'">Update Address</button>
                        <br><br>
                        <table>
                            <tr>
                                <td><strong>ID Type</strong></td>
                                <td>: <?php if ($sind_id_type == 'NI') {
                                    echo 'Malaysia IC';
                                } elseif ($sind_id_type == 'PP') {
                                    echo 'Passport';
                                } else {
                                    echo 'Unknown';
                                } ?></td>
                            </tr>
                            <tr>
                                <td><strong>IC No</strong></td>
                                <td>: <?php echo htmlspecialchars($sind_icno); ?></td>
                            </tr>
                            <tr>
                                <td><strong>D.O.B.</strong></td>
                                <td>: <?php echo htmlspecialchars($sind_dob); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Gender</strong></td>
                                <td>: <?php echo htmlspecialchars(ucfirst($sind_gender)); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Race</strong></td>
                                <td>: <?php echo htmlspecialchars(ucfirst($sind_race)); ?></td>
                            </tr>
                        </table>
                        <br>
                        <table>
                            <tr>
                                <td><label for="emer_name">Emergency Contact Name</label></td>
                                <td>: <input type="text" name="emer_name" value="<?php echo htmlspecialchars($sind_emer_name); ?>" required></td>
                            </tr>
                            <tr>
                                <td><label for="emer_phno">Emergency Contact Phone</label></td>
                                <td>: <input type="text" name="emer_phno" value="<?php echo htmlspecialchars($sind_emer_phno); ?>" required></td>
                            </tr>
                        </table>
                        <button type="submit" name="save_personal">Save Changes</button>
                        <br><br>
                        <table>
                            <tr>
                                <td><strong>Bank Name</strong></td>
                                <td>: <?php echo htmlspecialchars($bank_name); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Account Number</strong></td>
                                <td>: <?php echo htmlspecialchars($bank_acc_no); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div style="flex: 1; text-align: center;">
                        <img src="<?php echo htmlspecialchars($sind_profile_path); ?>" alt="Profile Photo" style="max-width: 200px;">
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
</script>
</body>
</html>