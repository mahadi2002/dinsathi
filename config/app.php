<?php
declare(strict_types=1);

return [
    'name'     => env('APP_NAME', 'DinSathi'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => env('APP_DEBUG', false) === true || env('APP_DEBUG') === 'true',
    'url'      => rtrim((string) env('APP_URL', ''), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Dhaka'),
    'locale'   => env('APP_LOCALE', 'bn'),
    'key'      => base64_decode((string) env('APP_KEY', ''), true) ?: '',
    'pepper'   => base64_decode((string) env('HASH_PEPPER', ''), true) ?: '',

    'session' => [
        'cookie'        => env('SESSION_COOKIE_NAME', 'dinsathi_sid'),
        'lifetime_min'  => (int) env('SESSION_LIFETIME_MIN', 43200),
        'absolute_days' => (int) env('SESSION_ABSOLUTE_DAYS', 30),
        'secure'        => (bool) env('SESSION_SECURE', true),
    ],

    'otp' => [
        'length'          => (int) env('OTP_LENGTH', 6),
        'ttl'             => (int) env('OTP_TTL_SECONDS', 300),
        'max_attempts'    => (int) env('OTP_MAX_ATTEMPTS', 3),
        'resend_cooldown' => (int) env('OTP_RESEND_COOLDOWN', 60),
    ],

    'admin_ip_allowlist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_IP_ALLOWLIST', ''))
    ))),

    'log_level' => env('LOG_LEVEL', 'info'),

    'support_email' => env('SUPPORT_EMAIL', ''),

    'quiet_hours' => [
        'start' => env('QUIET_HOURS_START', '22:00:00'),
        'end'   => env('QUIET_HOURS_END', '07:00:00'),
    ],

    'recurrence_horizon_days' => (int) env('RECURRENCE_HORIZON_DAYS', 30),
];
