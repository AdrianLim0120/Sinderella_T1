<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
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
// $stmt = $conn->prepare("SELECT b.booking_date, b.booking_from_time, b.booking_to_time, c.cust_name, b.full_address, sp.service_name, sp.service_price, b.booking_status
//                         FROM bookings b
//                         JOIN customers c ON b.cust_id = c.cust_id
//                         JOIN service_pricing sp ON b.service_id = sp.service_id
//                         WHERE b.booking_id = ? AND b.sind_id = ?");
$stmt = $conn->prepare("
    SELECT 
        b.booking_date, b.booking_from_time, b.booking_to_time, b.bp_total, 
        c.cust_id, c.cust_name, b.full_address, s.service_name, p.total_price, b.booking_status, b.booking_type
    FROM bookings b
    JOIN customers c ON b.cust_id = c.cust_id
    JOIN services s ON b.service_id = s.service_id
    LEFT JOIN pricings p ON b.service_id = p.service_id AND p.service_type = b.booking_type
    WHERE b.booking_id = ? AND b.sind_id = ?
");
$stmt->bind_param("ii", $booking_id, $_SESSION['sind_id']);
$stmt->execute();
$stmt->bind_result($booking_date, $booking_from_time, $booking_to_time, $bp_total,
                    $cust_id, $cust_name, $full_address, $service_name, $service_price, $booking_status, $booking_type);
$stmt->fetch();
$stmt->close();

if (empty($full_address)) {
    $full_address = "N/A";
}

// Fetch add-ons
$addon_details = [];
$total_addon_price = 0;
$stmt = $conn->prepare("SELECT ao.ao_desc, ao.ao_price
                        FROM booking_addons ba
                        JOIN addon ao ON ba.ao_id = ao.ao_id
                        WHERE ba.booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($ao_desc, $ao_price);
while ($stmt->fetch()) {
    $addon_details[] = ['desc' => $ao_desc, 'price' => $ao_price];
    $total_addon_price += $ao_price;
}
$stmt->close();

// $total_price = $service_price + $total_addon_price;

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

// Fetch the sind_id for this booking
$stmt = $conn->prepare("SELECT sind_id FROM bookings WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($booking_sind_id);
$stmt->fetch();
$stmt->close();

if ($booking_sind_id != $_SESSION['sind_id']) {
    echo "<script>
        alert('You are not authorized to view this booking.');
        window.history.back();
    </script>";
    exit();
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

$cust_comments = [];
$stmt = $conn->prepare("SELECT cmt_hse FROM cust_ratings WHERE cust_id = ? AND cmt_hse IS NOT NULL AND TRIM(cmt_hse) != ''");
$stmt->bind_param("i", $cust_id);
$stmt->execute();
$stmt->bind_result($popup_cmt_hse);
while ($stmt->fetch()) {
    $cust_comments[] = $popup_cmt_hse;
}
$stmt->close();

// $avg_cust_rating = null;
// $cust_review_count = 0;
// $stmt = $conn->prepare("SELECT ROUND(AVG(rate),1) as avg_rating, COUNT(*) as review_count FROM cust_ratings WHERE cust_id = ?");
// $stmt->bind_param("i", $cust_id);
// $stmt->execute();
// $stmt->bind_result($avg_cust_rating, $cust_review_count);
// $stmt->fetch();
// $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Sinderella - Sinderella</title>
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
    <script>
        let currentAction = ''; // 'checkin' or 'checkout'
        let bookingId = <?php echo json_encode($booking_id); ?>;

        function openCheckinModal() {
            currentAction = 'checkin';
            openCameraModal();
        }
        function openCheckoutModal() {
            currentAction = 'checkout';
            openCameraModal();
        }

        function openCameraModal() {
            document.getElementById('cameraModal').style.display = 'flex';
            startCamera();
        }

        function closeCameraModal() {
            document.getElementById('cameraModal').style.display = 'none';
            stopCamera();
            document.getElementById('canvas').style.display = 'none';
            document.getElementById('video').style.display = 'block';
            document.getElementById('captureBtn').style.display = '';
            document.getElementById('retakeBtn').style.display = 'none';
            document.getElementById('submitBtn').style.display = 'none';
        }

        let stream = null;
        function startCamera() {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(s) {
                    stream = s;
                    document.getElementById('video').srcObject = stream;
                });
        }
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }
    </script>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_sind.php'; ?>
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

                    <!-- <tr>
                        <td><strong>Customer</strong></td>
                        <td>: <?php echo htmlspecialchars($cust_name); ?></td>
                    </tr> -->

                    <tr>
                        <td><strong>Customer</strong></td>
                        <td>:
                            <?php echo htmlspecialchars($cust_name); ?>
                            <span class="cust-rating" style="margin-left:8px;color:#F09E0B;font-size:16px;cursor:pointer;z-index:9999;position:relative;" data-cust-id="<?php echo $cust_id; ?>">
                                View Comment
                            </span>
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
                    <!-- <tr>
                        <td><strong>Total</strong></td>
                        <td>: RM <?php echo number_format($total_price, 2); ?></td>
                    </tr> -->
                    <tr>
                        <td><strong>Status</strong></td>
                        <td>: <?php echo htmlspecialchars(ucfirst($booking_status)); ?></td>
                    </tr>
                    </table>

                    <?php if ($booking_status == 'rated'): ?>
                    <br><table>
                    <h3>Rating by Customer</h3>
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

                <!-- <?php if (($booking_status == 'done' || $booking_status == 'rated') && !$cust_rating_exists): ?>
                    <button type="button" onclick="openCustRatingModal()">Rate Customer</button>
                <?php endif; ?> -->

                <?php if (($booking_status == 'done' || $booking_status == 'rated') && !$cust_rating_exists): ?>
                    <button type="button" id="rateCustomerBtn">Rate Customer</button>
                <?php endif; ?>

                <?php if ($cust_rating_exists): ?>
                    <br><h3>Rating by Sinderella</h3>
                    <table>
                        <tr>
                            <td><strong>Comment to Customer</strong></td>
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

                <?php if ($booking_status == 'paid'): ?>
                    <button type="button" onclick="handleBookingAction('confirm')">Accept</button>
                    <button type="button" onclick="handleBookingAction('rejected')">Reject</button>
                <?php endif; ?>

                <?php if ($booking_status == 'confirm' && !$checkin_time): ?>
                    <tr>
                        <td colspan="2">
                            <button type="button" onclick="openCheckinModal()">Check In</button>
                        </td>
                    </tr>
                <?php elseif ($booking_status == 'confirm' && $checkin_time && !$checkout_time): ?>
                    <tr>
                        <td colspan="2">
                            <button type="button" onclick="openCheckoutModal()">Check Out</button>
                        </td>
                    </tr>
                <?php endif; ?>

                <div id="custRatingModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
                    <div style="background:#fff; padding:30px; border-radius:8px; text-align:center; min-width:300px;">
                        <!-- <h3>Rate Customer</h3>
                        <div id="custStarContainer" style="font-size:2em; margin-bottom:10px;">
                            <span class="cust-star" data-value="1">&#9734;</span>
                            <span class="cust-star" data-value="2">&#9734;</span>
                            <span class="cust-star" data-value="3">&#9734;</span>
                            <span class="cust-star" data-value="4">&#9734;</span>
                            <span class="cust-star" data-value="5">&#9734;</span>
                        </div>
                        <textarea id="custRatingComment" rows="4" style="width:90%;" placeholder="Leave a comment (optional)"></textarea>
                        <br> -->
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

                <br>
                <button type="button" onclick="window.location.href='view_bookings.php?search_date=&search_area=&search_status=<?php echo urlencode($booking_status); ?>'">Back</button>
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
                    <div id="custRatingPopupContent"></div>
                    <div style="text-align:center;">
                        <button onclick="document.getElementById('custRatingPopup').style.display='none'">Close</button>
                    </div>
                </div>
                
                <!-- Camera Modal -->
                <div id="cameraModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
                    <div style="background:#fff; padding:20px; border-radius:8px; text-align:center;">
                        <video id="video" width="320" height="240" autoplay></video>
                        <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>
                        <br>
                        <button id="captureBtn">Capture</button>
                        <button id="retakeBtn" style="display:none;">Retake</button>
                        <button id="submitBtn" style="display:none;">Submit</button>
                        <button onclick="closeCameraModal()">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        document.addEventListener('DOMContentLoaded', function () {
            const rateBtn = document.getElementById('rateCustomerBtn');
            if (rateBtn) {
                rateBtn.addEventListener('click', openCustRatingModal);
            }
        });

        let selectedCustRating = 0;

        function openCustRatingModal() {
            document.getElementById('custRatingModal').style.display = 'flex';
            // highlightCustStars(0);
            // document.getElementById('custRatingComment').value = '';
            document.getElementById('custRatingCommentPpl').value = '';
            document.getElementById('custRatingCommentHse').value = '';
        }

        function closeCustRatingModal() {
            document.getElementById('custRatingModal').style.display = 'none';
        }

        // function highlightCustStars(rating) {
        //     const stars = document.querySelectorAll('#custStarContainer .cust-star');
        //     stars.forEach(star => {
        //         star.innerHTML = (parseInt(star.dataset.value) <= rating) ? '★' : '☆';
        //     });
        //     selectedCustRating = rating;
        // }

        // document.querySelectorAll('#custStarContainer .cust-star').forEach(star => {
        //     star.addEventListener('mouseover', function() {
        //         highlightCustStars(parseInt(this.dataset.value));
        //     });
        //     star.addEventListener('click', function() {
        //         highlightCustStars(parseInt(this.dataset.value));
        //     });
        // });

        // document.getElementById('custStarContainer').addEventListener('mouseleave', function() {
        //     highlightCustStars(selectedCustRating);
        // });

        function submitCustRating() {
            // if (selectedCustRating < 1 || selectedCustRating > 5) {
            //     alert('Please select a rating.');
            //     return;
            // }
            // const comment = document.getElementById('custRatingComment').value;
            const commentPpl = document.getElementById('custRatingCommentPpl').value.trim();
            const commentHse = document.getElementById('custRatingCommentHse').value.trim();
            if (!commentPpl && !commentHse) {
                alert('Please enter at least one comment.');
                return;
            }

            const bookingId = <?php echo json_encode($booking_id); ?>;
            const sindId = <?php echo json_encode($_SESSION['sind_id']); ?>;
            const custId = <?php echo json_encode($cust_id); ?>;

            const formData = new FormData();
            formData.append('booking_id', bookingId);
            formData.append('sind_id', sindId);
            formData.append('cust_id', custId);
            // formData.append('rate', selectedCustRating);
            // formData.append('comment', comment);
            formData.append('cmt_ppl', commentPpl);
            formData.append('cmt_hse', commentHse);

            fetch('submit_cust_rating.php', {
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

        // let currentAction = ''; // 'checkin' or 'checkout'
        // let bookingId = <?php echo json_encode($booking_id); ?>;

        // function openCheckinModal() {
        //     currentAction = 'checkin';
        //     openCameraModal();
        // }
        // function openCheckoutModal() {
        //     currentAction = 'checkout';
        //     openCameraModal();
        // // }

        // function openCameraModal() {
        //     document.getElementById('cameraModal').style.display = 'flex';
        //     startCamera();
        // }

        // function closeCameraModal() {
        //     document.getElementById('cameraModal').style.display = 'none';
        //     stopCamera();
        //     document.getElementById('canvas').style.display = 'none';
        //     document.getElementById('video').style.display = 'block';
        //     document.getElementById('captureBtn').style.display = '';
        //     document.getElementById('retakeBtn').style.display = 'none';
        //     document.getElementById('submitBtn').style.display = 'none';
        // }

        // let stream = null;
        // function startCamera() {
        //     navigator.mediaDevices.getUserMedia({ video: true })
        //         .then(function(s) {
        //             stream = s;
        //             document.getElementById('video').srcObject = stream;
        //         });
        // }
        // function stopCamera() {
        //     if (stream) {
        //         stream.getTracks().forEach(track => track.stop());
        //         stream = null;
        //     }
        // }

        document.addEventListener('DOMContentLoaded', function () {

            var captureBtn = document.getElementById('captureBtn');
            if (captureBtn) {
                captureBtn.onclick = function() {
                    let video = document.getElementById('video');
                    let canvas = document.getElementById('canvas');
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    canvas.style.display = 'block';
                    video.style.display = 'none';
                    this.style.display = 'none';
                    document.getElementById('retakeBtn').style.display = '';
                    document.getElementById('submitBtn').style.display = '';
                };
            }

        // document.getElementById('captureBtn').onclick = function() {
        //     let video = document.getElementById('video');
        //     let canvas = document.getElementById('canvas');
        //     canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        //     canvas.style.display = 'block';
        //     video.style.display = 'none';
        //     this.style.display = 'none';
        //     document.getElementById('retakeBtn').style.display = '';
        //     document.getElementById('submitBtn').style.display = '';
        // };

            var retakeBtn = document.getElementById('retakeBtn');
            if (retakeBtn) {
                retakeBtn.onclick = function() {
                    document.getElementById('canvas').style.display = 'none';
                    document.getElementById('video').style.display = 'block';
                    document.getElementById('captureBtn').style.display = '';
                    this.style.display = 'none';
                    document.getElementById('submitBtn').style.display = 'none';
                };
            }

        // document.getElementById('retakeBtn').onclick = function() {
        //     document.getElementById('canvas').style.display = 'none';
        //     document.getElementById('video').style.display = 'block';
        //     document.getElementById('captureBtn').style.display = '';
        //     this.style.display = 'none';
        //     document.getElementById('submitBtn').style.display = 'none';
        // };

            var submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.onclick = function() {
                    let canvas = document.getElementById('canvas');
                    let dataURL = canvas.toDataURL('image/jpeg');
                    let formData = new FormData();
                    formData.append('booking_id', bookingId);
                    formData.append('action', currentAction);
                    formData.append('image', dataURL);

                    fetch('process_checkinout.php', {
                        method: 'POST',
                        body: formData
                    }).then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        closeCameraModal();
                        if (data.success) location.reload();
                    });
                };
            }

        // document.getElementById('submitBtn').onclick = function() {
        //     let canvas = document.getElementById('canvas');
        //     let dataURL = canvas.toDataURL('image/jpeg');
        //     let formData = new FormData();
        //     formData.append('booking_id', bookingId);
        //     formData.append('action', currentAction);
        //     formData.append('image', dataURL);

        //     fetch('process_checkinout.php', {
        //         method: 'POST',
        //         body: formData
        //     }).then(res => res.json())
        //     .then(data => {
        //         alert(data.message);
        //         closeCameraModal();
        //         if (data.success) location.reload();
        //     });
        // };

        });

        function handleBookingAction(action) {
            if (action === 'confirm' && !confirm('Are you sure you want to accept this booking?')) return;
            if (action === 'rejected' && !confirm('Are you sure you want to reject this booking?')) return;

            fetch('update_booking_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'booking_id=' + encodeURIComponent(<?php echo json_encode($booking_id); ?>) + '&status=' + encodeURIComponent(action)
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }


        // document.addEventListener('click', function(e) {
        //     if (e.target.classList.contains('cust-rating')) {
        //         console.log('cust-rating clicked');
        //         const custId = e.target.getAttribute('data-cust-id');
        //         const popup = document.getElementById('custRatingPopup');
        //         const content = document.getElementById('custRatingPopupContent');
        //         content.innerHTML = 'Loading...';
        //         popup.style.display = 'flex';

        //         fetch(`get_public_cust_ratings.php?cust_id=${custId}`)
        //             .then(resp => resp.text())
        //             .then(html => {
        //                 content.innerHTML = html;
        //             })
        //             .catch(() => {
        //                 content.innerHTML = '<div style="color:red;">Failed to load comments.</div>';
        //             });
        //     }
        // });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('cust-rating')) {
                console.log('clicked');
                const custId = e.target.getAttribute('data-cust-id');
                const popup = document.getElementById('custRatingPopup');
                const content = document.getElementById('custRatingPopupContent');
                content.innerHTML = 'Loading...';
                popup.style.display = 'flex';

                fetch(`get_public_cust_ratings.php?cust_id=${custId}`)
                    .then(resp => resp.text())
                    .then(html => {
                        content.innerHTML = html;
                    })
                    .catch(() => {
                        content.innerHTML = '<div style="color:red;">Failed to load comments.</div>';
                    });
            }
        });
    </script>
</body>
</html>