<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['sind_id']) && (int)$_SESSION['sind_id'] > 0) {
    $sindId = (int)$_SESSION['sind_id'];
} elseif (isset($_POST['sind_id']) && ctype_digit($_POST['sind_id'])) {
    $sindId = (int)$_POST['sind_id'];
} else {
    echo json_encode(['range_text'=>'Not logged in','rows'=>[],'total_bp_sind'=>0]);
    exit;
}

$weekOffset = isset($_POST['week_offset']) ? (int)$_POST['week_offset'] : 0;

$today   = new DateTime('today');
$w       = (int)$today->format('N');      
$thisMon = (clone $today)->modify('-' . ($w - 1) . ' days'); 
$prevMon = (clone $thisMon)->modify('-1 week');              
if ($weekOffset !== 0) { $prevMon->modify($weekOffset . ' week'); }

$start = $prevMon;                         
$end   = (clone $start)->modify('+6 days'); 
$startStr = $start->format('Y-m-d');
$endStr   = $end->format('Y-m-d');
$rangeText = $start->format('D, d M Y') . '  →  ' . $end->format('D, d M Y');

$sql = "
    SELECT 
        b.booking_id,
        b.booking_date,
        b.booking_from_time,
        b.booking_to_time,
        b.booking_type,
        c.cust_name,
        COALESCE(b.bp_sind,0) AS bp_sind
    FROM bookings b
    JOIN customers c ON b.cust_id = c.cust_id
    WHERE b.sind_id = ?
      AND b.booking_date >= ? AND b.booking_date <= ?
      AND (b.booking_status = 'rated' OR b.booking_status = 'done')
    ORDER BY b.booking_date DESC, b.booking_from_time DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $sindId, $startStr, $endStr);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$total = 0.0;

while ($r = $res->fetch_assoc()) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $r['booking_date']);
    $bookingDateFmt = $dateObj ? $dateObj->format('Y-m-d (l)') : $r['booking_date'];

    $rows[] = [
        'booking_id'    => (int)$r['booking_id'],
        'booking_date'  => $bookingDateFmt,
        'from_time'     => substr($r['booking_from_time'], 0, 5),
        'to_time'       => substr($r['booking_to_time'], 0, 5),
        'service_type'  => ($r['booking_type'] === 'r') ? 'Recurring' : 'Ad-hoc',
        'customer_name' => $r['cust_name'],
        'bp_sind'       => (float)$r['bp_sind'],
    ];
    $total += (float)$r['bp_sind'];
}
$stmt->close();

echo json_encode([
    'range_text'    => $rangeText,
    'rows'          => $rows,
    'total_bp_sind' => round($total, 2),
    'debug'         => [
        'sind_id'    => $sindId,
        'week_offset'=> $weekOffset,
        'start'      => $startStr,
        'end'        => $endStr,
        'row_count'  => count($rows),
    ]
]);

