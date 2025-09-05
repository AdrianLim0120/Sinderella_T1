<?php
require_once '../db_connect.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    // die('Invalid link.');
    echo "<script>
        alert('Invalid link.');
        window.history.back();
    </script>";
    exit();
}

$stmt = $conn->prepare("SELECT booking_id, expires_at, used FROM booking_rating_links WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->bind_result($booking_id, $expires_at, $used);
if (!$stmt->fetch()) {
    // die('Invalid or expired link.');
    echo "<script>
        alert('Invalid or expired link.');
        window.history.back();
    </script>";
    exit();
}
$stmt->close();

if ($used) {
    // die('This link has already been used.');
    echo "<script>
        alert('This link has already been used.');
        window.history.back();
    </script>";
    exit();
}
if (strtotime($expires_at) < time()) {
    // die('This link has expired.');
    echo "<script>
        alert('This link has expired.');
        window.history.back();
    </script>";
    exit();
}

// // // // // // // // // // // // // // // //
// copy from view_booking_details.php start  //
// // // // // // // // // // // // // // // //
$stmt = $conn->prepare("
    SELECT 
        b.booking_date, b.booking_from_time, b.booking_to_time, b.full_address, 
        s.sind_name, sv.service_name, p.total_price, b.booking_status, b.service_id, 
        b.sind_id, b.cust_id, b.bp_total, b.booking_type
    FROM bookings b
    JOIN sinderellas s ON b.sind_id = s.sind_id
    JOIN services sv ON b.service_id = sv.service_id
    LEFT JOIN pricings p ON b.service_id = p.service_id AND p.service_type = b.booking_type
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($booking_date, $booking_from_time, $booking_to_time, $full_address, 
                $sinderella_name, $service_name, $service_price, $booking_status, $service_id, 
                $sinderella_id, $cust_id, $bp_total, $booking_type);
$stmt->fetch();
$stmt->close();

if (empty($full_address)) {
    $full_address = "N/A";
}

// Fetch add-ons
$addon_details = [];
$addon_ids = [];
$total_addon_price = 0;
$total_addon_duration = 0;
$stmt = $conn->prepare("SELECT ao.ao_id, ao.ao_desc, ao.ao_price, ao.ao_duration
                        FROM booking_addons ba
                        JOIN addon ao ON ba.ao_id = ao.ao_id
                        WHERE ba.booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($ao_id, $ao_desc, $ao_price, $ao_duration);
while ($stmt->fetch()) {
    $addon_details[] = ['id' => $ao_id, 'desc' => $ao_desc, 'price' => $ao_price, 'duration' => $ao_duration];
    $addon_ids[] = $ao_id;
    $total_addon_price += $ao_price;
    $total_addon_duration += $ao_duration;
}
$stmt->close();

// Calculate total price
if ($booking_status == 'pending') {
    $total_price = $total_addon_price + $service_price;
} else {
    $total_price = $bp_total;
}

// $conn->close();

function formatTime($time) {
    $date = new DateTime($time);
    return $date->format('h:i A');
}

function formatDate($date) {
    $date = new DateTime($date);
    return $date->format('Y-m-d (l)');
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

$avg_sind_rating = null;
$sind_review_count = 0;
$stmt = $conn->prepare("SELECT ROUND(AVG(rate),1) as avg_rating, COUNT(*) as review_count FROM booking_ratings WHERE sind_id = ?");
$stmt->bind_param("i", $sinderella_id);
$stmt->execute();
$stmt->bind_result($avg_sind_rating, $sind_review_count);
$stmt->fetch();
$stmt->close();

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
        // Get all bookings with same recurring_id and same customer
        $stmt = $conn->prepare("
            SELECT b.booking_id, b.booking_date, b.booking_from_time, b.booking_to_time, s.sind_name, b.booking_status
            FROM booking_recurring br
            JOIN bookings b ON br.booking_id = b.booking_id
            JOIN sinderellas s ON b.sind_id = s.sind_id
            WHERE br.recurring_id = ? AND br.cust_id = ?
            ORDER BY b.booking_date ASC, b.booking_from_time ASC
        ");
        $stmt->bind_param("ii", $recurring_id, $cust_id);
        // $stmt->bind_param("ii", $recurring_id, $_SESSION['cust_id']);
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

// Fetch available add-ons for this service (for add-on modal)
$available_addons = [];
if ($booking_status == 'confirm') {
    $stmt = $conn->prepare("SELECT ao_id, ao_desc, ao_price FROM addon WHERE service_id = ? AND ao_status = 'active'");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->bind_result($ao_id, $ao_desc, $ao_price);
    while ($stmt->fetch()) {
        $available_addons[] = [
            'id' => $ao_id,
            'desc' => $ao_desc,
            'price' => $ao_price
        ];
    }
    $stmt->close();
}
// // // // // // // // // // // // // // // //
// copy from view_booking_details.php end    //
// // // // // // // // // // // // // // // //

// If booking status is 'rated', show message and mark token as used
// $stmt = $conn->prepare("SELECT booking_status FROM bookings WHERE booking_id = ?");
// $stmt->bind_param("i", $booking_id);
// $stmt->execute();
// $stmt->bind_result($booking_status);
// $stmt->fetch();
// $stmt->close();

if ($booking_status == 'rated') {
    // Optionally mark token as used
    $conn->query("UPDATE booking_rating_links SET used = 1 WHERE token = '". $conn->real_escape_string($token) ."'");
    // die('This booking has already been rated. Thank you!');
    echo "<script>
        alert('This booking has already been rated. Thank you!');
        window.history.back();
    </script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Customer - Sinderella</title>
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
        <div class="content-container">
            <div class="details-container">
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
                    <!-- <tr>
                        <td><strong>Sinderella</strong></td>
                        <td>: <?php echo htmlspecialchars($sinderella_name); ?></td>
                    </tr> -->
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
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Service</strong></td>
                        <td>: <?php echo htmlspecialchars($service_name); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Booking Type</strong></td>
                        <td>: 
                            <?php 
                            if ($booking_type == 'a') {
                                echo "Ad-Hoc";
                            } elseif ($booking_type == 'r') {
                                echo "Recurring";
                            } else {
                                echo "Unknown Type";
                            }
                            ?>
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
                    <?php if ($booking_status != 'cancel'): ?>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td>: RM <?php echo number_format($total_price, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td>: <?php echo htmlspecialchars(ucfirst($booking_status)); ?></td>
                    </tr>
                    <?php if ($booking_status == 'rejected'): ?>
                        <tr>
                            <td colspan="2" style="color:#e53935;">
                                Your booking was rejected by Sinderella. The admin will assign another Sinderella for your booking.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($booking_status == 'cancel'): ?>
                        <tr>
                            <td><strong>Cancellation<br>Reason</strong></td>
                            <td>: <?php echo htmlspecialchars($cancellation_reason); ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php if ($booking_status == 'rated'): ?>
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
                    <?php endif; ?>
                </table>

                <?php if ($booking_status == 'pending' && $booking_type == 'a'): ?>
                    <form id="confirmationForm" method="POST" action="confirm_booking.php">
                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                        <input type="hidden" name="booking_date" value="<?php echo htmlspecialchars($booking_date); ?>">
                        <input type="hidden" name="sinderella" value="<?php echo htmlspecialchars($sinderella_id); ?>">
                        <input type="hidden" name="start_time" value="<?php echo htmlspecialchars($booking_from_time); ?>">
                        <input type="hidden" name="end_time" value="<?php echo htmlspecialchars($booking_to_time); ?>">
                        <input type="hidden" name="service" value="<?php echo htmlspecialchars($service_id); ?>">
                        <input type="hidden" name="full_address" value="<?php echo htmlspecialchars($full_address); ?>">
                        <?php foreach ($addon_ids as $addon_id): ?>
                            <input type="hidden" name="addons[]" value="<?php echo htmlspecialchars($addon_id); ?>">
                        <?php endforeach; ?>
                        <button type="button" onclick="prePayNowCheck()">Pay Now</button>
                        <button type="button" onclick="cancelBooking(<?php echo $booking_id; ?>)">Cancel Booking</button><br>
                    </form>
                <?php endif; ?>

                <?php if ($booking_status == 'pending' && $booking_type == 'r'): ?>
                    <button type="button" onclick="prePayNowRecurringCheck(<?php echo $booking_id; ?>)">Pay Now (Recurring)</button>
                <?php endif; ?>

                <?php if ($booking_status == 'done'): ?>
                    <button type="button" onclick="openRatingModal()">Rate & Review Sinderella</button><br>
                <?php endif; ?>

                <?php if ($booking_status == 'confirm'): ?>
                    <button type="button" onclick="openAddonModal()">Add Add-on</button>
                <?php endif; ?>

                <!-- <button type="button" onclick="window.location.href='my_booking.php?search_date=&search_status=<?php echo urlencode($booking_status); ?>'">Back</button> -->
                <!-- <button type="button" onclick="window.location.href='my_booking.php?search_date=&search_status=' + booking_status">Back</button> -->

                
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

                <div id="sindRatingPopup" style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; box-shadow:0 2px 10px #0002; z-index:9999; padding:20px; max-width:90vw; max-height:80vh; overflow:auto;">
                    <div id="sindRatingPopupContent"></div>
                    <div style="text-align:center;">
                        <button onclick="document.getElementById('sindRatingPopup').style.display='none'">Close</button>
                    </div>
                </div>

                <!-- others booking details for recurring booking -->
                <!-- <?php if ($booking_type == 'r' && $recurring_id && count($recurring_bookings) > 0): ?>
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
                <?php endif; ?> -->
            </div>
        </div>
    </div>
    
    <script>
        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                window.location.href = 'cancel_booking.php?booking_id=' + bookingId + "&reason=user";
            }
        }

        async function prePayNowCheck() {
            const bookingDate = "<?php echo $booking_date; ?>";
            const today = new Date().toISOString().slice(0, 10);
            const bookingId = "<?php echo $booking_id; ?>";
            const sindId = "<?php echo $sinderella_id; ?>";
            const fromTime = "<?php echo $booking_from_time; ?>";
            const toTime = "<?php echo $booking_to_time; ?>";

            // 1. Date check
            if (bookingDate <= today) {
                alert("This booking date is in the past. The booking will be cancelled.");
                // Cancel booking via AJAX or redirect
                window.location.href = "cancel_booking.php?booking_id=" + bookingId + "&reason=past";
                return;
            } 
            // else if (bookingDate === today) {
            //     if (!confirm("You are proceeding to pay for a booking scheduled for today. Are you sure you want to proceed?")) {
            //         return;
            //     }
            // }

            // 2. Conflict check (AJAX to PHP) -- DISABLED
            // let conflictResp = await fetch("ajax_check_booking_conflict.php?booking_id=" + bookingId + "&sind_id=" + sindId + "&booking_date=" + bookingDate + "&from_time=" + fromTime + "&to_time=" + toTime);
            // let conflictData = await conflictResp.json();
            // if (conflictData.conflict) {
            //     alert("This Sinderella has already been booked by another customer for this time slot. The booking will be cancelled.");
            //     window.location.href = "cancel_booking.php?booking_id=" + bookingId + "&reason=conflict";
            //     return;
            // }

            // 3. Availability check (AJAX to PHP)  --DISABLED
            // let availResp = await fetch("ajax_check_sind_availability.php?booking_id=" + bookingId + "&sind_id=" + sindId + "&booking_date=" + bookingDate + "&from_time=" + fromTime + "&to_time=" + toTime);
            // let availData = await availResp.json();
            // if (!availData.available) {
            //     alert(availData.message || "Sinderella's schedule has changed. The booking will be cancelled.");
            //     window.location.href = "cancel_booking.php?booking_id=" + bookingId + "&reason=schedule";
            //     return;
            // }

            // All checks passed, submit the form
            document.getElementById('confirmationForm').submit();
        }
    </script>
    <script>
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

            fetch('submit_rating.php', {
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

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('sind-rating')) {
                const sindId = e.target.getAttribute('data-sind-id');
                const popup = document.getElementById('sindRatingPopup');
                const content = document.getElementById('sindRatingPopupContent');
                content.innerHTML = 'Loading...';
                popup.style.display = 'block';

                fetch(`get_public_ratings.php?sind_id=${sindId}`)
                    .then(resp => resp.text())
                    .then(html => {
                        content.innerHTML = html;
                    })
                    .catch(() => {
                        content.innerHTML = '<div style="color:red;">Failed to load ratings.</div>';
                    });
            }
        });

        async function prePayNowRecurringCheck(mainBookingId) {
            // 1. Get all 4 booking IDs from booking_recurring table via AJAX
            let resp = await fetch('ajax_get_recurring_bookings.php?booking_id=' + mainBookingId);
            let data = await resp.json();
            if (!data.success) {
                alert('Could not retrieve recurring booking details.');
                return;
            }
            let bookings = data.bookings; // Array of 4 booking objects

            let today = new Date().toISOString().slice(0, 10);
            let failed = [];
            // 2. Check each booking
            for (let i = 0; i < bookings.length; i++) {
                let b = bookings[i];
                // Date check
                if (b.booking_date <= today) {
                    failed.push(`Booking #${i+1} (${b.booking_date}): Date is in the past.`);
                    continue;
                }
                // Conflict check -- DISABLED
                // let conflictResp = await fetch(`ajax_check_booking_conflict.php?booking_id=${b.booking_id}&sind_id=${b.sind_id}&booking_date=${b.booking_date}&from_time=${b.booking_from_time}&to_time=${b.booking_to_time}`);
                // let conflictData = await conflictResp.json();
                // if (conflictData.conflict) {
                //     failed.push(`Booking #${i+1} (${b.booking_date}): Sinderella is already booked for this time slot.`);
                //     continue;
                // }
                // Availability check -- DISABLED
                // let availResp = await fetch(`ajax_check_sind_availability.php?booking_id=${b.booking_id}&sind_id=${b.sind_id}&booking_date=${b.booking_date}&from_time=${b.booking_from_time}&to_time=${b.booking_to_time}`);
                // let availData = await availResp.json();
                // if (!availData.available) {
                //     failed.push(`Booking #${i+1} (${b.booking_date}): ${availData.message || "Sinderella's schedule has changed."}`);
                //     continue;
                // }
            }

            if (failed.length > 0) {
                // 3. If any failed, cancel all 4 bookings via AJAX and alert
                await fetch('ajax_cancel_recurring_bookings.php?booking_id=' + mainBookingId);
                alert("One or more bookings cannot proceed:\n\n" + failed.join('\n') + "\n\nAll recurring bookings have been cancelled.");
                window.location.reload();
                return;
            }

            // 4. If all passed, redirect to confirm_recurring_booking.php with all booking values (use POST via form)
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'confirm_recurring_booking.php';
            // Add all booking data as hidden inputs
            bookings.forEach((b, idx) => {
                let i = idx + 1;
                form.innerHTML += `<input type="hidden" name="date_${i}" value="${b.booking_date}">`;
                form.innerHTML += `<input type="hidden" name="sinderella_${i}" value="${b.sind_id}">`;
                form.innerHTML += `<input type="hidden" name="time_${i}" value="${b.booking_from_time}">`;
                form.innerHTML += `<input type="hidden" name="end_time_${i}" value="${b.booking_to_time}">`;
                form.innerHTML += `<input type="hidden" name="addons_${i}" value="${b.addons}">`;
            });
            form.innerHTML += `<input type="hidden" name="address" value="${bookings[0].full_address}">`;
            form.innerHTML += `<input type="hidden" name="service" value="${bookings[0].service_id}">`;
            form.innerHTML += `<input type="hidden" name="block_count" value="${bookings.length}">`; 
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>