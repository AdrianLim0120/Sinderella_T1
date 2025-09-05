<?php
session_start();
require_once '../db_connect.php';
header('Content-Type: application/json; charset=utf-8');

if (isset($_POST['sind_id']) && ctype_digit($_POST['sind_id'])) {
    $sindId = (int) $_POST['sind_id'];
} elseif (isset($_SESSION['sind_id']) && (int) $_SESSION['sind_id'] > 0) {
    $sindId = (int) $_SESSION['sind_id'];
} else {
    echo json_encode(['error' => 'Not logged in', 'rows' => [], 'total_amount' => 0]);
    exit;
}

$monthOffset = isset($_POST['month_offset']) ? (int) $_POST['month_offset'] : 0;

$today = new DateTime('today');

$thisMonthFirst = (new DateTime(date('Y-m-01')));   // first of current month
$baseMonth = (clone $thisMonthFirst)->modify('-1 month'); // previous month

if ($monthOffset !== 0) {
    $baseMonth->modify(($monthOffset > 0 ? '+' : '') . $monthOffset . ' month');
}

$start = (clone $baseMonth)->modify('first day of this month');
$end = (clone $baseMonth)->modify('last day of this month');

$startStr = $start->format('Y-m-d');
$endStr = $end->format('Y-m-d');
$rangeText = $start->format('D, d M Y') . '  →  ' . $end->format('D, d M Y');

$sql = "
    SELECT
        b.booking_id,
        b.booking_date,
        b.booking_from_time,
        b.booking_to_time,
        b.booking_type,
        c.cust_name,
        s.sind_name AS downline_name,
        CASE
            WHEN b.bp_lvl1_sind_id = ? THEN 'Level 1'
            WHEN b.bp_lvl2_sind_id = ? THEN 'Level 2'
            ELSE ''
        END AS level_label,
        CASE
            WHEN b.bp_lvl1_sind_id = ? THEN COALESCE(b.bp_lvl1_amount,0)
            WHEN b.bp_lvl2_sind_id = ? THEN COALESCE(b.bp_lvl2_amount,0)
            ELSE 0
        END AS commission_amount
    FROM bookings b
    JOIN customers   c ON b.cust_id = c.cust_id
    JOIN sinderellas s ON s.sind_id = b.sind_id
    WHERE (b.bp_lvl1_sind_id = ? OR b.bp_lvl2_sind_id = ?)
      AND b.booking_date >= ? AND b.booking_date <= ?
      AND (b.booking_status = 'rated' OR b.booking_status = 'done')
    ORDER BY b.booking_date ASC, b.booking_from_time ASC, b.booking_id ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error, 'rows' => [], 'total_amount' => 0, 'range_text' => $rangeText, 'start' => $startStr, 'end' => $endStr]);
    exit;
}

if (
    !$stmt->bind_param(
        "iiiiiiss",
        $sindId,
        $sindId,   
        $sindId,
        $sindId,  
        $sindId,
        $sindId,  
        $startStr,
        $endStr  
    )
) {
    echo json_encode(['error' => 'bind_param failed: ' . $stmt->error, 'rows' => [], 'total_amount' => 0, 'range_text' => $rangeText, 'start' => $startStr, 'end' => $endStr]);
    exit;
}

if (!$stmt->execute()) {
    echo json_encode(['error' => 'Execute failed: ' . $stmt->error, 'rows' => [], 'total_amount' => 0, 'range_text' => $rangeText, 'start' => $startStr, 'end' => $endStr]);
    exit;
}

$res = $stmt->get_result();

$rows = [];
$total = 0.0;

while ($r = $res->fetch_assoc()) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $r['booking_date']);
    $dateFmt = $dateObj ? $dateObj->format('Y-m-d (l)') : $r['booking_date'];

    $rows[] = [
        'booking_id' => (int) $r['booking_id'],
        'booking_date' => $dateFmt,
        'from_time' => substr($r['booking_from_time'], 0, 5),
        'to_time' => substr($r['booking_to_time'], 0, 5),
        'downline' => $r['downline_name'],
        'level' => $r['level_label'],
        'service_type' => ($r['booking_type'] === 'r') ? 'Recurring' : 'Ad-hoc',
        'customer_name' => $r['cust_name'],
        'amount' => (float) $r['commission_amount'],
    ];
    $total += (float) $r['commission_amount'];
}
$stmt->close();

echo json_encode([
    'range_text' => $rangeText,
    'start' => $startStr,     
    'end' => $endStr,
    'rows' => $rows,
    'total_amount' => round($total, 2),
]);
