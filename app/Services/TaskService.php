<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TaskRepo;
use App\Support\RRuleLite;

/** Controllers stay thin — task creation/update policy (caps, recurrence, reminders) lives here. */
final class TaskService
{
    public function __construct(
        private TaskRepo $tasks = new TaskRepo(),
        private ReminderService $reminders = new ReminderService(),
        private RecurrenceService $recurrence = new RecurrenceService(),
    ) {
    }

    /**
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function create(int $userId, array $input): array
    {
        $recurrenceRule = $input['recurrence_rule'] ?? null;
        if ($recurrenceRule !== null && !RRuleLite::isValid($recurrenceRule)) {
            return ['ok' => false, 'error' => 'Recurrence-এর ধরন সঠিক নয়।'];
        }
        // A template with no due date has no anchor to generate instances from —
        // RecurrenceService::generateFor() would silently create zero instances,
        // leaving an invisible task the user can never see or delete again.
        if ($recurrenceRule !== null && ($input['due_at'] ?? null) === null) {
            return ['ok' => false, 'error' => 'Recurring Task তৈরি করতে Due Date দিতে হবে।'];
        }

        $isTemplate = $recurrenceRule !== null;

        $id = $this->tasks->create([
            'user_id'             => $userId,
            'list_id'             => $input['list_id'],
            'title'               => $input['title'],
            'notes'               => $input['notes'] ?? null,
            'priority'            => $this->validPriority($input['priority'] ?? null),
            'due_at'              => $input['due_at'] ?? null,
            'is_template'         => $isTemplate ? 1 : 0,
            'recurrence_rule'     => $recurrenceRule,
            'reminder_offset_min' => $input['reminder_offset_min'] ?? null,
        ]);

        if ($isTemplate) {
            $template = $this->tasks->find($id, $userId);
            if ($template !== null) {
                $this->recurrence->generateFor($template);
            }
        } else {
            $task = $this->tasks->find($id, $userId);
            if ($task !== null) {
                $this->reminders->scheduleForTask($task);
            }
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * $applyScope only matters when editing a recurring template
     * (is_template = 1): 'this_only' (default) touches just the template
     * row itself — already-generated instances, past or future, are left
     * exactly as they were, only *newly* generated instances beyond the
     * horizon pick up the change. 'future' additionally cascades the
     * editable fields onto every already-generated future, incomplete
     * instance — the "this and following" half of the Google-Calendar-style
     * prompt in views/app/task-show.php. Matches 01-BUILD-SPEC.md §8.
     */
    public function update(int $id, int $userId, array $input, string $applyScope = 'this_only'): bool
    {
        $existing = $this->tasks->find($id, $userId);
        if ($existing === null) {
            return false;
        }

        $priority = $this->validPriority($input['priority'] ?? null);

        $this->tasks->update($id, $userId, [
            'title'               => $input['title'],
            'notes'               => $input['notes'] ?? null,
            'priority'            => $priority,
            'due_at'              => $input['due_at'] ?? null,
            'list_id'             => $input['list_id'],
            'recurrence_rule'     => (int) $existing['is_template'] === 1 ? $existing['recurrence_rule'] : null,
            'reminder_offset_min' => $input['reminder_offset_min'] ?? null,
        ]);

        $updated = $this->tasks->find($id, $userId);
        if ($updated === null) {
            return true;
        }

        if ((int) $updated['is_template'] === 1) {
            if ($applyScope === 'future') {
                $affected = $this->tasks->cascadeToFutureInstances($id, $userId, [
                    'title'               => $updated['title'],
                    'notes'               => $updated['notes'],
                    'priority'            => $updated['priority'],
                    'reminder_offset_min' => $updated['reminder_offset_min'],
                ]);
                foreach ($affected as $instance) {
                    $this->reminders->scheduleForTask($instance);
                }
            }
        } else {
            $this->reminders->scheduleForTask($updated);
        }

        return true;
    }

    /** Drag-to-reschedule (calendar day/week views) — moves due_at only, keeps everything else on the task untouched. */
    public function reschedule(int $id, int $userId, ?string $dueAtUtc): bool
    {
        $existing = $this->tasks->find($id, $userId);
        if ($existing === null || (int) $existing['is_template'] === 1) {
            return false;
        }

        $this->tasks->updateDueAt($id, $userId, $dueAtUtc);

        $updated = $this->tasks->find($id, $userId);
        if ($updated !== null) {
            $this->reminders->scheduleForTask($updated);
        }

        return true;
    }

    public function delete(int $id, int $userId): void
    {
        $this->reminders->cancelForTask($id);
        $this->tasks->delete($id, $userId);
    }

    /** Guards the DB's ENUM column against an empty string — '' passes `??` unnoticed but isn't a valid enum member. */
    private function validPriority(?string $priority): string
    {
        return in_array($priority, ['low', 'medium', 'high', 'urgent'], true) ? $priority : 'medium';
    }

    public function toggleComplete(int $id, int $userId): bool
    {
        $task = $this->tasks->find($id, $userId);
        if ($task === null) {
            return false;
        }
        $this->tasks->setCompleted($id, $userId, $task['completed_at'] === null);
        return true;
    }
}
