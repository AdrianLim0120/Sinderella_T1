<?php
session_start();
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../login_cust.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$cust_id = $_SESSION['cust_id'];

// Retrieve customer details
$stmt = $conn->prepare("SELECT cust_name, cust_phno, cust_emer_name, cust_emer_phno FROM customers WHERE cust_id = ?");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($cust_name, $cust_phno, $cust_emer_name, $cust_emer_phno);
$stmt->fetch();
$stmt->close();

// Retrieve customer addresses
$addresses = [];
$stmt = $conn->prepare("SELECT cust_address_id, cust_address, cust_postcode, cust_area, cust_state, cust_housetype, cust_fm_num, cust_pet FROM cust_addresses WHERE cust_id = ?");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($cust_address_id, $cust_address, $cust_postcode, $cust_area, $cust_state, $cust_housetype, $cust_fm_num, $cust_pet);
while ($stmt->fetch()) {
    $addresses[] = [
        'id' => $cust_address_id, 
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
// $conn->close();

// Format phone number function
function format_phone($number) {
    $number = preg_replace('/\D/', '', $number); // Remove non-digits
    if (strlen($number) == 10) {
        // 012-345 6789
        return preg_replace("/(\d{3})(\d{3})(\d{4})/", "$1-$2 $3", $number);
    } elseif (strlen($number) == 11) {
        // 012-3456 7890
        return preg_replace("/(\d{3})(\d{4})(\d{4})/", "$1-$2 $3", $number);
    } else {
        return htmlspecialchars($number);
    }
}

$cust_id_formatted = str_pad($cust_id, 4, '0', STR_PAD_LEFT);
$cust_phno_formatted = format_phone($cust_phno);

$cust_emer_name_display = (!empty($cust_emer_name)) ? htmlspecialchars($cust_emer_name) : 'N/A';
$cust_emer_phno_display = (!empty($cust_emer_phno)) ? format_phone($cust_emer_phno) : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_cust.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_cust.php'; ?>
            <div class="profile-container">
                <h2>Manage Profile</h2>
                <table>
                    <tr>
                        <td><strong>Name</strong></td>
                        <td>: <?php echo htmlspecialchars($cust_name); ?> <!-- [ID: <?php echo $cust_id_formatted; ?>] --></td>
                    </tr>
                    <tr>
                        <td><strong>Phone Number</strong></td>
                        <td>: <?php echo htmlspecialchars($cust_phno_formatted); ?></td>
                    </tr>
                </table>

                <h3>Emergency Contact</h3>
                <table>
                    <tr>
                        <td><strong>Name</strong></td>
                        <td>: <?php echo $cust_emer_name_display; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Phone Number</strong></td>
                        <td>: <?php echo $cust_emer_phno_display; ?></td>
                    </tr>
                </table>

                <button onclick="location.href='reset_pwd.php'">Reset Password</button>

                <br>
                <h3>Addresses</h3>
                <?php if (!empty($addresses)): ?>
                    <form id="deleteForm" method="POST" action="delete_address.php" style="display: none;">
                        <input type="hidden" name="delete_address_id" id="delete_address_id">
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
                                    <button onclick="location.href='update_address.php?address_id=<?php echo $address['id']; ?>'">Update Address</button>
                                    <button onclick="confirmDelete(<?php echo $address['id']; ?>)" style="background-color: red; color: white;">Delete Address</button>
                                </td>
                            </tr>
                            <tr><td colspan="2"><hr></td></tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p style="color:red;">No addresses found.</p>
                     <!-- <a href="add_address.php">Add an address</a>.</p> -->
                <?php endif; ?>

                <button onclick="location.href='add_address.php'">Add Address</button>
            </div>
        </div>
    </div>
    <script>
        function confirmDelete(addressId) {
            if (confirm("Are you sure you want to delete this address? This action cannot be undone.")) {
                document.getElementById('delete_address_id').value = addressId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>