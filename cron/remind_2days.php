<?php
require_once __DIR__ . '/../includes/whatsapp/client.php';
require_once __DIR__ . '/../includes/whatsapp/helpers.php';
require_once __DIR__ . '/../db_connect.php'; // mysqli $conn

// Target date = today + 2 (MYT handled at DB or PHP layer; adjust if your DB uses UTC)
$target = (new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')))
    ->modify('+2 days')->format('Y-m-d');

$sql = "
SELECT
  b.booking_id, b.booking_date, b.booking_from_time, b.booking_to_time, b.full_address,
  si.sind_name, si.sind_phno,
  c.cust_name, c.cust_phno
FROM bookings b
JOIN sinderellas si ON si.sind_id     = b.sind_id
JOIN customers c    ON c.cust_id      = b.cust_id
WHERE b.booking_status IN ('confirm')
  AND b.booking_status <> 'cancel'
  AND b.booking_date = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $target);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($bk = $res->fetch_assoc()) {
    $params = [
        fmt_date($bk['booking_date']),
        time_range($bk['booking_from_time'], $bk['booking_to_time']),
        $bk['sind_name'],
        phone_to_e164($bk['sind_phno']) ?: $bk['sind_phno'],
        $bk['cust_name'],
        phone_to_e164($bk['cust_phno']) ?: $bk['cust_phno'],
        $bk['full_address'],
    ];
    $toCustomer = phone_to_e164($bk['cust_phno']);
    $toSinderella = phone_to_e164($bk['sind_phno']);
    $toAdmin = ADMIN_PHONE_E164 ?: null;

    $row = ['booking_id' => $bk['booking_id']];
    if ($toCustomer)
        $row['customer'] = wa_send_template($toCustomer, WA_TEMPLATE_BOOKING_REMINDER, 'en', $params);
    if ($toSinderella)
        $row['sinderella'] = wa_send_template($toSinderella, WA_TEMPLATE_BOOKING_REMINDER, 'en', $params);
    if ($toAdmin)
        $row['admin'] = wa_send_template($toAdmin, WA_TEMPLATE_BOOKING_REMINDER, 'en', $params);

    $out[] = $row;
}
$stmt->close();

header('Content-Type: application/json');
echo json_encode(['target_date' => $target, 'sent' => $out], JSON_PRETTY_PRINT);
