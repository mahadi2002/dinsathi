<?php
declare(strict_types=1);

namespace App\Gateways;

/**
 * Real Web Push over VAPID — NOT wired by default (config('push.driver')
 * must be explicitly set to 'webpush'). Flagged in
 * 04-AI-BUILD-PLAYBOOK.md's Production Readiness Flags as needing
 * independent crypto review before launch: this class would need to
 * hand-sign a VAPID JWT (ES256) and encrypt the payload per RFC 8291
 * (ECDH P-256 + HKDF-SHA256 + AES-128-GCM) using only ext-openssl, since the
 * project has zero Composer dependencies. That is real cryptographic code
 * that is easy to get subtly wrong in ways that fail silently (a malformed
 * ciphertext just never shows a notification — there is no error a user
 * sees), so it is intentionally left unimplemented here rather than shipped
 * unverified. Replace with a vetted implementation (or a carefully
 * unit-tested one, checked against RFC 8291's own test vectors) before
 * switching PUSH_GATEWAY away from 'mock'.
 */
final class WebPushGateway
{
    public function send(int $userId, array $subscription, string $title, string $body, string $url): array
    {
        throw new \RuntimeException(
            'WebPushGateway is not implemented — VAPID/AES-GCM web push crypto needs independent review before '
            . 'use (see 04-AI-BUILD-PLAYBOOK.md Production Readiness Flags). PUSH_GATEWAY=mock until then.'
        );
    }
}
