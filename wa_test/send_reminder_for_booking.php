<?php
// http://127.0.0.1/Sinderella_FYP/wa_test/send_reminder_for_booking.php?booking_id=17
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/whatsapp/client.php';
require_once __DIR__ . '/../includes/whatsapp/helpers.php';

$bookingId = (int) ($_GET['booking_id'] ?? 0);
if (!$bookingId) {
  echo "Usage: ?booking_id=17";
  exit;
}

$sql = "
SELECT
  b.booking_id, b.booking_date, b.booking_from_time, b.booking_to_time, b.full_address,
  si.sind_name, si.sind_phno,
  c.cust_name, c.cust_phno
FROM bookings b
JOIN sinderellas si ON si.sind_id     = b.sind_id
JOIN customers c    ON c.cust_id      = b.cust_id
WHERE b.booking_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $bookingId);
$stmt->execute();
$bk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bk) {
  echo "Booking not found.";
  exit;
}

// Build the 8 template variables in the exact order shown above
$params = [
  fmt_date($bk['booking_date']),                         // {{2}}
  time_range($bk['booking_from_time'], $bk['booking_to_time']), // {{3}}
  $bk['sind_name'],                                      // {{4}}
  phone_to_e164($bk['sind_phno']),                       // {{5}}
  $bk['cust_name'],                                      // {{6}}
  phone_to_e164($bk['cust_phno']),                       // {{7}}
  $bk['full_address'],                                   // {{8}}
];

// Who to send
$toCustomer = phone_to_e164($bk['cust_phno']);
$toSinderella = phone_to_e164($bk['sind_phno']);
$toAdmin = ADMIN_PHONE_E164 ?: null;

$sent = [];
if ($toCustomer)
  $sent['customer'] = wa_send_template($toCustomer, WA_TEMPLATE_BOOKING_REMINDER, 'en', $params);
if ($toSinderella)
  $sent['sinderella'] = wa_send_template($toSinderella, WA_TEMPLATE_BOOKING_REMINDER, 'en', $params);
if ($toAdmin)
  $sent['admin'] = wa_send_template($toAdmin, WA_TEMPLATE_BOOKING_REMINDER, 'en', $params);

header('Content-Type: application/json');
echo json_encode(['booking' => $bookingId, 'params' => $params, 'results' => $sent], JSON_PRETTY_PRINT);
