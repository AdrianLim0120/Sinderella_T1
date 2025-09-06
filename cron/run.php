<?php
require_once __DIR__.'/../includes/whatsapp/config.php';

// Simple guard so random people can't trigger your cron:
const CRON_HTTP_KEY = 'sinderella-run-123';  // change me!

if (($_GET['key'] ?? '') !== CRON_HTTP_KEY) {
  http_response_code(403); echo 'forbidden'; exit;
}

// Include your cron script
require __DIR__.'/remind_2days.php';
