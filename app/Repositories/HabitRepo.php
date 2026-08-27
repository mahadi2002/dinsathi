<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class HabitRepo
{
    public function forUser(int $userId, bool $includeArchived = false): array
    {
        $sql = 'SELECT * FROM habits WHERE user_id = ?';
        if (!$includeArchived) {
            $sql .= ' AND is_archived = 0';
        }
        $sql .= ' ORDER BY id ASC';
        return Db::all($sql, [$userId]);
    }

    public function find(int $id, int $userId): ?array
    {
        return Db::first('SELECT * FROM habits WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    /**
     * $targetQuantity/$unit stay null for a plain boolean check-in habit —
     * set both to make it a quantity habit (e.g. target 8, unit "glasses").
     */
    public function create(int $userId, string $name, ?string $icon, string $activeDays, ?int $targetQuantity = null, ?string $unit = null): int
    {
        return Db::insert(
            'INSERT INTO habits (user_id, name, icon, active_days, target_quantity, unit) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $name, $icon, $activeDays, $targetQuantity, $unit]
        );
    }

    public function archive(int $id, int $userId): void
    {
        Db::exec('UPDATE habits SET is_archived = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function allActive(): array
    {
        return Db::all('SELECT * FROM habits WHERE is_archived = 0');
    }
}
