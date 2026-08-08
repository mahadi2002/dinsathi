<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class AdminUserRepo
{
    public function findByEmail(string $email): ?array
    {
        return Db::first('SELECT * FROM admin_users WHERE email = ?', [$email]);
    }

    public function find(int $id): ?array
    {
        return Db::first('SELECT * FROM admin_users WHERE id = ?', [$id]);
    }
}
