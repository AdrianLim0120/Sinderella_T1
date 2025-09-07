<?php
// includes/pseudocron.php
// Triggers the daily reminder after 08:00 MYT on the first real page view.
// Creates a simple "last run" flag so it only fires once per day.

declare(strict_types=1);

$tz  = new DateTimeZone('Asia/Kuala_Lumpur');
$now = new DateTime('now', $tz);

// Only after 08:00 local time to match your requirement
if ((int)$now->format('G') >= 8) {

  $logDir = __DIR__ . '/../logs';
  if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
  }

  $flagFile = $logDir . '/last_pseudocron.txt';
  $today    = $now->format('Y-m-d');
  $last     = @trim(@file_get_contents($flagFile));

  if ($last !== $today) {
    // Run the real job inline
    define('RUN_INLINE', true);
    require_once __DIR__ . '/../cron/remind_2days.php';

    $result = remind_2days([
      // use your internal key so direct web hits still need a secret
      'internal_key' => 'sinderella-internal-456'
      // no 'date' passed -> script will use today+2 (MYT)
    ]);

    // Mark done for today
    @file_put_contents($flagFile, $today);

    // Optional: append a tiny log line
    @file_put_contents(
      $logDir . '/pseudocron.log',
      sprintf("%s target=%s sent=%s\n", date('c'), $result['target_date'] ?? '-', $result['sent_count'] ?? 0),
      FILE_APPEND
    );
  }
}
