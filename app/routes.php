<?php
declare(strict_types=1);

/**
 * The route table. Format: [method, path, 'Controller@action', [middleware]].
 *
 * Middleware keys: csrf | guest | auth | admin | rl:<bucket>
 * SecurityHeaders is applied globally in public/index.php, not per route.
 *
 * No subscription/billing anywhere in this app — every /app/* route only
 * requires 'auth' (a logged-in, registered account). There is no paid tier,
 * no free/paid split, and no separate SMS delivery gate; reminders are
 * push-only.
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

    // ── Auth (email + password) ────────────────────────────────────────
    ['GET',  '/register',            'AuthController@registerForm',   ['guest']],
    ['POST', '/register',            'AuthController@register',       ['guest', 'csrf', 'rl:register']],
    ['GET',  '/login',               'AuthController@loginForm',      ['guest']],
    ['POST', '/login',               'AuthController@login',          ['guest', 'csrf', 'rl:login']],
    ['GET',  '/forgot-password',     'AuthController@forgotPasswordForm', ['guest']],
    ['POST', '/forgot-password',     'AuthController@forgotPassword', ['guest', 'csrf', 'rl:password_reset']],
    ['GET',  '/reset-password/{slug}', 'AuthController@resetPasswordForm', ['guest']],
    ['POST', '/reset-password/{slug}', 'AuthController@resetPassword',     ['guest', 'csrf']],
    ['POST', '/logout',              'AuthController@logout',         ['auth', 'csrf']],

    // ── Gated app — every route requires a signed-in account ────────────
    ['GET',  '/app',                       'DashboardController@index',   ['auth']],
    ['GET',  '/app/day/{date}',            'CalendarController@day',      ['auth']],
    ['GET',  '/app/week/{date}',           'CalendarController@week',     ['auth']],
    ['GET',  '/app/month/{date}',          'CalendarController@month',    ['auth']],

    ['GET',  '/app/insights',              'InsightsController@index',    ['auth']],

    ['GET',  '/app/tasks',                 'TaskController@index',        ['auth']],
    ['POST', '/app/tasks',                 'TaskController@store',        ['auth', 'csrf']],
    ['GET',  '/app/tasks/{id}',            'TaskController@show',         ['auth']],
    ['PATCH','/app/tasks/{id}',            'TaskController@update',       ['auth', 'csrf']],
    ['PATCH','/app/tasks/{id}/reschedule', 'TaskController@reschedule',   ['auth', 'csrf']],
    ['DELETE','/app/tasks/{id}',           'TaskController@destroy',      ['auth', 'csrf']],
    ['POST', '/app/tasks/{id}/complete',   'TaskController@complete',     ['auth', 'csrf']],
    ['POST', '/app/tasks/{id}/subtasks',   'TaskController@addSubtask',   ['auth', 'csrf']],
    ['POST', '/app/subtasks/{id}/toggle',  'TaskController@toggleSubtask', ['auth', 'csrf']],

    ['GET',  '/app/lists',                 'TaskListController@index',    ['auth']],
    ['POST', '/app/lists',                 'TaskListController@store',    ['auth', 'csrf']],
    ['PATCH','/app/lists/{id}',            'TaskListController@update',   ['auth', 'csrf']],
    ['DELETE','/app/lists/{id}',           'TaskListController@destroy',  ['auth', 'csrf']],

    ['GET',  '/app/habits',                'HabitController@index',       ['auth']],
    ['POST', '/app/habits',                'HabitController@store',       ['auth', 'csrf']],
    ['POST', '/app/habits/{id}/checkin',   'HabitController@checkin',     ['auth', 'csrf']],
    ['GET',  '/app/habits/{id}/history',   'HabitController@history',     ['auth']],
    ['DELETE','/app/habits/{id}',          'HabitController@destroy',     ['auth', 'csrf']],

    ['GET',  '/app/focus',                 'FocusController@index',       ['auth']],
    ['POST', '/app/focus',                 'FocusController@store',       ['auth', 'csrf']],

    ['GET',  '/app/review/{date}',         'ReviewController@show',       ['auth']],
    ['POST', '/app/review/{date}',         'ReviewController@store',      ['auth', 'csrf']],

    ['POST', '/app/notifications/read',    'NotificationController@markRead', ['auth', 'csrf']],

    ['GET',  '/app/settings',              'SettingsController@index',    ['auth']],
    ['POST', '/app/settings',              'SettingsController@update',   ['auth', 'csrf']],
    ['POST', '/app/settings/push/subscribe',   'SettingsController@pushSubscribe',   ['auth', 'csrf']],
    ['POST', '/app/settings/push/unsubscribe', 'SettingsController@pushUnsubscribe', ['auth', 'csrf']],
    ['POST', '/app/settings/export',       'SettingsController@export',   ['auth', 'csrf', 'rl:export']],

    // ── Admin (separate auth entirely) ──────────────────────────────────
    ['GET',  '/admin/login',         'Admin/AdminAuthController@form',    []],
    ['POST', '/admin/login',         'Admin/AdminAuthController@login',   ['csrf', 'rl:admin_login']],
    ['POST', '/admin/logout',        'Admin/AdminAuthController@logout',  ['admin', 'csrf']],

    ['GET',  '/admin',               'Admin/AdminDashboardController@index', ['admin']],
    ['GET',  '/admin/logs',          'Admin/AdminDashboardController@logs',  ['admin']],

    ['GET',  '/admin/users',         'Admin/AdminUserController@index',   ['admin']],
    ['GET',  '/admin/users/{id}',    'Admin/AdminUserController@show',    ['admin']],

    ['GET',  '/admin/contact',       'Admin/AdminContactController@index',   ['admin']],
    ['POST', '/admin/contact/{id}/resolve', 'Admin/AdminContactController@resolve', ['admin', 'csrf']],

    ['GET',  '/admin/broadcast',     'Admin/AdminBroadcastController@form',  ['admin']],
    ['POST', '/admin/broadcast',     'Admin/AdminBroadcastController@send',  ['admin', 'csrf']],
];
