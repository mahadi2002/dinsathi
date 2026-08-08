<?php
declare(strict_types=1);

/**
 * SubscriptionGateway selection — mock for dev, BdApps for prod. The real
 * driver is a stub: every method throws until BDApps' actual SDP/OTP API
 * contract is available. Never fabricate the
 * request/response shape from guesswork.
 */
return [
    'driver'   => env('SUBSCRIPTION_GATEWAY', 'mock'),
    'base_url' => rtrim((string) env('BDAPPS_API_BASE', ''), '/'),
    'client_id'     => env('BDAPPS_CLIENT_ID'),
    'client_secret' => env('BDAPPS_CLIENT_SECRET'),
    'shortcode' => env('BDAPPS_SHORTCODE', '16216'),
    'stop_word' => env('BDAPPS_STOP_KEYWORD', 'STOP'),

    // Two-tier model, no free usage: 'planner' unlocks the app itself
    // (tasks, lists, calendar, recurring, habits, focus, review, insights);
    // 'sms_reminders' is a separate add-on for the SMS delivery channel
    // specifically, priced apart because it costs real money per message —
    // push notifications stay bundled free into the planner plan.
    'plans' => [
        'planner' => [
            'amount' => (float) env('BDAPPS_DAILY_AMOUNT', 2.78),
            'label'  => 'দিনসাথী Planner',
        ],
        'sms_reminders' => [
            'amount' => (float) env('BDAPPS_SMS_DAILY_AMOUNT', 1.00),
            'label'  => 'SMS Reminder',
        ],
    ],

    // Kept for call sites that only ever mean the base plan's price.
    'amount' => (float) env('BDAPPS_DAILY_AMOUNT', 2.78),
];
