<?php
declare(strict_types=1);

return [
    'host'    => env('DB_HOST', '127.0.0.1'),
    'port'    => (int) env('DB_PORT', 3306),
    'name'    => env('DB_NAME', 'dinsathi'),
    'user'    => env('DB_USER', 'dinsathi_app'),
    'pass'    => (string) env('DB_PASS', ''),

    // migrate.php only — never used by the web app.
    'migrate_user' => env('DB_MIGRATE_USER', env('DB_USER', 'dinsathi_app')),
    'migrate_pass' => (string) env('DB_MIGRATE_PASS', env('DB_PASS', '')),
];
