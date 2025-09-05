<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../db_connect.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(['error' => 'DB connection not available (expected $conn MySQLi)']);
  exit;
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");

$period = $_GET['period'] ?? 'week'; // 'week' | 'month'
$offset = (int)($_GET['offset'] ?? 0);

$tz   = new DateTimeZone('Asia/Kuala_Lumpur');
$base = new DateTime('today', $tz);

if ($period === 'month') {
  if ($offset !== 0) $base->modify(($offset > 0 ? '+' : '') . $offset . ' month');
  $startDT = (clone $base)->modify('first day of this month');
  $endDT   = (clone $base)->modify('last day of this month');
  $label   = $startDT->format('M Y');
} else {
  if ($offset !== 0) $base->modify(($offset > 0 ? '+' : '') . $offset . ' week');
  $startDT = (clone $base)->modify('monday this week');
  $endDT   = (clone $base)->modify('sunday this week');
  $label   = $startDT->format('d M') . ' – ' . $endDT->format('d M');
}

$start = $startDT->format('Y-m-d');
$end   = $endDT->format('Y-m-d');

/* --- Top 3 by AVERAGE rating --- */
$sqlTopRating = "
  SELECT 
    b.sind_id,
    COALESCE(s.sind_name, CONCAT('Sinderella #', b.sind_id)) AS sind_name,
    s.sind_profile_path,
    ROUND(AVG(r.rate), 2) AS avg_rating,
    COUNT(*) AS rating_count
  FROM booking_ratings r
  JOIN bookings b         ON b.booking_id = r.booking_id
  LEFT JOIN sinderellas s ON s.sind_id    = b.sind_id
  WHERE b.booking_status <> 'cancel'
    AND b.booking_date BETWEEN ? AND ?
  GROUP BY b.sind_id, s.sind_name, s.sind_profile_path
  HAVING rating_count > 0
  ORDER BY avg_rating DESC, rating_count DESC, b.sind_id ASC
  LIMIT 3
";
$stmt = $conn->prepare($sqlTopRating);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$topRating = [];
if ($res) while ($row = $res->fetch_assoc()) $topRating[] = $row;

/* --- Top 3 by SERVICE MINUTES (scheduled duration) --- */
$sqlTopHours = "
  SELECT 
    b.sind_id,
    COALESCE(s.sind_name, CONCAT('Sinderella #', b.sind_id)) AS sind_name,
    s.sind_profile_path,
    FLOOR(SUM(GREATEST(TIME_TO_SEC(b.booking_to_time) - TIME_TO_SEC(b.booking_from_time), 0)) / 60) AS minutes
  FROM bookings b
  LEFT JOIN sinderellas s ON s.sind_id = b.sind_id
  WHERE b.booking_status <> 'cancel'
    AND b.booking_date BETWEEN ? AND ?
  GROUP BY b.sind_id, s.sind_name, s.sind_profile_path
  HAVING minutes IS NOT NULL
  ORDER BY minutes DESC, b.sind_id ASC
  LIMIT 3
";
$stmt = $conn->prepare($sqlTopHours);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$topHours = [];
if ($res) while ($row = $res->fetch_assoc()) $topHours[] = $row;

echo json_encode([
  'ok'        => true,
  'period'    => $period,
  'range'     => ['start' => $start, 'end' => $end, 'label' => $label],
  'topRating' => $topRating, 
  'topHours'  => $topHours   
]);
