<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Repositories\HabitLogRepo;
use App\Support\DateBD;
use DateTimeImmutable;

/**
 * Streaks are computed on read, never stored denormalized — avoids drift
 * bugs. A streak is the run of consecutive *active*
 * days (habits.active_days) with a completed check-in, ending today or
 * yesterday — inactive days are skipped, not counted as gaps.
 */
final class HabitService
{
    private const DOW = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];

    public function __construct(private HabitLogRepo $logs = new HabitLogRepo())
    {
    }

    public function checkin(int $habitId): bool
    {
        return $this->logs->toggle($habitId, DateBD::today());
    }

    public function isDoneToday(int $habitId): bool
    {
        return $this->logs->isDoneOn($habitId, DateBD::today());
    }

    public function streak(int $habitId, string $activeDaysCsv): int
    {
        $active = array_flip(array_filter(array_map('trim', explode(',', $activeDaysCsv))));
        $done   = array_flip(array_column($this->logs->recent($habitId, 400), 'log_date'));

        $today  = new DateTimeImmutable(DateBD::today());
        $cursor = $today;

        // Today doesn't have to be checked in yet for the streak to still be "alive".
        if ($this->isActiveDay($today, $active) && !isset($done[$today->format('Y-m-d')])) {
            $cursor = $today->modify('-1 day');
        }

        $streak = 0;
        for ($i = 0; $i < 400; $i++) {
            $dateStr = $cursor->format('Y-m-d');

            if (!$this->isActiveDay($cursor, $active)) {
                $cursor = $cursor->modify('-1 day');
                continue;
            }
            if (!isset($done[$dateStr])) {
                break;
            }
            $streak++;
            $cursor = $cursor->modify('-1 day');
        }

        return $streak;
    }

    /** Last N days as ['date' => bool completed] for the flame-dot row, respecting active_days. */
    public function recentDays(int $habitId, string $activeDaysCsv, int $days = 7): array
    {
        $active = array_flip(array_filter(array_map('trim', explode(',', $activeDaysCsv))));
        $done   = array_flip(array_column($this->logs->recent($habitId, $days + 1), 'log_date'));

        $out = [];
        $cursor = new DateTimeImmutable(DateBD::today());
        for ($i = 0; $i < $days; $i++) {
            $dateStr = $cursor->format('Y-m-d');
            $out[] = [
                'date'      => $dateStr,
                'active'    => $this->isActiveDay($cursor, $active),
                'completed' => isset($done[$dateStr]),
            ];
            $cursor = $cursor->modify('-1 day');
        }

        return array_reverse($out);
    }

    private function isActiveDay(DateTimeImmutable $date, array $activeFlip): bool
    {
        return isset($activeFlip[self::DOW[(int) $date->format('w')]]);
    }
}
