<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Services\ReminderService;

/** Runs every cron minute. */
final class DispatchReminders
{
    public function run(): array
    {
        return (new ReminderService())->dispatchDue();
    }
}
