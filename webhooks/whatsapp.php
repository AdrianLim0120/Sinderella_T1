<?php
// /webhooks/whatsapp.php
require_once __DIR__.'/../includes/whatsapp/config.php';
header('Content-Type: application/json');

// Verification
if ($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['hub_mode'])) {
  $mode = $_GET['hub_mode'] ?? '';
  $token = $_GET['hub_verify_token'] ?? '';
  $challenge = $_GET['hub_challenge'] ?? '';
  if ($mode==='subscribe' && $token===WA_VERIFY_TOKEN) { echo $challenge; exit; }
  http_response_code(403); echo json_encode(['error'=>'verify failed']); exit;
}

// Events
$raw = file_get_contents('php://input') ?: '';
@file_put_contents(__DIR__.'/whatsapp_webhook.log', '['.date('c')."] $raw\n", FILE_APPEND);
wa_log(['webhook_in'=>$raw]);
echo json_encode(['ok'=>true]);
