<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class SmsLogRepo
{
    public function create(?int $reminderId, string $mobile, string $message, string $gateway, string $status, array $response = []): int
    {
        return Db::insert(
            'INSERT INTO sms_log (reminder_id, mobile_number, message, gateway, status, gateway_response) VALUES (?, ?, ?, ?, ?, ?)',
            [$reminderId, $mobile, $message, $gateway, $status, json_encode($response, JSON_UNESCAPED_UNICODE)]
        );
    }

    /** @return array{data: array, total: int} */
    public function paginate(int $page, int $perPage): array
    {
        $offset = max(0, $page - 1) * $perPage;
        $total  = (int) Db::value('SELECT COUNT(*) FROM sms_log');
        $rows   = Db::all('SELECT * FROM sms_log ORDER BY id DESC LIMIT ? OFFSET ?', [$perPage, $offset]);
        return ['data' => $rows, 'total' => $total];
    }

    public function deliveryRate(): float
    {
        $total = (int) Db::value('SELECT COUNT(*) FROM sms_log');
        if ($total === 0) {
            return 0.0;
        }
        $sent = (int) Db::value("SELECT COUNT(*) FROM sms_log WHERE status = 'sent'");
        return round(($sent / $total) * 100, 1);
    }
}
