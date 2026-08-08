<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class BillingEventRepo
{
    public function log(int $subscriptionId, string $type, ?float $amount, array $response = []): int
    {
        return Db::insert(
            'INSERT INTO billing_events (subscription_id, event_type, amount, gateway_response) VALUES (?, ?, ?, ?)',
            [$subscriptionId, $type, $amount, json_encode($response, JSON_UNESCAPED_UNICODE)]
        );
    }

    /** @return array{data: array, total: int} */
    public function paginate(int $page, int $perPage): array
    {
        $offset = max(0, $page - 1) * $perPage;
        $total  = (int) Db::value('SELECT COUNT(*) FROM billing_events');
        $rows   = Db::all(
            'SELECT be.*, s.user_id, s.plan FROM billing_events be
             JOIN subscriptions s ON s.id = be.subscription_id
             ORDER BY be.id DESC LIMIT ? OFFSET ?',
            [$perPage, $offset]
        );
        return ['data' => $rows, 'total' => $total];
    }
}
