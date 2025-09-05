<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['adm_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../db_connect.php'; 
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection not available (expected $conn MySQLi)']);
    exit;
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");

$action = $_GET['action'] ?? 'overview';

$chkJoin = "
  LEFT JOIN (
    SELECT booking_id,
           MAX(checkin_time)  AS checkin_time,
           MAX(checkout_time) AS checkout_time
    FROM booking_checkinout
    GROUP BY booking_id
  ) c ON c.booking_id = b.booking_id
";

/* -------------------- OVERVIEW (today KPIs) -------------------- */
if ($action === 'overview') {
    $sql = "
      SELECT
        SUM(bucket = 'ongoing')   AS ongoing,
        SUM(bucket = 'completed') AS completed,
        SUM(bucket = 'rated')     AS rated,
        SUM(bucket = 'pending')   AS pending,
        SUM(bucket = 'late')      AS late,
        SUM(bucket = 'overdue')   AS overdue
      FROM (
        SELECT
          b.booking_id,
          CASE
            WHEN EXISTS (SELECT 1 FROM booking_ratings r WHERE r.booking_id = b.booking_id)
            THEN 'rated'

            WHEN c.checkin_time IS NOT NULL
             AND c.checkout_time IS NOT NULL
            THEN 'completed'

            WHEN c.checkin_time IS NOT NULL
             AND c.checkout_time IS NULL
             AND CURRENT_TIME() > b.booking_to_time
            THEN 'overdue'

            WHEN c.checkin_time IS NOT NULL
             AND c.checkout_time IS NULL
            THEN 'ongoing'

            WHEN c.checkin_time IS NULL
             AND b.booking_status IN ('paid','confirm')
             AND CURRENT_TIME() > b.booking_from_time
            THEN 'late'

            WHEN c.checkin_time IS NULL
             AND b.booking_status IN ('paid','confirm')
             AND CURRENT_TIME() <= b.booking_from_time
            THEN 'pending'

            ELSE NULL
          END AS bucket
        FROM bookings b
        $chkJoin
        WHERE b.booking_status <> 'cancel'
          AND b.booking_date = '2025-08-25'   
      ) AS t
    ";

    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : [];
    echo json_encode(['ok' => true, 'data' => $row]);
    exit;
}

/* -------------------- RANGE (status counts) -------------------- */
if ($action === 'range') {
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;

    if (!$start || !$end || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date range']);
        exit;
    }

    $sql = "
    SELECT
      SUM(CASE WHEN b.booking_status='pending' THEN 1 ELSE 0 END) AS pending,
      SUM(CASE WHEN b.booking_status='paid'    THEN 1 ELSE 0 END) AS paid,
      SUM(CASE WHEN b.booking_status='confirm' THEN 1 ELSE 0 END) AS confirm,
      SUM(CASE WHEN b.booking_status='done'    THEN 1 ELSE 0 END) AS done,
      SUM(CASE WHEN b.booking_status='rated'   THEN 1 ELSE 0 END) AS rated
    FROM bookings b
    WHERE b.booking_date BETWEEN ? AND ?
  ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : [];
    echo json_encode(['ok' => true, 'data' => $row, 'range' => compact('start', 'end')]);
    exit;
}

/* -------------------- INSIGHTS (TOP 3 week/month) -------------------- */
if ($action === 'insights') {
    $period = $_GET['period'] ?? 'week'; // 'week' | 'month'
    $offset = (int)($_GET['offset'] ?? 0);

    $tz = new DateTimeZone('Asia/Kuala_Lumpur');
    $base = new DateTime('today', $tz);

    if ($period === 'month') {
        if ($offset !== 0) { $base->modify(($offset > 0 ? '+' : '') . $offset . ' month'); }
        $startDT = (clone $base)->modify('first day of this month');
        $endDT   = (clone $base)->modify('last day of this month');
        $label   = $startDT->format('M Y');
    } else {
        if ($offset !== 0) { $base->modify(($offset > 0 ? '+' : '') . $offset . ' week'); }
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
        ROUND(AVG(r.rate), 2) AS avg_rating,
        COUNT(*) AS rating_count
      FROM booking_ratings r
      JOIN bookings b        ON b.booking_id = r.booking_id
      LEFT JOIN sinderellas s ON s.sind_id   = b.sind_id
      WHERE b.booking_date BETWEEN ? AND ?
      GROUP BY b.sind_id, s.sind_name
      HAVING rating_count > 0
      ORDER BY avg_rating DESC, rating_count DESC, b.sind_id ASC
      LIMIT 3
    ";
    $stmt = $conn->prepare($sqlTopRating);
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    $topRating = [];
    if ($res) { while ($row = $res->fetch_assoc()) { $topRating[] = $row; } }

    /* --- Top 3 by SERVICE MINUTES --- */
    $sqlTopHours = "
      SELECT 
        b.sind_id,
        COALESCE(s.sind_name, CONCAT('Sinderella #', b.sind_id)) AS sind_name,
        FLOOR(SUM(GREATEST(TIME_TO_SEC(b.booking_to_time) - TIME_TO_SEC(b.booking_from_time), 0)) / 60) AS minutes
      FROM bookings b
      LEFT JOIN sinderellas s ON s.sind_id = b.sind_id
      WHERE b.booking_date BETWEEN ? AND ?
      AND b.booking_status IN ('done','rated')
      GROUP BY b.sind_id, s.sind_name
      HAVING minutes IS NOT NULL
      ORDER BY minutes DESC, b.sind_id ASC
      LIMIT 3
    ";
    $stmt = $conn->prepare($sqlTopHours);
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    $topHours = [];
    if ($res) { while ($row = $res->fetch_assoc()) { $topHours[] = $row; } }

    echo json_encode([
        'ok'        => true,
        'period'    => $period,
        'range'     => ['start' => $start, 'end' => $end, 'label' => $label],
        'topRating' => $topRating,
        'topHours'  => $topHours
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
