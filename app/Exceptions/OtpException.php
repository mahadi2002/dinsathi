<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A user-facing OTP failure. `$reason` is a short machine-readable tag
 * (rate_limited, wrong_code, expired, ...); `$message` is the Bangla
 * string actually shown to the user.
 */
class OtpException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
        public readonly int $attemptsLeft = 0,
    ) {
        parent::__construct($message);
    }
}
