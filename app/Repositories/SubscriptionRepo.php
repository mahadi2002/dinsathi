<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

/**
 * A user can hold two independent subscriptions at once — 'planner' (the
 * app itself) and 'sms_reminders' (the SMS delivery add-on) — so every
 * lookup is scoped by (user_id, plan), never just user_id alone.
 */
final class SubscriptionRepo
{
    public function findActive(int $userId, string $plan): ?array
    {
        return Db::first(
            "SELECT * FROM subscriptions WHERE user_id = ? AND plan = ? AND status = 'active' ORDER BY id DESC LIMIT 1",
            [$userId, $plan]
        );
    }

    public function latest(int $userId, string $plan): ?array
    {
        return Db::first(
            'SELECT * FROM subscriptions WHERE user_id = ? AND plan = ? ORDER BY id DESC LIMIT 1',
            [$userId, $plan]
        );
    }

    /** Both plans' latest rows in one call, keyed by plan — used by settings/dashboard. */
    public function latestBoth(int $userId): array
    {
        return [
            'planner'       => $this->latest($userId, 'planner'),
            'sms_reminders' => $this->latest($userId, 'sms_reminders'),
        ];
    }

    public function create(int $userId, string $plan, string $gateway, float $amount, ?string $externalRef): int
    {
        return Db::insert(
            'INSERT INTO subscriptions (user_id, plan, gateway, status, daily_amount, started_at, next_charge_at, external_ref)
             VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 DAY), ?)',
            [$userId, $plan, $gateway, 'active', $amount, $externalRef]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        $endedClause = in_array($status, ['unsubscribed', 'expired'], true) ? ', ended_at = UTC_TIMESTAMP()' : '';
        Db::exec("UPDATE subscriptions SET status = ?{$endedClause} WHERE id = ?", [$status, $id]);
    }

    public function bumpNextCharge(int $id): void
    {
        Db::exec('UPDATE subscriptions SET next_charge_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 DAY) WHERE id = ?', [$id]);
    }

    /** @return array<int,array> subscriptions with next_charge_at due, still active, either plan */
    public function dueForCharge(): array
    {
        return Db::all("SELECT * FROM subscriptions WHERE status = 'active' AND next_charge_at <= UTC_TIMESTAMP()");
    }

    public function countByStatus(string $status, ?string $plan = null): int
    {
        if ($plan !== null) {
            return (int) Db::value('SELECT COUNT(*) FROM subscriptions WHERE status = ? AND plan = ?', [$status, $plan]);
        }
        return (int) Db::value('SELECT COUNT(*) FROM subscriptions WHERE status = ?', [$status]);
    }
}
