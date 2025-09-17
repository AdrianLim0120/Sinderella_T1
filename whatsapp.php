<?php
// /whatsapp.php — webhook verify + Accept/Reject + reason capture with robust logging & correctness
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kuala_Lumpur');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/config/whatsapp.php';
require_once __DIR__ . '/cron/lib/wa.php'; // wa_send_text(), wa_send_template_body(), wa_messages_url()

/** MUST equal the Verify token you set in Meta → Webhooks */
const WEBHOOK_VERIFY_TOKEN = 'webhooks_verify_789';

/** Optional fallback template if plain text prompt fails */
const REJECT_REASON_TEMPLATE = 'sinderella_reject_reason_prompt'; // body: "Please reply with one message stating your reason for rejecting booking {{1}}."

/* ------------------------- logging ------------------------- */
$LOG_DIR  = __DIR__ . '/logs';
$LOG_FILE = $LOG_DIR . '/wa_webhook.log';
if (!is_dir($LOG_DIR)) @mkdir($LOG_DIR, 0775, true);
if (!is_dir($LOG_DIR) || !is_writable($LOG_DIR)) {
    $LOG_FILE = rtrim(sys_get_temp_dir(), '/\\') . '/wa_webhook.log';
}
function wlog($label, $data = null): void {
    global $LOG_FILE;
    $line = '[' . date('c') . '] ' . $label;
    if ($data !== null) $line .= ' ' . (is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE));
    $line .= PHP_EOL;
    @file_put_contents($LOG_FILE, $line, FILE_APPEND);
}

/* quick debug endpoints */
if (isset($_GET['ping'])) {
    wlog('PING', $_GET);
    header('Content-Type: text/plain; charset=utf-8');
    echo "pong " . date('c') . "\nlog: $LOG_FILE";
    exit;
}
if (isset($_GET['tail']) && ($_GET['key'] ?? '') === 'see-log-now') {
    header('Content-Type: text/plain; charset=utf-8');
    if (file_exists($LOG_FILE)) readfile($LOG_FILE); else echo "No log yet ($LOG_FILE)";
    exit;
}

/* ------------------------- VERIFY (GET) ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode        = $_GET['hub.mode']         ?? $_GET['hub_mode']         ?? null;
    $verifyToken = $_GET['hub.verify_token'] ?? $_GET['hub_verify_token'] ?? null;
    $challenge   = $_GET['hub.challenge']    ?? $_GET['hub_challenge']    ?? null;

    wlog('VERIFY_GET', ['mode'=>$mode, 'vt'=>$verifyToken, 'challenge'=>$challenge]);

    if ($mode === 'subscribe' && $verifyToken === WEBHOOK_VERIFY_TOKEN && $challenge !== null) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $challenge;
        exit;
    }
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

/* ------------------------- helpers ------------------------- */
function normalize_db_phone(?string $s): string {
    $d = preg_replace('/\D+/', '', (string)$s);
    if ($d === '') return '';
    if (strpos($d, '00') === 0) $d = substr($d, 2);  // drop leading 00 if present
    if (strpos($d, '60') === 0) return $d;           // already MY in E.164 (no plus)
    if ($d[0] === '0') return '60' . substr($d, 1);  // local MY -> add 60
    if ($d[0] !== '6') return '60' . $d;             // anything else -> assume MY
    return $d;
}
function find_sind_by_phone(mysqli $conn, string $fromNo): ?int {
    $rs = $conn->query("SELECT sind_id, sind_phno FROM sinderellas");
    if (!$rs) { wlog('ERR find_sind_by_phone query', $conn->error); return null; }
    while ($row = $rs->fetch_assoc()) {
        if (normalize_db_phone($row['sind_phno']) === $fromNo) return (int)$row['sind_id'];
    }
    return null;
}
function resolve_map(mysqli $conn, string $contextId): array {
    $booking_id = null; $sind_id = null; $prevDecision = null; $processedAt = null;
    $sel = $conn->prepare("SELECT booking_id, sind_id, decision, processed_at
                           FROM wa_outbound_map WHERE wa_message_id = ? LIMIT 1");
    if (!$sel) { wlog('ERR resolve_map prepare', $conn->error); return [null,null,null,null]; }
    $sel->bind_param('s', $contextId);
    $sel->execute();
    $sel->bind_result($booking_id, $sind_id, $prevDecision, $processedAt);
    $sel->fetch(); $sel->close();
    return [$booking_id, $sind_id, $prevDecision, $processedAt];
}
function latest_open_booking_for_sind(mysqli $conn, int $sind_id): ?int {
    $id = null;
    $q = $conn->prepare("SELECT booking_id
                         FROM bookings
                         WHERE sind_id=? AND booking_status IN ('paid','pending')
                         ORDER BY booking_id DESC LIMIT 1");
    if (!$q) { wlog('ERR latest_open_booking prepare', $conn->error); return null; }
    $q->bind_param('i', $sind_id);
    $q->execute();
    $q->bind_result($id);
    $q->fetch();
    $q->close();
    return $id ?: null;
}
/** Update wa_outbound_map safely */
function set_map(mysqli $conn, string $contextId, string $decision): void {
    $m = $conn->prepare("UPDATE wa_outbound_map
                         SET decision=?, processed_at=NOW()
                         WHERE wa_message_id=?");
    if ($m) {
        $m->bind_param('ss', $decision, $contextId);
        $m->execute();
        wlog('MAP_SET', ['context'=>$contextId,'decision'=>$decision,'aff'=>$m->affected_rows]);
        $m->close();
    } else {
        wlog('ERR map update', $conn->error);
    }
}

/** Start the reject flow: change booking, write hist + pending marker, prompt for reason */
function start_reject_flow(mysqli $conn, int $booking_id, int $sind_id, string $to_no_plus, ?string $contextId = null): void {
    $conn->begin_transaction();
    try {
        // 1) bookings -> rejected
        $u = $conn->prepare("UPDATE bookings
                             SET booking_status='rejected'
                             WHERE booking_id=? AND sind_id=? AND booking_status IN ('paid','pending')");
        if (!$u) throw new Exception('prepare bookings: '.$conn->error);
        $u->bind_param('ii', $booking_id, $sind_id);
        if (!$u->execute()) throw new Exception('exec bookings: '.$u->error);
        $affected = $u->affected_rows; $u->close();
        wlog('BOOKING_REJECTED', ['booking_id'=>$booking_id,'sind_id'=>$sind_id,'affected'=>$affected]);

        // 2) history row — try NULL first; if table disallows NULL, fallback to empty string (no rollback)
        $h = $conn->prepare("INSERT INTO sind_rejected_hist (sind_id, booking_id, reason, created_at)
                             VALUES (?, ?, NULL, NOW())");
        if (!$h) throw new Exception('prepare hist: '.$conn->error);
        if (!$h->bind_param('ii', $sind_id, $booking_id)) throw new Exception('bind hist: '.$h->error);
        if (!$h->execute()) {
            wlog('HIST_NULL_FAILED_RETRY_EMPTY', $h->error);
            $h->close();
            $h2 = $conn->prepare("INSERT INTO sind_rejected_hist (sind_id, booking_id, reason, created_at)
                                  VALUES (?, ?, '', NOW())");
            if (!$h2) throw new Exception('prepare hist2: '.$conn->error);
            $h2->bind_param('ii', $sind_id, $booking_id);
            if (!$h2->execute()) throw new Exception('exec hist2: '.$h2->error);
            $h2->close();
        } else {
            $h->close();
        }

        // 3) pending reason marker
        $w = $conn->prepare("INSERT IGNORE INTO wa_pending_reason (sind_id, booking_id, created_at)
                             VALUES (?, ?, NOW())");
        if (!$w) throw new Exception('prepare pending: '.$conn->error);
        $w->bind_param('ii', $sind_id, $booking_id);
        if (!$w->execute()) throw new Exception('exec pending: '.$w->error);
        $w->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        wlog('TXN_ROLLBACK_REJECT', $e->getMessage());
        // We still continue to prompt and mark the map below.
    }

    // 4) map decision (outside txn so we still mark rejected even if DB txn rolled back)
    if ($contextId) set_map($conn, $contextId, 'rejected');

    // 5) prompt for reason (text first; template fallback)
    $msg = "❌ Noted. Please reply with ONE message stating your reason for rejecting booking #{$booking_id}.";
    $resp = wa_send_text($to_no_plus, $msg, false);
    wlog('REJECT_PROMPT_TEXT', $resp);
    $ok = (is_array($resp) && isset($resp['http']) && $resp['http'] >= 200 && $resp['http'] < 300);
    if (!$ok) {
        $fb = wa_send_template_body($to_no_plus, REJECT_REASON_TEMPLATE, [(string)$booking_id], WA_LANG_CODE);
        wlog('REJECT_PROMPT_TEMPLATE_FB', $fb);
    }
}

/* ------------------------- EVENTS (POST) ------------------------- */
$raw = file_get_contents('php://input');
wlog('POST_RAW', $raw);
$payload = json_decode($raw, true);
if (!is_array($payload)) { wlog('POST_JSON_DECODE_FAIL'); http_response_code(200); echo 'OK'; exit; }

$value = $payload['entry'][0]['changes'][0]['value'] ?? null;
$field = $payload['entry'][0]['changes'][0]['field'] ?? null;
wlog('POST_FIELD', $field);

if (!$value || empty($value['messages'][0])) {
    wlog('NO_MESSAGES_BLOCK', $payload);
    http_response_code(200); echo 'OK'; exit;
}

$msg    = $value['messages'][0];
$fromNo = $msg['from'] ?? '';               // E.164 without '+'
$type   = $msg['type'] ?? '';
wlog('MSG_META', ['from'=>$fromNo, 'type'=>$type]);

/* ===== 1) BUTTON REPLIES (quick button OR interactive button_reply) ===== */
$handled   = false;
$isAccept  = false;
$isReject  = false;
$contextId = null;

if ($type === 'button') { // QUICK-REPLY
    $btnText    = strtolower(trim($msg['button']['text']    ?? ''));
    $btnPayload = strtolower(trim($msg['button']['payload'] ?? ''));
    $contextId  = $msg['context']['id'] ?? null;
    wlog('QUICK_BUTTON', ['text'=>$btnText, 'payload'=>$btnPayload, 'context'=>$contextId]);

    $isAccept = in_array($btnText, ['accept','yes'], true) || in_array($btnPayload, ['accept','yes','booking_accept'], true);
    $isReject = in_array($btnText, ['reject','no','decline'], true) || in_array($btnPayload, ['reject','no','decline','booking_reject'], true);
    $handled  = true;
}
if ($type === 'interactive' && ($msg['interactive']['type'] ?? '') === 'button_reply') { // INTERACTIVE
    $btn       = $msg['interactive']['button_reply'];
    $btnId     = strtolower(trim($btn['id'] ?? ''));
    $btnTitle  = strtolower(trim($btn['title'] ?? ''));
    $contextId = $msg['context']['id'] ?? null;
    wlog('INTERACTIVE_BUTTON', ['id'=>$btnId, 'title'=>$btnTitle, 'context'=>$contextId]);

    $isAccept = ($btnId === 'accept' || $btnId === 'booking_accept' || $btnTitle === 'accept');
    $isReject = ($btnId === 'reject' || $btnId === 'booking_reject' || $btnTitle === 'reject');
    $handled  = true;
}

if ($handled) {
    // Prefer mapping via context id
    if (!empty($contextId)) {
        [$booking_id, $sind_id, $prevDecision, $processedAt] = resolve_map($conn, $contextId);
        wlog('MAP_RESOLVE', compact('booking_id','sind_id','prevDecision','processedAt'));

        if (!$booking_id || !$sind_id) { http_response_code(200); echo 'OK'; exit; }

        // double-tap guard
        if (!empty($processedAt)) {
            $msgTxt = $prevDecision === 'accepted'
                ? "✅ This job was already accepted."
                : ($prevDecision === 'rejected'
                    ? "❌ This job was already rejected."
                    : "ℹ️ This job was already handled.");
            wa_send_text($fromNo, $msgTxt, false);
            http_response_code(200); echo 'OK'; exit;
        }

        if ($isAccept) {
            $u = $conn->prepare("UPDATE bookings
                                 SET booking_status='confirm'
                                 WHERE booking_id=? AND sind_id=? AND booking_status IN ('paid','pending')");
            if (!$u) { wlog('ERR accept prepare', $conn->error); http_response_code(200); echo 'OK'; exit; }
            $u->bind_param('ii', $booking_id, $sind_id);
            $u->execute();
            $aff = $u->affected_rows;
            $u->close();

            if ($aff > 0) {
                set_map($conn, $contextId, 'accepted');
                wa_send_text($fromNo, "✅ Thanks! Booking #{$booking_id} has been accepted. See you there.", false);
            } else {
                // Check current state for accurate reply + map update
                $st = null;
                $q = $conn->prepare("SELECT booking_status FROM bookings WHERE booking_id=? LIMIT 1");
                if ($q) { $q->bind_param('i', $booking_id); $q->execute(); $q->bind_result($st); $q->fetch(); $q->close(); }
                $st = strtolower((string)$st);
                if ($st === 'rejected') {
                    set_map($conn, $contextId, 'rejected');
                    wa_send_text($fromNo, "❌ This job was already rejected.", false);
                } elseif ($st === 'confirm') {
                    set_map($conn, $contextId, 'accepted');
                    wa_send_text($fromNo, "✅ This job was already accepted.", false);
                } else {
                    wa_send_text($fromNo, "ℹ️ This job was already handled.", false);
                }
            }
            http_response_code(200); echo 'OK'; exit;
        }

        if ($isReject) {
            start_reject_flow($conn, (int)$booking_id, (int)$sind_id, $fromNo, $contextId);
            http_response_code(200); echo 'OK'; exit;
        }

        http_response_code(200); echo 'OK'; exit;
    }

    // Fallback: no context id — resolve by sender
    $sind_id_fb = find_sind_by_phone($conn, $fromNo);
    $booking_id_fb = $sind_id_fb ? latest_open_booking_for_sind($conn, $sind_id_fb) : null;
    wlog('DECISION_FALLBACK_IDS', ['sind_id'=>$sind_id_fb, 'booking_id'=>$booking_id_fb]);

    if ($sind_id_fb && $booking_id_fb) {
        if ($isAccept) {
            $u = $conn->prepare("UPDATE bookings
                                 SET booking_status='confirm'
                                 WHERE booking_id=? AND sind_id=? AND booking_status IN ('paid','pending')");
            if ($u) { $u->bind_param('ii', $booking_id_fb, $sind_id_fb); $u->execute(); $aff = $u->affected_rows; $u->close(); }
            if (!empty($aff)) wa_send_text($fromNo, "✅ Thanks! Booking #{$booking_id_fb} has been accepted. See you there.", false);
            else wa_send_text($fromNo, "ℹ️ This job was already handled.", false);
        } elseif ($isReject) {
            start_reject_flow($conn, (int)$booking_id_fb, (int)$sind_id_fb, $fromNo, null);
        }
    }
    http_response_code(200); echo 'OK'; exit;
}

/* ===== 2) TEXT messages (typed accept/reject or the actual reason) ===== */
if ($type === 'text') {
    $body    = trim($msg['text']['body'] ?? '');
    $lower   = strtolower($body);
    $sind_id = find_sind_by_phone($conn, $fromNo);
    wlog('TEXT_IN', ['sind_id'=>$sind_id, 'body'=>$body]);

    // Optional typed accept/reject (fallback UX)
    if ($sind_id && in_array($lower, ['accept','yes','reject','no','decline'], true)) {
        $booking_id = latest_open_booking_for_sind($conn, $sind_id);
        if ($booking_id) {
            if (in_array($lower, ['accept','yes'], true)) {
                $u = $conn->prepare("UPDATE bookings
                                     SET booking_status='confirm'
                                     WHERE booking_id=? AND sind_id=? AND booking_status IN ('paid','pending')");
                if ($u) { $u->bind_param('ii', $booking_id, $sind_id); $u->execute(); $aff=$u->affected_rows; $u->close(); }
                if (!empty($aff)) wa_send_text($fromNo, "✅ Thanks! Booking #{$booking_id} has been accepted. See you there.", false);
                else wa_send_text($fromNo, "ℹ️ This job was already handled.", false);
            } else {
                start_reject_flow($conn, (int)$booking_id, (int)$sind_id, $fromNo, null);
            }
        }
        http_response_code(200); echo 'OK'; exit;
    }

    // Reason capture
    if ($sind_id && $body !== '') {
        $booking_id = null;
        $sel = $conn->prepare("SELECT booking_id
                               FROM wa_pending_reason
                               WHERE sind_id = ?
                               ORDER BY created_at DESC
                               LIMIT 1");
        if (!$sel) { wlog('ERR pending_reason select prepare', $conn->error); http_response_code(200); echo 'OK'; exit; }
        $sel->bind_param('i', $sind_id);
        $sel->execute();
        $sel->bind_result($booking_id);
        $has = $sel->fetch();
        $sel->close();

        if ($has && $booking_id) {
            $upd = $conn->prepare("UPDATE sind_rejected_hist
                                   SET reason = ?
                                   WHERE sind_id = ? AND booking_id = ?
                                   ORDER BY created_at DESC
                                   LIMIT 1");
            if ($upd) {
                $upd->bind_param('sii', $body, $sind_id, $booking_id);
                $upd->execute();
                wlog('REJECT_REASON_SAVED', ['aff'=>$upd->affected_rows]);
                $upd->close();
            } else {
                wlog('ERR hist update prepare', $conn->error);
            }

            $del = $conn->prepare("DELETE FROM wa_pending_reason WHERE sind_id = ? AND booking_id = ?");
            if ($del) { $del->bind_param('ii', $sind_id, $booking_id); $del->execute(); wlog('PENDING_REASON_DELETED', ['aff'=>$del->affected_rows]); $del->close(); }

            wa_send_text($fromNo, "📝 Thanks. Your reason for booking #{$booking_id} has been recorded.", false);
        } else {
            wlog('NO_PENDING_FOR_REASON', ['sind_id'=>$sind_id]);
        }
    }
    http_response_code(200); echo 'OK'; exit;
}

/* default */
http_response_code(200);
echo 'OK';
