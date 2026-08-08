<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class DailyReviewRepo
{
    public function find(int $userId, string $dhakaDate): ?array
    {
        return Db::first('SELECT * FROM daily_reviews WHERE user_id = ? AND review_date = ?', [$userId, $dhakaDate]);
    }

    public function upsert(int $userId, string $dhakaDate, ?string $note, ?string $mood): void
    {
        Db::exec(
            'INSERT INTO daily_reviews (user_id, review_date, note, mood) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE note = VALUES(note), mood = VALUES(mood)',
            [$userId, $dhakaDate, $note, $mood]
        );
    }

    public function recent(int $userId, int $limit = 14): array
    {
        return Db::all(
            'SELECT * FROM daily_reviews WHERE user_id = ? ORDER BY review_date DESC LIMIT ?',
            [$userId, $limit]
        );
    }
}
