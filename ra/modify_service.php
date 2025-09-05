<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$service_id = isset($_GET['service_id']) ? $_GET['service_id'] : 0;
$service = [];
$pricing = [];
$addons = [];

if ($service_id) {
    // Fetch service details
    $service_query = "SELECT service_name, service_duration FROM services WHERE service_id = ?";
    $stmt = $conn->prepare($service_query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $service_result = $stmt->get_result();
    $service = $service_result->fetch_assoc();

    // Fetch pricing details for both ad-hoc and recurring
    $pricing = [
        'adhoc' => [],
        'recurring' => []
    ];
    $pricing_query = "SELECT * FROM pricings WHERE service_id = ?";
    $stmt = $conn->prepare($pricing_query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $pricing_result = $stmt->get_result();
    while ($row = $pricing_result->fetch_assoc()) {
        $type = ($row['service_type'] == 'a') ? 'adhoc' : 'recurring';
        foreach ($row as $k => $v) {
            if ($k === 'service_type' || $k === 'service_id' || $k === 'id') continue;
            $pricing[$type][$k] = ($v === null) ? 0 : $v;
        }
    }

    // Fetch add-ons
    $addons = [];
    $addons_query = "SELECT * FROM addon WHERE service_id = ?";
    $stmt = $conn->prepare($addons_query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $addons_result = $stmt->get_result();
    while ($addon = $addons_result->fetch_assoc()) {
        // Set all nulls to 0 for price fields
        foreach (['ao_price','ao_platform','ao_sind','ao_price_recurring','ao_platform_recurring','ao_sind_recurring','ao_price_resched24','ao_platform_resched24','ao_sind_resched24','ao_price_resched2','ao_platform_resched2','ao_sind_resched2','ao_price_resched24_re','ao_platform_resched24_re','ao_sind_resched24_re','ao_price_resched2_re','ao_platform_resched2_re','ao_sind_resched2_re','ao_duration'] as $field) {
            if (!isset($addon[$field]) || $addon[$field] === null) $addon[$field] = 0;
        }
        $addons[] = $addon;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $service_id ? 'Modify' : 'Add'; ?> Service - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .profile-container label {
            display: block;
            margin-top: 10px;
        }
        .profile-container input[type="text"],
        .profile-container input[type="number"] {
            width: calc(50% - 10px);
            padding: 5px;
            margin-right: 10px;
        }
        .profile-container .addon-container {
            margin-top: 20px;
        }
        .profile-container .addon-item {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .profile-container .addon-item input[type="text"] {
            width: calc(25% - 10px);
        }
        .profile-container .addon-item input[type="number"] {
            width: calc(15% - 10px);
        }
        .profile-container .addon-item button {
            margin-left: 10px;
        }
        .profile-container .addon-container button {
            /* margin-top: 10px; */
            margin: 5px 0px;
        }
        .profile-container .addon-container input[type="text"],
        .profile-container .addon-container input[type="number"], 
        .profile-container .pricing-container input[type="number"]{
            width: 90%;
            margin: 5px 1px;
            text-align: center;
            border: 0;
            /* margin-right: 10px; */
        }

        h3 {
            text-align: center;
        }

        hr {
            border-top: 5px solid;
        }

        /* for add on section - activate & deactivate button */
        #activate-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        #activate-button:hover {
            background-color: #45a049;
        }
        #deactivate-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        #deactivate-button:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2><?php echo $service_id ? 'Modify' : 'Add'; ?> Service</h2>
                <form id="serviceForm" method="POST" action="save_service.php">
                    <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">

                    <label for="service_name">Service Name</label>
                    <input type="text" id="service_name" name="service_name" value="<?php echo htmlspecialchars($service['service_name'] ?? ''); ?>" required>

                    <label for="service_duration">Service Duration (Hours)</label>
                    <input type="number" step="0.01" id="service_duration" name="service_duration" value="<?php echo htmlspecialchars($service['service_duration'] ?? ''); ?>" required>

                    <div class="pricing-container">
                    <br><hr>
                    <h3>Service Pricing - Ad-Hoc</h3>

                    <!-- <label for="service_price">Total Price</label>
                    <input type="number" step="0.01" id="service_price" name="service_price" value="<?php echo htmlspecialchars($service['service_price'] ?? ''); ?>" required>

                    <label for="pr_platform">Platform</label>
                    <input type="number" step="0.01" id="pr_platform" name="pr_platform" value="<?php echo htmlspecialchars($pricing['pr_platform'] ?? ''); ?>" required>

                    <label for="pr_sind">Sinderella</label>
                    <input type="number" step="0.01" id="pr_sind" name="pr_sind" value="<?php echo htmlspecialchars($pricing['pr_sind'] ?? ''); ?>" required>

                    <label for="pr_lvl1">Level 1 Referral</label>
                    <input type="number" step="0.01" id="pr_lvl1" name="pr_lvl1" value="<?php echo htmlspecialchars($pricing['pr_lvl1'] ?? ''); ?>" required>

                    <label for="pr_lvl2">Level 2 Referral</label>
                    <input type="number" step="0.01" id="pr_lvl2" name="pr_lvl2" value="<?php echo htmlspecialchars($pricing['pr_lvl2'] ?? ''); ?>" required>

                    <label for="pr_lvl3">Level 3 Referral</label>
                    <input type="number" step="0.01" id="pr_lvl3" name="pr_lvl3" value="<?php echo htmlspecialchars($pricing['pr_lvl3'] ?? ''); ?>" required>

                    <label for="pr_lvl4">Level 4 Referral</label>
                    <input type="number" step="0.01" id="pr_lvl4" name="pr_lvl4" value="<?php echo htmlspecialchars($pricing['pr_lvl4'] ?? ''); ?>" required>

                    <h3>Sinderella Income Breakdown</h3>
                    <!-- <table border="1" style="width: 100%; border-collapse: collapse; text-align: center;">
                        <thead style="background-color: #0c213b; color: white;">
                            <tr>
                                <th>Basic</th>
                                <th>Rating</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="number" step="0.01" id="pr_br_basic" name="pr_br_basic" value="<?php echo htmlspecialchars($pricing['pr_br_basic'] ?? ''); ?>" required></td>
                                <td><input type="number" step="0.01" id="pr_br_rate" name="pr_br_rate" value="<?php echo htmlspecialchars($pricing['pr_br_rate'] ?? ''); ?>" required></td>
                                <td><input type="number" step="0.01" id="pr_br_perf" name="pr_br_perf" value="<?php echo htmlspecialchars($pricing['pr_br_perf'] ?? ''); ?>" required></td>
                            </tr>
                        </tbody>
                    </table> 

                    <label for="pr_br_basic">Basic</label>
                    <input type="number" step="0.01" id="pr_br_basic" name="pr_br_basic" value="<?php echo htmlspecialchars($pricing['pr_br_basic'] ?? ''); ?>" required>

                    <label for="pr_br_rate">Rating</label>
                    <input type="number" step="0.01" id="pr_br_rate" name="pr_br_rate" value="<?php echo htmlspecialchars($pricing['pr_br_rate'] ?? ''); ?>" required>

                    <label for="pr_br_perf">Performance</label>
                    <input type="number" step="0.01" id="pr_br_perf" name="pr_br_perf" value="<?php echo htmlspecialchars($pricing['pr_br_perf'] ?? ''); ?>" required> -->

                    <table border="1" style="width:100%; border-collapse:collapse; text-align:center;">
                    <thead>
                        <tr style="background:#eee;">
                            <th rowspan="2"></th>
                            <th><strong>Booking Price (RM)</strong></th>
                            <th colspan="2"><strong>Penalty for Reschedule (RM)</strong></th>

                        </tr>
                        <tr>
                            <th style="background:#4CAF50;color:#fff;">Ad-Hoc</th>
                            <th style="background:#FF9800;color:#fff;">Reschedule &lt; 48 hours</th>
                            <th style="background:#F44336;color:#fff;">Reschedule &lt; 24 hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Price</td>
                            <td><input type="number" step="0.01" name="adhoc_service_price" value="<?php echo $pricing['adhoc']['total_price'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_service_price_resched24" value="<?php echo $pricing['adhoc']['penalty24_total'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_service_price_resched2" value="<?php echo $pricing['adhoc']['penalty2_total'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Platform</td>
                            <td><input type="number" step="0.01" name="adhoc_pr_platform" value="<?php echo $pricing['adhoc']['platform'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_platform_resched24" value="<?php echo $pricing['adhoc']['penalty24_platform'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_platform_resched2" value="<?php echo $pricing['adhoc']['penalty2_platform'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Sinderella</td>
                            <td><input type="number" step="0.01" name="adhoc_pr_sind" value="<?php echo $pricing['adhoc']['sinderella'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_sind_resched24" value="<?php echo $pricing['adhoc']['penalty24_sind'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_sind_resched2" value="<?php echo $pricing['adhoc']['penalty2_sind'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Level 1 Referral</td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl1" value="<?php echo $pricing['adhoc']['lvl1'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl1_resched24" value="<?php echo $pricing['adhoc']['penalty24_lvl1'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl1_resched2" value="<?php echo $pricing['adhoc']['penalty2_lvl1'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Level 2 Referral</td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl2" value="<?php echo $pricing['adhoc']['lvl2'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl2_resched24" value="<?php echo $pricing['adhoc']['penalty24_lvl2'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl2_resched2" value="<?php echo $pricing['adhoc']['penalty2_lvl2'] ?? 0; ?>" required></td>
                        </tr>
                        <!-- <tr>
                            <td>Level 3 Referral</td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl3" value="<?php echo $pricing['adhoc']['lvl3'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl3_resched24" value="<?php echo $pricing['adhoc']['penalty24_lvl3'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl3_resched2" value="<?php echo $pricing['adhoc']['penalty2_lvl3'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Level 4 Referral</td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl4" value="<?php echo $pricing['adhoc']['lvl4'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl4_resched24" value="<?php echo $pricing['adhoc']['penalty24_lvl4'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_lvl4_resched2" value="<?php echo $pricing['adhoc']['penalty2_lvl4'] ?? 0; ?>" required></td>
                        </tr> -->
                        <!-- <tr>
                            <th colspan="5" style="background:#eee;"><strong>Sinderella Breakdown</strong></th>
                        </tr>
                        <tr>
                            <td>Basic</td>
                            <td><input type="number" step="0.01" name="adhoc_pr_br_basic" value="<?php echo $pricing['adhoc']['br_basic'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_br_basic_resched24" value="<?php echo $pricing['adhoc']['penalty24_br_basic'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_br_basic_resched2" value="<?php echo $pricing['adhoc']['penalty2_br_basic'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Rating</td>
                            <td><input type="number" step="0.01" name="adhoc_pr_br_rate" value="<?php echo $pricing['adhoc']['br_rate'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_br_rate_resched24" value="<?php echo $pricing['adhoc']['penalty24_br_rate'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_br_rate_resched2" value="<?php echo $pricing['adhoc']['penalty2_br_rate'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Performance</td>
                            <td><input type="number" step="0.01" name="adhoc_pr_br_perf" value="<?php echo $pricing['adhoc']['br_perf'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_br_perf_resched24" value="<?php echo $pricing['adhoc']['penalty24_br_perf'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="adhoc_pr_br_perf_resched2" value="<?php echo $pricing['adhoc']['penalty2_br_perf'] ?? 0; ?>" required></td>
                        </tr> -->
                    </tbody>
                </table>

                <br><hr>
                <h3>Service Pricing - Recurring</h3>
                <table border="1" style="width:100%; border-collapse:collapse; text-align:center;">
                    <thead>
                        <tr style="background:#eee;">
                            <th rowspan="2"></th>
                            <th><strong>Booking Price (RM)</strong></th>
                            <th colspan="2"><strong>Penalty for Reschedule (RM)</strong></th>

                        </tr>
                        <tr>
                            <th style="background:#2196F3;color:#fff;">Recurring</th>
                            <th style="background:#FF9800;color:#fff;">Reschedule &lt; 48 hours</th>
                            <th style="background:#F44336;color:#fff;">Reschedule &lt; 24 hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Price</td>
                            <td><input type="number" step="0.01" name="rec_service_price" value="<?php echo $pricing['recurring']['total_price'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_service_price_resched24" value="<?php echo $pricing['recurring']['penalty24_total'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_service_price_resched2" value="<?php echo $pricing['recurring']['penalty2_total'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Platform</td>
                            <td><input type="number" step="0.01" name="rec_pr_platform" value="<?php echo $pricing['recurring']['platform'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_platform_resched24" value="<?php echo $pricing['recurring']['penalty24_platform'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_platform_resched2" value="<?php echo $pricing['recurring']['penalty2_platform'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Sinderella</td>
                            <td><input type="number" step="0.01" name="rec_pr_sind" value="<?php echo $pricing['recurring']['sinderella'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_sind_resched24" value="<?php echo $pricing['recurring']['penalty24_sind'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_sind_resched2" value="<?php echo $pricing['recurring']['penalty2_sind'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Level 1 Referral</td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl1" value="<?php echo $pricing['recurring']['lvl1'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl1_resched24" value="<?php echo $pricing['recurring']['penalty24_lvl1'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl1_resched2" value="<?php echo $pricing['recurring']['penalty2_lvl1'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Level 2 Referral</td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl2" value="<?php echo $pricing['recurring']['lvl2'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl2_resched24" value="<?php echo $pricing['recurring']['penalty24_lvl2'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl2_resched2" value="<?php echo $pricing['recurring']['penalty2_lvl2'] ?? 0; ?>" required></td>
                        </tr>
                        <!-- <tr>
                            <td>Level 3 Referral</td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl3" value="<?php echo $pricing['recurring']['lvl3'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl3_resched24" value="<?php echo $pricing['recurring']['penalty24_lvl3'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl3_resched2" value="<?php echo $pricing['recurring']['penalty2_lvl3'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Level 4 Referral</td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl4" value="<?php echo $pricing['recurring']['lvl4'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl4_resched24" value="<?php echo $pricing['recurring']['penalty24_lvl4'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_lvl4_resched2" value="<?php echo $pricing['recurring']['penalty2_lvl4'] ?? 0; ?>" required></td>
                        </tr> -->
                        <!-- <tr>
                            <th colspan="5" style="background:#eee;"><strong>Sinderella Breakdown</strong></th>
                        </tr>
                        <tr>
                            <td>Basic</td>
                            <td><input type="number" step="0.01" name="rec_pr_br_basic" value="<?php echo $pricing['recurring']['br_basic'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_br_basic_resched24" value="<?php echo $pricing['recurring']['penalty24_br_basic'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_br_basic_resched2" value="<?php echo $pricing['recurring']['penalty2_br_basic'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Rating</td>
                            <td><input type="number" step="0.01" name="rec_pr_br_rate" value="<?php echo $pricing['recurring']['br_rate'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_br_rate_resched24" value="<?php echo $pricing['recurring']['penalty24_br_rate'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_br_rate_resched2" value="<?php echo $pricing['recurring']['penalty2_br_rate'] ?? 0; ?>" required></td>
                        </tr>
                        <tr>
                            <td>Performance</td>
                            <td><input type="number" step="0.01" name="rec_pr_br_perf" value="<?php echo $pricing['recurring']['br_perf'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_br_perf_resched24" value="<?php echo $pricing['recurring']['penalty24_br_perf'] ?? 0; ?>" required></td>
                            <td><input type="number" step="0.01" name="rec_pr_br_perf_resched2" value="<?php echo $pricing['recurring']['penalty2_br_perf'] ?? 0; ?>" required></td>
                        </tr> -->
                    </tbody>
                </table>
                </div>

                    <!-- <div class="addon-container">
                        <h3>Add-ons</h3>
                        <div id="addons">
                            <?php foreach ($addons as $addon): ?>
                                <div class="addon-item">
                                    <input type="hidden" name="addon_id[]" value="<?php echo htmlspecialchars($addon['ao_id']); ?>">
                                    <input type="text" name="addon_desc[]" value="<?php echo htmlspecialchars($addon['ao_desc']); ?>" required>
                                    <input type="number" step="0.01" name="addon_price[]" value="<?php echo htmlspecialchars($addon['ao_price']); ?>" required>
                                    <input type="number" step="0.01" name="addon_duration[]" value="<?php echo htmlspecialchars($addon['ao_duration']); ?>" required>
                                    <button type="button" onclick="removeAddon(this)">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" onclick="addAddon()">Add Add-on</button>
                    </div> -->

                    <br><hr>
                    <div class="addon-container">
                        <h3>Add-ons</h3>
                        <!-- <table id="addonsTable" border="1" style="width: 100%; border-collapse: collapse; text-align: center;">
                            <thead style="background-color: #0c213b; color: white;">
                                <tr>
                                    <th>Description</th>
                                    <th>Duration<br>(Hours)</th>
                                    <th>Price</th>
                                    <th>Platform</th>
                                    <th>Sinderella</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($addons as $addon): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="addon_id[]" value="<?php echo htmlspecialchars($addon['ao_id']); ?>">
                                            <input type="text" name="addon_desc[]" value="<?php echo htmlspecialchars($addon['ao_desc']); ?>" required>
                                        </td>
                                        <td><input type="number" step="0.01" name="addon_duration[]" value="<?php echo htmlspecialchars($addon['ao_duration']); ?>" required></td>
                                        <td><input type="number" step="0.01" name="addon_price[]" value="<?php echo htmlspecialchars($addon['ao_price']); ?>" required></td>
                                        <td><input type="number" step="0.01" name="addon_platform[]" value="<?php echo htmlspecialchars($addon['ao_platform']); ?>" required></td>
                                        <td><input type="number" step="0.01" name="addon_sind[]" value="<?php echo htmlspecialchars($addon['ao_sind']); ?>" required></td>
                                        <!-- <td><button type="button" onclick="removeAddonRow(this)">Remove</button></td> 
                                        <td>
                                            <?php if ($addon['ao_status'] == 'active'): ?>
                                                <button type="button" id="deactivate-button" onclick="toggleAddonStatus(<?php echo $addon['ao_id']; ?>, 'inactive')">Deactivate</button>
                                            <?php else: ?>
                                                <button type="button" id="activate-button" onclick="toggleAddonStatus(<?php echo $addon['ao_id']; ?>, 'active')">Activate</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table> -->

                        <table id="addonsTable" border="1" style="width:100%; border-collapse:collapse; text-align:center;">
                        <thead>
                            <tr style="background-color:#eee; color:#000;">
                                <th style="width:180px;">&nbsp;</th>

                                <?php foreach ($addons as $i => $addon): ?>
                                    <th><strong>Add-on <?php echo $i+1; ?></strong></th>
                                <?php endforeach; ?>

                                <!-- <th>
                                    <button type="button" onclick="addAddonRowVertical()">Add Add-on</button>
                                </th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Description</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="hidden" step="0.01" name="addon_id[]" value="<?php echo htmlspecialchars($addon['ao_id']); ?>" required>
                                        <input type="text" name="addon_desc[]" value="<?php echo htmlspecialchars($addon['ao_desc']); ?>" required>
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Duration (Hours)</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_duration[]" value="<?php echo htmlspecialchars($addon['ao_duration']); ?>" required>
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td colspan="<?php echo count($addons)+2; ?>" style="background:#4CAF50;color:#fff;"><strong>Booking Price - Ad-Hoc</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Price</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_price[]" value="<?php echo htmlspecialchars($addon['ao_price']); ?>" required>
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Platform</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_platform[]" value="<?php echo htmlspecialchars($addon['ao_platform']); ?>" required>
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Sinderella</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_sind[]" value="<?php echo htmlspecialchars($addon['ao_sind']); ?>" required>
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td colspan="<?php echo count($addons)+2; ?>" style="background:#FF9800;color:#fff;"><strong>Penalty (Reschedule &lt; 48 hours) - Ad-Hoc</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Price</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_price_resched24[]" value="<?php echo htmlspecialchars($addon['ao_price_resched24'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Platform</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_platform_resched24[]" value="<?php echo htmlspecialchars($addon['ao_platform_resched24'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Sinderella</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_sind_resched24[]" value="<?php echo htmlspecialchars($addon['ao_sind_resched24'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td colspan="<?php echo count($addons)+2; ?>" style="background:#F44336;color:#fff;"><strong>Penalty (Reschedule &lt; 24 hours) - Ad-Hoc</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Price</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_price_resched2[]" value="<?php echo htmlspecialchars($addon['ao_price_resched2'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Platform</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_platform_resched2[]" value="<?php echo htmlspecialchars($addon['ao_platform_resched2'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Sinderella</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_sind_resched2[]" value="<?php echo htmlspecialchars($addon['ao_sind_resched2'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td colspan="<?php echo count($addons)+2; ?>" style="background:#2196F3;color:#fff;"><strong>Booking Price - Recurring</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Price</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_price_recurring[]" value="<?php echo htmlspecialchars($addon['ao_price_recurring'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Platform</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_platform_recurring[]" value="<?php echo htmlspecialchars($addon['ao_platform_recurring'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Sinderella</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_sind_recurring[]" value="<?php echo htmlspecialchars($addon['ao_sind_recurring'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td colspan="<?php echo count($addons)+2; ?>" style="background:#FF9800;color:#fff;"><strong>Penalty (Reschedule &lt; 48 hours) - Recurring</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Price</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_price_resched24_re[]" value="<?php echo htmlspecialchars($addon['ao_price_resched24_re'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Platform</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_platform_resched24_re[]" value="<?php echo htmlspecialchars($addon['ao_platform_resched24_re'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Sinderella</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_sind_resched24_re[]" value="<?php echo htmlspecialchars($addon['ao_sind_resched24_re'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td colspan="<?php echo count($addons)+2; ?>" style="background:#F44336;color:#fff;"><strong>Penalty (Reschedule &lt; 24 hours) - Recurring</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Price</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_price_resched2_re[]" value="<?php echo htmlspecialchars($addon['ao_price_resched2_re'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Platform</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_platform_resched2_re[]" value="<?php echo htmlspecialchars($addon['ao_platform_resched2_re'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Sinderella</strong></td>
                                <?php foreach ($addons as $addon): ?>
                                    <td>
                                        <input type="number" step="0.01" name="addon_sind_resched2_re[]" value="<?php echo htmlspecialchars($addon['ao_sind_resched2_re'] ?? ''); ?>">
                                    </td>
                                <?php endforeach; ?>
                                <!-- <td></td> -->
                            </tr>
                            <tr>
                                <td><strong>Action</strong></td>
                                <?php foreach ($addons as $i => $addon): ?>
                                <td>
                                    <?php if ($addon['ao_status'] == 'active'): ?>
                                        <button type="button" id="deactivate-button" onclick="toggleAddonStatus(<?php echo $addon['ao_id']; ?>, 'inactive')">Deactivate</button>
                                    <?php else: ?>
                                        <button type="button" id="activate-button" onclick="toggleAddonStatus(<?php echo $addon['ao_id']; ?>, 'active')">Activate</button>
                                    <?php endif; ?>
                                    <!-- <button type="button" onclick="removeAddonRow(this)">Remove</button> -->
                                </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                    <button type="button" onclick="addAddonRowVertical()">Add Add-on</button>
                    <br>
                    <button type="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        function toggleAddonStatus(addonId, status) {
            if (confirm("Are you sure you want to " + (status === 'inactive' ? "deactivate" : "activate") + " this add-on?")) {
                $.ajax({
                    url: 'toggle_addon_status.php',
                    type: 'POST',
                    data: {
                        addon_id: addonId,
                        status: status
                    },
                    success: function(response) {
                        if (response === 'success') {
                            alert("Add-on " + (status === 'inactive' ? "deactivated" : "activated") + " successfully.");
                            location.reload();
                        } else {
                            alert("Failed to update add-on status.");
                        }
                    }
                });
            }
        }

        function addAddon() {
            var addonContainer = document.getElementById('addons');
            var addonItem = document.createElement('div');
            addonItem.className = 'addon-item';
            addonItem.innerHTML = `
                <input type="hidden" name="addon_id[]" value="">
                <input type="text" name="addon_desc[]" placeholder="Add-on Description" required>
                <input type="number" step="0.01" name="addon_duration[]" placeholder="Add-on Duration (Hours)" required>
                <input type="number" step="0.01" name="addon_price[]" placeholder="Add-on Price" required>
                <button type="button" onclick="removeAddon(this)">Remove</button>
            `;
            addonContainer.appendChild(addonItem);
        }

        function addAddonRow() {
            var table = document.getElementById('addonsTable').getElementsByTagName('tbody')[0];
            var newRow = table.insertRow();
            newRow.innerHTML = `
                <tr>
                    <td>
                        <input type="hidden" name="addon_id[]" value="">
                        <input type="text" name="addon_desc[]" placeholder="Add-on Description" required>
                    </td>
                    <td><input type="number" step="0.01" name="addon_duration[]" placeholder="Duration (Hours)" required></td>
                    <td><input type="number" step="0.01" name="addon_price[]" placeholder="Add-on Price" required></td>
                    <td><input type="number" step="0.01" name="addon_platform[]" placeholder="Platform" required></td>
                    <td><input type="number" step="0.01" name="addon_sind[]" placeholder="Sinderella" required></td>
                    <td><button type="button" onclick="removeAddonRow(this)">Remove</button></td>
                </tr>
            `;
        }

        function removeAddon(button) {
            button.parentElement.remove();
        }

        function removeAddonRow(button) {
            button.closest('tr').remove();
        }

        document.getElementById('serviceForm').addEventListener('submit', function(event) {
            console.log("Service form submit handler triggered");
            event.preventDefault();

            var serviceName = document.getElementById('service_name').value.trim();
            serviceName = serviceName.charAt(0).toUpperCase() + serviceName.slice(1).toLowerCase();
            document.getElementById('service_name').value = serviceName;

            document.querySelectorAll('input[name="addon_desc[]"]').forEach(function(input) {
                var desc = input.value.trim();
                desc = desc.charAt(0).toUpperCase() + desc.slice(1).toLowerCase();
                input.value = desc;
            });

            // var totalPrice = parseFloat(document.getElementById('service_price').value);
            // var prPlatform = parseFloat(document.getElementById('pr_platform').value);
            // var prSind = parseFloat(document.getElementById('pr_sind').value);
            // var prLvl1 = parseFloat(document.getElementById('pr_lvl1').value);
            // var prLvl2 = parseFloat(document.getElementById('pr_lvl2').value);
            // var prLvl3 = parseFloat(document.getElementById('pr_lvl3').value);
            // var prLvl4 = parseFloat(document.getElementById('pr_lvl4').value);

            // var sumCategories = (prPlatform + prSind + prLvl1 + prLvl2 + prLvl3 + prLvl4).toFixed(2);
            // if (sumCategories != totalPrice.toFixed(2)) {
            //     alert('Platform + Sinderella + Level 1-4 Referral must equal Total Price\nTotal Price: ' + totalPrice.toFixed(2) + '\nSum of Categories: ' + sumCategories);
            //     return;
            // }

            // var prBrBasic = parseFloat(document.getElementById('pr_br_basic').value);
            // var prBrRate = parseFloat(document.getElementById('pr_br_rate').value);
            // var prBrPerf = parseFloat(document.getElementById('pr_br_perf').value);

            // var sumBreakdown = (prBrBasic + prBrRate + prBrPerf).toFixed(2);
            // if (sumBreakdown != prSind.toFixed(2)) {
            //     alert('Basic + Rating + Performance must equal Sinderella\nSinderella: ' + prSind.toFixed(2) + '\nSum of Breakdown: ' + sumBreakdown);
            //     return;
            // }

            var totalPrice = parseFloat(document.querySelector('input[name="adhoc_service_price"]').value) || 0;
            var prPlatform = parseFloat(document.querySelector('input[name="adhoc_pr_platform"]').value) || 0;
            var prSind = parseFloat(document.querySelector('input[name="adhoc_pr_sind"]').value) || 0;
            var prLvl1 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl1"]').value) || 0;
            var prLvl2 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl2"]').value) || 0;
            // var prLvl3 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl3"]').value) || 0;
            // var prLvl4 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl4"]').value) || 0;
            var sumCategories = (prPlatform + prSind + prLvl1 + prLvl2).toFixed(2);
            if (sumCategories != totalPrice.toFixed(2)) {
                alert('Ad-hoc: \nPlatform + Sinderella + Level 1-4 Referral must equal Total Price\nTotal Price: ' + totalPrice.toFixed(2) + '\nSum of Categories: ' + sumCategories);
                return;
            }

            var totalPrice24 = parseFloat(document.querySelector('input[name="adhoc_service_price_resched24"]').value) || 0;
            var prPlatform24 = parseFloat(document.querySelector('input[name="adhoc_pr_platform_resched24"]').value) || 0;
            var prSind24 = parseFloat(document.querySelector('input[name="adhoc_pr_sind_resched24"]').value) || 0;
            var prLvl124 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl1_resched24"]').value) || 0;
            var prLvl224 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl2_resched24"]').value) || 0;
            // var prLvl324 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl3_resched24"]').value) || 0;
            // var prLvl424 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl4_resched24"]').value) || 0;
            var sumCategories24 = (prPlatform24 + prSind24 + prLvl124 + prLvl224).toFixed(2);
            if (sumCategories24 != totalPrice24.toFixed(2)) {
                alert('Ad-hoc Penalty < 24h: \nPlatform + Sinderella + Level 1-4 Referral must equal Total Price\nTotal Price: ' + totalPrice24.toFixed(2) + '\nSum of Categories: ' + sumCategories24);
                return;
            }

            var totalPrice2 = parseFloat(document.querySelector('input[name="adhoc_service_price_resched2"]').value) || 0;
            var prPlatform2 = parseFloat(document.querySelector('input[name="adhoc_pr_platform_resched2"]').value) || 0;
            var prSind2 = parseFloat(document.querySelector('input[name="adhoc_pr_sind_resched2"]').value) || 0;
            var prLvl12 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl1_resched2"]').value) || 0;
            var prLvl22 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl2_resched2"]').value) || 0;
            // var prLvl32 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl3_resched2"]').value) || 0;
            // var prLvl42 = parseFloat(document.querySelector('input[name="adhoc_pr_lvl4_resched2"]').value) || 0;
            var sumCategories2 = (prPlatform2 + prSind2 + prLvl12 + prLvl22).toFixed(2);
            if (sumCategories2 != totalPrice2.toFixed(2)) {
                alert('Ad-hoc Penalty < 2h: \nPlatform + Sinderella + Level 1-4 Referral must equal Total Price\nTotal Price: ' + totalPrice2.toFixed(2) + '\nSum of Categories: ' + sumCategories2);
                return;
            }

            var recTotalPrice = parseFloat(document.querySelector('input[name="rec_service_price"]').value) || 0;
            var recPlatform = parseFloat(document.querySelector('input[name="rec_pr_platform"]').value) || 0;
            var recSind = parseFloat(document.querySelector('input[name="rec_pr_sind"]').value) || 0;
            var recLvl1 = parseFloat(document.querySelector('input[name="rec_pr_lvl1"]').value) || 0;
            var recLvl2 = parseFloat(document.querySelector('input[name="rec_pr_lvl2"]').value) || 0;
            // var recLvl3 = parseFloat(document.querySelector('input[name="rec_pr_lvl3"]').value) || 0;
            // var recLvl4 = parseFloat(document.querySelector('input[name="rec_pr_lvl4"]').value) || 0;
            var recSumCategories = (recPlatform + recSind + recLvl1 + recLvl2).toFixed(2);
            if (recSumCategories != recTotalPrice.toFixed(2)) {
                alert('Recurring: \nPlatform + Sinderella + Level 1-4 Referral must equal Total Price\nTotal Price: ' + recTotalPrice.toFixed(2) + '\nSum of Categories: ' + recSumCategories);
                return;
            }

            var recTotalPrice24 = parseFloat(document.querySelector('input[name="rec_service_price_resched24"]').value) || 0;
            var recPlatform24 = parseFloat(document.querySelector('input[name="rec_pr_platform_resched24"]').value) || 0;
            var recSind24 = parseFloat(document.querySelector('input[name="rec_pr_sind_resched24"]').value) || 0;
            var recLvl124 = parseFloat(document.querySelector('input[name="rec_pr_lvl1_resched24"]').value) || 0;
            var recLvl224 = parseFloat(document.querySelector('input[name="rec_pr_lvl2_resched24"]').value) || 0;
            // var recLvl324 = parseFloat(document.querySelector('input[name="rec_pr_lvl3_resched24"]').value) || 0;
            // var recLvl424 = parseFloat(document.querySelector('input[name="rec_pr_lvl4_resched24"]').value) || 0;
            var recSumCategories24 = (recPlatform24 + recSind24 + recLvl124 + recLvl224).toFixed(2);
            // var recSumCategories24 = (recPlatform24 + recSind24 + recLvl124 + recLvl224 + recLvl324 + recLvl424).toFixed(2);
            if (recSumCategories24 != recTotalPrice24.toFixed(2)) {
                alert('Recurring Penalty < 24h: \nPlatform + Sinderella + Level 1-4 Referral must equal Total Price\nTotal Price: ' + recTotalPrice24.toFixed(2) + '\nSum of Categories: ' + recSumCategories24);
                return;
            }

            var recTotalPrice2 = parseFloat(document.querySelector('input[name="rec_service_price_resched2"]').value) || 0;
            var recPlatform2 = parseFloat(document.querySelector('input[name="rec_pr_platform_resched2"]').value) || 0;
            var recSind2 = parseFloat(document.querySelector('input[name="rec_pr_sind_resched2"]').value) || 0;
            var recLvl12 = parseFloat(document.querySelector('input[name="rec_pr_lvl1_resched2"]').value) || 0;
            var recLvl22 = parseFloat(document.querySelector('input[name="rec_pr_lvl2_resched2"]').value) || 0;
            // var recLvl32 = parseFloat(document.querySelector('input[name="rec_pr_lvl3_resched2"]').value) || 0;
            // var recLvl42 = parseFloat(document.querySelector('input[name="rec_pr_lvl4_resched2"]').value) || 0;
            var recSumCategories2 = (recPlatform2 + recSind2 + recLvl12 + recLvl22).toFixed(2);
            // var recSumCategories2 = (recPlatform2 + recSind2 + recLvl12 + recLvl22 + recLvl32 + recLvl42).toFixed(2);
            if (recSumCategories2 != recTotalPrice2.toFixed(2)) {
                alert('Recurring Penalty < 2h: \nPlatform + Sinderella + Level 1-4 Referral must equal Total Price\nTotal Price: ' + recTotalPrice2.toFixed(2) + '\nSum of Categories: ' + recSumCategories2);
                return;
            }

            // Sinderella breakdown 
            /*var prBrBasic = parseFloat(document.querySelector('input[name="adhoc_pr_br_basic"]').value) || 0;
            var prBrRate = parseFloat(document.querySelector('input[name="adhoc_pr_br_rate"]').value) || 0;
            var prBrPerf = parseFloat(document.querySelector('input[name="adhoc_pr_br_perf"]').value) || 0;
            var sumBreakdown = (prBrBasic + prBrRate + prBrPerf).toFixed(2);
            if (sumBreakdown != prSind.toFixed(2)) {
                alert('Ad-hoc: \nBasic + Rating + Performance must equal Sinderella\nSinderella: ' + prSind.toFixed(2) + '\nSum of Breakdown: ' + sumBreakdown);
                return;
            }

            var prBrBasic24 = parseFloat(document.querySelector('input[name="adhoc_pr_br_basic_resched24"]').value) || 0;
            var prBrRate24 = parseFloat(document.querySelector('input[name="adhoc_pr_br_rate_resched24"]').value) || 0;
            var prBrPerf24 = parseFloat(document.querySelector('input[name="adhoc_pr_br_perf_resched24"]').value) || 0;
            if ((prBrBasic24 + prBrRate24 + prBrPerf24).toFixed(2) != prSind24.toFixed(2)) {
                alert('Ad-hoc Penalty < 24h: \nBasic + Rating + Performance must equal Sinderella\nSinderella: ' + prSind24.toFixed(2) + '\nSum of Breakdown: ' + (prBrBasic24 + prBrRate24 + prBrPerf24).toFixed(2));
                return;
            }

            var prBrBasic2 = parseFloat(document.querySelector('input[name="adhoc_pr_br_basic_resched2"]').value) || 0;
            var prBrRate2 = parseFloat(document.querySelector('input[name="adhoc_pr_br_rate_resched2"]').value) || 0;
            var prBrPerf2 = parseFloat(document.querySelector('input[name="adhoc_pr_br_perf_resched2"]').value) || 0;
            if ((prBrBasic2 + prBrRate2 + prBrPerf2).toFixed(2) != prSind2.toFixed(2)) {
                alert('Ad-hoc Penalty < 2h: \nBasic + Rating + Performance must equal Sinderella\nSinderella: ' + prSind2.toFixed(2) + '\nSum of Breakdown: ' + (prBrBasic2 + prBrRate2 + prBrPerf2).toFixed(2));
                return;
            }

            var recBrBasic = parseFloat(document.querySelector('input[name="rec_pr_br_basic"]').value) || 0;
            var recBrRate = parseFloat(document.querySelector('input[name="rec_pr_br_rate"]').value) || 0;
            var recBrPerf = parseFloat(document.querySelector('input[name="rec_pr_br_perf"]').value) || 0;
            if ((recBrBasic + recBrRate + recBrPerf).toFixed(2) != recSind.toFixed(2)) {
                alert('Recurring: \nBasic + Rating + Performance must equal Sinderella\nSinderella: ' + recSind.toFixed(2) + '\nSum of Breakdown: ' + (recBrBasic + recBrRate + recBrPerf).toFixed(2));
                return;
            }

            var recBrBasic24 = parseFloat(document.querySelector('input[name="rec_pr_br_basic_resched24"]').value) || 0;
            var recBrRate24 = parseFloat(document.querySelector('input[name="rec_pr_br_rate_resched24"]').value) || 0;
            var recBrPerf24 = parseFloat(document.querySelector('input[name="rec_pr_br_perf_resched24"]').value) || 0;
            if ((recBrBasic24 + recBrRate24 + recBrPerf24).toFixed(2) != recSind24.toFixed(2)) {
                alert('Recurring Penalty < 24h: \nBasic + Rating + Performance must equal Sinderella\nSinderella: ' + recSind24.toFixed(2) + '\nSum of Breakdown: ' + (recBrBasic24 + recBrRate24 + recBrPerf24).toFixed(2));
                return;
            }

            var recBrBasic2 = parseFloat(document.querySelector('input[name="rec_pr_br_basic_resched2"]').value) || 0;
            var recBrRate2 = parseFloat(document.querySelector('input[name="rec_pr_br_rate_resched2"]').value) || 0;
            var recBrPerf2 = parseFloat(document.querySelector('input[name="rec_pr_br_perf_resched2"]').value) || 0;
            if ((recBrBasic2 + recBrRate2 + recBrPerf2).toFixed(2) != recSind2.toFixed(2)) {
                alert('Recurring Penalty < 2h: \nBasic + Rating + Performance must equal Sinderella\nSinderella: ' + recSind2.toFixed(2) + '\nSum of Breakdown: ' + (recBrBasic2 + recBrRate2 + recBrPerf2).toFixed(2));
                return;
            }*/

            // var addonRows = document.querySelectorAll('#addonsTable tbody tr');
            // for (var i = 0; i < addonRows.length; i++) {
            //         console.log("Checking add-on row", i);
            //         var priceInput = addonRows[i].querySelector('input[name="addon_price[]"]');
            //         var platformInput = addonRows[i].querySelector('input[name="addon_platform[]"]');
            //         var sindInput = addonRows[i].querySelector('input[name="addon_sind[]"]');
            //         if (priceInput && platformInput && sindInput) {
            //             var addonPrice = parseFloat(priceInput.value) || 0;
            //             var addonPlatform = parseFloat(platformInput.value) || 0;
            //             var addonSind = parseFloat(sindInput.value) || 0;
            //             if ((addonPlatform + addonSind).toFixed(2) != addonPrice.toFixed(2)) {
            //                 alert('For Add-on ' + (i + 1) + ' (Ad-Hoc): \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + addonPrice.toFixed(2) + '\nSum of Platform + Sinderella: ' + (addonPlatform + addonSind).toFixed(2));
            //                 return;
            //             }
            //         }

            //         var priceRecInput = addonRows[i].querySelector('input[name="addon_price_recurring[]"]');
            //         var platformRecInput = addonRows[i].querySelector('input[name="addon_platform_recurring[]"]');
            //         var sindRecInput = addonRows[i].querySelector('input[name="addon_sind_recurring[]"]');
            //         if (priceRecInput && platformRecInput && sindRecInput) {
            //             var addonPriceRec = parseFloat(priceRecInput.value) || 0;
            //             var addonPlatformRec = parseFloat(platformRecInput.value) || 0;
            //             var addonSindRec = parseFloat(sindRecInput.value) || 0;
            //             if ((addonPlatformRec + addonSindRec).toFixed(2) != addonPriceRec.toFixed(2)) {
            //                 alert('For Add-on ' + (i + 1) + ' (Recurring): \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + addonPriceRec.toFixed(2) + '\nSum of Platform + Sinderella: ' + (addonPlatformRec + addonSindRec).toFixed(2));
            //                 return;
            //             }
            //         }

            //         var price24Input = addonRows[i].querySelector('input[name="addon_price_resched24[]"]');
            //         var platform24Input = addonRows[i].querySelector('input[name="addon_platform_resched24[]"]');
            //         var sind24Input = addonRows[i].querySelector('input[name="addon_sind_resched24[]"]');
            //         if (price24Input && platform24Input && sind24Input) {
            //             var addonPrice24 = parseFloat(price24Input.value) || 0;
            //             var addonPlatform24 = parseFloat(platform24Input.value) || 0;
            //             var addonSind24 = parseFloat(sind24Input.value) || 0;
            //             if ((addonPlatform24 + addonSind24).toFixed(2) != addonPrice24.toFixed(2)) {
            //                 alert('For Add-on ' + (i + 1) + ' (Penalty < 24h): \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + addonPrice24.toFixed(2) + '\nSum of Platform + Sinderella: ' + (addonPlatform24 + addonSind24).toFixed(2));
            //                 return;
            //             }
            //         }

            //         var price2Input = addonRows[i].querySelector('input[name="addon_price_resched2[]"]');
            //         var platform2Input = addonRows[i].querySelector('input[name="addon_platform_resched2[]"]');
            //         var sind2Input = addonRows[i].querySelector('input[name="addon_sind_resched2[]"]');
            //         if (price2Input && platform2Input && sind2Input) {
            //             var addonPrice2 = parseFloat(price2Input.value) || 0;
            //             var addonPlatform2 = parseFloat(platform2Input.value) || 0;
            //             var addonSind2 = parseFloat(sind2Input.value) || 0;
            //             if ((addonPlatform2 + addonSind2).toFixed(2) != addonPrice2.toFixed(2)) {
            //                 alert('For Add-on ' + (i + 1) + ' (Penalty < 2h): \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + addonPrice2.toFixed(2) + '\nSum of Platform + Sinderella: ' + (addonPlatform2 + addonSind2).toFixed(2));
            //                 return;
            //             }
            //         }
            //     }

                var addonCount = document.querySelectorAll('#addonsTable thead tr th').length - 1; // minus the first empty header
                for (var col = 0; col < addonCount; col++) {
                    // Get all rows for this add-on column (skip the first column which is the label)
                    var price = parseFloat(document.querySelectorAll('input[name="addon_price[]"]')[col].value) || 0;
                    var platform = parseFloat(document.querySelectorAll('input[name="addon_platform[]"]')[col].value) || 0;
                    var sind = parseFloat(document.querySelectorAll('input[name="addon_sind[]"]')[col].value) || 0;
                    if ((platform + sind).toFixed(2) != price.toFixed(2)) {
                        alert('For Add-on ' + (col + 1) + ' - Ad-Hoc: \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + price.toFixed(2) + '\nSum of Platform + Sinderella: ' + (platform + sind).toFixed(2));
                        return;
                    }
                    // Repeat for recurring, penalty <24h, penalty <2h
                    var price24 = parseFloat(document.querySelectorAll('input[name="addon_price_resched24[]"]')[col].value) || 0;
                    var platform24 = parseFloat(document.querySelectorAll('input[name="addon_platform_resched24[]"]')[col].value) || 0;
                    var sind24 = parseFloat(document.querySelectorAll('input[name="addon_sind_resched24[]"]')[col].value) || 0;
                    if ((platform24 + sind24).toFixed(2) != price24.toFixed(2)) {
                        alert('For Add-on ' + (col + 1) + ' (Penalty < 24h) - Ad-Hoc: \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + price24.toFixed(2) + '\nSum of Platform + Sinderella: ' + (platform24 + sind24).toFixed(2));
                        return;
                    }
                    var price2 = parseFloat(document.querySelectorAll('input[name="addon_price_resched2[]"]')[col].value) || 0;
                    var platform2 = parseFloat(document.querySelectorAll('input[name="addon_platform_resched2[]"]')[col].value) || 0;
                    var sind2 = parseFloat(document.querySelectorAll('input[name="addon_sind_resched2[]"]')[col].value) || 0;
                    if ((platform2 + sind2).toFixed(2) != price2.toFixed(2)) {
                        alert('For Add-on ' + (col + 1) + ' (Penalty < 2h) - Ad-Hoc: \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + price2.toFixed(2) + '\nSum of Platform + Sinderella: ' + (platform2 + sind2).toFixed(2));
                        return;
                    }
                    var priceRec = parseFloat(document.querySelectorAll('input[name="addon_price_recurring[]"]')[col].value) || 0;
                    var platformRec = parseFloat(document.querySelectorAll('input[name="addon_platform_recurring[]"]')[col].value) || 0;
                    var sindRec = parseFloat(document.querySelectorAll('input[name="addon_sind_recurring[]"]')[col].value) || 0;
                    if ((platformRec + sindRec).toFixed(2) != priceRec.toFixed(2)) {
                        alert('For Add-on ' + (col + 1) + ' - Recurring: \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + priceRec.toFixed(2) + '\nSum of Platform + Sinderella: ' + (platformRec + sindRec).toFixed(2));
                        return;
                    }
                    var price24_re = parseFloat(document.querySelectorAll('input[name="addon_price_resched24_re[]"]')[col].value) || 0;
                    var platform24_re = parseFloat(document.querySelectorAll('input[name="addon_platform_resched24_re[]"]')[col].value) || 0;
                    var sind24_re = parseFloat(document.querySelectorAll('input[name="addon_sind_resched24_re[]"]')[col].value) || 0;
                    if ((platform24_re + sind24_re).toFixed(2) != price24_re.toFixed(2)) {
                        alert('For Add-on ' + (col + 1) + ' (Penalty < 24h) - Recurring: \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + price24_re.toFixed(2) + '\nSum of Platform + Sinderella: ' + (platform24_re + sind24_re).toFixed(2));
                        return;
                    }
                    var price2_re = parseFloat(document.querySelectorAll('input[name="addon_price_resched2_re[]"]')[col].value) || 0;
                    var platform2_re = parseFloat(document.querySelectorAll('input[name="addon_platform_resched2_re[]"]')[col].value) || 0;
                    var sind2_re = parseFloat(document.querySelectorAll('input[name="addon_sind_resched2_re[]"]')[col].value) || 0;
                    if ((platform2_re + sind2_re).toFixed(2) != price2_re.toFixed(2)) {
                        alert('For Add-on ' + (col + 1) + ' (Penalty < 2h) - Recurring: \nAdd-on Price must equal Platform + Sinderella\nAdd-on Price: ' + price2_re.toFixed(2) + '\nSum of Platform + Sinderella: ' + (platform2_re + sind2_re).toFixed(2));
                        return;
                    }
                }

            console.log("Validation passed, would submit form now.");
            this.submit();
        });


        function addAddonRowVertical() {
            var table = document.getElementById('addonsTable');
            var thead = table.tHead;
            var tbody = table.tBodies[0];

            // 1. Add new <th> at the end of the first <tr> in <thead>
            var thRow = thead.rows[0];
            var addonCount = thRow.cells.length;
            var newTh = document.createElement('th');
            newTh.innerHTML = `<strong>Add-on ${addonCount}</strong>`;
            thRow.appendChild(newTh);

            // 2. For each row in tbody, add a new <td> at the end
            for (var i = 0; i < tbody.rows.length; i++) {
                var row = tbody.rows[i];
                // Section headers (colspan rows) - skip
                if (row.cells.length === 1) continue;

                // For the action row
                if (row.cells[0].innerText.trim() === "Action") {
                    var td = document.createElement('td');
                    td.innerHTML = `<button type="button" onclick="removeAddonColumn(this)">Remove</button>`;
                    row.appendChild(td);
                    continue;
                }

                // For the rest, add input fields
                var td = document.createElement('td');
                var label = row.cells[0].innerText.trim();
                if (label === "Description") {
                    td.innerHTML = `<input type="hidden" name="addon_id[]" value=""><input type="text" name="addon_desc[]" required>`;
                } else if (label === "Duration (Hours)") {
                    td.innerHTML = `<input type="number" step="0.01" name="addon_duration[]" required>`;
                } else if (label === "Price") {
                    var section = "";
                    for (var j = i-1; j >= 0; j--) {
                        if (tbody.rows[j].cells[0].colSpan > 1) {
                            section = tbody.rows[j].cells[0].innerText;
                            break;
                        }
                    }
                    if (section.includes("Ad-Hoc") && !section.includes("Penalty")) td.innerHTML = `<input type="number" step="0.01" name="addon_price[]" required>`;
                    else if (section.includes("Recurring") && !section.includes("Penalty")) td.innerHTML = `<input type="number" step="0.01" name="addon_price_recurring[]" >`;
                    else if (section.includes("48 hours") && section.includes("Ad-Hoc")) td.innerHTML = `<input type="number" step="0.01" name="addon_price_resched24[]" >`;
                    else if (section.includes("24 hours") && section.includes("Ad-Hoc")) td.innerHTML = `<input type="number" step="0.01" name="addon_price_resched2[]" >`;
                    else if (section.includes("48 hours") && section.includes("Recurring")) td.innerHTML = `<input type="number" step="0.01" name="addon_price_resched24_re[]" >`;
                    else if (section.includes("24 hours") && section.includes("Recurring")) td.innerHTML = `<input type="number" step="0.01" name="addon_price_resched2_re[]" >`;
                } else if (label === "Platform") {
                    var section = "";
                    for (var j = i-1; j >= 0; j--) {
                        if (tbody.rows[j].cells[0].colSpan > 1) {
                            section = tbody.rows[j].cells[0].innerText;
                            break;
                        }
                    }
                    if (section.includes("Ad-Hoc") && !section.includes("Penalty")) td.innerHTML = `<input type="number" step="0.01" name="addon_platform[]" required>`;
                    else if (section.includes("Recurring") && !section.includes("Penalty")) td.innerHTML = `<input type="number" step="0.01" name="addon_platform_recurring[]" >`;
                    else if (section.includes("48 hours") && section.includes("Ad-Hoc")) td.innerHTML = `<input type="number" step="0.01" name="addon_platform_resched24[]" >`;
                    else if (section.includes("24 hours") && section.includes("Ad-Hoc")) td.innerHTML = `<input type="number" step="0.01" name="addon_platform_resched2[]" >`;
                    else if (section.includes("48 hours") && section.includes("Recurring")) td.innerHTML = `<input type="number" step="0.01" name="addon_platform_resched24_re[]" >`;
                    else if (section.includes("24 hours") && section.includes("Recurring")) td.innerHTML = `<input type="number" step="0.01" name="addon_platform_resched2_re[]" >`;
                } else if (label === "Sinderella") {
                    var section = "";
                    for (var j = i-1; j >= 0; j--) {
                        if (tbody.rows[j].cells[0].colSpan > 1) {
                            section = tbody.rows[j].cells[0].innerText;
                            break;
                        }
                    }
                    if (section.includes("Ad-Hoc") && !section.includes("Penalty")) td.innerHTML = `<input type="number" step="0.01" name="addon_sind[]" required>`;
                    else if (section.includes("Recurring") && !section.includes("Penalty")) td.innerHTML = `<input type="number" step="0.01" name="addon_sind_recurring[]" >`;
                    else if (section.includes("48 hours") && section.includes("Ad-Hoc")) td.innerHTML = `<input type="number" step="0.01" name="addon_sind_resched24[]" >`;
                    else if (section.includes("24 hours") && section.includes("Ad-Hoc")) td.innerHTML = `<input type="number" step="0.01" name="addon_sind_resched2[]" >`;
                    else if (section.includes("48 hours") && section.includes("Recurring")) td.innerHTML = `<input type="number" step="0.01" name="addon_sind_resched24_re[]" >`;
                    else if (section.includes("24 hours") && section.includes("Recurring")) td.innerHTML = `<input type="number" step="0.01" name="addon_sind_resched2_re[]" >`;
                } else {
                    td.innerHTML = "";
                }
                row.appendChild(td);
            }
        }

        // Remove the add-on column for the clicked remove button
        function removeAddonColumn(btn) {
            var td = btn.parentNode;
            var colIndex = td.cellIndex;
            var table = document.getElementById('addonsTable');
            var thead = table.tHead;
            var rows = table.tBodies[0].rows;

            // Remove the <th> in the header row
            if (thead && thead.rows.length > 0 && thead.rows[0].cells.length > colIndex) {
                thead.rows[0].deleteCell(colIndex);
            }

            // Remove the <td> in each body row
            for (var i = 0; i < rows.length; i++) {
                if (rows[i].cells.length > colIndex) {
                    rows[i].deleteCell(colIndex);
                }
            }
        }
    </script>
</body>
</html>

<?php
// // $conn->close();
?>