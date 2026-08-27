<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Best-effort mailer. No SMTP library is pulled in — outside `local`, this
 * sends through PHP's mail() the same way the pustisathi/gardenbondhu sibling
 * apps in this series do, relying on the host's local MTA. In `local` it
 * keeps writing to storage/logs/mail-*.log instead, so the password-reset
 * flow stays testable end-to-end without a mail server on a dev box. A send
 * failure is logged but never surfaced to the caller — a notification
 * failing must never break the password-reset flow itself, and the flow
 * never reveals whether the send succeeded either way.
 */
final class MailerService
{
    public function sendPasswordReset(string $email, string $resetUrl): void
    {
        if ((string) config('app.env', 'production') === 'local') {
            Logger::channel('mail', "Password reset for {$email} -> {$resetUrl}");
            return;
        }

        $subject = (string) config('app.name') . ' — Password রিসেট';
        $body    = "আপনার Password রিসেট করতে নিচের লিংকে যান (নির্দিষ্ট সময়ের জন্য বৈধ):\n\n{$resetUrl}\n\n"
                 . "আপনি যদি এই Request না করে থাকেন, এই Email উপেক্ষা করুন।";
        $headers = 'From: no-reply@' . self::domain() . "\r\nContent-Type: text/plain; charset=UTF-8";

        try {
            if (!@mail($email, $subject, $body, $headers)) {
                Logger::warning('mailer.send_failed', ['context' => 'password_reset']);
            }
        } catch (\Throwable $e) {
            Logger::warning('mailer.send_exception', ['error' => $e->getMessage()]);
        }
    }

    private static function domain(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : 'localhost';
    }
}
