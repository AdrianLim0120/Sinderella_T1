<?php
// /cron/lib/wa.php
declare(strict_types=1);

require_once __DIR__ . '/../../config/whatsapp.php';

/**
 * Normalize Malaysian mobile numbers to E.164 without '+'
 * Accepts inputs like '011-234 56789', '+601123456789', '01123456789'
 */
function format_msisdn(?string $raw): ?string {
    if (!$raw) return '';
    $n = preg_replace('/\D+/', '', $raw); // keep digits only
    // If it starts with '0', assume MY and replace leading 0 with '60'
    if (preg_match('/^0\d+$/', $n)) {
        $n = '60' . substr($n, 1);
    }
    // If it starts with '60...' already, leave it.
    if (!preg_match('/^6\d{9,12}$/', $n)) {
        return ''; // fail formatting
    }
    return $n;
}

function fmt_time($hms)
{
    return substr((string) $hms, 0, 5);
}      // 15:00:00 -> 15:00
function time_range($from, $to)
{
    return fmt_time($from) . ' - ' . fmt_time($to);
}

// booking reminder
function wa_send_template(string $to, string $templateName, string $langCode, array $components = []): array {
    $url  = WA_GRAPH_API_BASE . WA_PHONE_NUMBER_ID . '/messages';
    $body = [
        'messaging_product' => 'whatsapp',
        'to'                => $to,
        'type'              => 'template',
        'template'          => [
            'name'      => $templateName,
            'language'  => ['code' => $langCode],
            'components'=> $components,
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WA_ACCESS_TOKEN,
        ],
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 60,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($resp ?? '', true);
    $ok   = ($err === '' && $code >= 200 && $code < 300 && isset($json['messages'][0]['id']));
    return [
        'ok'        => $ok,
        'http_code' => $code,
        'error'     => $err,
        'response'  => $json ?: $resp,
        'to'        => $to,
    ];
}

function build_booking_components(array $row): array {
    $serviceDate = date('Y-m-d', strtotime($row['booking_date']));
    $fromTime    = fmt_time($row['booking_from_time']);
    $toTime      = fmt_time($row['booking_to_time']);
    $serviceTime = time_range($fromTime, $toTime);

    $params = [
        ['type' => 'text', 'text' => $serviceDate],
        ['type' => 'text', 'text' => $serviceTime],
        ['type' => 'text', 'text' => $row['sind_name'] ?? ''],
        ['type' => 'text', 'text' => format_msisdn($row['sind_phno']) ?: ($row['sind_phno'] ?? '')],
        ['type' => 'text', 'text' => $row['cust_name'] ?? ''],
        ['type' => 'text', 'text' => format_msisdn($row['cust_phno']) ?: ($row['cust_phno'] ?? '')],
        ['type' => 'text', 'text' => $row['full_address'] ?? ''],
    ];

    return [[ 'type' => 'body', 'parameters' => $params ]];
}

// payment success
function build_payment_components($amount, string $date, string $fromTime, string $toTime): array {
    $amt = number_format((float)$amount, 2, '.', '');          
    $from = substr($fromTime, 0, 5);                          
    $to   = substr($toTime, 0, 5);                            
    $when = sprintf('%s (%s - %s)', $date, $from, $to);

    return [[
        'type'       => 'body',
        'parameters' => [
            ['type' => 'text', 'text' => $amt],
            ['type' => 'text', 'text' => $when],
        ],
    ]];
}

function wa_send_payment_confirmation(string $recipient, $amount, string $date, string $fromTime, string $toTime): array {
    $components = build_payment_components($amount, $date, $fromTime, $toTime);
    return wa_send_template($recipient, WA_TEMPLATE_PAYMENT_NAME, WA_LANG_CODE, $components);
}

// otp verification
function wa_send_otp(string $toE164NoPlus, string $otp): array {
    $components = wa_build_otp_components($otp);
    return wa_send_template($toE164NoPlus, WA_TEMPLATE_OTP_NAME, WA_LANG_CODE, $components);
}

function wa_build_otp_components(string $otp): array {
    return [
        [
            'type' => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $otp],
            ],
        ],
        [
            'type'     => 'button',
            'sub_type' => 'URL',
            'index'    => '0',
            'parameters' => [
                ['type' => 'text', 'text' => $otp],  
            ],
        ],
    ];
}

function wa_messages_url(): string {
    return 'https://graph.facebook.com/' . WA_GRAPH_API_VERSION . '/' . WA_PHONE_NUMBER_ID . '/messages';
}

function wa_log($data): void {
    $line = '[' . date('c') . '] ' . (is_string($data) ? $data : json_encode($data)) . PHP_EOL;
    @file_put_contents(__DIR__ . '/../logs/wa.log', $line, FILE_APPEND);
}

/** Send a template with body variables only */
function wa_send_template_body(string $to_no_plus, string $template, array $bodyParams, string $lang = WA_DEFAULT_LANG): array
{
    $parameters = [];
    foreach ($bodyParams as $v) {
        $parameters[] = ['type' => 'text', 'text' => (string)$v];
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $to_no_plus,
        'type'              => 'template',
        'template'          => [
            'name'       => $template,
            'language'   => ['code' => $lang],
            'components' => [[
                'type'       => 'body',
                'parameters' => $parameters
            ]]
        ]
    ];

    $ch = curl_init(wa_messages_url());
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WA_ACCESS_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    $ret = ['http'=>$http, 'error'=>$err, 'response'=>$resp];
    wa_log(['send_template'=>$template,'payload'=>$payload,'result'=>$ret]);
    return $ret;
}

/** Send a plain text message */
function wa_send_text(string $to_no_plus, string $text, bool $previewUrl = false): array
{
    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $to_no_plus,
        'type'              => 'text',
        'text'              => ['body' => $text, 'preview_url' => $previewUrl]
    ];

    $ch = curl_init(wa_messages_url());
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WA_ACCESS_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    $ret = ['http'=>$http, 'error'=>$err, 'response'=>$resp];
    wa_log(['send_text'=>$text,'result'=>$ret]);
    return $ret;
}

function wa_send_booking_decision(string $to_no_plus, string $when, string $custName, string $custPhone, string $address, int $bookingId): array {
    return wa_send_template_body(
        $to_no_plus,
        WA_TEMPLATE_BOOKING_DECISION,
        [$when, $custName, $custPhone, $address, (string)$bookingId],
        WA_LANG_CODE
    );
}