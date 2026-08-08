<?php
declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

/**
 * A deliberately small RRULE subset — not full iCal RFC 5545, to avoid a
 * dependency. Stored as a single string on
 * tasks.recurrence_rule:
 *
 *   DAILY
 *   WEEKLY:MO,WE,FR
 *   MONTHLY:15            (day-of-month; clamped to the month's last day)
 *   CUSTOM:3               (every N days)
 */
final class RRuleLite
{
    private const WEEKDAY_MAP = ['MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 0];

    public static function isValid(string $rule): bool
    {
        return self::parse($rule) !== null;
    }

    private static function parse(string $rule): ?array
    {
        $rule = trim($rule);
        if ($rule === 'DAILY') {
            return ['type' => 'DAILY'];
        }
        if (str_starts_with($rule, 'WEEKLY:')) {
            $days = array_filter(array_map('trim', explode(',', substr($rule, 7))));
            $days = array_values(array_intersect($days, array_keys(self::WEEKDAY_MAP)));
            return $days === [] ? null : ['type' => 'WEEKLY', 'days' => $days];
        }
        if (str_starts_with($rule, 'MONTHLY:')) {
            $day = (int) substr($rule, 8);
            return ($day >= 1 && $day <= 31) ? ['type' => 'MONTHLY', 'day' => $day] : null;
        }
        if (str_starts_with($rule, 'CUSTOM:')) {
            $n = (int) substr($rule, 7);
            return $n >= 1 ? ['type' => 'CUSTOM', 'interval' => $n] : null;
        }
        return null;
    }

    public static function label(string $rule): string
    {
        $p = self::parse($rule);
        if ($p === null) {
            return $rule;
        }
        return match ($p['type']) {
            'DAILY'   => 'প্রতিদিন',
            'WEEKLY'  => 'প্রতি সপ্তাহে ' . implode(', ', array_map(
                static fn($d) => ['MO' => 'সোম', 'TU' => 'মঙ্গল', 'WE' => 'বুধ', 'TH' => 'বৃহঃ', 'FR' => 'শুক্র', 'SA' => 'শনি', 'SU' => 'রবি'][$d],
                $p['days']
            )),
            'MONTHLY' => 'প্রতি মাসের ' . bn_num($p['day']) . ' তারিখে',
            'CUSTOM'  => 'প্রতি ' . bn_num($p['interval']) . ' দিন পরপর',
            default   => $rule,
        };
    }

    /**
     * Generate occurrence dates (Asia/Dhaka calendar dates, 'Y-m-d') in
     * [$fromDate, $fromDate + $horizonDays], starting no earlier than
     * $anchorDate (the template's own due date).
     *
     * @return string[]
     */
    public static function occurrences(string $rule, string $anchorDate, string $fromDate, int $horizonDays): array
    {
        $p = self::parse($rule);
        if ($p === null) {
            return [];
        }

        $start = new DateTimeImmutable(max($anchorDate, $fromDate));
        $end   = (new DateTimeImmutable($fromDate))->modify('+' . $horizonDays . ' days');
        $anchor = new DateTimeImmutable($anchorDate);

        $out = [];

        switch ($p['type']) {
            case 'DAILY':
                for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
                    $out[] = $d->format('Y-m-d');
                }
                break;

            case 'WEEKLY':
                $wanted = array_map(static fn($code) => self::WEEKDAY_MAP[$code], $p['days']);
                for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
                    if (in_array((int) $d->format('w'), $wanted, true)) {
                        $out[] = $d->format('Y-m-d');
                    }
                }
                break;

            case 'MONTHLY':
                $cursor = $start->modify('first day of this month');
                while ($cursor <= $end) {
                    $lastDay = (int) $cursor->format('t');
                    $day     = min($p['day'], $lastDay);
                    $occ     = $cursor->setDate((int) $cursor->format('Y'), (int) $cursor->format('n'), $day);
                    if ($occ >= $start && $occ <= $end) {
                        $out[] = $occ->format('Y-m-d');
                    }
                    $cursor = $cursor->modify('first day of next month');
                }
                break;

            case 'CUSTOM':
                $interval = $p['interval'];
                $cursor = $anchor;
                while ($cursor < $start) {
                    $cursor = $cursor->modify('+' . $interval . ' days');
                }
                for ($d = $cursor; $d <= $end; $d = $d->modify('+' . $interval . ' days')) {
                    $out[] = $d->format('Y-m-d');
                }
                break;
        }

        return $out;
    }
}
