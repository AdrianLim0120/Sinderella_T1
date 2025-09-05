<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['status_id']) && isset($_GET['order_id'])) {
    $status = (int)$_GET['status_id']; // 1 = success
    $orderRaw = trim((string)$_GET['order_id']); 

    $parts = preg_split('/[-,]/', $orderRaw);
    $bookingIds = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '' && ctype_digit($p)) {
            $bookingIds[] = (int)$p;
        }
    }
    $bookingIds = array_values(array_unique($bookingIds));

    if (empty($bookingIds)) {
        echo "<h2>Invalid order id.</h2>";
        exit;
    }

    require_once '../db_connect.php'; 

    if ($status === 1) {
        // --- First pass per booking: mark payment row + ensure available_time
        foreach ($bookingIds as $booking_id) {
            // booking_payments -> confirmed
            if ($stmt = $conn->prepare("UPDATE booking_payments SET payment_status='confirmed' WHERE booking_id = ?")) {
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $stmt->close();
            }

            // Ensure sind_available_time row exists for the maid on the booking date
            $bookingRes = $conn->prepare("SELECT sind_id, booking_date FROM bookings WHERE booking_id = ? LIMIT 1");
            $bookingRes->bind_param("i", $booking_id);
            $bookingRes->execute();
            $bookingRes->bind_result($sind_id, $booking_date);
            if ($bookingRes->fetch()) {
                $bookingRes->close();

                $day_of_week = date('l', strtotime($booking_date)); 

                // Check exists
                $checkRes = $conn->prepare("SELECT schedule_id FROM sind_available_time WHERE sind_id = ? AND available_date = ? LIMIT 1");
                $checkRes->bind_param("is", $sind_id, $booking_date);
                $checkRes->execute();
                $checkRes->store_result();
                $exists = $checkRes->num_rows > 0;
                $checkRes->close();

                if (!$exists) {
                    // Pull default hours from sind_available_day
                    $dayRes = $conn->prepare("SELECT available_from1, available_from2 FROM sind_available_day WHERE sind_id = ? AND day_of_week = ? LIMIT 1");
                    $dayRes->bind_param("is", $sind_id, $day_of_week);
                    $dayRes->execute();
                    $dayRes->bind_result($af1, $af2);
                    $hasDay = $dayRes->fetch();
                    $dayRes->close();

                    if ($hasDay) {
                        // Convert '00:00:00' to NULL as in your original
                        $from1 = ($af1 && $af1 !== '00:00:00') ? "'{$af1}'" : "NULL";
                        $from2 = ($af2 && $af2 !== '00:00:00') ? "'{$af2}'" : "NULL";
                        $conn->query("INSERT INTO sind_available_time (sind_id, available_date, available_from1, available_from2)
                                      VALUES ($sind_id, '{$booking_date}', $from1, $from2)");
                    }
                }
            } else {
                $bookingRes->close();
            }
        }

        // ========= PAYMENT RECEIVED HANDLER (now loops over all bookings) =========
        $conn->begin_transaction();

        try {
            foreach ($bookingIds as $booking_id) {
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
                if (!$stmt->fetch()) {
                    $stmt->close();
                    throw new Exception('Booking not found (ID: '.$booking_id.')');
                }
                $stmt->close();

                // Already paid? skip this ID but keep processing others
                if (strtolower((string)$b_status_now) === 'paid') {
                    continue;
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
                    $p_lvl1        = (float)$p_lvl1_raw;
                    $p_lvl2        = (float)$p_lvl2_raw;
                }
                $stmt->close();

                // 3) Sum add-ons (type-aware)
                $add_total = $add_platform = $add_sind = 0.0;
                $stmt = $conn->prepare("
                    SELECT
                        COALESCE(SUM(CASE WHEN ?='a' THEN ao.ao_price       ELSE ao.ao_price_recurring     END), 0) AS add_total,
                        COALESCE(SUM(CASE WHEN ?='a' THEN ao.ao_platform   ELSE ao.ao_platform_recurring END), 0) AS add_platform,
                        COALESCE(SUM(CASE WHEN ?='a' THEN ao.ao_sind       ELSE ao.ao_sind_recurring     END), 0) AS add_sind
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

                // 5) Resolve uplines
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

                // B: upline of A (if any)
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

                // 7) Persist booking
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
                // allow NULLs for *_sind_id and *_amount
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

                if (!$ok) {
                    throw new Exception('Failed to update booking '.$booking_id);
                }
            } 

            $conn->commit();

        } catch (Throwable $e) {
            $conn->rollback();
            echo "<script>
                alert('Payment succeeded but update failed: ".htmlspecialchars($e->getMessage(), ENT_QUOTES)."');
                window.location.href = 'my_booking.php?search_date=&search_status=pending';
            </script>";
            exit;
        }

        echo "<script>
            alert('🎉 Payment Successful! Your booking(s) are paid.');
            window.location.href = 'my_booking.php?search_date=&search_status=paid';
        </script>";

    } else {
        echo "<script>
            alert('❌ Payment Failed or Cancelled.');
            window.location.href = 'my_booking.php?search_date=&search_status=pending';
        </script>";
    }

} else {
    echo "<h2>Invalid access.</h2>";
}
