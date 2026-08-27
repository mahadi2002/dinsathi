<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use App\Repositories\HabitLogRepo;
use App\Repositories\HabitRepo;
use App\Repositories\NotificationRepo;
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

    /** Boolean habits only (habits.target_quantity IS NULL) — toggles today's check-in. */
    public function checkin(int $habitId): bool
    {
        return $this->logs->toggle($habitId, DateBD::today());
    }

    public function isDoneToday(int $habitId): bool
    {
        return $this->logs->isDoneOn($habitId, DateBD::today());
    }

    public function quantityToday(int $habitId): int
    {
        return $this->logs->quantityOn($habitId, DateBD::today());
    }

    /**
     * Quantity habits only — adds $amount to today's running total and
     * recomputes "done" against the habit's target_quantity. A quantity
     * habit only counts as done for the day once the running total meets
     * the target; logging more after that keeps it done, and a negative
     * $amount (undo) can drop it back below target. Streak computation
     * itself is untouched — it still just reads habit_logs.completed,
     * this is the one place that flag gets set for a quantity habit.
     *
     * @return array{quantity: int, done: bool}
     */
    public function logQuantity(int $habitId, int $targetQuantity, int $amount): array
    {
        $today   = DateBD::today();
        $current = $this->logs->quantityOn($habitId, $today);
        $newQty  = max(0, $current + $amount);
        $done    = $targetQuantity > 0 && $newQty >= $targetQuantity;

        $this->logs->setQuantity($habitId, $today, $newQty, $done);

        return ['quantity' => $newQty, 'done' => $done];
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

    /**
     * Last N days for the flame-dot strip and the contribution-grid heatmap,
     * respecting active_days. $targetQuantity is optional — pass a quantity
     * habit's target to also compute per-day 'intensity' (0..1, share of
     * target reached that day); boolean habits/callers that omit it get
     * intensity 1.0 on a completed day and 0.0 otherwise. The 14-day/7-day
     * strips on the dashboard, habit list and insights pages just ignore
     * the extra 'quantity'/'intensity' keys — only the heatmap reads them.
     */
    public function recentDays(int $habitId, string $activeDaysCsv, int $days = 7, ?int $targetQuantity = null): array
    {
        $active = array_flip(array_filter(array_map('trim', explode(',', $activeDaysCsv))));
        $byDate = array_column($this->logs->history($habitId, $days + 1), null, 'log_date');

        $out = [];
        $cursor = new DateTimeImmutable(DateBD::today());
        for ($i = 0; $i < $days; $i++) {
            $dateStr  = $cursor->format('Y-m-d');
            $row      = $byDate[$dateStr] ?? null;
            $completed = $row !== null && (int) $row['completed'] === 1;
            $quantity  = $row !== null ? (int) $row['quantity'] : 0;
            $intensity = $targetQuantity !== null && $targetQuantity > 0
                ? min(1.0, $quantity / $targetQuantity)
                : ($completed ? 1.0 : 0.0);

            $out[] = [
                'date'      => $dateStr,
                'active'    => $this->isActiveDay($cursor, $active),
                'completed' => $completed,
                'quantity'  => $quantity,
                'intensity' => $intensity,
            ];
            $cursor = $cursor->modify('-1 day');
        }

        return array_reverse($out);
    }

    /**
     * Arranges a chronological (oldest→newest) recentDays() result into
     * Monday-start week columns for a GitHub-style contribution grid,
     * padding the first/last week with null cells so every week has
     * exactly 7 days. Returns the weeks plus a week-index => month-number
     * map for the month labels drawn above the grid.
     *
     * @return array{weeks: list<list<array|null>>, monthLabels: array<int,int>}
     */
    public function toGrid(array $days): array
    {
        if ($days === []) {
            return ['weeks' => [], 'monthLabels' => []];
        }

        $byDate = array_column($days, null, 'date');
        $first  = new DateTimeImmutable($days[0]['date']);
        $last   = new DateTimeImmutable($days[count($days) - 1]['date']);

        // ISO-8601: Monday = 1 .. Sunday = 7.
        $start = $first->modify('-' . ((int) $first->format('N') - 1) . ' days');
        $end   = $last->modify('+' . (7 - (int) $last->format('N')) . ' days');

        $weeks       = [];
        $monthLabels = [];
        $lastMonth   = null;
        $cursor      = $start;
        $weekIndex   = 0;

        while ($cursor <= $end) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateStr = $cursor->format('Y-m-d');
                $week[]  = $byDate[$dateStr] ?? null;

                $month = $cursor->format('Y-m');
                if ($i === 0 && $month !== $lastMonth) {
                    $monthLabels[$weekIndex] = (int) $cursor->format('n');
                    $lastMonth = $month;
                }

                $cursor = $cursor->modify('+1 day');
            }
            $weeks[] = $week;
            $weekIndex++;
        }

        return ['weeks' => $weeks, 'monthLabels' => $monthLabels];
    }

    private function isActiveDay(DateTimeImmutable $date, array $activeFlip): bool
    {
        return isset($activeFlip[self::DOW[(int) $date->format('w')]]);
    }

    /**
     * Run by cron/run_jobs.php, self-guarding on time rather than the daily
     * job-guard — fires once per day once Asia/Dhaka local time reaches
     * 20:00 (checked every cron minute), sending the locked "streak at
     * risk" copy to any user whose active habit is still missing today's
     * check-in.
     */
    public function rolloverStreaks(): int
    {
        if (DateBD::nowDhaka()->format('H:i') < '20:00') {
            return 0;
        }

        $habitRepo     = new HabitRepo();
        $notifRepo     = new NotificationRepo();
        $today         = DateBD::today();
        $todayStartUtc = (string) DateBD::toUtcStorage($today . 'T00:00');
        $sent          = 0;

        foreach ($habitRepo->allActive() as $habit) {
            if ($this->isDoneToday((int) $habit['id'])) {
                continue;
            }

            $activeDays = array_flip(explode(',', (string) $habit['active_days']));
            $todayCode  = self::DOW[(int) date('w', strtotime($today))];
            if (!isset($activeDays[$todayCode])) {
                continue;
            }

            $userId = (int) $habit['user_id'];
            $body   = "আজকের '{$habit['name']}' এখনো Check-in করা হয়নি — Streak ভাঙার আগেই করে ফেলুন!";

            // At most one "streak at risk" notification per habit per day —
            // rolloverStreaks() self-guards on time (not the daily job-guard)
            // and can otherwise fire this same insert every cron minute from
            // 20:00 onward.
            if ($notifRepo->existsSince($userId, 'streak_risk', $body, $todayStartUtc)) {
                continue;
            }

            $notifRepo->create($userId, 'streak_risk', 'Streak ভাঙার আগে!', $body);
            $sent++;
        }

        return $sent;
    }
}
