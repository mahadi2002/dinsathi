<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Services\RecurrenceService;

/** Daily — refreshes every recurring template's generated instances up to the horizon. */
final class GenerateRecurringInstances
{
    public function run(): int
    {
        return (new RecurrenceService())->generateForAllTemplates();
    }
}
