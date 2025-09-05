<?php
session_start();
if (!isset($_SESSION['adm_id'])) { header("Location: ../login_adm.php"); exit(); }
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    // Fetch booking details if not already posted
    if (isset($_POST['booking_date'])) {
        // Coming from view_booking_details.php (first load)
        $booking_date = $_POST['booking_date'];
        $booking_from_time = $_POST['booking_from_time'];
        $booking_to_time = $_POST['booking_to_time'];
        $full_address = $_POST['full_address'];
        $sind_id = $_POST['sind_id'];
    } else {
        // Coming from this form (save changes), fetch from DB
        $stmt = $conn->prepare("SELECT booking_date, booking_from_time, booking_to_time, full_address, sind_id FROM bookings WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->bind_result($booking_date, $booking_from_time, $booking_to_time, $full_address, $sind_id);
        $stmt->fetch();
        $stmt->close();
    }

    // Fetch booking details for display
    $stmt = $conn->prepare("
        SELECT 
            b.booking_status, 
            b.service_id, 
            b.cust_id,
            b.booking_type, 
            s.service_name, 
            c.cust_name, 
            p.total_price,
            b.bp_total
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN customers c ON b.cust_id = c.cust_id
        LEFT JOIN pricings p ON b.service_id = p.service_id AND p.service_type = b.booking_type
        WHERE b.booking_id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($booking_status, $service_id, $cust_id, $booking_type, $service_name, $cust_name, $service_price, $bp_total);
    $stmt->fetch();
    $stmt->close();

    // Fetch add-on details
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

    // Fetch service duration
    $stmt = $conn->prepare("SELECT s.service_duration FROM bookings b JOIN services s ON b.service_id = s.service_id WHERE b.booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($service_duration);
    $stmt->fetch();
    $stmt->close();

    // Fetch add-on durations
    $total_addon_duration = 0;
    $stmt = $conn->prepare("SELECT ao.ao_duration FROM booking_addons ba JOIN addon ao ON ba.ao_id = ao.ao_id WHERE ba.booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($ao_duration);
    while ($stmt->fetch()) {
        $total_addon_duration += $ao_duration;
    }
    $stmt->close();

    $total_duration = $service_duration + $total_addon_duration;

    // If saving changes
    if (isset($_POST['save_changes'])) {
        $new_date = $_POST['new_booking_date'];
        $new_from_time = $_POST['new_booking_from_time'];
        $new_to_time = $_POST['new_booking_to_time'];
        $new_address = $_POST['new_full_address'];
        $new_sind_id = $_POST['new_sind_id'];

        if (strtolower($booking_status) === 'rejected') {
            $stmt = $conn->prepare("UPDATE bookings SET booking_date=?, booking_from_time=?, booking_to_time=?, full_address=?, sind_id=?, booking_status='paid' WHERE booking_id=?");
            $stmt->bind_param("ssssii", $new_date, $new_from_time, $new_to_time, $new_address, $new_sind_id, $booking_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE bookings SET booking_date=?, booking_from_time=?, booking_to_time=?, full_address=?, sind_id=? WHERE booking_id=?");
            $stmt->bind_param("ssssii", $new_date, $new_from_time, $new_to_time, $new_address, $new_sind_id, $booking_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: view_booking_details.php?booking_id=$booking_id");
        exit();
    }

    // Fetch all sinderellas for dropdown
    $sind_list = [];
    $res = $conn->query("SELECT sind_id, sind_name FROM sinderellas WHERE sind_status='active' AND acc_approved='approve'");
    while ($row = $res->fetch_assoc()) $sind_list[] = $row;

    // ========= PAYMENT RECEIVED HANDLER ====================================
    if (isset($_POST['mark_paid'])) {
        // 0) Transaction
        $conn->begin_transaction();

        // 1) Lock & read booking essentials
        $stmt = $conn->prepare("
            SELECT b.booking_status, b.booking_type, b.service_id, b.sind_id
            FROM bookings b
            WHERE b.booking_id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->bind_result($b_status_now, $b_type, $b_service_id, $maid_sind_id);
        if (!$stmt->fetch()) { $stmt->close(); $conn->rollback(); die('Booking not found'); }
        $stmt->close();

        // Already paid? exit cleanly
        if (strtolower((string)$b_status_now) === 'paid') {
            $conn->commit();
            header("Location: view_booking_details.php?booking_id=" . urlencode($booking_id));
            exit();
        }

        // 2) Base pricing from pricings (by service_id + booking_type)
        $base_total = $base_platform = $base_sind = 0.0;
        $p_lvl1 = $p_lvl2 = 0.0;

        $stmt = $conn->prepare("
            SELECT total_price, platform, sinderella, lvl1, lvl2
            FROM pricings
            WHERE service_id = ? AND service_type = ?
            LIMIT 1
        ");
        $stmt->bind_param("is", $b_service_id, $b_type);
        $stmt->execute();
        $stmt->bind_result($p_total, $p_platform, $p_sind, $p_lvl1_raw, $p_lvl2_raw);
        if ($stmt->fetch()) {
            $base_total    = (float)$p_total;
            $base_platform = (float)$p_platform;
            $base_sind     = (float)$p_sind;
            $p_lvl1        = (float)$p_lvl1_raw;  // pricing lvl1 amount
            $p_lvl2        = (float)$p_lvl2_raw;  // pricing lvl2 amount
        }
        $stmt->close();

        // 3) Sum add-ons (type-aware)
        $add_total = $add_platform = $add_sind = 0.0;

        $stmt = $conn->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN ?='a' THEN ao.ao_price             ELSE ao.ao_price_recurring           END), 0) AS add_total,
                COALESCE(SUM(CASE WHEN ?='a' THEN ao.ao_platform         ELSE ao.ao_platform_recurring       END), 0) AS add_platform,
                COALESCE(SUM(CASE WHEN ?='a' THEN ao.ao_sind             ELSE ao.ao_sind_recurring           END), 0) AS add_sind
            FROM booking_addons ba
            JOIN addon ao ON ba.ao_id = ao.ao_id
            WHERE ba.booking_id = ?
        ");
        $stmt->bind_param("sssi", $b_type, $b_type, $b_type, $booking_id);
        $stmt->execute();
        $stmt->bind_result($s_add_total, $s_add_platform, $s_add_sind);
        if ($stmt->fetch()) {
            $add_total    = (float)$s_add_total;
            $add_platform = (float)$s_add_platform;
            $add_sind     = (float)$s_add_sind;
        }
        $stmt->close();

        // 4) Initial breakdown
        $bp_total    = $base_total    + $add_total;
        $bp_platform = $base_platform + $add_platform;
        $bp_sind     = $base_sind     + $add_sind;

        // 5) Resolve uplines from sinderellas (A = immediate, B = upline-of-A)
        //    lvl1 = A, lvl2 = B
        $lvl1_sind_id = null;
        $lvl2_sind_id = null;

        // A: immediate upline of maid
        $stmt = $conn->prepare("SELECT sind_upline_id FROM sinderellas WHERE sind_id = ? LIMIT 1");
        $stmt->bind_param("i", $maid_sind_id);
        $stmt->execute();
        $stmt->bind_result($A);
        if ($stmt->fetch()) {
            if (!is_null($A) && (int)$A > 0) { $lvl1_sind_id = (int)$A; }
        }
        $stmt->close();

        // B: upline of A (if A exists)
        if ($lvl1_sind_id !== null) {
            $stmt = $conn->prepare("SELECT sind_upline_id FROM sinderellas WHERE sind_id = ? LIMIT 1");
            $stmt->bind_param("i", $lvl1_sind_id);
            $stmt->execute();
            $stmt->bind_result($B);
            if ($stmt->fetch()) {
                if (!is_null($B) && (int)$B > 0) { $lvl2_sind_id = (int)$B; }
            }
            $stmt->close();
        }

        // 6) Level amounts + platform top-up when missing
        $bp_lvl1_amount = ($lvl1_sind_id === null) ? null : $p_lvl1;
        $bp_lvl2_amount = ($lvl2_sind_id === null) ? null : $p_lvl2;

        if ($lvl1_sind_id === null) { $bp_platform += $p_lvl1; }
        if ($lvl2_sind_id === null) { $bp_platform += $p_lvl2; }

        // 7) Persist
        $stmt = $conn->prepare("
            UPDATE bookings
            SET booking_status   = 'paid',
                bp_total         = ?,
                bp_platform      = ?,
                bp_sind          = ?,
                bp_lvl1_sind_id  = ?,
                bp_lvl1_amount   = ?,
                bp_lvl2_sind_id  = ?,
                bp_lvl2_amount   = ?
            WHERE booking_id = ?
        ");
        // NULL is allowed for *_sind_id and *_amount
        $stmt->bind_param(
            "dddididi",
            $bp_total,
            $bp_platform,
            $bp_sind,
            $lvl1_sind_id,
            $bp_lvl1_amount,
            $lvl2_sind_id,
            $bp_lvl2_amount,
            $booking_id
        );
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $conn->commit();
        } else {
            $conn->rollback();
            die('Failed to mark payment.');
        }

        header("Location: view_booking_details.php?booking_id=" . urlencode($booking_id));
        exit();
    }
    // ======== END PAYMENT RECEIVED HANDLER =================================

    if (isset($_POST['mark_done'])) {
        // 1. Mark booking as done
        $stmt = $conn->prepare("UPDATE bookings SET booking_status='done' WHERE booking_id=?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        // 2. Generate token for rating
        $token = bin2hex(random_bytes(16));
        $expires_at = date('Y-m-d H:i:s', time() + 86400); // 24 hours from now

        // Remove old tokens for this booking
        $conn->query("DELETE FROM booking_rating_links WHERE booking_id = $booking_id");

        // Store new token
        $stmt = $conn->prepare("INSERT INTO booking_rating_links (booking_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $booking_id, $token, $expires_at);
        $stmt->execute();
        $stmt->close();

        // 3. Build the link
        $rate_link = "http://sinderellauat.free.nf/rc/rate_booking.php?token=$token";

        // 4. Show alert with the link and redirect
        echo "<script>
            alert('Booking marked as done!\\n\\nLink for customer to rate:\\n$rate_link');
            window.location.href = 'view_booking_details.php?booking_id=" . urlencode($booking_id) . "';
        </script>";
        exit();
    }

    if (isset($_POST['accept_booking'])) {
        $stmt = $conn->prepare("UPDATE bookings SET booking_status='confirm' WHERE booking_id=?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();
        header("Location: view_booking_details.php?booking_id=" . urlencode($booking_id));
        exit();
    }

    if (isset($_POST['cancel_booking'])) {
        $booking_id = $_POST['booking_id'];
        $penaltyType = $_POST['penaltyType']; // 'penalty2', 'penalty24', or 'none'

        // Fetch booking_type and service_id
        $stmt = $conn->prepare("SELECT booking_type, service_id FROM bookings WHERE booking_id=?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->bind_result($booking_type, $service_id);
        $stmt->fetch();
        $stmt->close();

        // Fetch penalty columns from pricings table
        $bp_total = $bp_platform = $bp_sind = $bp_lvl1_amount = $bp_lvl2_amount = 0;
        $ao_penalty24_total = $ao_penalty2_total = 0;
        $ao_penalty24_platform = $ao_penalty2_platform = 0;
        $ao_penalty24_sind = $ao_penalty2_sind = 0;
        if ($penaltyType === 'penalty2' || $penaltyType === 'penalty24') {
            $col_total = $penaltyType . '_total';
            $col_platform = $penaltyType . '_platform';
            $col_sind = $penaltyType . '_sind';
            $col_lvl1 = $penaltyType . '_lvl1';
            $col_lvl2 = $penaltyType . '_lvl2';

            $stmt = $conn->prepare("SELECT $col_total, $col_platform, $col_sind, $col_lvl1, $col_lvl2 FROM pricings WHERE service_id=? AND service_type=? LIMIT 1");
            $stmt->bind_param("is", $service_id, $booking_type);
            $stmt->execute();
            $stmt->bind_result($bp_total, $bp_platform, $bp_sind, $bp_lvl1_amount, $bp_lvl2_amount);
            $stmt->fetch();
            $stmt->close();

            // --- Add-on penalties ---
            $ao_price_col = 'ao_price_resched' . ($penaltyType === 'penalty2' ? '2' : '24');
            $ao_platform_col = 'ao_platform_resched' . ($penaltyType === 'penalty2' ? '2' : '24');
            $ao_sind_col = 'ao_sind_resched' . ($penaltyType === 'penalty2' ? '2' : '24');

            $sql = "SELECT 
                        COALESCE(SUM($ao_price_col),0), 
                        COALESCE(SUM($ao_platform_col),0), 
                        COALESCE(SUM($ao_sind_col),0)
                    FROM booking_addons ba
                    JOIN addon ao ON ba.ao_id = ao.ao_id
                    WHERE ba.booking_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->bind_result($addon_penalty_total, $addon_penalty_platform, $addon_penalty_sind);
            $stmt->fetch();
            $stmt->close();

            $bp_total    += $addon_penalty_total;
            $bp_platform += $addon_penalty_platform;
            $bp_sind     += $addon_penalty_sind;
        }

        // Update bookings table
        $stmt = $conn->prepare("UPDATE bookings SET booking_status='cancel', bp_total=?, bp_platform=?, bp_sind=?, bp_lvl1_amount=?, bp_lvl2_amount=? WHERE booking_id=?");
        $stmt->bind_param("dddddi", $bp_total, $bp_platform, $bp_sind, $bp_lvl1_amount, $bp_lvl2_amount, $booking_id);
        $stmt->execute();
        $stmt->close();

        // Insert into booking_cancellation
        $reason = "Cancelled by Admin";
        $stmt = $conn->prepare("INSERT INTO booking_cancellation (booking_id, cancellation_reason) VALUES (?, ?)");
        $stmt->bind_param("is", $booking_id, $reason);
        $stmt->execute();
        $stmt->close();

        header("Location: view_booking_details.php?booking_id=" . urlencode($booking_id));
        exit();
    }

    // Fetch rejection stats for this sinderella
    $rejected_this_month = 0;
    $total_rejected = 0;
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())), 0) as month_count,
            COUNT(*) as total_count
        FROM sind_rejected_hist
        WHERE sind_id = ?
    ");
    $stmt->bind_param("i", $sind_id);
    $stmt->execute();
    $stmt->bind_result($rejected_this_month, $total_rejected);
    $stmt->fetch();
    $stmt->close();
} else {
    header("Location: view_bookings.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Booking</title>
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
        display: inline-block;
        margin-top: 10px;
        font-weight: bold;
    }
    .profile-container input[type="text"],
    .profile-container input[type="number"],
    .profile-container select,
    .profile-container textarea {
        width: 80%;
        /* padding: 5px; */
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
    td {
        padding: 8px;
    }
</style>

<script>
function pad(num) { return num.toString().padStart(2, '0'); }
function calcEndTime() {
    var start = document.querySelector('[name="new_booking_from_time"]').value;
    var duration = parseFloat(document.getElementById('total_duration').value);
    if (!start || isNaN(duration)) return;
    var parts = start.split(':');
    var d = new Date();
    d.setHours(parseInt(parts[0]), parseInt(parts[1]), 0, 0);
    d.setHours(d.getHours() + duration);
    var end = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':00';
    document.querySelector('[name="new_booking_to_time"]').value = end;
}
window.addEventListener('DOMContentLoaded', function() {
    calcEndTime();
    document.querySelector('[name="new_booking_from_time"]').addEventListener('change', calcEndTime);
});
</script>
</head>
<body>
<div class="main-container">
    <?php include '../includes/menu/menu_adm.php'; ?>
    <div class="content-container">
        <?php include '../includes/header_adm.php'; ?>
        <div class="profile-container">
            <h2>Edit Booking</h2>
            <form method="POST" id="editBookingForm">
                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                <table>
                    <tr>
                        <td><label>Date</label></td>
                        <td>: <input type="date" name="new_booking_date" 
                            value="<?php echo htmlspecialchars($booking_date); ?>" required></td>
                    </tr>
                    <tr>
                        <td><label>Start Time</label></td>
                        <td>: <input type="time" name="new_booking_from_time" 
                            value="<?php echo htmlspecialchars($booking_from_time); ?>" required></td>
                    </tr>
                    <tr>
                        <td><label>End Time</label></td>
                        <td>: <input type="time" name="new_booking_to_time" 
                            value="<?php echo htmlspecialchars($booking_to_time); ?>" readonly required></td>
                    </tr>
                    <tr>
                        <td><label>Address</label></td>
                        <!-- <td>: <input type="textarea" name="new_full_address"  -->
                            <!-- value="<?php echo htmlspecialchars($full_address); ?>" required></td> -->
                        <td>: <textarea name="new_full_address" required><?php echo htmlspecialchars($full_address); ?></textarea></td>
                    </tr>
                    <tr>
                        <td><label>Sinderella</label></td>
                        <td>: 
                            <select name="new_sind_id" required>
                                <?php foreach ($sind_list as $s): ?>
                                    <option value="<?php echo $s['sind_id']; ?>" <?php if ($s['sind_id'] == $sind_id) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($s['sind_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Service</strong></td>
                        <td>: <?php echo htmlspecialchars($service_name); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Customer</strong></td>
                        <td>: <?php echo htmlspecialchars($cust_name); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Add-ons</strong></td>
                        <td>
                            <ul>
                            <?php if (empty($addon_details)): ?>
                                <li>N/A</li>
                            <?php else: ?>
                                <?php foreach ($addon_details as $addon): ?>
                                    <li><?php echo htmlspecialchars($addon['desc']); ?></li>
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
                </table>
                <button type="submit" name="save_changes" id="saveChangesBtn">Save Changes</button>
                <button type="button" onclick="window.location.href='view_booking_details.php?booking_id=<?php echo $booking_id; ?>'">Discard Changes</button>
                
                <?php if (strtolower((string)$booking_status) === 'pending'): ?>
                    <br><button type="submit" name="mark_paid" id="markPaidBtn" style="background:#28a745">Payment Received</button>
                <?php endif; ?>
                <input type="hidden" id="total_duration" value="<?php echo htmlspecialchars($total_duration); ?>">

                <?php if (strtolower((string)$booking_status) === 'confirm'): ?>
                    <br><button type="submit" name="mark_done" id="markDoneBtn" style="background:#28a745">Mark as Done</button>
                <?php endif; ?>

                <?php if (strtolower((string)$booking_status) === 'paid'): ?>
                    <br>
                    <button type="submit" name="accept_booking" id="acceptBookingBtn" style="background:#28a745">Accept Booking</button>
                    <button type="button" id="rejectWithReasonBtn" style="background:#dc3545">Reject with Reason</button>
                <?php endif; ?>

                <?php if (in_array(strtolower((string)$booking_status), ['paid', 'confirm', 'rejected'])): ?>
                    <br><button type="button" id="cancelBookingBtn" style="background:#dc3545">Cancel Booking</button>
                <?php endif; ?>
            </form>

            <form method="POST" id="cancelBookingHidden" style="display:none;">
                <input type="hidden" name="cancel_booking" value="1">
                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                <input type="hidden" name="penaltyType" id="penaltyType" value="">
            </form>

            <div id="rejectModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
                <div style="background:#fff; padding:30px; border-radius:8px; text-align:center; min-width:320px; max-width:95vw;">
                    <h3>Reject Service</h3>
                    <div style="margin:15px 0 10px 0; font-size:15px;">
                        <b>Rejected this month:</b> <?php echo $rejected_this_month; ?><br>
                        <b>Total rejected:</b> <?php echo $total_rejected; ?>
                    </div>
                    <p style="margin-bottom:10px;">Please provide a reason for rejection:</p>
                    <textarea id="rejectReason" rows="3" style="width:90%;" placeholder="Enter your reason here..." required></textarea>
                    <br><br>
                    <button onclick="submitRejectReason()" style="margin-right:10px;">Submit</button>
                    <button onclick="closeRejectModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<?php
// Fetch penalty columns for this service and booking type (for JS)
$penalty24_total = $penalty24_platform = $penalty24_sind = $penalty24_lvl1 = $penalty24_lvl2 = 0;
$penalty2_total = $penalty2_platform = $penalty2_sind = $penalty2_lvl1 = $penalty2_lvl2 = 0;
$ao_penalty24_total = $ao_penalty24_platform = $ao_penalty24_sind = 0;
$ao_penalty2_total = $ao_penalty2_platform = $ao_penalty2_sind = 0;

$stmt = $conn->prepare("SELECT penalty24_total, penalty24_platform, penalty24_sind, penalty24_lvl1, penalty24_lvl2,
                               penalty2_total, penalty2_platform, penalty2_sind, penalty2_lvl1, penalty2_lvl2
                        FROM pricings WHERE service_id=? AND service_type=? LIMIT 1");
$stmt->bind_param("is", $service_id, $booking_type);
$stmt->execute();
$stmt->bind_result(
    $penalty24_total, $penalty24_platform, $penalty24_sind, $penalty24_lvl1, $penalty24_lvl2,
    $penalty2_total, $penalty2_platform, $penalty2_sind, $penalty2_lvl1, $penalty2_lvl2
);
$stmt->fetch();
$stmt->close();

foreach (['24', '2'] as $xx) {
    if ($booking_type === 'a') {
        $ao_price_col = "ao_price_resched$xx";
        $ao_platform_col = "ao_platform_resched$xx";
        $ao_sind_col = "ao_sind_resched$xx";
    } else { 
        $ao_price_col = "ao_price_resched{$xx}_re";
        $ao_platform_col = "ao_platform_resched{$xx}_re";
        $ao_sind_col = "ao_sind_resched{$xx}_re";
    }
    $stmt = $conn->prepare("SELECT 
        COALESCE(SUM($ao_price_col),0), 
        COALESCE(SUM($ao_platform_col),0), 
        COALESCE(SUM($ao_sind_col),0)
        FROM booking_addons ba
        JOIN addon ao ON ba.ao_id = ao.ao_id
        WHERE ba.booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($ao_total, $ao_platform, $ao_sind);
    $stmt->fetch();
    $stmt->close();
    if ($xx == '24') {
        $ao_penalty24_total = $ao_total;
        $ao_penalty24_platform = $ao_platform;
        $ao_penalty24_sind = $ao_sind;
    } else {
        $ao_penalty2_total = $ao_total;
        $ao_penalty2_platform = $ao_platform;
        $ao_penalty2_sind = $ao_sind;
    }
}
?>
<script>
var penaltyData = <?php echo json_encode([
    'penalty24_total' => (float)$penalty24_total + (float)$ao_penalty24_total,
    'penalty24_platform' => (float)$penalty24_platform + (float)$ao_penalty24_platform,
    'penalty24_sind' => (float)$penalty24_sind + (float)$ao_penalty24_sind,
    'penalty24_lvl1' => (float)$penalty24_lvl1,
    'penalty24_lvl2' => (float)$penalty24_lvl2,
    'penalty2_total' => (float)$penalty2_total + (float)$ao_penalty2_total,
    'penalty2_platform' => (float)$penalty2_platform + (float)$ao_penalty2_platform,
    'penalty2_sind' => (float)$penalty2_sind + (float)$ao_penalty2_sind,
    'penalty2_lvl1' => (float)$penalty2_lvl1,
    'penalty2_lvl2' => (float)$penalty2_lvl2,
]); ?>;
document.addEventListener('DOMContentLoaded', function() {
    var markPaidBtn = document.getElementById('markPaidBtn');
    if (markPaidBtn) {
        markPaidBtn.addEventListener('click', function(e) {
            var confirmed = confirm("Are you sure you have received the payment? \n\nThis action cannot be undone.");
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    var saveChangesBtn = document.getElementById('saveChangesBtn');
    var editBookingForm = document.getElementById('editBookingForm');
    if (saveChangesBtn && editBookingForm) {
        saveChangesBtn.addEventListener('click', function(e) {
            // Get current status from PHP
            var currentStatus = "<?php echo strtolower($booking_status); ?>";
            var msg = "Are you sure you want to save the changes?\n\nThe Sinderella / new Sinderella (if changed) will receive the updated booking details.";
            if (currentStatus === "rejected") {
                msg = "Are you sure you want to save the changes?\n\nThe Sinderella / new Sinderella (if changed) will receive the updated booking details\n\nIf you save changes, the status will be changed from 'REJECTED' to 'PAID'.";
            }
            var confirmed = confirm(msg);
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    var markDoneBtn = document.getElementById('markDoneBtn');
    if (markDoneBtn) {
        markDoneBtn.addEventListener('click', function(e) {
            var confirmed = confirm("Please CONFIRM Sinderella have COMPLETED the service.\n\nThe rating link will be send to the customer.\n\nThis action CANNOT be UNDONE.");
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    var acceptBookingBtn = document.getElementById('acceptBookingBtn');
    if (acceptBookingBtn) {
        acceptBookingBtn.addEventListener('click', function(e) {
            var confirmed = confirm("Please CONFIRM Sinderella availability. \n\nThis action CANNOT be UNDONE.");
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }

    var rejectWithReasonBtn = document.getElementById('rejectWithReasonBtn');
    if (rejectWithReasonBtn) {
        rejectWithReasonBtn.addEventListener('click', function(e) {
            document.getElementById('rejectModal').style.display = 'flex';
            document.getElementById('rejectReason').value = '';
        });
    }

    var cancelBookingBtn = document.getElementById('cancelBookingBtn');
    if (cancelBookingBtn) {
        cancelBookingBtn.addEventListener('click', function(e) {
            var bookingDate = "<?php echo $booking_date; ?>";
            var bookingFromTime = "<?php echo $booking_from_time; ?>";
            var bookingStart = new Date(bookingDate + 'T' + bookingFromTime);
            var now = new Date();
            var diffMs = bookingStart - now;
            var diffH = diffMs / (1000 * 60 * 60);

            var penaltyType = '';
            if (diffH < 24) {
                penaltyType = 'penalty2';
            } else if (diffH < 48) {
                penaltyType = 'penalty24';
            } else {
                penaltyType = 'none';
            }

            var bookingTotal = <?php echo json_encode((float)$total_price); ?>;
            var earnings = (penaltyType === 'none') ? 0 : penaltyData[penaltyType + '_total'];
            var refund = (bookingTotal - earnings).toFixed(2);

            var timeMsg = (diffMs > 0)
                ? ("Booking starts in " + Math.floor(diffH) + " hour(s) " + Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60)) + " minute(s)")
                : "Booking start time has passed.";

            var msg = timeMsg + "\n\nBooking Total: RM " + bookingTotal.toFixed(2) +
                    "\nEarnings (Penalty): RM " + earnings.toFixed(2) +
                    "\nAmount to Refund: RM " + refund +
                    "\n\nAre you sure you want to cancel this booking?";

            document.getElementById('penaltyType').value = penaltyType;

            if (confirm(msg)) {
                document.getElementById('cancelBookingHidden').submit();
            }
        });
    }
});

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
function submitRejectReason() {
    var reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        alert('Please provide a reason for rejection.');
        return;
    }
    var confirmed = confirm("This will add to the rejected history.\n\nAre you sure you want to proceed?");
    if (!confirmed) {
        return;
    }
    var bookingId = <?php echo json_encode($booking_id); ?>;
    var sindId = <?php echo json_encode($sind_id); ?>; // Use the current sind_id for this booking
    var rejectedThisMonth = <?php echo json_encode($rejected_this_month); ?>;
    fetch('../rs/reject_booking.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'booking_id=' + encodeURIComponent(bookingId) +
              '&sind_id=' + encodeURIComponent(sindId) +
              '&reason=' + encodeURIComponent(reason) + 
              '&rejected_this_month=' + encodeURIComponent(rejectedThisMonth)
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            closeRejectModal();
            window.location.reload();
        }
    });
}
</script>
</html>