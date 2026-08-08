<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\HabitRepo;
use App\Repositories\NotificationRepo;
use App\Services\HabitService;
use App\Services\SubscriptionService;
use App\Support\DateBD;

/**
 * Fires once per day once Asia/Dhaka local time reaches 20:00 — checked
 * every cron minute but self-guarding on the time threshold, so it needs
 * no separate crontab line. Sends the locked "streak at risk" copy to any
 * subscribed user whose active habit is still missing today's check-in.
 */
final class RolloverStreaks
{
    public function run(): int
    {
        if (DateBD::nowDhaka()->format('H:i') < '20:00') {
            return 0;
        }

        $habitRepo = new HabitRepo();
        $habitService = new HabitService();
        $notifRepo = new NotificationRepo();
        $today = DateBD::today();
        $sent = 0;

        foreach ($habitRepo->allActive() as $habit) {
            if (!SubscriptionService::hasAccess((int) $habit['user_id'])) {
                continue;
            }
            if ($habitService->isDoneToday((int) $habit['id'])) {
                continue;
            }

            $activeDays = array_flip(explode(',', (string) $habit['active_days']));
            $todayCode  = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'][(int) date('w', strtotime($today))];
            if (!isset($activeDays[$todayCode])) {
                continue;
            }

            $notifRepo->create(
                (int) $habit['user_id'],
                'streak_risk',
                'Streak ভাঙার আগে!',
                "আজকের '{$habit['name']}' এখনো Check-in করা হয়নি — Streak ভাঙার আগেই করে ফেলুন!"
            );
            $sent++;
        }

        return $sent;
    }
}
