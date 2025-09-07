<?php
// cron/remind_2days.php
// Can be executed two ways:
//   A) Included by other PHP (pseudo-cron or /cron/run.php) => returns an array
//   B) Opened directly in the browser with ?internal_key=... (for debugging) => echoes JSON

declare(strict_types=1);

require_once __DIR__.'/../includes/whatsapp/client.php';
require_once __DIR__.'/../includes/whatsapp/helpers.php';
require_once __DIR__.'/../includes/whatsapp/config.php';
require_once __DIR__.'/../db_connect.php'; // provides mysqli $conn

/**
 * Run reminders for (today + 2 days) in Asia/Kuala_Lumpur,
 * or a specific ?date=YYYY-MM-DD when testing.
 *
 * $opts keys (all optional):
 *   - internal_key: string  // must match your secret unless ?date provided
 *   - date: YYYY-MM-DD      // testing override; when provided, bypass daily lock
 *   - force: bool           // if true, bypass daily lock
 *
 * Returns: array with target_date, sent_count, attempts, sent, etc.
 */
function remind_2days(array $opts = []): array {
  global $conn;

  $internalKey = $opts['internal_key'] ?? ($_GET['internal_key'] ?? null);
  $paramDate   = $opts['date']         ?? ($_GET['date']         ?? null);
  $force       = (bool)($opts['force'] ?? ($_GET['force']        ?? false));

  // Only allow public/URL access when internal_key is correct,
  // unless a test date is specified (so you can debug in browser).
  if ($internalKey !== 'sinderella-internal-456' && !isset($paramDate)) {
    http_response_code(403);
    return ['error'=>'forbidden'];
  }

  $tz = new DateTimeZone('Asia/Kuala_Lumpur');

  if ($paramDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $paramDate)) {
    return ['error'=>'Invalid ?date, use YYYY-MM-DD'];
  }

  // Target date = today+2 (MYT) unless overridden for testing
  $target = $paramDate ?: (new DateTime('now', $tz))->modify('+2 days')->format('Y-m-d');

  // Daily run-once lock for the "real" daily job (skip when testing or forced)
  if (!$paramDate && !$force) {
    $today = (new DateTime('now', $tz))->format('Y-m-d');
    $lockFile = __DIR__ . '/../logs/cron_remind_lock.txt';
    $last = @trim(@file_get_contents($lockFile));
    if ($last === $today) {
      return ['status'=>'skipped','reason'=>'already ran today','target_date'=>$target];
    }
    @file_put_contents($lockFile, $today);
  }

  // ----- FETCH CANDIDATES FOR target DATE -----
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
  $stmt->bind_param('s', $target);
  $stmt->execute();
  $res = $stmt->get_result();

  $attempts = [];
  $sent = [];

  while ($bk = $res->fetch_assoc()) {
    $row = [
      'booking_id' => (int)$bk['booking_id'],
      'date'       => $bk['booking_date'],
      'from_time'  => $bk['booking_from_time'],
      'to_time'    => $bk['booking_to_time'],
      'sinderella' => ['name'=>$bk['sind_name'], 'phone_raw'=>$bk['sind_phno']],
      'customer'   => ['name'=>$bk['cust_name'], 'phone_raw'=>$bk['cust_phno']],
      'address'    => $bk['full_address'],
      'skips'      => [],
    ];

    // Template variable order (must match your approved WA template):
    // 1 Service Date, 2 Time Range, 3 Sinderella, 4 Sinderella Phone,
    // 5 Customer Name, 6 Customer Phone, 7 Address
    $params = [
      fmt_date($bk['booking_date']),
      time_range($bk['booking_from_time'], $bk['booking_to_time']),
      $bk['sind_name'],
      phone_to_e164($bk['sind_phno']) ?: '',
      $bk['cust_name'],
      phone_to_e164($bk['cust_phno']) ?: '',
      $bk['full_address'],
    ];

    $toCustomer   = phone_to_e164($bk['cust_phno']);
    $toSinderella = phone_to_e164($bk['sind_phno']);
    $toAdmin      = defined('ADMIN_PHONE_E164') ? ADMIN_PHONE_E164 : null;

    if (!$toCustomer)   { $row['skips'][] = 'Missing/invalid customer phone'; }
    if (!$toSinderella) { $row['skips'][] = 'Missing/invalid sinderella phone'; }

    // If all are invalid (inc. no admin), just record attempt info
    if (!$toCustomer && !$toSinderella && !$toAdmin) {
      $attempts[] = $row;
      continue;
    }

    // Send approved template
    if ($toCustomer) {
      $row['customer_send'] = wa_send_template($toCustomer, WA_TEMPLATE_BOOKING_REMINDER, 'en', $params);
    }
    if ($toSinderella) {
      $row['sinderella_send'] = wa_send_template($toSinderella, WA_TEMPLATE_BOOKING_REMINDER, 'en', $params);
    }
    if ($toAdmin) {
      $row['admin_send'] = wa_send_template($toAdmin, WA_TEMPLATE_BOOKING_REMINDER, 'en', $params);
    }

    $sent[] = $row;
  }
  $stmt->close();

  return [
    'target_date' => $target,
    'found'       => count($attempts) + count($sent),
    'sent_count'  => count($sent),
    'attempts'    => $attempts,
    'sent'        => $sent,
  ];
}

// If opened directly via URL (for debug), emit JSON
if (!defined('RUN_INLINE')) {
  header('Content-Type: application/json');
  echo json_encode(remind_2days(), JSON_PRETTY_PRINT);
}
