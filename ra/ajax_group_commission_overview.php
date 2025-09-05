<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['adm_id'])) {
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

$offset = isset($_POST['month_offset']) ? (int) $_POST['month_offset'] : 0;

$first = new DateTime('first day of this month 00:00:00');
if ($offset !== 0) {
    $first->modify(($offset > 0 ? '+' : '') . $offset . ' month');
}
$last = (clone $first);
$last->modify('last day of this month 23:59:59');

$start = $first->format('Y-m-d H:i:s');
$end = $last->format('Y-m-d H:i:s');

$rangeText = $first->format('D, d M Y') . ' → ' . $last->format('D, d M Y');
$monthText = $first->format('F Y');
$maxOffset = 0;

$sql = "
        SELECT s.sind_id,
               s.sind_name,
               ROUND(SUM(x.amount), 2) AS total
        FROM (
            SELECT b.bp_lvl1_sind_id AS sind_id, SUM(COALESCE(b.bp_lvl1_amount,0)) AS amount
            FROM bookings b
            WHERE b.booking_date >= ? AND b.booking_date <= ?
              AND b.bp_lvl1_sind_id IS NOT NULL
              AND b.booking_status IN ('done','rated')
            GROUP BY b.bp_lvl1_sind_id

            UNION ALL

            SELECT b.bp_lvl2_sind_id AS sind_id, SUM(COALESCE(b.bp_lvl2_amount,0)) AS amount
            FROM bookings b
            WHERE b.booking_date >= ? AND b.booking_date <= ?
              AND b.bp_lvl2_sind_id IS NOT NULL
              AND b.booking_status IN ('done','rated')
            GROUP BY b.bp_lvl2_sind_id
        ) x
        INNER JOIN sinderellas s ON s.sind_id = x.sind_id
        GROUP BY s.sind_id, s.sind_name
        ORDER BY total DESC
    ";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $start, $end, $start, $end);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = [
        'sind_id' => (int) $r['sind_id'],
        'sind_name' => $r['sind_name'],
        'total' => (float) $r['total']
    ];
}
$stmt->close();

echo json_encode([
    'range_text' => $rangeText,
    'month_text' => $monthText,
    'rows' => $rows,
    'max_offset' => $maxOffset
]);
