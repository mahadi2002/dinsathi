<?php
declare(strict_types=1);

namespace App\Gateways;

interface PushGateway
{
    /**
     * @param array{endpoint: string, p256dh_key: string, auth_key: string} $subscription
     * @return array{success: bool, expired: bool}  expired=true means the caller should delete the subscription row (410 Gone)
     */
    public function send(int $userId, array $subscription, string $title, string $body, string $url): array;
}
