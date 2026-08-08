<?php
declare(strict_types=1);

/**
 * SmsGateway selection. MockSmsGateway (dev) writes straight to `sms_log`
 * with status='sent' so the whole reminder pipeline is testable end-to-end
 * today. SmsGatewayAdapter (prod) is a provider-agnostic stub — the actual
 * Bangladeshi SMS provider is unresolved; wire the
 * base URL/API key here once one is chosen, never guess the shape.
 */
return [
    'driver'    => env('SMS_GATEWAY', 'mock'),
    'base_url'  => rtrim((string) env('SMS_PROVIDER_API_BASE', ''), '/'),
    'api_key'   => env('SMS_PROVIDER_API_KEY'),
    'sender_id' => env('SMS_SENDER_ID', 'DinSathi'),
];
