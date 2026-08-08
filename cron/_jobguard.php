<?php
declare(strict_types=1);

use App\Core\Db;
use App\Support\DateBD;

/**
 * "Have I already run today" guard against the `jobs` table, so daily-only
 * jobs (GenerateRecurringInstances, CheckSubscriptionExpiry, cleanup, the
 * evening streak-risk check) can share one per-minute crontab line with the
 * minute-granularity jobs instead of needing their own entry.
 */
function job_already_ran_today(string $jobName): bool
{
    $today = DateBD::today();
    $row = Db::first(
        "SELECT id FROM jobs WHERE job_name = ? AND status = 'done' AND DATE(run_at) = ?",
        [$jobName, $today]
    );
    return $row !== null;
}

function job_mark_done(string $jobName): void
{
    Db::insert(
        "INSERT INTO jobs (job_name, status, run_at, finished_at) VALUES (?, 'done', UTC_TIMESTAMP(), UTC_TIMESTAMP())",
        [$jobName]
    );
}

function job_mark_failed(string $jobName, string $error): void
{
    Db::insert(
        "INSERT INTO jobs (job_name, status, run_at, finished_at, error) VALUES (?, 'failed', UTC_TIMESTAMP(), UTC_TIMESTAMP(), ?)",
        [$jobName, $error]
    );
}
