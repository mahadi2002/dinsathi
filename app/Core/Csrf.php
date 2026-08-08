<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = Crypto::randomToken(32);
        }
        return (string) $_SESSION['_token'];
    }

    public static function check(?string $candidate): bool
    {
        $expected = $_SESSION['_token'] ?? null;
        if (!is_string($expected) || !is_string($candidate) || $candidate === '') {
            return false;
        }
        return hash_equals($expected, $candidate);
    }

    /** Issue a fresh token — called after a mismatch so the retry can succeed. */
    public static function rotate(): void
    {
        $_SESSION['_token'] = Crypto::randomToken(32);
    }
}
