<?php
declare(strict_types=1);

/**
 * Single per-minute cron entry — dispatches everything the app's background
 * processing needs:
 *
 *   * * * * * php /path/to/dinsathi/cron/run_jobs.php >> /path/to/dinsathi/storage/logs/cron.log 2>&1
 *
 * Minute-granularity jobs (DispatchReminders) run every invocation.
 * Daily-only jobs guard themselves via the `jobs` table's "have I already
 * run today" check (see cron/_jobguard.php), so one crontab line is enough
 * — no separate entries needed.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';
require __DIR__ . '/_lock.php';
require __DIR__ . '/_jobguard.php';

use App\Core\Logger;
use App\Services\HabitService;
use App\Services\MaintenanceService;
use App\Services\ReminderService;
use App\Services\RecurrenceService;

$lock = cron_lock('run_jobs');
if ($lock === null) {
    fwrite(STDOUT, "[run_jobs] previous run still in progress, skipping.\n");
    exit(0);
}

try {
    run('DispatchReminders', static fn() => (new ReminderService())->dispatchDue(), daily: false);
    run('GenerateRecurringInstances', static fn() => (new RecurrenceService())->generateForAllTemplates(), daily: true);
    run('RolloverStreaks', static fn() => (new HabitService())->rolloverStreaks(), daily: false); // self-guards on the 20:00 threshold
    run('Cleanup', static fn() => (new MaintenanceService())->cleanup(), daily: true);
} finally {
    cron_unlock($lock);
}

function run(string $name, callable $fn, bool $daily): void
{
    if ($daily && job_already_ran_today($name)) {
        return;
    }

    try {
        $result = $fn();
        if ($daily) {
            job_mark_done($name);
        }
        fwrite(STDOUT, '[' . date('c') . "] {$name}: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n");
    } catch (\Throwable $e) {
        Logger::exception($e, ['job' => $name]);
        if ($daily) {
            job_mark_failed($name, $e->getMessage());
        }
        fwrite(STDERR, '[' . date('c') . "] {$name} FAILED: " . $e->getMessage() . "\n");
    }
}
