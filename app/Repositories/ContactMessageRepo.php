<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

/**
 * Backed by the contact_messages table (database/migrations/008). Used to
 * log to storage/logs/contact-*.log with no way to triage volume — promoted
 * to a real table + new/resolved states so /admin/contact can do more than
 * tail a file.
 */
final class ContactMessageRepo
{
    public function create(string $name, string $mobileOrEmail, string $message): int
    {
        return Db::insert(
            'INSERT INTO contact_messages (name, contact, message, status) VALUES (?, ?, ?, ?)',
            [$name, $mobileOrEmail, $message, 'new']
        );
    }

    /** @return array{data: array, total: int} */
    public function paginate(int $page, int $perPage, ?string $status = null): array
    {
        $offset = max(0, $page - 1) * $perPage;
        $where  = $status !== null ? ' WHERE status = ?' : '';
        $params = $status !== null ? [$status] : [];

        $total = (int) Db::value("SELECT COUNT(*) FROM contact_messages{$where}", $params);
        $rows  = Db::all(
            "SELECT * FROM contact_messages{$where} ORDER BY id DESC LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        return ['data' => $rows, 'total' => $total];
    }

    public function find(int $id): ?array
    {
        return Db::first('SELECT * FROM contact_messages WHERE id = ?', [$id]);
    }

    public function markResolved(int $id): bool
    {
        return Db::exec(
            "UPDATE contact_messages SET status = 'resolved', resolved_at = NOW() WHERE id = ?",
            [$id]
        ) > 0;
    }

    public function countNew(): int
    {
        return (int) Db::value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
    }
}
