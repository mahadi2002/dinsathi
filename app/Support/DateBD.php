<?php
declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * The single place UTC ↔ Asia/Dhaka conversion happens. Every `due_at` /
 * `fire_at` column is stored UTC; controllers and views never call
 * date()/strtotime() directly against a raw DB column — they go through
 * here instead.
 */
final class DateBD
{
    private const TZ_UTC   = 'UTC';
    private const TZ_DHAKA = 'Asia/Dhaka';

    public static function nowUtc(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone(self::TZ_UTC));
    }

    public static function nowDhaka(): DateTimeImmutable
    {
        return self::nowUtc()->setTimezone(new DateTimeZone(self::TZ_DHAKA));
    }

    public static function today(): string
    {
        return self::nowDhaka()->format('Y-m-d');
    }

    /** A stored UTC 'Y-m-d H:i:s' → Asia/Dhaka DateTimeImmutable, or null. */
    public static function toDhaka(?string $utc): ?DateTimeImmutable
    {
        if ($utc === null || $utc === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable($utc, new DateTimeZone(self::TZ_UTC)))
                ->setTimezone(new DateTimeZone(self::TZ_DHAKA));
        } catch (Exception) {
            return null;
        }
    }

    /** A local Asia/Dhaka 'Y-m-d\TH:i' (datetime-local input) → UTC storage string. */
    public static function toUtcStorage(?string $localDhaka): ?string
    {
        if ($localDhaka === null || trim($localDhaka) === '') {
            return null;
        }
        try {
            $dt = new DateTimeImmutable($localDhaka, new DateTimeZone(self::TZ_DHAKA));
            return $dt->setTimezone(new DateTimeZone(self::TZ_UTC))->format('Y-m-d H:i:s');
        } catch (Exception) {
            return null;
        }
    }

    /** A Dhaka-local 'Y-m-d' date + 'H:i' time → UTC storage string. */
    public static function combineToUtc(string $dateDhaka, ?string $timeDhaka): ?string
    {
        if (trim($dateDhaka) === '') {
            return null;
        }
        $time = $timeDhaka !== null && $timeDhaka !== '' ? $timeDhaka : '00:00';
        return self::toUtcStorage($dateDhaka . 'T' . $time);
    }

    public static function isPastDhaka(?string $utc): bool
    {
        $dt = self::toDhaka($utc);
        return $dt !== null && $dt < self::nowDhaka();
    }

    /** Whether a Dhaka-local time-of-day falls inside the user's quiet-hours window (handles overnight wrap). */
    public static function isWithinQuietHours(string $timeHis, string $startHis, string $endHis): bool
    {
        if ($startHis === $endHis) {
            return false;
        }
        if ($startHis < $endHis) {
            return $timeHis >= $startHis && $timeHis < $endHis;
        }
        // Overnight window, e.g. 22:00 → 07:00
        return $timeHis >= $startHis || $timeHis < $endHis;
    }
}
