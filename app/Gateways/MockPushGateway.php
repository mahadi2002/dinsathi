<?php
declare(strict_types=1);

namespace App\Gateways;

use App\Repositories\NotificationRepo;

/**
 * Default push driver. Real Web Push needs a hand-written VAPID JWT (ES256)
 * plus RFC 8291 payload encryption (ECDH + HKDF + AES-128-GCM) — genuine
 * browser-facing crypto with zero Composer dependencies available to lean
 * on. 04-AI-BUILD-PLAYBOOK.md's Production Readiness Flags calls that
 * implementation out by name as needing independent review before launch.
 *
 * Until that review happens, this mock is what every environment (including
 * a "finished" dev build) actually runs: it writes a `notifications` row
 * instead of a real push, so the in-app bell shows the reminder and the
 * whole dispatch pipeline — quiet hours, idempotent claiming, dual-channel
 * fan-out — is fully exercised without shipping unverified crypto to a
 * browser. See WebPushGateway for the real (flagged, not wired by default)
 * implementation path.
 */
final class MockPushGateway implements PushGateway
{
    public function send(int $userId, array $subscription, string $title, string $body, string $url): array
    {
        (new NotificationRepo())->create($userId, 'reminder', $title, $body);
        return ['success' => true, 'expired' => false];
    }
}
