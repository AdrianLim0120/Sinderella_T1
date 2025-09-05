<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['adm_id'])) {
    echo json_encode(['range_text'=>'Not authorized','rows'=>[]]);
    exit();
}

$weekOffset = isset($_POST['week_offset']) ? (int)$_POST['week_offset'] : 0;

$today   = new DateTime('today');
$w       = (int)$today->format('N');                        
$thisMon = (clone $today)->modify('-' . ($w - 1) . ' days'); 
$prevMon = (clone $thisMon)->modify('-1 week');              
if ($weekOffset !== 0) { $prevMon->modify(($weekOffset > 0 ? '+' : '').$weekOffset.' week'); }

$start = $prevMon;
$end   = (clone $start)->modify('+6 days');

$startStr  = $start->format('Y-m-d');
$endStr    = $end->format('Y-m-d');
$rangeText = $start->format('D, d M Y') . '  →  ' . $end->format('D, d M Y');

$sql = "
    SELECT
        s.sind_id,
        s.sind_name,
        s.sind_phno,
        COUNT(b.booking_id) AS bookings,
        SUM(COALESCE(b.bp_sind,0)) AS total_bp_sind
    FROM sinderellas s
    JOIN bookings b ON b.sind_id = s.sind_id
    WHERE b.booking_date >= ? AND b.booking_date <= ?
    AND b.booking_status IN ('done','rated')
    GROUP BY s.sind_id, s.sind_name
    ORDER BY total_bp_sind DESC, s.sind_name ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['range_text'=>$rangeText,'rows'=>[], 'error'=>'Prepare failed: '.$conn->error]);
    exit;
}
$stmt->bind_param('ss', $startStr, $endStr);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = [
        'sind_id'   => (int)$r['sind_id'],
        'sind_name' => $r['sind_name'],
        'phone_no'  => $r['sind_phno'],
        'bookings'  => (int)$r['bookings'],
        'total'     => (float)$r['total_bp_sind'],
    ];
}
$stmt->close();

echo json_encode([
    'range_text' => $rangeText,
    'start'      => $startStr,
    'end'        => $endStr,
    'rows'       => $rows
]);
