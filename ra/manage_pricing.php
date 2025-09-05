<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

// Fetch all services
// $services_query = "SELECT service_id, service_name, service_price, service_duration, service_status FROM service_pricing ORDER BY service_status = 'active' DESC, service_name ASC";
// $services_result = $conn->query($services_query);

$services_query = "
    SELECT 
        s.service_id, 
        s.service_name, 
        s.service_duration, 
        s.service_status,
        (SELECT total_price FROM pricings WHERE service_id = s.service_id AND service_type = 'a' LIMIT 1) AS adhoc_price,
        (SELECT total_price FROM pricings WHERE service_id = s.service_id AND service_type = 'r' LIMIT 1) AS recurring_price
    FROM services s
    ORDER BY s.service_status = 'active' DESC, s.service_name ASC
";
$services_result = $conn->query($services_query);

// Fetch Sinderella registration fee
$fee_query = "SELECT master_amount FROM master_number WHERE master_desc = 'Sind Registration Fee'";
$fee_result = $conn->query($fee_query);
$fee_row = $fee_result->fetch_assoc();
$registration_fee = $fee_row['master_amount'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pricing - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .service-table, .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .service-table th, .service-table td, .fee-table th, .fee-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        .service-table th, .fee-table th {
            background-color: #0c213b;
            color: white;
        }
        .inactive-service {
            background-color: #f0f0f0;
        }
        #remove-button, .modify-button, .add-button, #activate-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        #remove-button:hover, .modify-button:hover, .add-button:hover, #activate-button:hover {
            background-color: #d32f2f;
        }
        #activate-button {
            background-color: #4CAF50;
        }
        #activate-button:hover {
            background-color: #45a049;
        }
        .update-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }
        .update-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Manage Pricing</h2>
                <h3>Services</h3>
                <table class="service-table">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Ad-Hoc Price</th>
                            <th>Recurring Price</th>
                            <th>Service Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($service = $services_result->fetch_assoc()): ?>
                            <tr class="<?php echo $service['service_status'] == 'inactive' ? 'inactive-service' : ''; ?>">
                                <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                <!-- <td><?php echo htmlspecialchars(number_format($service['service_price'], 2)); ?></td> -->
                                <td><?php echo isset($service['adhoc_price']) ? htmlspecialchars(number_format($service['adhoc_price'], 2)) : '0.00'; ?></td>
                                <td><?php echo isset($service['recurring_price']) ? htmlspecialchars(number_format($service['recurring_price'], 2)) : '0.00'; ?></td>
                                <td>
                                    <?php
                                    $duration = (float)$service['service_duration'];
                                    echo htmlspecialchars($duration) . ' ' . ($duration <= 1 ? 'Hour' : 'Hours');
                                    ?>
                                </td>
                                <td>
                                    <button class="modify-button" onclick="window.location.href='modify_service.php?service_id=<?php echo $service['service_id']; ?>'">Modify</button>
                                    <?php if ($service['service_status'] == 'active'): ?>
                                        <button id="remove-button" onclick="toggleServiceStatus(<?php echo $service['service_id']; ?>, 'inactive')">Deactivate</button>
                                    <?php else: ?>
                                        <button id="activate-button" onclick="toggleServiceStatus(<?php echo $service['service_id']; ?>, 'active')">Activate</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <button class="add-button" onclick="window.location.href='modify_service.php'">Add Service</button>
                <h3>Sinderella Registration Fee</h3>
                <form id="updateFeeForm">
                    <table class="fee-table">
                        <thead>
                            <tr>
                                <th>Current Fee</th>
                                <th>New Fee</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars(number_format($registration_fee, 2)); ?></td>
                                <td><input type="number" step="0.01" id="newFee" name="newFee" required></td>
                                <td><button type="submit" class="update-button">Update Fee</button></td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
    <script>
        function toggleServiceStatus(serviceId, status) {
            var password = prompt("Please enter your password to " + (status == 'inactive' ? 'deactivate' : 'activate') + " the service:");
            if (password != null && password != "") {
                $.ajax({
                    url: 'toggle_service_status.php',
                    type: 'POST',
                    data: {
                        service_id: serviceId,
                        status: status,
                        password: password
                    },
                    success: function(response) {
                        if (response == 'success') {
                            alert("Service " + (status == 'inactive' ? 'deactivated' : 'activated') + " successfully.");
                            location.reload();
                        } else {
                            alert("Incorrect password. Service not " + (status == 'inactive' ? 'deactivated' : 'activated') + ".");
                        }
                    }
                });
            }
        }

        $('#updateFeeForm').submit(function(event) {
            event.preventDefault();
            var newFee = $('#newFee').val();
            $.ajax({
                url: 'update_fee.php',
                type: 'POST',
                data: {
                    new_fee: newFee
                },
                success: function(response) {
                    if (response == 'success') {
                        alert("Registration fee updated successfully.");
                        location.reload();
                    } else {
                        alert("Failed to update registration fee.");
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php
// // $conn->close();
?>