<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class AuditLogRepo
{
    public function log(?int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, array $detail = []): void
    {
        Db::exec(
            'INSERT INTO audit_log (admin_id, action, target_type, target_id, detail) VALUES (?, ?, ?, ?, ?)',
            [$adminId, $action, $targetType, $targetId, json_encode($detail, JSON_UNESCAPED_UNICODE)]
        );
    }

    public function recent(int $limit = 50): array
    {
        return Db::all(
            'SELECT al.*, au.email AS admin_email FROM audit_log al
             LEFT JOIN admin_users au ON au.id = al.admin_id
             ORDER BY al.id DESC LIMIT ?',
            [$limit]
        );
    }
}
