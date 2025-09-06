<?php
const WA_PHONE_NUMBER_ID = '771566906040670';
const WA_WABA_ID = '656491673647775';  
const WA_ACCESS_TOKEN    = 'EAAUOcrtW2l8BPV8Bb31qH3K16WWOyVzwlfFb3gDVlLhZAuL4Hv29zGLIZAswLtPlYdFaHGDXWAR6JicQrKE2zmhuxBglDNTIctuApC8dVhZA2okdrTrxpwU7ZCjZAZAFKOaUoNU0C2ZBqjCOiGZBJDO2ZBZAjb2j0qZAfGyInstQrrYRtqbT7bbpBpP7x2rd8IgxzVQmAZDZD';

const WA_TEMPLATE_BOOKING_REMINDER = 'booking'; 

// Admin fallback number (use E.164; or fetch from DB in code)
const ADMIN_PHONE_E164   = '+60124037014';  // replace with admin number
const WA_TEMPLATE_LANG = 'en';

// API + logging
const WA_API_BASE        = 'https://graph.facebook.com/v21.0';
const WA_LOG_FILE        = __DIR__ . '/../../logs/wa.log';
const DEFAULT_COUNTRY_CC = '+60'; // to convert 01xxxxxxxx to +601xxxxxxxx

function wa_log($msg){
  @file_put_contents(WA_LOG_FILE, '['.date('c').'] '.(is_string($msg)?$msg:json_encode($msg)).PHP_EOL, FILE_APPEND);
}
