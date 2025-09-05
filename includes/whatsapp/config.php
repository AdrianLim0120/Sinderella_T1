<?php
const WA_PHONE_NUMBER_ID = '771566906040670';
const WA_WABA_ID = '656491673647775';  
const WA_ACCESS_TOKEN    = 'EAAUOcrtW2l8BPbO2W8L85iD71qCUStDxY2vdYel08UYP7NgzfigZAUBjn0ZAsvzJKlXAYo2uWA0tJYTZA0eLldtoo73mTlPneHpUsugxA3pdblalY9wt2TevB2GqHYvRbSt3Y0jH4ZAnTTXaZCZCim3EuSOq7fvIBYECd172V5d3pkof1Ao7H3UUZAa5TgnuwZDZD';

const WA_TEMPLATE_BOOKING_REMINDER = 'booking_reminders'; 

// Admin fallback number (use E.164; or fetch from DB in code)
const ADMIN_PHONE_E164   = '+60XXXXXXXXX';  // replace with admin number

// API + logging
const WA_API_BASE        = 'https://graph.facebook.com/v21.0';
const WA_LOG_FILE        = __DIR__ . '/../../logs/wa.log';
const DEFAULT_COUNTRY_CC = '+60'; // to convert 01xxxxxxxx to +601xxxxxxxx

function wa_log($msg){
  @file_put_contents(WA_LOG_FILE, '['.date('c').'] '.(is_string($msg)?$msg:json_encode($msg)).PHP_EOL, FILE_APPEND);
}
