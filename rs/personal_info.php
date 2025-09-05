<?php
$error_message = '';
$success_message = '';
$phone = $_GET['phone'] ?? $_POST['phone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../db_connect.php';

    $stmt = $conn->prepare("SELECT sind_id FROM sinderellas WHERE sind_phno=?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 0) {
        $error_message = "User does not exist.";
    }
    $stmt->close();

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
            if (!$race_other) $error_message = "Please specify your race.";
            $race = $race_other;
        }
        if ($marital_status === 'others') {
            if (!$marital_status_other) $error_message = "Please specify your marital status.";
            $marital_status = $marital_status_other;
        }

        $current_year = date('Y');
        foreach ($_POST['child_name'] as $i => $child_name) {
            $child_born_year_raw = $_POST['child_born_year'][$i] ?? '';
            $child_born_year = intval($child_born_year_raw);
            if (trim($child_name) || $child_born_year_raw !== '') {
                if (!ctype_digit($child_born_year_raw) || $child_born_year < 1900 || $child_born_year > $current_year) {
                    $error_message = "Child born year must be between 1900 and $current_year.";
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
                $error_message = "Emergency contact phone must be numeric only.";
            }
            elseif ($spouse_phno && !ctype_digit($spouse_phno)) {
                $error_message = "Spouse mobile number must be numeric only.";
            }
            elseif (!ctype_digit($bank_acc_no)) {
                $error_message = "Bank account number must be numeric only.";
            }
            elseif (!empty($spouse_ic_no) && !ctype_digit($spouse_ic_no)) {
                $error_message = "Spouse IC number must be numeric only.";
            }
            // else {
            if (!$error_message) {
                // Update sinderellas table
                $stmt = $conn->prepare("UPDATE sinderellas SET sind_emer_name=?, sind_emer_phno=?, sind_race=?, sind_marital_status=?, sind_no_kids=?, sind_spouse_name=?, sind_spouse_ic_no=?, sind_spouse_phno=?, sind_spouse_occupation=?, sind_bank_name=?, sind_bank_acc_no=? WHERE sind_phno=?");
                $stmt->bind_param("ssssisssssss", $emer_name, $emer_phno, $race, $marital_status, $no_kids, $spouse_name, $spouse_ic_no, $spouse_phno, $spouse_occupation, $bank_name, $bank_acc_no, $phone);
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
        .other-input { display: none; margin-top: 5px; }
        label { font-weight: bold; }
        .children-table, .children-table th, .children-table td {
            border: 1px solid;
            border-collapse: collapse;
        }
        #rmv-btn {
            background-color: #f44336;
            margin: 0;
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
        document.getElementById(name + '_other').style.display = (value === 'others') ? 'block' : 'none';
    }
    </script>
</head>
<body>
<div class="login-container">
    <div class="login-right">
        <form method="POST" action="personal_info.php" autocomplete="off">
            <h2>Complete Your Profile</h2>
            <?php if ($error_message): ?>
                <p style="color:red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">

            <label>Emergency Contact Name: <span style="color:red">*</span></label>
            <input type="text" name="emer_name" required
                value="<?php echo htmlspecialchars($emer_name ?? ''); ?>">

            <label>Emergency Contact Phone: <span style="color:red">*</span></label>
            <input type="text" name="emer_phno" required
                value="<?php echo htmlspecialchars($emer_phno ?? ''); ?>">

            <label>Race: <span style="color:red">*</span></label>
            <div class="radio-group">
                <input type="radio" name="race" value="malay" onclick="toggleOtherInput('race', this.value)" required
                    <?php if (($race ?? '') === 'malay') echo 'checked'; ?>> Malay
                <input type="radio" name="race" value="chinese" onclick="toggleOtherInput('race', this.value)"
                    <?php if (($race ?? '') === 'chinese') echo 'checked'; ?>> Chinese
                <input type="radio" name="race" value="indian" onclick="toggleOtherInput('race', this.value)"
                    <?php if (($race ?? '') === 'indian') echo 'checked'; ?>> Indian
                <input type="radio" name="race" value="others" onclick="toggleOtherInput('race', this.value)"
                    <?php if (($race ?? '') === 'others' || (!in_array(($race ?? ''), ['malay','chinese','indian','others']) && !empty($race))) echo 'checked'; ?>> Others
            </div>
            <input type="text" id="race_other" name="race_other" class="other-input" placeholder="Please specify your race"
                value="<?php echo htmlspecialchars($race_other ?? ''); ?>">

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
                    <?php if (($marital_status ?? '') === 'others' || (!in_array(($marital_status ?? ''), ['single','married','divorced','widow','others']) && !empty($marital_status))) echo 'checked'; ?>> Others
            </div>
            <input type="text" id="marital_status_other" name="marital_status_other" class="other-input" placeholder="Please specify your marital status"
                value="<?php echo htmlspecialchars($marital_status_other ?? ''); ?>">

            <label>No. of Kids (if any):</label>
            <input type="number" name="no_kids" min="0"
                value="<?php echo htmlspecialchars($no_kids ?? ''); ?>">

            <label>Spouse Name:</label>
            <input type="text" name="spouse_name"
                value="<?php echo htmlspecialchars($spouse_name ?? ''); ?>">

            <label>Spouse NRIC:</label>
            <input type="text" name="spouse_ic_no"
                value="<?php echo htmlspecialchars($spouse_ic_no ?? ''); ?>">

            <label>Spouse Mobile No.:</label>
            <input type="text" name="spouse_phno"
                value="<?php echo htmlspecialchars($spouse_phno ?? ''); ?>">

            <label>Spouse Occupation:</label>
            <input type="text" name="spouse_occupation"
                value="<?php echo htmlspecialchars($spouse_occupation ?? ''); ?>">

            <?php
            $child_names = $_POST['child_name'] ?? [];
            $child_years = $_POST['child_born_year'] ?? [];
            $child_occs  = $_POST['child_occupation'] ?? [];
            $child_count = max(count($child_names), 1); // At least 1 row
            ?>

            <label>Children (optional):</label>
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

            <h3>Bank Details</h3>
            <label>Bank Name: <span style="color:red">*</span></label>
            <input type="text" name="bank_name" required
                value="<?php echo htmlspecialchars($bank_name ?? ''); ?>">

            <label>Account Number: <span style="color:red">*</span></label>
            <input type="text" name="bank_acc_no" required
                value="<?php echo htmlspecialchars($bank_acc_no ?? ''); ?>">

            <button type="submit">Submit</button>
        </form>
    </div>
</div>
<script>
    // Show "other" input if already selected on reload
    window.onload = function() {
        var race = document.querySelector('input[name="race"]:checked');
        if (race && race.value === 'others') document.getElementById('race_other').style.display = 'block';
        var ms = document.querySelector('input[name="marital_status"]:checked');
        if (ms && ms.value === 'others') document.getElementById('marital_status_other').style.display = 'block';
    };
</script>
</body>
</html>