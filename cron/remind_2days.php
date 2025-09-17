<?php
// /cron/remind_2days.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../db_connect.php';      // existing DB conn
require_once __DIR__ . '/lib/wa.php';             // wa_send_template, build_booking_components, format_msisdn
require_once __DIR__ . '/../config/whatsapp.php'; // constants + TZ

// --- Access control ---
$providedKey = $_GET['internal_key'] ?? $_GET['key'] ?? '';
if ($providedKey !== INTERNAL_KEY && $providedKey !== CRON_HTTP_KEY) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

// --- Controls / testing flags ---
$dryrun        = isset($_GET['dryrun']) && $_GET['dryrun'] == '1';
$force         = isset($_GET['force'])  && $_GET['force']  == '1';
$targetDateStr = $_GET['date'] ?? date('Y-m-d', strtotime('+' . REMIND_DAYS_AHEAD . ' days'));

// --- Fetch confirmed bookings for target date ---
$sql = "
SELECT
    b.booking_id, b.booking_date, b.booking_from_time, b.booking_to_time, b.full_address,
    si.sind_name, si.sind_phno,
    c.cust_name, c.cust_phno
FROM bookings b
JOIN sinderellas si ON si.sind_id = b.sind_id
JOIN customers  c  ON c.cust_id   = b.cust_id
WHERE b.booking_status = 'confirm'
  AND b.booking_date   = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $targetDateStr);
$stmt->execute();
$res  = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

$now         = date('c');
$total_msgs  = 0;      // total attempted sends (all recipients)
$sent_ok     = 0;
$sent_failed = 0;
$items       = [];

foreach ($rows as $r) {
    // Build the template once for this booking
    $components = build_booking_components($r);

    // Collect recipients: customer, sinderella, and all admins
    $recipients = [];

    // customer
    $cust = format_msisdn($r['cust_phno'] ?? null);
    if ($cust) $recipients['customer|'.$cust] = $cust;

    // sinderella/worker
    $sind = format_msisdn($r['sind_phno'] ?? null);
    if ($sind) $recipients['sinderella|'.$sind] = $sind;

    // admins
    if (!empty(ADMIN_NUMBERS)) {
        foreach (ADMIN_NUMBERS as $admRaw) {
            $adm = format_msisdn($admRaw);
            if ($adm) $recipients['admin|'.$adm] = $adm;
        }
    }

    // Send to each (deduped by key)
    foreach ($recipients as $key => $to) {
        [$role] = explode('|', $key, 2);
        $total_msgs++;

        if ($dryrun) {
            $items[] = [
                'booking_id' => $r['booking_id'],
                'role'       => $role,
                'to'         => $to,
                'ok'         => true,
                'dryrun'     => true,
            ];
            $sent_ok++;
            continue;
        }

        $resp = wa_send_template($to, WA_TEMPLATE_NAME, WA_LANG_CODE, $components);

        $items[] = [
            'booking_id' => $r['booking_id'],
            'role'       => $role,
            'to'         => $to,
            'ok'         => $resp['ok'],
            'http_code'  => $resp['http_code'],
            'error'      => $resp['error'],
            'response'   => $resp['response'],
        ];

        if ($resp['ok']) $sent_ok++; else $sent_failed++;

        // gentle throttle to avoid burst
        usleep(WHATSAPP_SEND_DELAY_US);
    }
}

echo json_encode([
    'ok'            => true,
    'now'           => $now,
    'target_date'   => $targetDateStr,
    'bookings'      => count($rows),
    'messages_tried'=> $total_msgs,
    'sent_ok'       => $sent_ok,
    'sent_failed'   => $sent_failed,
    'details'       => $items,
], JSON_UNESCAPED_UNICODE);
