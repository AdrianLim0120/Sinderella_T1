<?php
require_once __DIR__.'/config.php';

function wa_post(array $payload): array {
  $url = WA_API_BASE . '/' . WA_PHONE_NUMBER_ID . '/messages';
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . WA_ACCESS_TOKEN,
      'Content-Type: application/json'
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
  ]);
  $res = curl_exec($ch);
  $err = curl_error($ch);
  curl_close($ch);

  if ($err) { wa_log(['curl_err'=>$err, 'payload'=>$payload]); return ['error'=>$err]; }
  $json = json_decode($res, true);
  wa_log(['req'=>$payload, 'res'=>$json ?: $res]);
  return $json ?: ['raw'=>$res];
}

function wa_send_template(string $to, string $template, string $lang='en_US', array $bodyParams=[]): array {
  $components = [];
  if ($bodyParams) {
    $components[] = [
      'type'=>'body',
      'parameters'=> array_map(fn($v)=>['type'=>'text','text'=>(string)$v], $bodyParams)
    ];
  }
  return wa_post([
    'messaging_product'=>'whatsapp',
    'to'=>$to,
    'type'=>'template',
    'template'=>[
      'name'=>$template,
      'language'=>['code'=>$lang],
      'components'=>$components
    ]
  ]);
}
