<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    header("Location: view_bookings.php");
    exit();
}

$booking_id = $_GET['booking_id'];

// Database connection
require_once '../db_connect.php';

// Fetch booking details
// $stmt = $conn->prepare("SELECT b.booking_date, b.booking_from_time, b.booking_to_time, c.cust_name, b.full_address, s.sind_name, sp.service_name, sp.service_price, b.booking_status
//                         FROM bookings b
//                         JOIN customers c ON b.cust_id = c.cust_id
//                         JOIN sinderellas s ON b.sind_id = s.sind_id
//                         JOIN service_pricing sp ON b.service_id = sp.service_id
//                         WHERE b.booking_id = ?");
// Fetch booking details
$stmt = $conn->prepare("
    SELECT 
        b.booking_date, b.booking_from_time, b.booking_to_time, c.cust_id, c.cust_name, b.full_address, 
        s.sind_id, s.sind_name, sv.service_name, p.total_price, b.booking_status, b.bp_total, b.booking_type
    FROM bookings b
    JOIN customers c ON b.cust_id = c.cust_id
    JOIN sinderellas s ON b.sind_id = s.sind_id
    JOIN services sv ON b.service_id = sv.service_id
    LEFT JOIN pricings p ON b.service_id = p.service_id AND p.service_type = b.booking_type
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result(
    $booking_date, $booking_from_time, $booking_to_time, $cust_id, $cust_name, $full_address, 
    $sinderella_id, $sinderella_name, $service_name, $service_price, $booking_status, $bp_total, $booking_type
);
$stmt->fetch();
$stmt->close();

if (empty($full_address)) {
    $full_address = "N/A";
}

// Fetch add-ons
$addon_details = [];
$total_addon_price = 0;
$stmt = $conn->prepare("SELECT ao.ao_desc, 
    CASE 
        WHEN ? = 'a' THEN ao.ao_price 
        ELSE ao.ao_price_recurring 
    END AS ao_price
    FROM booking_addons ba
    JOIN addon ao ON ba.ao_id = ao.ao_id
    WHERE ba.booking_id = ?");
$stmt->bind_param("si", $booking_type, $booking_id);
$stmt->execute();
$stmt->bind_result($ao_desc, $ao_price);
while ($stmt->fetch()) {
    $addon_details[] = ['desc' => $ao_desc, 'price' => $ao_price];
    $total_addon_price += $ao_price;
}
$stmt->close();

// $conn->close();

function formatTime($time) {
    $date = new DateTime($time);
    return $date->format('h:i A');
}

function formatDate($date) {
    $date = new DateTime($date);
    return $date->format('Y-m-d (l)');
}

$checkin_photo_path = $checkin_time = $checkout_photo_path = $checkout_time = null;
$stmt = $conn->prepare("SELECT checkin_photo_path, checkin_time, checkout_photo_path, checkout_time FROM booking_checkinout WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($checkin_photo_path, $checkin_time, $checkout_photo_path, $checkout_time);
$stmt->fetch();
$stmt->close();

$rating = null;
$review = null;
if ($booking_status == 'rated') {
    $stmt = $conn->prepare("SELECT rate, comment FROM booking_ratings WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($rating, $review);
    $stmt->fetch();
    $stmt->close();
}

$cancellation_reason = '';
if ($booking_status == 'cancel') {
    $stmt = $conn->prepare("SELECT cancellation_reason FROM booking_cancellation WHERE booking_id = ? ORDER BY cancelled_at DESC LIMIT 1");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($cancellation_reason);
    $stmt->fetch();
    $stmt->close();
}

$rating = null;
$review = null;
if ($booking_status == 'rated') {
    $stmt = $conn->prepare("SELECT rate, comment FROM booking_ratings WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($rating, $review);
    $stmt->fetch();
    $stmt->close();
}

if ($booking_status == 'pending') {
    $total_price = $total_addon_price + $service_price;
} elseif ($booking_status == 'cancel') {
    if ($bp_total > 0) {
        $total_price = $bp_total;
    } else {
        $total_price = $total_addon_price + $service_price;
    }
}else {
    $total_price = $bp_total;
}

$cmt_ppl = null;
$cmt_hse = null;
// $cust_rating = null;
// $cust_review = null;
$cust_rating_exists = false;
// $stmt = $conn->prepare("SELECT rate, comment FROM cust_ratings WHERE booking_id = ?");
$stmt = $conn->prepare("SELECT cmt_ppl, cmt_hse FROM cust_ratings WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
// $stmt->bind_result($cust_rating, $cust_review);
$stmt->bind_result($cmt_ppl, $cmt_hse);
if ($stmt->fetch()) {
    $cust_rating_exists = true;
}
$stmt->close();

// $avg_cust_rating = null;
// $cust_review_count = 0;
// $stmt = $conn->prepare("SELECT ROUND(AVG(rate),1) as avg_rating, COUNT(*) as review_count FROM cust_ratings WHERE cust_id = (SELECT cust_id FROM bookings WHERE booking_id = ?)");
// $stmt->bind_param("i", $booking_id);
// $stmt->execute();
// $stmt->bind_result($avg_cust_rating, $cust_review_count);
// $stmt->fetch();
// $stmt->close();

$avg_sind_rating = null;
$sind_review_count = 0;
$stmt = $conn->prepare("SELECT ROUND(AVG(rate),1) as avg_rating, COUNT(*) as review_count FROM booking_ratings WHERE sind_id = (SELECT sind_id FROM bookings WHERE booking_id = ?)");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($avg_sind_rating, $sind_review_count);
$stmt->fetch();
$stmt->close();

// Fetch customer labels
$customer_labels = [];
$stmt = $conn->prepare("
    SELECT cl.clbl_name, cl.clbl_color_code
    FROM cust_id_label cil
    JOIN cust_label cl ON cil.clbl_id = cl.clbl_id
    WHERE cil.cust_id = ? AND cl.clbl_status = 'active'
");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($clbl_name, $clbl_color_code);
while ($stmt->fetch()) {
    $customer_labels[] = [
        'name' => $clbl_name,
        'color' => $clbl_color_code
    ];
}
$stmt->close();

// Fetch sinderella labels
$sinderella_labels = [];
$stmt = $conn->prepare("
    SELECT sl.slbl_name, sl.slbl_color_code
    FROM sind_id_label sil
    JOIN sind_label sl ON sil.slbl_id = sl.slbl_id
    WHERE sil.sind_id = ? AND sl.slbl_status = 'active'
");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($slbl_name, $slbl_color_code);
while ($stmt->fetch()) {
    $sinderella_labels[] = [
        'name' => $slbl_name,
        'color' => $slbl_color_code
    ];
}
$stmt->close();

// Fetch cust_address_id and cust_id for this booking
$cust_address_id = null;
$cust_id_for_address = null;
$stmt = $conn->prepare("SELECT cust_address_id, cust_id FROM bookings WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($cust_address_id, $cust_id_for_address);
$stmt->fetch();
$stmt->close();

// Fetch address details (house type, family members, pets)
$housetype = $fm_num = $pet = '';
if ($cust_id_for_address && $cust_address_id) {
    $stmt = $conn->prepare("SELECT cust_housetype, cust_fm_num, cust_pet FROM cust_addresses WHERE cust_id = ? AND cust_address_id = ?");
    $stmt->bind_param("ii", $cust_id_for_address, $cust_address_id);
    $stmt->execute();
    $stmt->bind_result($housetype, $fm_num, $pet);
    $stmt->fetch();
    $stmt->close();
}

// Fetch reject reason if status is rejected
$reject_reason = null;
if ($booking_status == 'rejected') {
    $stmt = $conn->prepare("SELECT reason FROM sind_rejected_hist WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($reject_reason);
    $stmt->fetch();
    $stmt->close();
}

// Fetch all bookings for this recurring set if booking_type is 'r'
$recurring_id = null;
$recurring_bookings = [];
if ($booking_type == 'r') {
    // Get recurring_id for this booking
    $stmt = $conn->prepare("SELECT recurring_id FROM booking_recurring WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($recurring_id);
    $stmt->fetch();
    $stmt->close();

    if ($recurring_id) {
        // Get all bookings with same recurring_id
        $stmt = $conn->prepare("
            SELECT b.booking_id, b.booking_date, b.booking_from_time, b.booking_to_time, s.sind_name, b.booking_status
            FROM booking_recurring br
            JOIN bookings b ON br.booking_id = b.booking_id
            JOIN sinderellas s ON b.sind_id = s.sind_id
            WHERE br.recurring_id = ?
            ORDER BY b.booking_date ASC, b.booking_from_time ASC
        ");
        $stmt->bind_param("i", $recurring_id);
        $stmt->execute();
        $stmt->bind_result($rec_bid, $rec_date, $rec_from, $rec_to, $rec_sind, $rec_status);
        while ($stmt->fetch()) {
            $recurring_bookings[] = [
                'booking_id' => $rec_bid,
                'booking_date' => $rec_date,
                'booking_from_time' => $rec_from,
                'booking_to_time' => $rec_to,
                'sind_name' => $rec_sind,
                'booking_status' => $rec_status
            ];
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .details-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .details-container h2 {
            margin-top: 0;
        }
        .details-container label {
            display: block;
            margin-top: 10px;
        }
        .details-container button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .details-container button:hover {
            background-color: #0056b3;
        }
        .details-container td {
            padding: 8px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="details-container" style="display: flex;">
                <div style="flex: 1;">
                <h2>Booking Details</h2>
                <table>
                    <tr>
                        <td><strong>Date</strong></td>
                        <td>: <?php echo htmlspecialchars(formatDate($booking_date)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Time</strong></td>
                        <td>: <?php echo htmlspecialchars(formatTime($booking_from_time)) . ' - ' . htmlspecialchars(formatTime($booking_to_time)); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Address</strong></td>
                        <td>: <?php echo htmlspecialchars($full_address); ?></td>                    
                    </tr>

                    <tr>
                        <td><strong>House Type</strong></td>
                        <td>: <?php echo htmlspecialchars($housetype); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Family Member(s)</strong></td>
                        <td>: <?php echo htmlspecialchars($fm_num); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Pet(s)</strong></td>
                        <td>: <?php echo htmlspecialchars($pet); ?></td>
                    </tr>                    
                    
                        <!-- <tr>
                        <td><strong>Customer</strong></td>
                        <td>: <?php echo htmlspecialchars($cust_name); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Sinderella</strong></td>
                        <td>: <?php echo htmlspecialchars($sinderella_name); ?></td>
                    </tr> -->
                    
                    <tr>
                        <td><strong>Customer</strong></td>
                        <td>:
                            <?php echo htmlspecialchars($cust_name); ?>
                            <span class="cust-rating" style="margin-left:8px;color:#F09E0B;font-size:16px;cursor:pointer;z-index:9999;position:relative;" data-cust-id="<?php echo $cust_id; ?>">
                                View Comment
                            </span>
                            <?php if (!empty($customer_labels)): ?>
                                <?php foreach ($customer_labels as $label): ?>
                                    <span class="label-badge" style="background-color: <?php echo htmlspecialchars($label['color']); ?>; color: #fff; margin-left: 5px; padding: 5px 5px; font-size: 13px;">
                                        <?php echo htmlspecialchars($label['name']); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- <?php if ($avg_cust_rating !== null && $cust_review_count > 0): ?>
                                <span class="cust-rating" style="margin-left:8px;color:#F09E0B;font-size:16px;cursor:pointer;" data-cust-id="<?php echo $cust_id; ?>">
                                    &#11088;<?php echo $avg_cust_rating; ?> (<?php echo $cust_review_count; ?> review<?php echo $cust_review_count > 1 ? 's' : ''; ?>)
                                </span>
                            <?php else: ?>
                                <span style="margin-left:8px;color:#888;font-size:14px;">No ratings</span>
                            <?php endif; ?> -->
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Sinderella</strong></td>
                        <td>:
                            <?php echo htmlspecialchars($sinderella_name); ?>
                            <?php if ($avg_sind_rating !== null && $sind_review_count > 0): ?>
                                <span class="sind-rating" style="margin-left:8px;color:#F09E0B;font-size:16px;cursor:pointer;" data-sind-id="<?php echo $sinderella_id; ?>">
                                    &#11088;<?php echo $avg_sind_rating; ?> (<?php echo $sind_review_count; ?> review<?php echo $sind_review_count > 1 ? 's' : ''; ?>)
                                </span>
                            <?php else: ?>
                                <span style="margin-left:8px;color:#888;font-size:14px;">No ratings</span>
                            <?php endif; ?>
                            <?php if (!empty($sinderella_labels)): ?>
                                <?php foreach ($sinderella_labels as $label): ?>
                                    <span class="label-badge" style="background-color: <?php echo htmlspecialchars($label['color']); ?>; color: #fff; margin-left: 5px; padding: 5px 5px; font-size: 13px;">
                                        <?php echo htmlspecialchars($label['name']); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Service</strong></td>
                        <td>: <?php echo htmlspecialchars($service_name); ?> 
                        <!-- (RM <?php echo number_format($service_price, 2); ?>) -->
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Booking Type</strong></td>
                        <td>: 
                            <?php 
                            if ($booking_type == 'a') {
                                echo 'Ad-Hoc';
                            } elseif ($booking_type == 'r') {
                                echo 'Recurring';
                            } else {
                                echo 'Unknown';
                            }
                            ?>
                        </td>
                    <tr>
                        <td><strong>Add-ons</strong></td>
                        <td>
                            <ul>
                            <?php if (empty($addon_details)): ?>
                                <li>N/A</li>
                            <?php else: ?>
                                <?php foreach ($addon_details as $addon): ?>
                                    <li><?php echo htmlspecialchars($addon['desc']); ?> 
                                    <!-- (RM <?php echo number_format($addon['price'], 2); ?>) -->
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td>: RM <?php echo number_format($total_price, 2); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td>: <?php echo htmlspecialchars(ucfirst($booking_status)); ?></td>
                    </tr>

                    <?php if ($booking_status == 'cancel'): ?>
                    <tr>
                        <td><strong>Cancellation<br>Reason</strong></td>
                        <td>: <?php echo htmlspecialchars($cancellation_reason); ?></td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($booking_status == 'rejected'): ?>
                    <tr>
                        <td><strong>Reject Reason</strong></td>
                        <td>: <?php echo htmlspecialchars($reject_reason); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <?php if ($booking_status == 'rated'): ?>
                    <br><h3>Rating by Customer</h3>
                    <table>
                    <tr>
                        <td><strong>Rating</strong></td>
                        <td>: 
                            <?php
                            if ($rating !== null) {
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '⭐' : '☆';
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Review</strong></td>
                        <td>: <?php echo $review ? htmlspecialchars($review) : '<em>No review provided.</em>'; ?></td>
                    </tr>
                    </table>
                <?php endif; ?>

                <?php if ($cust_rating_exists): ?>
                    <br><h3>Comments by Sinderella</h3>
                    <table>
                        <tr>
                            <td><strong>Comment to Cust</strong></td>
                            <td>: <?php echo $cmt_ppl ? htmlspecialchars($cmt_ppl) : '<em>No comment provided.</em>'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Comment to House</strong></td>
                            <td>: <?php echo $cmt_hse ? htmlspecialchars($cmt_hse) : '<em>No comment provided.</em>'; ?></td>
                        </tr>
                    </table>
                    <!-- <table>
                        <tr>
                            <td><strong>Rating</strong></td>
                            <td>: 
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $cust_rating ? '⭐' : '☆';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Review</strong></td>
                            <td>: <?php echo $cust_review ? htmlspecialchars($cust_review) : '<em>No review provided.</em>'; ?></td>
                        </tr>
                    </table> -->
                <?php endif; ?>

                <?php if ($booking_status == 'done'): ?>
                    <button type="button" onclick="openRatingModal()">Rate & Review Sinderella</button><br>
                <?php endif; ?>

                <?php if (($booking_status == 'done' || $booking_status == 'rated') && !$cust_rating_exists): ?>
                    <button type="button" id="rateCustomerBtn">Comment Customer</button><br>
                <?php endif; ?>

                <?php if (in_array($booking_status, ['pending', 'confirm', 'rejected', 'paid'])): ?>
                    <button type="button" onclick="editBooking()">Edit Booking</button>
                <?php endif; ?>
                <!-- <button type="button" onclick="window.location.href='view_bookings.php?search_status=<?php echo urlencode($booking_status); ?>'">Back</button> -->
                <button type="button" onclick="window.location.href='view_bookings.php'">Back</button>
                </div>

                <?php if ($booking_status == 'done' || $booking_status == 'rated'): ?>
                <div style="flex: 1; text-align: center;">
                    <h3>Check In / Check Out</h3>
                    <div style="margin-bottom: 20px;">
                        <strong>Check In Time:</strong><br>
                        <?php if ($checkin_time): ?>
                            <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($checkin_time))); ?><br>
                            <?php if ($checkin_photo_path && file_exists($checkin_photo_path)): ?>
                                <img src="<?php echo htmlspecialchars($checkin_photo_path); ?>" alt="Check In Photo" style="max-width: 200px; margin-top: 10px;">
                            <?php else: ?>
                                <span>No photo</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span>Not checked in yet</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong>Check Out Time:</strong><br>
                        <?php if ($checkout_time): ?>
                            <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($checkout_time))); ?><br>
                            <?php if ($checkout_photo_path && file_exists($checkout_photo_path)): ?>
                                <img src="<?php echo htmlspecialchars($checkout_photo_path); ?>" alt="Check Out Photo" style="max-width: 200px; margin-top: 10px;">
                            <?php else: ?>
                                <span>No photo</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span>Not checked out yet</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div id="custRatingPopup" style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; box-shadow:0 2px 10px #0002; z-index:9999; padding:20px; max-width:90vw; max-height:80vh; overflow:auto;">
                    <h3 style="text-align:center;">Customer Ratings</h3>
                    <!-- <table id="custRatingsTable" border="1" cellpadding="5" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th>Rated By</th>
                                <th>Rate</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            // Ratings will be loaded here
                        </tbody>
                    </table>
                    <div style="text-align:center;">
                        <button onclick="closeCustRatingsPopup()">Close</button>
                    </div> -->
                    <div id="custRatingPopupContent"></div>
                    <div style="text-align:center;">
                        <button onclick="document.getElementById('custRatingPopup').style.display='none'">Close</button>
                    </div>
                </div>

                <div id="sindRatingPopup" style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; box-shadow:0 2px 10px #0002; z-index:9999; padding:20px; max-width:90vw; max-height:80vh; overflow:auto;">
                    <h3 style="text-align:center;">Sinderella Ratings</h3>
                    <table id="sindRatingsTable" border="1" cellpadding="5" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th>Rated By</th>
                                <th>Rate</th>
                                <th>Comment</th>
                                <th>Public</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Ratings will be loaded here -->
                        </tbody>
                    </table>
                    <div style="text-align:center;">
                        <button onclick="closeSindRatingsPopup()">Close</button>
                    </div>
                </div>

                <div id="ratingModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
                    <div style="background:#fff; padding:30px; border-radius:8px; text-align:center; min-width:300px;">
                        <h3>Rate & Review Sinderella</h3>
                        <div id="starContainer" style="font-size:2em; margin-bottom:10px;">
                            <span class="star" data-value="1">&#9734;</span>
                            <span class="star" data-value="2">&#9734;</span>
                            <span class="star" data-value="3">&#9734;</span>
                            <span class="star" data-value="4">&#9734;</span>
                            <span class="star" data-value="5">&#9734;</span>
                        </div>
                        <textarea id="ratingComment" rows="4" style="width:90%;" placeholder="Leave a comment (optional)"></textarea>
                        <br>
                        <button onclick="submitRating()">Submit</button>
                        <button onclick="closeRatingModal()">Cancel</button>
                    </div>
                </div>

                <div id="custRatingModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
                    <div style="background:#fff; padding:30px; border-radius:8px; text-align:center; min-width:300px;">
                        <h3>Comment to Customer</h3>
                        <textarea id="custRatingCommentPpl" rows="3" style="width:90%;" placeholder="Comment to Customer (optional)"></textarea>
                        <br>
                        <h3>Comment to House</h3>
                        <textarea id="custRatingCommentHse" rows="3" style="width:90%;" placeholder="Comment to House (optional)"></textarea>
                        <br>
                        <button onclick="submitCustRating()">Submit</button>
                        <button onclick="closeCustRatingModal()">Cancel</button>
                    </div>
                </div>
            </div>

            <div class="details-container" style="box-shadow:none; padding-top: 1px;">
                <?php if ($booking_type == 'r' && $recurring_id && count($recurring_bookings) > 0): ?>
                    <hr>
                    <h3>All Bookings in This Recurring Set</h3>
                    <table border="1" cellpadding="8" style="border-collapse:collapse; text-align:center; width:100%; margin-top:10px;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Sinderella</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recurring_bookings as $rb): ?>
                            <tr style="cursor:pointer;<?php if ($rb['booking_id'] == $booking_id) echo 'background:#e0eaff;'; ?>"
                                onclick="window.location.href='view_booking_details.php?booking_id=<?php echo $rb['booking_id']; ?>'">
                                <td><?php echo htmlspecialchars(formatDate($rb['booking_date'])); ?></td>
                                <td><?php echo htmlspecialchars(formatTime($rb['booking_from_time'])) . ' - ' . htmlspecialchars(formatTime($rb['booking_to_time'])); ?></td>
                                <td><?php echo htmlspecialchars($rb['sind_name']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($rb['booking_status'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // function showCustRatingsPopup(cust_id) {
    //     $('#custRatingPopup').show();
    //     $('#custRatingsTable tbody').html('<tr><td colspan="3">Loading...</td></tr>');
    //     $.get('get_customer_ratings.php', { cust_id: cust_id }, function(data) {
    //         $('#custRatingsTable tbody').html(data);
    //     });
    // }
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('cust-rating')) {
            const custId = e.target.getAttribute('data-cust-id');
            const popup = document.getElementById('custRatingPopup');
            const content = document.getElementById('custRatingPopupContent');
            content.innerHTML = 'Loading...';
            popup.style.display = '';

            fetch(`get_customer_ratings.php?cust_id=${custId}`)
                .then(resp => resp.text())
                .then(html => {
                    content.innerHTML = html;
                })
                .catch(() => {
                    content.innerHTML = '<div style="color:red;">Failed to load comments.</div>';
                });
        }
    });
    function closeCustRatingsPopup() {
        $('#custRatingPopup').hide();
    }

    function showSindRatingsPopup(sind_id) {
        $('#sindRatingPopup').show();
        $('#sindRatingsTable tbody').html('<tr><td colspan="4">Loading...</td></tr>');
        $.get('get_sinderella_ratings.php', { sind_id: sind_id }, function(data) {
            $('#sindRatingsTable tbody').html(data);
        });
    }
    function closeSindRatingsPopup() {
        $('#sindRatingPopup').hide();
    }

    // Attach click handlers
    // $(document).on('click', '.cust-rating', function() {
    //     showCustRatingsPopup($(this).data('cust-id'));
    // });
    $(document).on('click', '.sind-rating', function() {
        showSindRatingsPopup($(this).data('sind-id'));
    });

    // Handle public checkbox change (delegated event)
    $(document).on('change', '.public-checkbox', function() {
        var rating_id = $(this).data('rating-id');
        var is_public = $(this).is(':checked') ? 1 : 0;
        $.post('update_rating_public.php', { rating_id: rating_id, public: is_public }, function(resp) {
            // Optionally show a toast or alert
        });
    });

    function editBooking() {
        if (confirm('Are you sure you want to edit this booking?')) {
            // Create a form and submit all booking details via POST
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'edit_booking.php';

            // Add booking_id
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'booking_id';
            input.value = '<?php echo $booking_id; ?>';
            form.appendChild(input);

            // Add cust_address_id
            var addrInput = document.createElement('input');
            addrInput.type = 'hidden';
            addrInput.name = 'cust_address_id';
            addrInput.value = '<?php echo $cust_address_id; ?>';
            form.appendChild(addrInput);

            // Add editable fields
            var fields = {
                booking_date: '<?php echo htmlspecialchars($booking_date, ENT_QUOTES); ?>',
                booking_from_time: '<?php echo htmlspecialchars($booking_from_time, ENT_QUOTES); ?>',
                booking_to_time: '<?php echo htmlspecialchars($booking_to_time, ENT_QUOTES); ?>',
                full_address: '<?php echo htmlspecialchars($full_address, ENT_QUOTES); ?>',
                sind_id: '<?php echo htmlspecialchars($sinderella_id, ENT_QUOTES); ?>'
            };
            for (var key in fields) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = key;
                inp.value = fields[key];
                form.appendChild(inp);
            }

            document.body.appendChild(form);
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const rateBtn = document.getElementById('rateCustomerBtn');
        if (rateBtn) {
            rateBtn.addEventListener('click', openCustRatingModal);
        }
    });

    function openCustRatingModal() {
        document.getElementById('custRatingModal').style.display = 'flex';
        document.getElementById('custRatingCommentPpl').value = '';
        document.getElementById('custRatingCommentHse').value = '';
    }

    function closeCustRatingModal() {
        document.getElementById('custRatingModal').style.display = 'none';
    }

    function submitCustRating() {
        const commentPpl = document.getElementById('custRatingCommentPpl').value.trim();
        const commentHse = document.getElementById('custRatingCommentHse').value.trim();
        if (!commentPpl && !commentHse) {
            alert('Please enter at least one comment.');
            return;
        }

        const bookingId = <?php echo json_encode($booking_id); ?>;
        const sindId = <?php echo json_encode($sinderella_id); ?>;
        const custId = <?php echo json_encode($cust_id); ?>;

        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('sind_id', sindId);
        formData.append('cust_id', custId);
        formData.append('cmt_ppl', commentPpl);
        formData.append('cmt_hse', commentHse);

        fetch('../rs/submit_cust_rating.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeCustRatingModal();
                location.reload();
            }
        });
    }

    let selectedRating = 0;

    function openRatingModal() {
        document.getElementById('ratingModal').style.display = 'flex';
        highlightStars(0);
        document.getElementById('ratingComment').value = '';
    }

    function closeRatingModal() {
        document.getElementById('ratingModal').style.display = 'none';
    }

    function highlightStars(rating) {
        const stars = document.querySelectorAll('#starContainer .star');
        stars.forEach(star => {
            star.innerHTML = (parseInt(star.dataset.value) <= rating) ? '★' : '☆';
        });
        selectedRating = rating;
    }

    document.querySelectorAll('#starContainer .star').forEach(star => {
        star.addEventListener('mouseover', function() {
            highlightStars(parseInt(this.dataset.value));
        });
        star.addEventListener('click', function() {
            highlightStars(parseInt(this.dataset.value));
        });
    });

    document.getElementById('starContainer').addEventListener('mouseleave', function() {
        highlightStars(selectedRating);
    });

    function submitRating() {
        if (selectedRating < 1 || selectedRating > 5) {
            alert('Please select a rating.');
            return;
        }
        const comment = document.getElementById('ratingComment').value;
        const bookingId = <?php echo json_encode($booking_id); ?>;
        const sindId = <?php echo json_encode($sinderella_id); ?>;
        const custId = <?php echo json_encode($cust_id); ?>;

        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('sind_id', sindId);
        formData.append('cust_id', custId);
        formData.append('rate', selectedRating);
        formData.append('comment', comment);

        fetch('../rc/submit_rating.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                closeRatingModal();
                location.reload();
            }
        });
    }
    </script>
</body>
</html>