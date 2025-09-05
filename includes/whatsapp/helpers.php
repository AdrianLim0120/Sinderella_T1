<?php
require_once __DIR__.'/config.php';

// Convert "0123456789" -> "+60123456789"
function phone_to_e164(?string $raw): ?string {
  if (!$raw) return null;
  $digits = preg_replace('/\D+/', '', $raw);
  if (!$digits) return null;
  if ($raw[0] === '+') return '+' . $digits;
  if ($raw[0] === '0') return DEFAULT_COUNTRY_CC . substr($digits, 1);
  return '+' . $digits;
}
function fmt_date($ymd){ return (string)$ymd; }                    // tweak if you want pretty date
function fmt_time($hms){ return substr((string)$hms, 0, 5); }      // 15:00:00 -> 15:00
function time_range($from, $to){ return fmt_time($from) . ' - ' . fmt_time($to); }
