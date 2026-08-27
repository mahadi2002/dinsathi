<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

/**
 * Daily housekeeping — purges rows nothing needs to keep around forever.
 * Doesn't belong to any single domain service (touches auth, rate-limit,
 * session, and job-tracking tables at once), so it gets its own thin one.
 */
final class MaintenanceService
{
    public function cleanup(): array
    {
        return [
            'rate_limits' => Db::exec('DELETE FROM rate_limits WHERE window_start < NOW() - INTERVAL 7 DAY'),
            'sessions'    => Db::exec('DELETE FROM sessions WHERE expires_at < NOW()'),
            'jobs'        => Db::exec('DELETE FROM jobs WHERE run_at < NOW() - INTERVAL 30 DAY'),
        ];
    }
}
