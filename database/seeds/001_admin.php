<?php
declare(strict_types=1);

/**
 * Seeds one superadmin so /admin/login is reachable on a fresh install.
 * Change this password immediately after first login — see STARTING.md.
 */

use App\Core\Db;

$email = 'admin@prohor.local';
$exists = Db::value('SELECT id FROM admin_users WHERE email = ?', [$email]);

if ($exists === null) {
    Db::insert(
        'INSERT INTO admin_users (email, password_hash, role) VALUES (?, ?, ?)',
        [$email, password_hash('ChangeMe123!', PASSWORD_DEFAULT), 'superadmin']
    );
    fwrite(STDOUT, "  seeded admin_users: $email / ChangeMe123! (change on first login)\n");
}
