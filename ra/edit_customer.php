<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

require_once '../db_connect.php';

$cust_id = isset($_GET['cust_id']) ? $_GET['cust_id'] : 0;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cust_name = ucwords(strtolower(trim($_POST['cust_name'])));
    $cust_phno = $_POST['cust_phno'];
    $cust_emer_name = trim($_POST['cust_emer_name']);
    $cust_emer_phno = trim($_POST['cust_emer_phno']);
    $cust_status = $_POST['cust_status'];
    $cust_labels = isset($_POST['cust_labels']) ? $_POST['cust_labels'] : [];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM customers WHERE cust_phno = ? AND cust_id != ? AND cust_status = 'active'");
    $stmt->bind_param("si", $cust_phno, $cust_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $error_message = 'The phone number is already used by another active customer.';
    } else {
        $stmt = $conn->prepare("UPDATE customers SET cust_name = ?, cust_phno = ?, cust_status = ?, cust_emer_name = ?, cust_emer_phno = ? WHERE cust_id = ?");
        $stmt->bind_param("sssssi", $cust_name, $cust_phno, $cust_status, $cust_emer_name, $cust_emer_phno, $cust_id);
        if (!$stmt->execute()) {
            die("Error updating record: " . $stmt->error);
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM cust_id_label WHERE cust_id = ?");
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $stmt->close();

        foreach ($cust_labels as $label_id) {
            $stmt = $conn->prepare("INSERT INTO cust_id_label (cust_id, clbl_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $cust_id, $label_id);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: view_customers.php");
        exit();
    }
}

$stmt = $conn->prepare("SELECT c.cust_name, c.cust_phno, c.cust_status, c.cust_emer_name, c.cust_emer_phno FROM customers c WHERE c.cust_id = ?");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($cust_name, $cust_phno, $cust_status, $cust_emer_name, $cust_emer_phno);
$stmt->fetch();
$stmt->close();

$labels = $conn->query("SELECT clbl_id, clbl_name FROM cust_label WHERE clbl_status = 'Active'");

$selected_labels = [];
$stmt = $conn->prepare("SELECT clbl_id FROM cust_id_label WHERE cust_id = ?");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($clbl_id);
while ($stmt->fetch()) {
    $selected_labels[] = $clbl_id;
}
$stmt->close();

$addresses = [];
$stmt = $conn->prepare("SELECT cust_address_id, cust_address, cust_postcode, cust_area, cust_state, cust_housetype, cust_fm_num, cust_pet FROM cust_addresses WHERE cust_id = ?");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($address_id, $cust_address, $cust_postcode, $cust_area, $cust_state, $cust_housetype, $cust_fm_num, $cust_pet);
while ($stmt->fetch()) {
    $addresses[] = [
        'id' => $address_id,
        'address' => $cust_address,
        'postcode' => $cust_postcode,
        'area' => $cust_area,
        'state' => $cust_state,
        'housetype' => $cust_housetype,
        'fm_num' => $cust_fm_num,
        'pet' => $cust_pet
    ];
}
$stmt->close();

// $avg_rating = null;
// $review_count = 0;
// $stmt = $conn->prepare("SELECT AVG(rate) as avg_rating, COUNT(*) as review_count FROM cust_ratings WHERE cust_id = ?");
// $stmt->bind_param("i", $cust_id);
// $stmt->execute();
// $stmt->bind_result($avg_rating, $review_count);
// $stmt->fetch();
// $stmt->close();
// $avg_rating = $avg_rating ? round($avg_rating, 1) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .profile-container {
            /* display: flex; */
        }
        .profile-container .left, .profile-container .right {
            flex: 1;
            padding: 20px;
        }
        .profile-container label {
            display: block;
            margin-top: 10px;
        }
        .profile-container input[type="text"],
        .profile-container input[type="number"],
        .profile-container select,
        .profile-container textarea {
            width: calc(100% - 10px);
            padding: 5px;
            margin-right: 10px;
        }
        .profile-container button {
            /* margin-top: 20px; */
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
        /* .button-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        } */
        .error-message {
            color: red;
            margin-top: 10px;
        }
        .profile-photo-container {
            text-align: center;
        }
        .invalid-postcode {
            color: red;
            margin-top: 5px;
        }
    </style>
    <script>
        function confirmDelete(addressId) {
            if (confirm("Are you sure you want to delete this address? This action cannot be undone.")) {
                document.getElementById('delete_address_id').value = addressId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</head>
<body>
<div class="main-container">
    <?php include '../includes/menu/menu_adm.php'; ?>
    <div class="content-container">
        <?php include '../includes/header_adm.php'; ?>
        <div class="profile-container">
            <h2>Edit Customer</h2>
            <form method="POST" action="">
                <label for="cust_name"><strong>Name: </strong></label>
                <input type="text" id="cust_name" name="cust_name" value="<?php echo htmlspecialchars($cust_name); ?>" required>

                <label for="cust_phno"><strong>Phone Number: </strong></label>
                <input type="text" id="cust_phno" name="cust_phno" value="<?php echo htmlspecialchars($cust_phno); ?>" required>

                <label for="cust_emer_name"><strong>Emergency Contact Name: </strong></label>
                <input type="text" id="cust_emer_name" name="cust_emer_name" value="<?php echo htmlspecialchars($cust_emer_name); ?>">

                <label for="cust_emer_phno"><strong>Emergency Contact Phone: </strong></label>
                <input type="text" id="cust_emer_phno" name="cust_emer_phno" value="<?php echo htmlspecialchars($cust_emer_phno); ?>">
                
                <label for="cust_status"><strong>Status: </strong></label>
                <select id="cust_status" name="cust_status" required>
                    <option value="active" <?php if ($cust_status == 'active') echo 'selected'; ?>>Active</option>
                    <option value="inactive" <?php if ($cust_status == 'inactive') echo 'selected'; ?>>Inactive</option>
                </select>

                <!-- <label style="display:inline-block;"><strong>Rating: </strong></label>
                <span style="margin-left:5px;">
                    <?php if ($avg_rating !== null && $review_count > 0): ?>
                        <span style="color:#F09E0B;font-size:16px;cursor:pointer;" onclick="showCustRatingsPopup(<?php echo $cust_id; ?>)">
                        &#11088;<?php echo $avg_rating; ?> (<?php echo $review_count; ?> review<?php echo $review_count > 1 ? 's' : ''; ?>)
                        </span>
                    <?php else: ?>
                        <span style="color:#888;font-size:14px;">N/A</span>
                    <?php endif; ?>
                </span> -->
                <label style="display:inline-block;"><strong>Comments: </strong></label>
                <span style="margin-left:5px;">
                    <?php
                    $stmt = $conn->prepare("SELECT cmt_ppl, cmt_hse FROM cust_ratings WHERE cust_id = ?");
                    $stmt->bind_param("i", $cust_id);
                    $stmt->execute();
                    $stmt->bind_result($cmt_ppl, $cmt_hse);
                    $stmt->fetch();
                    $stmt->close();

                    if ($cmt_ppl || $cmt_hse): ?>
                        <!-- <button type="button" onclick="showCustRatingsPopup(<?php echo $cust_id; ?>)">View Comments</button> -->
                        <span class="cust-rating" style="margin-left:0px;color:#F09E0B;font-size:16px;cursor:pointer;" onclick="showCustRatingsPopup(<?php echo $cust_id; ?>)">
                            View Comments
                        </span>
                    <?php else: ?>
                        <span style="color:#888;font-size:14px;">No comments yet</span>
                    <?php endif; ?>
                </span>

                <label for="cust_labels"><strong>Labels: </strong></label>
                <?php if ($labels->num_rows == 0): ?>
                    <span style="color:#888;">No active label</span>
                <?php endif; ?>
                <?php while ($label = $labels->fetch_assoc()): ?>
                    <input type="checkbox" name="cust_labels[]" value="<?php echo $label['clbl_id']; ?>" <?php if (in_array($label['clbl_id'], $selected_labels)) echo 'checked'; ?>>
                    <?php echo htmlspecialchars($label['clbl_name']); ?><br>
                <?php endwhile; ?>

                <div class="button-container">
                    <button type="submit">Save Changes</button>
                    <button type="button" onclick="window.location.href='view_customers.php'">Back</button>
                </div>
            </form>

            <br><hr style="border-top: 5px solid;">
            <h3>Addresses</h3>
            <?php if (!empty($addresses)): ?>
                <form id="deleteForm" method="POST" action="delete_cust_address.php" style="display: none;">
                    <input type="hidden" name="delete_address_id" id="delete_address_id">
                    <input type="hidden" name="cust_id" value="<?php echo $cust_id; ?>">
                </form>
                <table>
                    <?php foreach ($addresses as $index => $address): ?>
                        <tr>
                            <td><strong>Address <?php echo $index + 1; ?></strong></td>
                            <td>: <?php echo htmlspecialchars($address['address']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Postcode</strong></td>
                            <td>: <?php echo htmlspecialchars($address['postcode']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Area</strong></td>
                            <td>: <?php echo htmlspecialchars($address['area']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>State</strong></td>
                            <td>: <?php echo htmlspecialchars($address['state']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>House Type</strong></td>
                            <td>: <?php echo htmlspecialchars($address['housetype']); ?></td>
                        </tr>
                        <tr>
                            <td><strong># of Family Members</strong></td>
                            <td>: <?php echo htmlspecialchars($address['fm_num']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Pet</strong></td>
                            <td>: <?php echo htmlspecialchars($address['pet']); ?></td>
                        </tr>
                            <tr>
                            <td colspan="2">
                                <button onclick="location.href='update_cust_address.php?address_id=<?php echo $address['id']; ?>&cust_id=<?php echo $cust_id; ?>'">Update Address</button>
                                <button onclick="confirmDelete(<?php echo $address['id']; ?>)" style="background-color: red; color: white;">Delete Address</button>
                            </td>
                        </tr>
                        <tr><td colspan="2"><hr></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No addresses found. <a href="add_cust_address.php?cust_id=<?php echo $cust_id; ?>">Add an address</a>.</p>
            <?php endif; ?>
            <button onclick="location.href='add_cust_address.php?cust_id=<?php echo $cust_id; ?>'">Add Address</button>

            <div id="custRatingsPopup" style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; box-shadow:0 2px 10px #0002; z-index:9999; padding:20px; max-width:90vw; max-height:80vh; overflow:auto;">
                <h3 style="text-align:center;">Customer Ratings</h3>
                <table id="custRatingsTable" border="1" cellpadding="5" style="width:100%; border-collapse:collapse;">
                    <!-- <thead>
                        <tr>
                            <th>Rated By</th>
                            <th>Rate</th>
                            <th>Comment</th>
                        </tr>
                    </thead> -->
                    <tbody>
                        <!-- Ratings will be loaded here -->
                    </tbody>
                </table>
                <div style="text-align:center;">
                    <button onclick="closeCustRatingsPopup()">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function showCustRatingsPopup(cust_id) {
    $('#custRatingsPopup').show();
    $('#custRatingsTable tbody').html('<tr><td colspan="3">Loading...</td></tr>');
    $.get('get_customer_ratings.php', { cust_id: cust_id }, function(data) {
        $('#custRatingsTable tbody').html(data);
    });
}
function closeCustRatingsPopup() {
    $('#custRatingsPopup').hide();
}
</script>
</body>
</html>
<?php
// // $conn->close();
?>
