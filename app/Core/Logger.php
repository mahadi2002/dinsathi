<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Daily rotating file logger.
 *
 * NEVER log: a full mobile number, an OTP code, a session id, or APP_KEY —
 * a log file is the easiest place for a secret to leak. Log the last 4
 * digits or a hash prefix instead, enough to correlate entries without
 * exposing anything.
 */
final class Logger
{
    private const LEVELS = ['debug' => 10, 'info' => 20, 'warning' => 30, 'error' => 40];

    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function exception(Throwable $e, array $context = []): void
    {
        self::write('error', get_class($e) . ': ' . $e->getMessage(), $context + [
            'file'  => $e->getFile() . ':' . $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString()),
        ]);
    }

    /** MockGateway/MockSmsGateway write generated OTPs and SMS bodies here in dev only. */
    public static function channel(string $channel, string $message): void
    {
        self::append($channel, date('c') . ' ' . $message . PHP_EOL);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $min = self::LEVELS[(string) config('app.log_level', 'info')] ?? 20;
        if ((self::LEVELS[$level] ?? 20) < $min) {
            return;
        }

        $line = sprintf(
            '[%s] %s: %s %s%s',
            date('c'),
            strtoupper($level),
            $message,
            $context === [] ? '' : (string) json_encode(self::scrub($context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            PHP_EOL
        );

        self::append('app', $line);
    }

    /** Belt-and-braces redaction in case a caller passes something sensitive. */
    private static function scrub(array $context): array
    {
        $blocked = ['mobile_number', 'msisdn', 'phone', 'otp', 'code', 'password', 'app_key', 'secret', 'token', 'payload', 'auth_key', 'p256dh_key'];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $blocked, true)) {
                $context[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $context[$key] = self::scrub($value);
            }
        }
        return $context;
    }

    private static function append(string $channel, string $line): void
    {
        $dir = APP_ROOT . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        @file_put_contents($dir . '/' . $channel . '-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
