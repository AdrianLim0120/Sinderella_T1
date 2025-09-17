<?php
// request_otp.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_connect.php';         // your existing DB connector ($conn)
require_once __DIR__ . '/cron/lib/wa.php';        // helpers above
require_once __DIR__ . '/config/whatsapp.php';

function json_out(array $x) { echo json_encode($x, JSON_UNESCAPED_UNICODE); exit; }
function gen_otp(int $len = OTP_LENGTH): string {
    $min = (int) str_pad('1', $len, '0');  // e.g. 100000
    $max = (int) str_pad('',  $len, '9');  // e.g. 999999
    return (string) random_int($min, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'POST only']);
}

$rawPhone = $_POST['phone'] ?? '';
$clean    = preg_replace('/\s+|-/', '', $rawPhone);
$to       = format_msisdn($clean);
if (!$to) json_out(['ok' => false, 'error' => 'Invalid Malaysian phone number. Use 01XXXXXXXX.']);

try {
    $q1 = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()) AS sec_since_last
                          FROM verification_codes WHERE user_phno = ?");
    $q1->bind_param('s', $clean);
    $q1->execute();
    $r1 = $q1->get_result()->fetch_assoc();
    $secSinceLast = (int)($r1['sec_since_last'] ?? 999999);
    if ($secSinceLast >= 0 && $secSinceLast < OTP_RATE_LIMIT_SECONDS) {
        json_out(['ok' => false, 'error' => 'Please wait a bit before requesting another code.']);
    }

    $q2 = $conn->prepare("SELECT COUNT(*) AS c FROM verification_codes 
                          WHERE user_phno = ? AND created_at > (NOW() - INTERVAL 1 DAY)");
    $q2->bind_param('s', $clean);
    $q2->execute();
    $r2 = $q2->get_result()->fetch_assoc();
    if ((int)$r2['c'] >= OTP_DAILY_LIMIT) {
        json_out(['ok' => false, 'error' => 'Daily OTP request limit reached. Try again tomorrow.']);
    }
} catch (\Throwable $e) {
    json_out(['ok' => false, 'error' => 'Rate-limit check failed: '.$e->getMessage()]);
}

$otp = gen_otp();
try {
    $stmt = $conn->prepare("INSERT INTO verification_codes
        (user_phno, ver_code, created_at, expires_at, used)
        VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE), 0)");
    $stmt->bind_param('sii', $clean, $otp, $ttl);
    $ttl = OTP_TTL_MINUTES;
    $stmt->execute();
    $stmt->close();
} catch (\Throwable $e) {
    json_out(['ok' => false, 'error' => 'Failed to store OTP: '.$e->getMessage()]);
}

$send = wa_send_otp($to, $otp);
if (!$send['ok']) {
    json_out(['ok' => false, 'error' => 'WhatsApp send failed', 'details' => $send]);
}

json_out(['ok' => true, 'message' => 'OTP sent via WhatsApp.', 'ttl_min' => OTP_TTL_MINUTES]);
