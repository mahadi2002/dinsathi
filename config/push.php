<?php
declare(strict_types=1);

/**
 * Web Push (VAPID) configuration.
 *
 * WebPushGateway::send() would need to sign a VAPID JWT (ES256) and encrypt
 * the payload per RFC 8291 (ECDH + HKDF + AES-128-GCM) — real browser-facing
 * crypto, hand-written, with zero Composer dependencies. Per
 * 04-AI-BUILD-PLAYBOOK.md's Production Readiness Flags, that implementation
 * "must be independently reviewed or replaced with a vetted library before
 * launch." Until that review happens, ReminderService uses MockPushGateway
 * directly: it writes a row to `notifications` (visible in the in-app bell)
 * instead of calling a real push service, so the whole reminder pipeline is
 * still testable end-to-end without shipping unverified crypto to a browser.
 */
return [
    'vapid_public'  => env('WEBPUSH_VAPID_PUBLIC_KEY', ''),
];
