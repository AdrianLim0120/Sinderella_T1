<?php
// /cron/run.php — instant ACK + background HTTP call

const CRON_HTTP_KEY     = 'sinderella-run-123';        // change me
const CRON_INTERNAL_KEY = 'sinderella-internal-456';   // change me

if (($_GET['key'] ?? '') !== CRON_HTTP_KEY) {
  http_response_code(403);
  header('Cache-Control: no-store'); echo 'forbidden'; exit;
}

// quick ACK (so scheduler never times out)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
ignore_user_abort(true);
ob_start();
echo json_encode(['status'=>'accepted','ts'=>date('c')]);
$len = ob_get_length();
header('Content-Type: application/json');
header('Content-Length: '.$len);
header('Connection: close');
ob_end_flush(); flush();
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

// build background request to the real job
$host   = $_SERVER['HTTP_HOST'] ?? 'sinderellatesting.free.nf';
$port   = 443;
$scheme = 'https';

// pass through optional testing params (?date=..., &force=1)
$q = [];
if (isset($_GET['date']))  $q['date']  = $_GET['date'];
if (isset($_GET['force'])) $q['force'] = $_GET['force'];
$q['internal_key'] = CRON_INTERNAL_KEY;

$path = '/cron/remind_2days.php' . ($q ? ('?' . http_build_query($q)) : '');

// non-blocking socket, send GET, close
$errno = $errstr = null;
$fp = fsockopen('ssl://'.$host, $port, $errno, $errstr, 2);
if ($fp) {
  stream_set_timeout($fp, 1);
  $out  = "GET $path HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n\r\n";
  fwrite($fp, $out);
  fclose($fp);
}
