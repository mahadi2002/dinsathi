<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TaskRepo;
use App\Support\DateBD;
use App\Support\RRuleLite;

/**
 * Recurring tasks store one template row (tasks.is_template = 1) plus
 * generated instance rows for the next N days, refreshed daily by
 * app/Jobs/GenerateRecurringInstances. This class does the
 * actual date math; the cron job and TaskService just call generateFor().
 */
final class RecurrenceService
{
    public function __construct(private TaskRepo $tasks = new TaskRepo())
    {
    }

    /** Generate any missing instances for one template, up to the configured horizon. Returns count created. */
    public function generateFor(array $template): int
    {
        if ((int) $template['is_template'] !== 1 || $template['recurrence_rule'] === null) {
            return 0;
        }

        $rule = (string) $template['recurrence_rule'];
        if (!RRuleLite::isValid($rule)) {
            return 0;
        }

        $anchor = DateBD::toDhaka((string) $template['due_at']);
        if ($anchor === null) {
            return 0;
        }

        $horizon = (int) config('app.recurrence_horizon_days', 30);
        $today   = DateBD::today();
        $dates   = RRuleLite::occurrences($rule, $anchor->format('Y-m-d'), $today, $horizon);

        $existing = array_flip($this->tasks->instanceDatesForTemplate((int) $template['id']));
        $timeOfDay = $anchor->format('H:i');

        $created = 0;
        foreach ($dates as $date) {
            if (isset($existing[$date])) {
                continue;
            }

            $dueAtUtc = DateBD::combineToUtc($date, $timeOfDay);
            $taskId = $this->tasks->create([
                'user_id'             => $template['user_id'],
                'list_id'             => $template['list_id'],
                'title'               => $template['title'],
                'notes'               => $template['notes'],
                'priority'            => $template['priority'],
                'due_at'              => $dueAtUtc,
                'is_template'         => 0,
                'recurrence_rule'     => null,
                'parent_template_id'  => $template['id'],
                'reminder_offset_min' => $template['reminder_offset_min'],
            ]);

            if ($dueAtUtc !== null && $template['reminder_offset_min'] !== null) {
                (new ReminderService())->scheduleForTask([
                    'id'                  => $taskId,
                    'user_id'             => $template['user_id'],
                    'due_at'              => $dueAtUtc,
                    'reminder_offset_min' => $template['reminder_offset_min'],
                    'priority'            => $template['priority'],
                ]);
            }

            $created++;
        }

        return $created;
    }

    public function generateForAllTemplates(): int
    {
        $total = 0;
        foreach ($this->tasks->templates() as $template) {
            $total += $this->generateFor($template);
        }
        return $total;
    }
}
