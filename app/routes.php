<?php
declare(strict_types=1);

/**
 * The route table. Format: [method, path, 'Controller@action', [middleware]].
 *
 * Middleware keys: csrf | guest | auth | sub | admin | rl:<bucket>
 * SecurityHeaders is applied globally in public/index.php, not per route.
 *
 * No free tier: every /app/* route carries 'sub' (the planner subscription)
 * on top of 'auth' — there is no degraded free path through the app itself,
 * only the public marketing pages and the subscribe funnel are reachable
 * without paying. The SMS Reminder add-on is a second, separate
 * subscription checked inside ReminderService at send time, not at the
 * route level — it's a delivery channel toggle inside Settings, not a page.
 *
 * Order matters: literal paths must precede {slug}/{id}/{date} patterns that
 * would also match (e.g. /app/tasks before /app/tasks/{id}).
 */
return [
    // ── Ops ─────────────────────────────────────────────────────────────
    ['GET',  '/health',              'HealthController@check',        []],

    // ── Public ──────────────────────────────────────────────────────────
    ['GET',  '/',                    'HomeController@index',          []],
    ['GET',  '/privacy',             'HomeController@privacy',        []],
    ['GET',  '/terms',               'HomeController@terms',          []],
    ['GET',  '/contact',             'HomeController@contact',        []],
    ['POST', '/contact',             'HomeController@submitContact',  ['csrf', 'rl:contact']],

    // ── Auth (mobile OTP only) ─────────────────────────────────────────
    ['GET',  '/register',            'AuthController@registerForm',   ['guest']],
    ['POST', '/register/otp',        'AuthController@requestRegisterOtp', ['guest', 'csrf', 'rl:otp_request']],
    ['GET',  '/register/verify',     'AuthController@registerVerifyForm', ['guest']],
    ['POST', '/register/verify',     'AuthController@verifyRegister', ['guest', 'csrf', 'rl:otp_verify']],
    ['GET',  '/login',               'AuthController@loginForm',      ['guest']],
    ['POST', '/login/otp',           'AuthController@requestLoginOtp', ['guest', 'csrf', 'rl:otp_request']],
    ['GET',  '/login/verify',        'AuthController@loginVerifyForm', ['guest']],
    ['POST', '/login/verify',        'AuthController@verifyLogin',    ['guest', 'csrf', 'rl:otp_verify']],
    ['POST', '/otp/resend',          'AuthController@resendOtp',      ['guest', 'csrf', 'rl:otp_resend']],
    ['POST', '/logout',              'AuthController@logout',         ['auth', 'csrf']],

    // ── Subscription funnel — 'auth' only, never 'sub' (that would lock
    //    a not-yet-subscribed user out of the one place that lets them
    //    subscribe). Unsubscribe is reachable from every state on purpose. ──
    ['GET',  '/subscribe',           'SubscribeController@show',      ['auth']],
    ['POST', '/subscribe/otp',       'SubscribeController@requestOtp', ['auth', 'csrf', 'rl:otp_request']],
    ['POST', '/subscribe/confirm',   'SubscribeController@confirm',   ['auth', 'csrf']],
    ['GET',  '/subscribe/status',    'SubscribeController@status',    ['auth']],
    ['POST', '/unsubscribe',         'SubscribeController@unsubscribe', ['auth', 'csrf']],

    ['GET',  '/subscribe/sms',            'SubscribeController@showSms',       ['auth']],
    ['POST', '/subscribe/sms/otp',        'SubscribeController@requestOtpSms', ['auth', 'csrf', 'rl:otp_request']],
    ['POST', '/subscribe/sms/confirm',    'SubscribeController@confirmSms',    ['auth', 'csrf']],
    ['GET',  '/subscribe/sms/status',     'SubscribeController@statusSms',     ['auth']],
    ['POST', '/unsubscribe/sms',          'SubscribeController@unsubscribeSms', ['auth', 'csrf']],

    // ── Gated app — every route requires an active planner subscription ──
    ['GET',  '/app',                       'DashboardController@index',   ['auth', 'sub']],
    ['GET',  '/app/day/{date}',            'CalendarController@day',      ['auth', 'sub']],
    ['GET',  '/app/week/{date}',           'CalendarController@week',     ['auth', 'sub']],
    ['GET',  '/app/month/{date}',          'CalendarController@month',    ['auth', 'sub']],

    ['GET',  '/app/insights',              'InsightsController@index',    ['auth', 'sub']],

    ['GET',  '/app/tasks',                 'TaskController@index',        ['auth', 'sub']],
    ['POST', '/app/tasks',                 'TaskController@store',        ['auth', 'sub', 'csrf']],
    ['GET',  '/app/tasks/{id}',            'TaskController@show',         ['auth', 'sub']],
    ['PATCH','/app/tasks/{id}',            'TaskController@update',       ['auth', 'sub', 'csrf']],
    ['DELETE','/app/tasks/{id}',           'TaskController@destroy',      ['auth', 'sub', 'csrf']],
    ['POST', '/app/tasks/{id}/complete',   'TaskController@complete',     ['auth', 'sub', 'csrf']],
    ['POST', '/app/tasks/{id}/subtasks',   'TaskController@addSubtask',   ['auth', 'sub', 'csrf']],
    ['POST', '/app/subtasks/{id}/toggle',  'TaskController@toggleSubtask', ['auth', 'sub', 'csrf']],

    ['GET',  '/app/lists',                 'TaskListController@index',    ['auth', 'sub']],
    ['POST', '/app/lists',                 'TaskListController@store',    ['auth', 'sub', 'csrf']],
    ['PATCH','/app/lists/{id}',            'TaskListController@update',   ['auth', 'sub', 'csrf']],
    ['DELETE','/app/lists/{id}',           'TaskListController@destroy',  ['auth', 'sub', 'csrf']],

    ['GET',  '/app/habits',                'HabitController@index',       ['auth', 'sub']],
    ['POST', '/app/habits',                'HabitController@store',       ['auth', 'sub', 'csrf']],
    ['POST', '/app/habits/{id}/checkin',   'HabitController@checkin',     ['auth', 'sub', 'csrf']],
    ['GET',  '/app/habits/{id}/history',   'HabitController@history',     ['auth', 'sub']],
    ['DELETE','/app/habits/{id}',          'HabitController@destroy',     ['auth', 'sub', 'csrf']],

    ['GET',  '/app/focus',                 'FocusController@index',       ['auth', 'sub']],
    ['POST', '/app/focus',                 'FocusController@store',       ['auth', 'sub', 'csrf']],

    ['GET',  '/app/review/{date}',         'ReviewController@show',       ['auth', 'sub']],
    ['POST', '/app/review/{date}',         'ReviewController@store',      ['auth', 'sub', 'csrf']],

    ['POST', '/app/notifications/read',    'NotificationController@markRead', ['auth', 'sub', 'csrf']],

    ['GET',  '/app/settings',              'SettingsController@index',    ['auth', 'sub']],
    ['POST', '/app/settings',              'SettingsController@update',   ['auth', 'sub', 'csrf']],
    ['POST', '/app/settings/push/subscribe',   'SettingsController@pushSubscribe',   ['auth', 'sub', 'csrf']],
    ['POST', '/app/settings/push/unsubscribe', 'SettingsController@pushUnsubscribe', ['auth', 'sub', 'csrf']],
    ['POST', '/app/settings/export',       'SettingsController@export',   ['auth', 'sub', 'csrf', 'rl:export']],

    // ── Admin (separate auth entirely) ──────────────────────────────────
    ['GET',  '/admin/login',         'Admin/AdminAuthController@form',    []],
    ['POST', '/admin/login',         'Admin/AdminAuthController@login',   ['csrf', 'rl:admin_login']],
    ['POST', '/admin/logout',        'Admin/AdminAuthController@logout',  ['admin', 'csrf']],

    ['GET',  '/admin',               'Admin/AdminDashboardController@index', ['admin']],
    ['GET',  '/admin/logs',          'Admin/AdminDashboardController@logs',  ['admin']],

    ['GET',  '/admin/users',         'Admin/AdminUserController@index',   ['admin']],
    ['GET',  '/admin/users/{id}',    'Admin/AdminUserController@show',    ['admin']],

    ['GET',  '/admin/billing-events','Admin/AdminBillingController@index', ['admin']],
    ['GET',  '/admin/sms-log',       'Admin/AdminSmsController@index',     ['admin']],

    ['GET',  '/admin/contact',       'Admin/AdminContactController@index',   ['admin']],

    ['GET',  '/admin/broadcast',     'Admin/AdminBroadcastController@form',  ['admin']],
    ['POST', '/admin/broadcast',     'Admin/AdminBroadcastController@send',  ['admin', 'csrf']],
];
