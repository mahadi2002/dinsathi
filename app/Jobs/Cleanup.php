<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Core\Db;

/** Daily housekeeping — purges rows nothing needs to keep around forever. */
final class Cleanup
{
    public function run(): array
    {
        return [
            'otp_verifications' => Db::exec('DELETE FROM otp_verifications WHERE expires_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)'),
            'rate_limits'       => Db::exec('DELETE FROM rate_limits WHERE window_start < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)'),
            'sessions'          => Db::exec('DELETE FROM sessions WHERE expires_at < UTC_TIMESTAMP()'),
            'jobs'              => Db::exec('DELETE FROM jobs WHERE run_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)'),
        ];
    }
}
