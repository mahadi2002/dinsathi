<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Fixed-window rate limiting on the `rate_limits` table (bucket_key,
 * window_start, hit_count — see 02-SCHEMA.sql). MySQL-backed on purpose,
 * no Redis assumption on shared hosting.
 *
 * Each bucket definition is [limit, window seconds]. The window itself is
 * the floor of "now" to the window size, so concurrent requests in the same
 * window collapse onto one row via the UNIQUE(bucket_key, window_start) key.
 */
final class RateLimit
{
    public const BUCKETS = [
        'otp_request'  => ['limit' => 3, 'window' => 3600],
        'otp_verify'   => ['limit' => 5, 'window' => 900],
        'otp_resend'   => ['limit' => 3, 'window' => 3600],
        'login'        => ['limit' => 5, 'window' => 900],
        'admin_login'  => ['limit' => 5, 'window' => 900],
        'contact'      => ['limit' => 3, 'window' => 3600],
        'search'       => ['limit' => 30, 'window' => 60],
        'export'       => ['limit' => 5, 'window' => 3600],
    ];

    /** @return int|null seconds until the caller may retry, or null when allowed */
    public static function hit(string $bucket, string $key): ?int
    {
        $def = self::BUCKETS[$bucket] ?? ['limit' => 60, 'window' => 60];
        $windowSeconds = (int) $def['window'];
        $limit         = (int) $def['limit'];

        $now         = time();
        $windowStart = $now - ($now % $windowSeconds);
        $bucketKey   = $bucket . ':' . substr($key, 0, 140);
        $windowDt    = gmdate('Y-m-d H:i:s', $windowStart);

        Db::exec(
            'INSERT INTO rate_limits (bucket_key, window_start, hit_count) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE hit_count = hit_count + 1',
            [$bucketKey, $windowDt]
        );

        $hits = (int) Db::value(
            'SELECT hit_count FROM rate_limits WHERE bucket_key = ? AND window_start = ?',
            [$bucketKey, $windowDt]
        );

        if ($hits > $limit) {
            return ($windowStart + $windowSeconds) - $now;
        }

        return null;
    }

    /** Check without consuming a hit. */
    public static function tooMany(string $bucket, string $key): ?int
    {
        $def = self::BUCKETS[$bucket] ?? ['limit' => 60, 'window' => 60];
        $windowSeconds = (int) $def['window'];
        $limit         = (int) $def['limit'];

        $now         = time();
        $windowStart = $now - ($now % $windowSeconds);
        $bucketKey   = $bucket . ':' . substr($key, 0, 140);

        $hits = (int) Db::value(
            'SELECT hit_count FROM rate_limits WHERE bucket_key = ? AND window_start = ?',
            [$bucketKey, gmdate('Y-m-d H:i:s', $windowStart)]
        );

        return $hits >= $limit ? ($windowStart + $windowSeconds) - $now : null;
    }

    public static function reset(string $bucket, string $key): void
    {
        Db::exec('DELETE FROM rate_limits WHERE bucket_key = ?', [$bucket . ':' . substr($key, 0, 140)]);
    }

    /** "৫৯ মিনিট" / "৪৫ সেকেন্ড" — turns a raw second count into shown wording. */
    public static function humanWait(int $seconds): string
    {
        if ($seconds < 60) {
            return bn_num(max(1, $seconds)) . ' সেকেন্ড';
        }
        if ($seconds < 3600) {
            return bn_num((int) ceil($seconds / 60)) . ' মিনিট';
        }
        return bn_num((int) ceil($seconds / 3600)) . ' ঘণ্টা';
    }
}
