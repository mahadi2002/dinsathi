<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Services\ReminderService;

/** Runs every cron minute — capped retry (3) with the natural 1-minute cadence acting as backoff. */
final class RetrySmsFailures
{
    public function run(): int
    {
        return (new ReminderService())->retrySmsFailures();
    }
}
