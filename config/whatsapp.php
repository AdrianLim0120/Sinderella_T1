<?php
// /config/whatsapp.php
declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');

const WA_GRAPH_API_VERSION = 'v23.0';
const WA_GRAPH_API_BASE    = 'https://graph.facebook.com/' . WA_GRAPH_API_VERSION . '/';
const WA_PHONE_NUMBER_ID   = '771566906040670';
const WA_ACCESS_TOKEN = 'EAAUOcrtW2l8BPV8Bb31qH3K16WWOyVzwlfFb3gDVlLhZAuL4Hv29zGLIZAswLtPlYdFaHGDXWAR6JicQrKE2zmhuxBglDNTIctuApC8dVhZA2okdrTrxpwU7ZCjZAZAFKOaUoNU0C2ZBqjCOiGZBJDO2ZBZAjb2j0qZAfGyInstQrrYRtqbT7bbpBpP7x2rd8IgxzVQmAZDZD';

const WA_TEMPLATE_NAME = 'booking'; 
const WA_TEMPLATE_PAYMENT_NAME = 'payment_confirmation';
const WA_TEMPLATE_OTP_NAME = 'otp_verify_v2';
const WA_TEMPLATE_BOOKING_DECISION = 'sinderella_reminder_payment_recevied';
const WA_LANG_CODE     = 'en';

const OTP_TTL_MINUTES        = 5;   
const OTP_LENGTH             = 6;    
const OTP_RATE_LIMIT_SECONDS = 60;  
const OTP_DAILY_LIMIT        = 10;    

// Security keys for HTTP endpoints
const CRON_HTTP_KEY    = 'sinderella-run-123';     
const INTERNAL_KEY     = 'sinderella-internal-456';
const ADMIN_NUMBERS = ['60169673981'];

const REMIND_DAYS_AHEAD = 2;

const WHATSAPP_SEND_DELAY_US = 200_000; // 0.2s

function wa_env(string $key, ?string $fallback = null): ?string {
    $v = getenv($key);
    return ($v !== false && $v !== '') ? $v : $fallback;
}
?>